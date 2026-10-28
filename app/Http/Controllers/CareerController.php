<?php

namespace App\Http\Controllers;

use App\Enums\ApplicantDocumentKind;
use App\Http\Requests\StoreApplicantDocumentsRequest;
use App\Http\Requests\StoreApplicationRequest;
use App\Models\JobApplication;
use App\Models\JobPosting;
use App\Support\Depots;
use App\Support\Intake;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CareerController extends Controller
{
    public function index(Request $request): View
    {
        $depot = input_string($request->query('depot'));
        $depot = Depots::exists($depot) ? $depot : null;

        return view('pages.careers.index', [
            'jobs' => JobPosting::open()->when($depot, fn ($q) => $q->where('depot', $depot))->orderBy('role')->orderBy('depot')->orderBy('title')->get(),
            'depot' => $depot,
            'depotCounts' => JobPosting::open()->selectRaw('depot, count(*) as total')->groupBy('depot')->pluck('total', 'depot'),
        ]);
    }

    public function show(JobPosting $job): View
    {
        abort_unless($job->isOpen(), 404);

        return view('pages.careers.show', ['job' => $job]);
    }

    public function create(Request $request): View
    {
        $jobs = JobPosting::open()->orderBy('title')->get();

        return view('pages.careers.apply', ['jobs' => $jobs, 'selected' => $jobs->firstWhere('slug', input_string($request->query('job')))]);
    }

    public function store(StoreApplicationRequest $request): RedirectResponse
    {
        if (Intake::isBot($request, JobApplication::fakeReference(), 'Job application')) {
            $request->releaseHeldUploads();

            return to_route('thanks');
        }

        $data = $request->safe()->except(['cv', 'consent']);
        if (! $request->role()?->drives()) {
            // Office and warehouse applicants are not asked about driver cards.
            $data = array_merge($data, ['cpc_expires_on' => null, 'tacho_card_expires_on' => null]);
        }
        if ($file = $request->file('cv')) {
            $data += [
                'cv_path' => $file->store('cvs', 'local'),
                'cv_name' => mb_substr($file->getClientOriginalName(), 0, 200),
                'cv_mime' => $file->getMimeType(),
                'cv_size' => (int) $file->getSize(),
            ];
        }
        $application = JobApplication::create($data + ['consented_at' => now()]);

        $request->releaseHeldUploads();
        Intake::complete($application->refresh()->load('jobPosting'), $request);

        return to_route('thanks');
    }

    /** Signed link emailed by HR: upload copies of the licence, cards and right-to-work document. */
    public function documents(string $application): View
    {
        $application = $this->applicationFor($application);

        return view('pages.careers.documents', [
            'application' => $application->load('currentDocuments'),
            'kinds' => ApplicantDocumentKind::forDriving($application->drives()),
        ]);
    }

    public function storeDocuments(StoreApplicantDocumentsRequest $request, string $application): RedirectResponse
    {
        $application = $this->applicationFor($application);
        $kinds = ApplicantDocumentKind::forDriving($application->drives());
        $stored = [];
        foreach ($kinds as $kind) {
            $file = $request->file('docs.'.$kind->value);
            if (! $file instanceof UploadedFile) {
                continue;
            }
            DB::transaction(function () use ($application, $kind, $file, &$stored): void {
                // A re-upload supersedes the earlier copy; nothing is deleted.
                $application->documents()->where('kind', $kind->value)->whereNull('superseded_at')->update(['superseded_at' => now()]);
                $application->documents()->create([
                    'kind' => $kind,
                    'path' => $file->store('applicants/'.$application->id, 'local'),
                    'original_name' => mb_substr($file->getClientOriginalName(), 0, 200),
                    'mime' => $file->getMimeType(),
                    'size' => (int) $file->getSize(),
                ]);
                $stored[] = $kind->label();
            });
        }
        $request->releaseHeldUploads();
        if ($stored !== []) {
            $application->log('Applicant uploaded: '.implode('; ', $stored).'.', 'file');
        }

        return back()->with('status', $stored === [] ? 'Nothing was uploaded.' : 'Thank you. We have received: '.implode('; ', $stored).'.');
    }

    private function applicationFor(string $publicId): JobApplication
    {
        $application = JobApplication::where('public_id', $publicId)->firstOrFail();
        abort_unless($application->canUploadDocuments(), 403);

        return $application;
    }
}
