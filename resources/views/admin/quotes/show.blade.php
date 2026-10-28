@php($closed = in_array($quote->status, [\App\Enums\QuoteStatus::Accepted, \App\Enums\QuoteStatus::NotTaken, \App\Enums\QuoteStatus::Declined], true))
<x-layouts.admin :title="$quote->reference.' · '.($quote->company ?: $quote->contact_name)">
    <x-slot:actions><a href="{{ route('admin.quotes.index', ['depot' => $quote->depot]) }}" class="a-btn a-btn-ghost"><x-icon name="arrow-left" class="h-4 w-4" />Quote requests</a></x-slot:actions>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.5fr)_minmax(0,1fr)]">
        <div class="min-w-0 space-y-6">
            <section class="a-card overflow-hidden">
                <div class="flex flex-wrap items-center gap-3 border-b border-line px-5 py-4">
                    <x-status :status="$quote->status" />
                    @if ($quote->status->isOpen())<x-chip :tone="$quote->collectionTone()" icon="clock">{{ $quote->collectionLabel() }}</x-chip>@endif
                    @if ($quote->adr)<x-chip tone="attention" icon="hazard">ADR</x-chip>@endif
                    @if ($quote->regular)<x-chip tone="good" icon="route">Regular load</x-chip>@endif
                    <span class="ml-auto text-sm text-ink-3">Received {{ $quote->created_at->format('j M Y, H:i') }}</span>
                </div>
                <div class="grid gap-0 sm:grid-cols-2">
                    <div class="border-b border-line p-5 sm:border-r"><p class="text-xs font-bold uppercase tracking-wider text-ink-3">Collect</p><p class="font-display mt-1 text-3xl font-extrabold num">{{ $quote->collection_postcode }}</p><p class="text-ink-2">{{ $quote->collection_town }} · {{ $quote->collection_date->format('D j M Y') }}</p></div>
                    <div class="border-b border-line p-5"><p class="text-xs font-bold uppercase tracking-wider text-ink-3">Deliver</p><p class="font-display mt-1 text-3xl font-extrabold num">{{ $quote->delivery_postcode }}</p><p class="text-ink-2">{{ $quote->delivery_town }} · {{ $quote->delivery_date?->format('D j M Y') ?? 'any day after' }}</p></div>
                </div>
                <dl class="dl-grid p-5">
                    <dt>Service</dt><dd>{{ $quote->service->label() }}</dd>
                    <dt>Goods</dt><dd>{{ $quote->goods }}</dd>
                    <dt>Weight</dt><dd class="num">{{ number_format($quote->weight_kg) }} kg</dd>
                    @if ($quote->pallets)<dt>Pallets</dt><dd class="num">{{ $quote->pallets }}</dd>@endif
                    @if ($quote->dimensions())<dt>Dimensions</dt><dd class="num">{{ $quote->dimensions() }}</dd>@endif
                    <dt>Dangerous goods</dt><dd>{{ $quote->adr ? 'Yes · '.($quote->adr_details ?: 'details not given') : 'No' }}</dd>
                    <dt>Tail lift</dt><dd>{{ $quote->tail_lift ? 'Needed' : 'No' }}</dd>
                    @if ($quote->message)<dt>Notes</dt><dd class="whitespace-pre-line">{{ $quote->message }}</dd>@endif
                </dl>
                @if ($quote->files->isNotEmpty())
                    <div class="border-t border-line p-5">
                        <p class="text-xs font-bold uppercase tracking-wider text-ink-3">Files</p>
                        <ul class="mt-2 space-y-1">@foreach ($quote->files as $f)<li><a href="{{ route('admin.quotes.file', [$quote, $f]) }}" class="inline-flex min-h-11 items-center gap-2 text-link"><x-icon name="file" class="h-4 w-4" />{{ $f->original_name }} <span class="text-sm text-ink-3">{{ file_size($f->size) }}</span></a></li>@endforeach</ul>
                    </div>
                @endif
            </section>

            <section class="a-card p-5" aria-labelledby="price-title">
                <h2 id="price-title" class="font-semibold">{{ $quote->price !== null ? 'Quote sent' : 'Send a price' }}</h2>
                @if ($quote->price !== null)
                    <p class="mt-2"><span class="font-display text-4xl font-extrabold num">{{ gbp($quote->price) }}</span> <span class="text-ink-2">+ VAT · valid until {{ $quote->valid_until?->format('j M Y') }}</span></p>
                    @if ($quote->responded_at)<p class="mt-1 text-ink-2">Customer answered {{ $quote->responded_at->format('j M Y, H:i') }}{{ $quote->customer_note ? ': “'.$quote->customer_note.'”' : '' }}</p>@endif
                @endif
                @can('update', $quote)
                    @unless ($closed)
                        <form method="POST" action="{{ route('admin.quotes.price', $quote) }}" class="mt-4 grid gap-3 sm:grid-cols-2">
                            @csrf
                            <div><label for="q-price" class="mb-1 block text-sm text-ink-2">Price excluding VAT (£)</label><input id="q-price" name="price" type="number" step="0.01" min="1" required class="a-input w-full num" value="{{ old('price', $quote->price) }}"></div>
                            <div><label for="q-valid" class="mb-1 block text-sm text-ink-2">Valid until</label><input id="q-valid" name="valid_until" type="date" required class="a-input w-full" value="{{ old('valid_until', $quote->valid_until?->format('Y-m-d') ?? today()->addDays(14)->format('Y-m-d')) }}"></div>
                            <div class="sm:col-span-2"><label for="q-notes" class="mb-1 block text-sm text-ink-2">Notes for the customer</label><textarea id="q-notes" name="price_notes" class="a-input" maxlength="2000" placeholder="Vehicle type, booking-in, waiting time terms">{{ old('price_notes', $quote->price_notes) }}</textarea></div>
                            <div class="sm:col-span-2"><button class="a-btn a-btn-primary"><x-icon name="send" class="h-4 w-4" />{{ $quote->price !== null ? 'Update and email again' : 'Email the quote' }}</button>
                                <p class="mt-2 text-sm text-ink-3">Emails {{ $quote->email }} a private link to accept or decline online.</p></div>
                        </form>
                    @endunless
                @endcan
            </section>

            @include('admin.partials.timeline', ['notes' => $quote->notes, 'action' => auth()->user()->can('update', $quote) ? route('admin.quotes.note', $quote) : null])
        </div>

        <aside class="min-w-0 space-y-6">
            <section class="a-card p-5">
                <h2 class="font-semibold">Customer</h2>
                <dl class="dl-grid mt-3">
                    <dt>Name</dt><dd>{{ $quote->contact_name }}</dd>
                    @if ($quote->company)<dt>Company</dt><dd>{{ $quote->company }}</dd>@endif
                    <dt>Phone</dt><dd><a class="text-link" href="{{ tel_href($quote->phone) }}">{{ format_phone($quote->phone) }}</a></dd>
                    <dt>Email</dt><dd><a class="text-link" href="mailto:{{ $quote->email }}">{{ $quote->email }}</a></dd>
                </dl>
            </section>
            @can('update', $quote)
                <section class="a-card p-5">
                    <h2 class="font-semibold">Handling</h2>
                    <form method="POST" action="{{ route('admin.quotes.update', $quote) }}" class="mt-3 space-y-3">
                        @csrf @method('PATCH')
                        <div><label for="q-status" class="mb-1 block text-sm text-ink-2">Status</label><select id="q-status" name="status" class="a-input w-full">@foreach (\App\Enums\QuoteStatus::cases() as $s)<option value="{{ $s->value }}" @selected($quote->status === $s)>{{ $s->label() }}</option>@endforeach</select></div>
                        <div><label for="q-depot" class="mb-1 block text-sm text-ink-2">Depot</label><select id="q-depot" name="depot" class="a-input w-full">@foreach (\App\Support\Depots::options() as $k => $n)<option value="{{ $k }}" @selected($quote->depot === $k)>{{ $n }}</option>@endforeach</select></div>
                        <div><label for="q-assign" class="mb-1 block text-sm text-ink-2">Assigned to</label><select id="q-assign" name="assigned_to" class="a-input w-full"><option value="">Unassigned</option>@foreach ($staff as $u)<option value="{{ $u->id }}" @selected($quote->assigned_to === $u->id)>{{ $u->name }}{{ $u->depot ? ' · '.\App\Support\Depots::name($u->depot) : '' }}</option>@endforeach</select></div>
                        <button class="a-btn a-btn-primary">Save</button>
                    </form>
                </section>
            @endcan
        </aside>
    </div>
</x-layouts.admin>
