@php
    $answered = in_array($quote->status, [\App\Enums\QuoteStatus::Accepted, \App\Enums\QuoteStatus::NotTaken], true);
@endphp
<x-layouts.site title="Your quote {{ $quote->reference }}" :mobile-bar="false">
    <x-page-header photo="a1m" position="center 60%" kicker="Your quote" :title="'Quote '.$quote->reference" :ref="$quote->reference" lead="Check the details, then accept or decline online. Prices exclude VAT." :label="['From' => $quote->collection_postcode, 'To' => $quote->delivery_postcode, '*Contents' => $quote->service->label()]" />

    <section class="wrap mt-12 grid gap-12 md:mt-16 lg:grid-cols-[minmax(0,1fr)_22rem]">
        <div class="min-w-0 space-y-8">
            @if (session('status'))
                <div class="panel flex items-start gap-3 border-l-[6px] border-l-[color:var(--good)] p-5" role="status"><x-icon name="check-circle" class="h-5 w-5 flex-none text-[color:var(--good)]" /><p>{{ session('status') }}</p></div>
            @endif
            <x-form-errors />

            <x-consignment :flat="true" head="Allinson Transport · {{ $quote->depotName() }}" :ref="$quote->reference" :fields="array_filter([
                'Collect' => $quote->collection_postcode.($quote->collection_town ? ' · '.$quote->collection_town : ''),
                'Deliver' => $quote->delivery_postcode.($quote->delivery_town ? ' · '.$quote->delivery_town : ''),
                'Collection date' => $quote->collection_date->format('D j M Y'),
                'Delivery date' => $quote->delivery_date?->format('D j M Y') ?? 'Any day after',
                '*Goods' => $quote->goods,
                'Weight' => number_format($quote->weight_kg).' kg',
                'Pallets' => $quote->pallets,
                '*Dimensions' => $quote->dimensions(),
                '*Dangerous goods' => $quote->adr ? 'Yes · '.$quote->adr_details : null,
            ], fn ($v) => $v !== null && $v !== '')" />

            @if ($quote->price_notes)
                <div><h2 class="kicker text-ink-3">Notes from our traffic office</h2><p class="mt-2 whitespace-pre-line measure">{{ $quote->price_notes }}</p></div>
            @endif
        </div>

        <aside class="space-y-6">
            <div class="sheet p-6">
                <p class="kicker text-ink-3">Price</p>
                <p class="font-display mt-1 text-6xl font-extrabold leading-none num">{{ gbp($quote->price) }}</p>
                <p class="mt-2 text-ink-2">plus VAT{{ $quote->valid_until ? ' · valid until '.$quote->valid_until->format('j F Y') : '' }}</p>
                <p class="mt-4"><x-status :status="$quote->status" /></p>

                @if ($quote->canBeAccepted())
                    <form method="POST" action="{{ request()->fullUrl() }}" class="mt-6 space-y-4" x-data="sendForm" @submit="submit">
                        @csrf
                        <x-field name="note" label="Note for the traffic office" type="textarea" :optional="true" maxlength="1000" rows="3" hint="Booking-in reference, contact on site or anything else." />
                        <div class="grid gap-3">
                            <button type="submit" name="decision" value="accept" class="btn btn-primary w-full" :disabled="submitting">Accept and book</button>
                            <button type="submit" name="decision" value="decline" class="btn btn-outline w-full" :disabled="submitting">Decline</button>
                        </div>
                    </form>
                @elseif (! $answered)
                    <p class="mt-6 text-ink-2">This quote has expired. Call <a class="text-link" href="{{ tel_href(ga('phone')) }}">{{ format_phone(ga('phone')) }}</a> and we will price it again.</p>
                @endif
            </div>
            <p class="text-sm text-ink-3">Questions? Call {{ format_phone(ga('phone')) }} and quote {{ $quote->reference }}.</p>
        </aside>
    </section>
</x-layouts.site>
