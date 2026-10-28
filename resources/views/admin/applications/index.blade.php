<x-layouts.admin title="Applications">
    <x-slot:actions>
        <a href="{{ route('admin.applications.export', request()->query()) }}" class="a-btn a-btn-ghost"><x-icon name="download" class="h-4 w-4" />Export CSV</a>
        <a href="{{ route('admin.jobs.index') }}" class="a-btn a-btn-ghost"><x-icon name="briefcase" class="h-4 w-4" />Vacancies</a>
    </x-slot:actions>
    @include('admin.partials.status-tabs', ['cases' => \App\Enums\ApplicationStatus::cases(), 'counts' => $counts, 'current' => $filters['status'], 'route' => 'admin.applications.index', 'extra' => ['role' => $filters['role'], 'depot' => $filters['depot'], 'job' => $filters['job'], 'q' => $filters['q']]])
    <form method="GET" class="mt-3 flex flex-wrap items-end gap-3">
        <input type="hidden" name="status" value="{{ $filters['status'] }}">
        <div><label for="f-role" class="mb-1 block text-sm text-ink-2">Role</label>
            <select id="f-role" name="role" class="a-input"><option value="">All</option>@foreach (\App\Enums\JobRole::cases() as $r)<option value="{{ $r->value }}" @selected($filters['role'] === $r->value)>{{ $r->label() }}</option>@endforeach</select></div>
        <div><label for="f-depot" class="mb-1 block text-sm text-ink-2">Depot</label>
            <select id="f-depot" name="depot" class="a-input"><option value="">All</option>@foreach (\App\Support\Depots::options() as $k => $n)<option value="{{ $k }}" @selected($filters['depot'] === $k)>{{ $n }}</option>@endforeach</select></div>
        <div><label for="f-job" class="mb-1 block text-sm text-ink-2">Vacancy</label>
            <select id="f-job" name="job" class="a-input max-w-[16rem]"><option value="">All</option>@foreach ($jobs as $j)<option value="{{ $j->id }}" @selected($filters['job'] === (string) $j->id)>{{ $j->title }}</option>@endforeach</select></div>
        <label class="flex min-h-11 items-center gap-2"><input type="checkbox" name="waiting" value="1" @checked($filters['waiting']) class="h-5 w-5 accent-[var(--primary)]">Waiting over {{ \App\Models\JobApplication::REVIEW_DAYS }} days</label>
        <div class="min-w-0 flex-1 basis-full sm:basis-56"><label for="f-q" class="mb-1 block text-sm text-ink-2">Search</label><input id="f-q" name="q" value="{{ $filters['q'] }}" class="a-input w-full" placeholder="Name, phone or reference"></div>
        <button class="a-btn a-btn-primary">Filter</button>
    </form>
    <div class="a-card mt-5 min-w-0">
        <div class="table-box">
            <table class="data-table">
                <thead><tr><th>Applicant</th><th>Role and depot</th><th>Licence</th><th>Driver CPC</th><th>Tacho card</th><th>Status</th><th class="r">Received</th></tr></thead>
                <tbody>
                    @forelse ($applications as $a)
                        <tr>
                            <td class="min-w-[11rem]"><a href="{{ route('admin.applications.show', $a) }}" class="font-semibold text-link underline-offset-2 hover:underline">{{ $a->full_name }}</a><p class="num text-xs text-ink-3">{{ $a->reference }}</p></td>
                            <td class="min-w-[11rem]">{{ $a->role->label() }}<p class="text-xs text-ink-3">{{ $a->depotName() }}</p></td>
                            <td class="whitespace-nowrap">{{ $a->licenceList() ?: '—' }}@if($a->licence_points)<p class="text-xs text-ink-3">{{ $a->licence_points }} pts</p>@endif</td>
                            <td class="whitespace-nowrap">@if($a->drives())<x-card-date :date="$a->cpc_expires_on" />@else<span class="text-ink-3">—</span>@endif</td>
                            <td class="whitespace-nowrap">@if($a->drives())<x-card-date :date="$a->tacho_card_expires_on" />@else<span class="text-ink-3">—</span>@endif</td>
                            <td><x-status :status="$a->status" /></td>
                            <td class="r whitespace-nowrap">@if($a->isWaiting())<x-chip tone="attention" icon="clock">{{ $a->created_at->diffForHumans(null, true, true) }}</x-chip>@else{{ $a->created_at->format('j M') }}@endif</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="py-10 text-center text-ink-3">No applications match.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-4">{{ $applications->links() }}</div>
</x-layouts.admin>
