<?php

namespace App\Http\Controllers\Admin;

use App\Enums\JobRole;
use App\Http\Controllers\Controller;
use App\Models\StaffMember;
use App\Support\Depots;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Staff compliance: Driver CPC, tacho driver card and licence photocard expiry dates. */
class StaffController extends Controller
{
    use Concerns;

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', StaffMember::class);
        $filters = $this->filters($request);
        $staff = StaffMember::query()->filter($filters)->get()
            ->sortBy(fn (StaffMember $s) => [array_search($s->worstState(), ['expired', 'missing', 'due', 'valid'], true), $s->nextExpiry()[1] ?? now()->addYears(50), $s->name])
            ->values();

        return view('admin.staff.index', [
            'staff' => $staff,
            'filters' => $filters,
            'dueCount' => StaffMember::due()->count(),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', StaffMember::class);

        return view('admin.staff.form', ['staff' => new StaffMember(['role' => JobRole::CeDriver, 'depot' => Depots::HEAD_OFFICE, 'active' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', StaffMember::class);
        $staff = StaffMember::create($this->validated($request));
        $staff->log('Added to staff compliance.', 'system', $this->user($request));

        return to_route('admin.staff.show', $staff)->with('status', "{$staff->name} added.");
    }

    public function show(StaffMember $staff): View
    {
        Gate::authorize('view', $staff);

        return view('admin.staff.show', ['staff' => $staff->load(['notes.user', 'application'])]);
    }

    public function edit(StaffMember $staff): View
    {
        Gate::authorize('update', $staff);

        return view('admin.staff.form', ['staff' => $staff]);
    }

    public function update(Request $request, StaffMember $staff): RedirectResponse
    {
        Gate::authorize('update', $staff);
        $data = $this->validated($request);
        $staff->fill($data);
        $changed = [];
        foreach (StaffMember::CARDS as [$column, , $label]) {
            if ($staff->isDirty($column)) {
                $changed[] = $label.' '.($staff->getAttribute($column)?->format('j M Y') ?? 'cleared');
            }
        }
        $staff->save();
        $staff->log($changed === [] ? 'Details updated.' : 'Updated: '.implode('; ', $changed).'.', 'status', $this->user($request));

        return to_route('admin.staff.show', $staff)->with('status', "{$staff->name} saved.");
    }

    public function note(Request $request, StaffMember $staff): RedirectResponse
    {
        Gate::authorize('update', $staff);
        $data = $request->validate(['body' => ['required', 'string', 'max:3000']]);
        $staff->log($data['body'], 'note', $this->user($request));

        return back()->with('status', 'Note added.')->withFragment('timeline');
    }

    public function export(Request $request): StreamedResponse
    {
        Gate::authorize('viewAny', StaffMember::class);

        return $this->csv('allinson-staff-compliance', ['Name', 'Employee number', 'Role', 'Depot', 'Driver CPC expires', 'Tacho card expires', 'Licence photocard expires', 'Cards checked', 'Active'],
            StaffMember::query()->filter($this->filters($request))->orderBy('name')->lazy(500)->map(fn (StaffMember $s) => [
                $s->name, $s->employee_number, $s->role->label(), $s->depotName(), $s->cpc_expires_on?->format('Y-m-d'), $s->tacho_card_expires_on?->format('Y-m-d'),
                $s->licence_expires_on?->format('Y-m-d'), $s->cards_checked_on?->format('Y-m-d'), $s->active ? 'yes' : 'no',
            ]));
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'employee_number' => ['nullable', 'string', 'max:30'],
            'role' => ['required', Rule::enum(JobRole::class)],
            'depot' => ['required', Rule::in(Depots::keys())],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email:rfc', 'max:255'],
            'licence_categories' => ['nullable', 'array'],
            'licence_categories.*' => ['string', Rule::in(array_keys((array) ga('careers.licence_categories')))],
            'cpc_expires_on' => ['nullable', 'date_format:Y-m-d'],
            'tacho_card_expires_on' => ['nullable', 'date_format:Y-m-d'],
            'licence_expires_on' => ['nullable', 'date_format:Y-m-d'],
            'cards_checked_on' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
        ]);

        return $data + ['active' => $request->boolean('active'), 'licence_categories' => $data['licence_categories'] ?? []];
    }

    /** @return array<string, string|null> */
    private function filters(Request $request): array
    {
        $depot = input_string($request->query('depot'));
        $active = input_string($request->query('active'));

        return [
            'active' => in_array($active, ['0', 'all'], true) ? $active : '1',
            'depot' => Depots::exists($depot) ? $depot : null,
            'role' => JobRole::tryFrom(input_string($request->query('role')))?->value,
            'due' => $request->boolean('due') ? '1' : null,
            'q' => input_string($request->query('q')) ?: null,
        ];
    }
}
