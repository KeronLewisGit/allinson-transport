<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ApplicationStatus;
use App\Enums\AssessmentResult;
use App\Enums\JobRole;
use App\Enums\ReferenceStatus;
use App\Http\Controllers\Controller;
use App\Models\ApplicantDocument;
use App\Models\JobApplication;
use App\Models\JobPosting;
use App\Models\StaffMember;
use App\Notifications\DocumentsRequested;
use App\Support\Depots;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** HR's recruitment pipeline: screening, right to work, references, assessment drive, offer, hire. */
class ApplicationController extends Controller
{
    use Concerns;

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', JobApplication::class);
        $filters = $this->filters($request);
        $counts = JobApplication::query()->filter(array_merge($filters, ['status' => null]))
            ->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return view('admin.applications.index', [
            'applications' => JobApplication::with('jobPosting')->filter($filters)
                ->orderByRaw("case when status = 'new' then 0 else 1 end")->orderByDesc('id')->paginate(25)->withQueryString(),
            'filters' => $filters,
            'counts' => $counts,
            'jobs' => JobPosting::orderBy('title')->get(['id', 'title', 'depot']),
        ]);
    }

    public function show(JobApplication $application): View
    {
        Gate::authorize('view', $application);

        return view('admin.applications.show', ['application' => $application->load(['jobPosting', 'notes.user', 'currentDocuments', 'rtwChecker', 'staffMember'])]);
    }

    public function update(Request $request, JobApplication $application): RedirectResponse
    {
        Gate::authorize('update', $application);
        $data = $request->validate(['status' => ['required', Rule::enum(ApplicationStatus::class)]]);
        $status = ApplicationStatus::from($data['status']);
        if ($status === ApplicationStatus::Hired) {
            return back()->withErrors(['status' => 'Use "Hire and start compliance tracking" to mark someone as hired.']);
        }
        if ($application->claimStatus($status)) {
            $application->log('Moved to '.$status->label().'.', 'status', $this->user($request));
        }

        return back()->with('status', "{$application->reference} saved.");
    }

    /** Right to work, references and the assessment drive. */
    public function checks(Request $request, JobApplication $application): RedirectResponse
    {
        Gate::authorize('update', $application);
        $user = $this->user($request);
        $data = $request->validate([
            'rtw_method' => ['nullable', Rule::in(array_keys(JobApplication::RTW_METHODS))],
            'references_status' => ['required', Rule::enum(ReferenceStatus::class)],
            'assessment_at' => ['nullable', 'date_format:Y-m-d\TH:i'],
            'assessment_result' => ['nullable', Rule::enum(AssessmentResult::class)],
        ]);

        $changes = [];
        if (! empty($data['rtw_method']) && ! $application->rtw_checked_at) {
            $application->forceFill(['rtw_checked_at' => now(), 'rtw_checked_by' => $user->id, 'rtw_method' => $data['rtw_method']]);
            $changes[] = 'Right to work checked: '.JobApplication::RTW_METHODS[$data['rtw_method']].'.';
        }
        $refs = ReferenceStatus::from($data['references_status']);
        if ($refs !== $application->references_status) {
            $application->forceFill(['references_status' => $refs, 'references_updated_at' => now()]);
            $changes[] = 'References: '.$refs->label().'.';
        }
        $at = ! empty($data['assessment_at']) ? Carbon::createFromFormat('Y-m-d\TH:i', $data['assessment_at']) : null;
        if ($at?->format('Y-m-d H:i') !== $application->assessment_at?->format('Y-m-d H:i')) {
            $application->assessment_at = $at;
            $changes[] = $at ? 'Assessment drive booked for '.$at->format('l j F, g:i a').' at '.$application->depotName().'.' : 'Assessment drive cancelled.';
        }
        $result = AssessmentResult::tryFrom((string) ($data['assessment_result'] ?? ''));
        if ($result !== $application->assessment_result) {
            $application->assessment_result = $result;
            $changes[] = $result ? 'Assessment: '.$result->label().'.' : 'Assessment result cleared.';
        }
        $application->save();
        foreach ($changes as $c) {
            $application->log($c, 'status', $user);
        }

        return back()->with('status', $changes === [] ? 'Nothing changed.' : 'Checks saved.');
    }

    /** Email the applicant a signed link to upload copies of their licence, cards and right-to-work document. */
    public function requestDocuments(Request $request, JobApplication $application): RedirectResponse
    {
        Gate::authorize('update', $application);
        if (! $application->email) {
            return back()->withErrors(['email' => 'This applicant gave no email address. Ask them to bring the originals to the assessment instead.']);
        }
        if (! $application->status->isActive()) {
            return back()->withErrors(['email' => 'This application is closed.']);
        }
        $application->forceFill(['documents_requested_at' => now()])->save();
        Notification::route('mail', [$application->email])->notify(new DocumentsRequested($application, $application->documentsUrl()));
        $application->log('Emailed a link to upload licence, card and right-to-work copies (valid '.JobApplication::DOCUMENTS_LINK_DAYS.' days).', 'status', $this->user($request));

        return back()->with('status', 'Upload link emailed to '.$application->email.'.');
    }

    /** Mark as hired and start tracking the driver's CPC, tacho card and licence expiry dates. */
    public function hire(Request $request, JobApplication $application): RedirectResponse
    {
        Gate::authorize('hire', $application);
        $user = $this->user($request);
        $data = $request->validate([
            'employee_number' => ['nullable', 'string', 'max:30'],
            'licence_expires_on' => ['nullable', 'date_format:Y-m-d'],
        ]);
        if ($application->outstanding() !== []) {
            return back()->withErrors(['hire' => 'Still outstanding: '.implode(', ', $application->outstanding()).'.']);
        }

        $staff = DB::transaction(function () use ($application, $data): ?StaffMember {
            if (! $application->claimStatus(ApplicationStatus::Hired)) {
                return null;
            }

            return StaffMember::create([
                'name' => $application->full_name,
                'role' => $application->role,
                'depot' => $application->depot,
                'employee_number' => $data['employee_number'] ?? null,
                'phone' => $application->phone,
                'email' => $application->email,
                'licence_categories' => $application->licence_categories,
                'licence_expires_on' => $data['licence_expires_on'] ?? null,
                'cpc_expires_on' => $application->cpc_expires_on,
                'tacho_card_expires_on' => $application->tacho_card_expires_on,
                'job_application_id' => $application->id,
                'active' => true,
            ]);
        });
        if (! $staff) {
            return back()->withErrors(['hire' => 'This application was already moved on. Reload the page.']);
        }
        $application->log('Hired. Compliance tracking started.', 'status', $user);
        $staff->log('Added from application '.$application->reference.'. Card dates are as the applicant declared them; check them against the cards.', 'system', $user);

        return to_route('admin.staff.show', $staff)->with('status', "{$staff->name} added to staff compliance.");
    }

    public function note(Request $request, JobApplication $application): RedirectResponse
    {
        Gate::authorize('update', $application);
        $data = $request->validate(['body' => ['required', 'string', 'max:3000']]);
        $application->log($data['body'], 'note', $this->user($request));

        return back()->with('status', 'Note added.')->withFragment('timeline');
    }

    public function cv(Request $request, JobApplication $application): StreamedResponse
    {
        Gate::authorize('openCv', $application);
        abort_unless($application->cv_path && Storage::disk('local')->exists($application->cv_path), 404);
        $application->log('Opened the CV.', 'file', $this->user($request));
        $name = Str::slug($application->reference.'-'.$application->full_name.'-cv').'.'.pathinfo($application->cv_path, PATHINFO_EXTENSION);

        return Storage::disk('local')->download($application->cv_path, $name, ['Cache-Control' => 'private, no-store']);
    }

    public function document(Request $request, JobApplication $application, ApplicantDocument $document): StreamedResponse
    {
        Gate::authorize('openDocument', [$application, $document]);
        abort_unless(Storage::disk('local')->exists($document->path), 404);
        $application->log('Opened '.$document->kind->label().'.', 'file', $this->user($request));
        $name = Str::slug($application->reference.'-'.$document->kind->value).'.'.pathinfo($document->path, PATHINFO_EXTENSION);

        return Storage::disk('local')->download($document->path, $name, ['Cache-Control' => 'private, no-store']);
    }

    public function export(Request $request): StreamedResponse
    {
        Gate::authorize('viewAny', JobApplication::class);
        $query = JobApplication::with('jobPosting')->filter($this->filters($request));

        return $this->csv('allinson-applications', ['Reference', 'Received', 'Status', 'Name', 'Position', 'Role', 'Depot', 'Experience', 'Licence', 'CPC expires', 'Tacho card expires', 'Points', 'Right to work checked', 'References', 'Assessment', 'Phone', 'Email'],
            $query->lazyByIdDesc(500)->map(fn (JobApplication $a) => [
                $a->reference, $a->created_at->format('Y-m-d'), $a->status->label(), $a->full_name, $a->position(), $a->role->label(), $a->depotName(),
                JobApplication::EXPERIENCE[$a->experience] ?? $a->experience, $a->licenceList(), $a->cpc_expires_on?->format('Y-m-d'), $a->tacho_card_expires_on?->format('Y-m-d'),
                $a->licence_points, $a->rtw_checked_at?->format('Y-m-d'), $a->references_status->label(), $a->assessment_result?->label() ?? $a->assessment_at?->format('Y-m-d H:i'),
                format_phone($a->phone), $a->email,
            ]));
    }

    /** @return array<string, string|null> */
    private function filters(Request $request): array
    {
        $job = input_string($request->query('job'));
        $depot = input_string($request->query('depot'));

        return [
            'status' => ApplicationStatus::tryFrom(input_string($request->query('status')))?->value,
            'role' => JobRole::tryFrom(input_string($request->query('role')))?->value,
            'depot' => Depots::exists($depot) ? $depot : null,
            'job' => ctype_digit($job) ? $job : null,
            'waiting' => $request->boolean('waiting') ? '1' : null,
            'q' => input_string($request->query('q')) ?: null,
        ];
    }
}
