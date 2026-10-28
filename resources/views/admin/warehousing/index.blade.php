<x-layouts.admin title="Warehousing enquiries">
    <x-slot:actions>
        <a href="{{ route('admin.warehousing.export', request()->query()) }}" class="a-btn a-btn-ghost"><x-icon name="download" class="h-4 w-4" />Export CSV</a>
    </x-slot:actions>
    @include('admin.partials.status-tabs', ['cases' => \App\Enums\WarehousingStatus::cases(), 'counts' => $counts, 'current' => $filters['status'], 'route' => 'admin.warehousing.index', 'extra' => ['goods' => $filters['goods'], 'depot' => $filters['depot'], 'q' => $filters['q']]])
    <form method="GET" class="mt-3 flex flex-wrap items-end gap-3">
        <input type="hidden" name="status" value="{{ $filters['status'] }}">
        <div><label for="f-goods" class="mb-1 block text-sm text-ink-2">Goods</label>
            <select id="f-goods" name="goods" class="a-input"><option value="">All</option>@foreach (\App\Enums\GoodsType::cases() as $g)<option value="{{ $g->value }}" @selected($filters['goods'] === $g->value)>{{ $g->label() }}</option>@endforeach</select></div>
        <div><label for="f-depot" class="mb-1 block text-sm text-ink-2">Preferred depot</label>
            <select id="f-depot" name="depot" class="a-input"><option value="">Any</option>@foreach (\App\Support\Depots::options() as $k => $n)<option value="{{ $k }}" @selected($filters['depot'] === $k)>{{ $n }}</option>@endforeach</select></div>
        <label class="flex min-h-11 items-center gap-2"><input type="checkbox" name="waiting" value="1" @checked($filters['waiting']) class="h-5 w-5 accent-[var(--primary)]">Waiting over {{ \App\Models\WarehousingEnquiry::RESPONSE_DAYS }} day</label>
        <div class="min-w-0 flex-1 basis-full sm:basis-56"><label for="f-q" class="mb-1 block text-sm text-ink-2">Search</label><input id="f-q" name="q" value="{{ $filters['q'] }}" class="a-input w-full" placeholder="Company, name or reference"></div>
        <button class="a-btn a-btn-primary">Filter</button>
    </form>
    <div class="a-card mt-5 min-w-0">
        <div class="table-box">
            <table class="data-table">
                <thead><tr><th>Company</th><th class="r">Pallet spaces</th><th>Period</th><th>Goods</th><th>Status</th><th>Assigned</th><th class="r">Received</th></tr></thead>
                <tbody>
                    @forelse ($enquiries as $e)
                        <tr>
                            <td class="min-w-[12rem]"><a href="{{ route('admin.warehousing.show', $e) }}" class="font-semibold text-link underline-offset-2 hover:underline">{{ $e->company }}</a><p class="num text-xs text-ink-3">{{ $e->reference }} · {{ $e->contact_name }}</p></td>
                            <td class="r font-semibold">{{ number_format($e->pallet_spaces) }}</td>
                            <td class="whitespace-nowrap">{{ $e->duration->label() }}</td>
                            <td>{{ $e->goods_type->label() }}@if($e->hazardous) <x-chip tone="attention" icon="hazard">Hazardous</x-chip>@endif</td>
                            <td><x-status :status="$e->status" /></td>
                            <td>{{ $e->assignee->name ?? '—' }}</td>
                            <td class="r whitespace-nowrap">@if($e->isWaiting())<x-chip tone="attention" icon="clock">{{ $e->created_at->diffForHumans(null, true, true) }}</x-chip>@else{{ $e->created_at->format('j M') }}@endif</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="py-10 text-center text-ink-3">No warehousing enquiries match.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-4">{{ $enquiries->links() }}</div>
</x-layouts.admin>
