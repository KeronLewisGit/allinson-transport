<x-layouts.admin :title="$enquiry->reference.' · '.$enquiry->company">
    <x-slot:actions><a href="{{ route('admin.warehousing.index') }}" class="a-btn a-btn-ghost"><x-icon name="arrow-left" class="h-4 w-4" />Warehousing enquiries</a></x-slot:actions>
    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.5fr)_minmax(0,1fr)]">
        <div class="min-w-0 space-y-6">
            <section class="a-card overflow-hidden">
                <div class="flex flex-wrap items-center gap-3 border-b border-line px-5 py-4">
                    <x-status :status="$enquiry->status" />
                    @if ($enquiry->hazardous)<x-chip tone="attention" icon="hazard">Hazardous goods</x-chip>@endif
                    <span class="ml-auto text-sm text-ink-3">Received {{ $enquiry->created_at->format('j M Y, H:i') }}</span>
                </div>
                <div class="grid sm:grid-cols-3">
                    <div class="border-b border-line p-5 sm:border-r"><p class="text-xs font-bold uppercase tracking-wider text-ink-3">Pallet spaces</p><p class="font-display mt-1 text-4xl font-extrabold num">{{ number_format($enquiry->pallet_spaces) }}</p></div>
                    <div class="border-b border-line p-5 sm:border-r"><p class="text-xs font-bold uppercase tracking-wider text-ink-3">Period</p><p class="mt-2 text-lg font-semibold">{{ $enquiry->duration->label() }}</p></div>
                    <div class="border-b border-line p-5"><p class="text-xs font-bold uppercase tracking-wider text-ink-3">From</p><p class="mt-2 text-lg font-semibold">{{ $enquiry->start_on?->format('j M Y') ?? 'Not given' }}</p></div>
                </div>
                <dl class="dl-grid p-5">
                    <dt>Goods</dt><dd>{{ $enquiry->goods_type->label() }}</dd>
                    <dt>Description</dt><dd class="whitespace-pre-line">{{ $enquiry->goods_description }}</dd>
                    <dt>Handling</dt><dd>{{ implode('; ', $enquiry->handlingLabels()) ?: 'Not specified' }}</dd>
                    <dt>Preferred depot</dt><dd>{{ $enquiry->depot ? $enquiry->depotName() : 'No preference' }}</dd>
                    @if ($enquiry->message)<dt>Notes</dt><dd class="whitespace-pre-line">{{ $enquiry->message }}</dd>@endif
                </dl>
            </section>
            @include('admin.partials.timeline', ['notes' => $enquiry->notes, 'action' => auth()->user()->can('update', $enquiry) ? route('admin.warehousing.note', $enquiry) : null])
        </div>
        <aside class="min-w-0 space-y-6">
            <section class="a-card p-5">
                <h2 class="font-semibold">Contact</h2>
                <dl class="dl-grid mt-3">
                    <dt>Company</dt><dd>{{ $enquiry->company }}</dd>
                    <dt>Name</dt><dd>{{ $enquiry->contact_name }}</dd>
                    <dt>Phone</dt><dd><a class="text-link" href="{{ tel_href($enquiry->phone) }}">{{ format_phone($enquiry->phone) }}</a></dd>
                    <dt>Email</dt><dd><a class="text-link" href="mailto:{{ $enquiry->email }}">{{ $enquiry->email }}</a></dd>
                </dl>
            </section>
            @can('update', $enquiry)
                <section class="a-card p-5">
                    <h2 class="font-semibold">Handling</h2>
                    <form method="POST" action="{{ route('admin.warehousing.update', $enquiry) }}" class="mt-3 space-y-3">
                        @csrf @method('PATCH')
                        <div><label for="w-status" class="mb-1 block text-sm text-ink-2">Status</label><select id="w-status" name="status" class="a-input w-full">@foreach (\App\Enums\WarehousingStatus::cases() as $s)<option value="{{ $s->value }}" @selected($enquiry->status === $s)>{{ $s->label() }}</option>@endforeach</select></div>
                        <div><label for="w-depot" class="mb-1 block text-sm text-ink-2">Depot</label><select id="w-depot" name="depot" class="a-input w-full"><option value="">Not decided</option>@foreach (\App\Support\Depots::options() as $k => $n)<option value="{{ $k }}" @selected($enquiry->depot === $k)>{{ $n }}</option>@endforeach</select></div>
                        <div><label for="w-assign" class="mb-1 block text-sm text-ink-2">Assigned to</label><select id="w-assign" name="assigned_to" class="a-input w-full"><option value="">Unassigned</option>@foreach ($staff as $u)<option value="{{ $u->id }}" @selected($enquiry->assigned_to === $u->id)>{{ $u->name }}</option>@endforeach</select></div>
                        <button class="a-btn a-btn-primary">Save</button>
                    </form>
                </section>
            @endcan
        </aside>
    </div>
</x-layouts.admin>
