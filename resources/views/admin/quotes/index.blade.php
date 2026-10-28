<x-layouts.admin title="Quote requests">
    <x-slot:actions>
        <a href="{{ route('admin.quotes.export', request()->query()) }}" class="a-btn a-btn-ghost"><x-icon name="download" class="h-4 w-4" />Export CSV</a>
    </x-slot:actions>
    <nav class="tag-row" aria-label="Filter by status">
        <a href="{{ route('admin.quotes.index', ['depot' => $filters['depot'] ?? '']) }}" class="tag" @if(! $filters['status']) aria-current="true" @endif>All <span class="count">{{ $counts->sum() }}</span></a>
        <a href="{{ route('admin.quotes.index', ['status' => 'open', 'depot' => $filters['depot'] ?? '']) }}" class="tag" @if($filters['status'] === 'open') aria-current="true" @endif>To price <span class="count">{{ $openCount }}</span></a>
        @foreach (\App\Enums\QuoteStatus::cases() as $s)
            <a href="{{ route('admin.quotes.index', ['status' => $s->value, 'depot' => $filters['depot'] ?? '']) }}" class="tag" @if($filters['status'] === $s->value) aria-current="true" @endif>{{ $s->label() }} <span class="count">{{ $counts[$s->value] ?? 0 }}</span></a>
        @endforeach
    </nav>
    <form method="GET" class="mt-3 flex flex-wrap items-end gap-3">
        <input type="hidden" name="status" value="{{ $filters['status'] }}">
        <div><label for="f-depot" class="mb-1 block text-sm text-ink-2">Depot</label>
            <select id="f-depot" name="depot" class="a-input"><option value="">All depots</option>@foreach (\App\Support\Depots::options() as $k => $n)<option value="{{ $k }}" @selected($filters['depot'] === $k)>{{ $n }}</option>@endforeach</select></div>
        <div><label for="f-service" class="mb-1 block text-sm text-ink-2">Service</label>
            <select id="f-service" name="service" class="a-input"><option value="">All</option>@foreach (\App\Enums\QuoteService::cases() as $s)<option value="{{ $s->value }}" @selected($filters['service'] === $s->value)>{{ $s->label() }}</option>@endforeach</select></div>
        <div><label for="f-assigned" class="mb-1 block text-sm text-ink-2">Assigned</label>
            <select id="f-assigned" name="assigned" class="a-input"><option value="">Anyone</option><option value="me" @selected($filters['assigned'] === (string) auth()->id())>Me</option><option value="none" @selected($filters['assigned'] === 'none')>Unassigned</option>@foreach ($staff as $u)<option value="{{ $u->id }}" @selected($filters['assigned'] === (string) $u->id && $u->id !== auth()->id())>{{ $u->name }}</option>@endforeach</select></div>
        <label class="flex min-h-11 items-center gap-2"><input type="checkbox" name="adr" value="1" @checked($filters['adr']) class="h-5 w-5 accent-[var(--primary)]">ADR only</label>
        <div class="min-w-0 flex-1 basis-full sm:basis-56"><label for="f-q" class="mb-1 block text-sm text-ink-2">Search</label><input id="f-q" name="q" value="{{ $filters['q'] }}" class="a-input w-full" placeholder="Reference, company or postcode"></div>
        <button class="a-btn a-btn-primary">Filter</button>
    </form>
    <div class="a-card mt-5 min-w-0">
        <div class="table-box">
            <table class="data-table">
                <thead><tr><th>Request</th><th>Route</th><th>Load</th><th>Depot</th><th>Status</th><th>Assigned</th><th class="r">Collects</th></tr></thead>
                <tbody>
                    @forelse ($quotes as $q)
                        <tr>
                            <td class="min-w-[12rem]"><a href="{{ route('admin.quotes.show', $q) }}" class="font-semibold text-link underline-offset-2 hover:underline">{{ $q->company ?: $q->contact_name }}</a><p class="num text-xs text-ink-3">{{ $q->reference }} · {{ $q->service->label() }}</p></td>
                            <td class="num whitespace-nowrap font-semibold">{{ $q->route() }}</td>
                            <td class="min-w-[10rem]">{{ number_format($q->weight_kg) }} kg{{ $q->pallets ? ' · '.$q->pallets.' plt' : '' }}@if($q->adr) <x-chip tone="attention" icon="hazard">ADR</x-chip>@endif @if($q->files_count)<x-icon name="file" class="inline h-4 w-4 text-ink-3" /><span class="sr-only">{{ $q->files_count }} files</span>@endif</td>
                            <td>{{ $q->depotName() }}</td>
                            <td><x-status :status="$q->status" />@if($q->price !== null)<p class="num mt-0.5 text-xs text-ink-3">{{ gbp($q->price) }}</p>@endif</td>
                            <td>{{ $q->assignee->name ?? '—' }}</td>
                            <td class="r whitespace-nowrap">@if($q->status->isOpen())<x-chip :tone="$q->collectionTone()" icon="clock">{{ $q->collectionLabel() }}</x-chip>@else{{ $q->collection_date->format('j M') }}@endif</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="py-10 text-center text-ink-3">No quote requests match.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-4">{{ $quotes->links() }}</div>
</x-layouts.admin>
