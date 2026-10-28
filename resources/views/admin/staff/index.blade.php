<x-layouts.admin title="Staff compliance">
    <x-slot:actions>
        <a href="{{ route('admin.staff.export', request()->query()) }}" class="a-btn a-btn-ghost"><x-icon name="download" class="h-4 w-4" />Export CSV</a>
        <a href="{{ route('admin.staff.create') }}" class="a-btn a-btn-primary"><x-icon name="plus" class="h-4 w-4" />Add a person</a>
    </x-slot:actions>
    <p class="measure mb-4 text-ink-2">Driver CPC, tacho driver card and licence photocard expiry dates for drivers. HR is emailed at 90, 60, 30 and 14 days before a date and on the day it expires. A driver must not drive once a card has expired.</p>
    <nav class="tag-row" aria-label="Filter">
        <a href="{{ route('admin.staff.index') }}" class="tag" @if(! $filters['due'] && $filters['active'] === '1') aria-current="true" @endif>Active staff</a>
        <a href="{{ route('admin.staff.index', ['due' => 1]) }}" class="tag" @if($filters['due']) aria-current="true" @endif>Expired or due in {{ \App\Models\StaffMember::WARNING_DAYS }} days <span class="count">{{ $dueCount }}</span></a>
        <a href="{{ route('admin.staff.index', ['active' => '0']) }}" class="tag" @if($filters['active'] === '0') aria-current="true" @endif>Left</a>
    </nav>
    <form method="GET" class="mt-3 flex flex-wrap items-end gap-3">
        @if ($filters['due'])<input type="hidden" name="due" value="1">@endif
        <input type="hidden" name="active" value="{{ $filters['active'] }}">
        <div><label for="f-depot" class="mb-1 block text-sm text-ink-2">Depot</label>
            <select id="f-depot" name="depot" class="a-input"><option value="">All</option>@foreach (\App\Support\Depots::options() as $k => $n)<option value="{{ $k }}" @selected($filters['depot'] === $k)>{{ $n }}</option>@endforeach</select></div>
        <div><label for="f-role" class="mb-1 block text-sm text-ink-2">Role</label>
            <select id="f-role" name="role" class="a-input"><option value="">All</option>@foreach (\App\Enums\JobRole::cases() as $r)<option value="{{ $r->value }}" @selected($filters['role'] === $r->value)>{{ $r->label() }}</option>@endforeach</select></div>
        <div class="min-w-0 flex-1 basis-full sm:basis-56"><label for="f-q" class="mb-1 block text-sm text-ink-2">Search</label><input id="f-q" name="q" value="{{ $filters['q'] }}" class="a-input w-full" placeholder="Name or employee number"></div>
        <button class="a-btn a-btn-primary">Filter</button>
    </form>
    <div class="a-card mt-5 min-w-0">
        <div class="table-box">
            <table class="data-table">
                <thead><tr><th>Name</th><th>Role and depot</th><th>Driver CPC</th><th>Tacho card</th><th>Licence</th><th class="r">Cards checked</th></tr></thead>
                <tbody>
                    @forelse ($staff as $s)
                        <tr>
                            <td class="min-w-[11rem]"><a href="{{ route('admin.staff.show', $s) }}" class="font-semibold text-link underline-offset-2 hover:underline">{{ $s->name }}</a><p class="num text-xs text-ink-3">{{ $s->employee_number ?? '—' }}@if(! $s->active) · left @endif</p></td>
                            <td class="min-w-[10rem]">{{ $s->role->label() }}<p class="text-xs text-ink-3">{{ $s->depotName() }}</p></td>
                            @foreach (['cpc', 'tacho', 'licence'] as $card)
                                <td class="whitespace-nowrap">@if($s->drives())<x-card-date :date="$s->expiry($card)" :state="$s->state($card)" />@else<span class="text-ink-3">—</span>@endif</td>
                            @endforeach
                            <td class="r whitespace-nowrap">{{ $s->cards_checked_on?->format('j M Y') ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-10 text-center text-ink-3">{{ $filters['due'] ? 'Every active driver\'s cards are in date.' : 'Nobody here yet.' }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layouts.admin>
