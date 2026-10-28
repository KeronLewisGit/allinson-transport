@php
    $services = \App\Enums\QuoteService::cases();
    $current = input_string(old('service', $service)) ?: 'full_load';
@endphp
<x-layouts.site title="Request a haulage quote" description="Ask George Allinson (Transport) Ltd for a price: full load, part load, pallets, Hiab, abnormal loads or storage, from any UK postcode.">
    <x-page-header photo="a1m" position="center 60%" kicker="Haulage quote" title="Request a quote" lead="Postcodes, dates and weight are enough to start. The request goes to the depot nearest the collection, and the traffic office emails your price." :label="['From' => 'Your postcode', 'To' => 'Any UK postcode', '*Contents' => 'Quote request']" />

    <section class="wrap mt-12 grid gap-12 md:mt-16 lg:grid-cols-[minmax(0,1fr)_20rem]">
        <form method="POST" action="{{ route('quotes.store') }}" enctype="multipart/form-data" class="min-w-0 space-y-10" x-data="quoteForm(@js($current), @js((bool) old('adr')))" @submit="submit">
            @csrf
            <x-form-errors />
            <x-honeypot />

            <x-fieldset n="1" title="What needs moving">
                <fieldset>
                    <legend class="label">Type of job</legend>
                    <div class="grid gap-2 sm:grid-cols-2">
                        @foreach ($services as $s)
                            <label class="choice"><input type="radio" name="service" value="{{ $s->value }}" x-model="service" @checked($current === $s->value) required><span>{{ $s->label() }}<small>{{ $s->hint() }}</small></span></label>
                        @endforeach
                    </div>
                    <x-error for="service" />
                </fieldset>
                <x-field name="goods" label="What are the goods?" :required="true" maxlength="300" hint="For example: palletised flooring, steel extrusions, bagged polymer, boxed retail stock." />
                <div class="grid gap-5 sm:grid-cols-2">
                    <div x-show="pallets" x-cloak><x-field name="pallets" label="Number of pallets" type="number" min="1" max="52" inputmode="numeric" hint="A 13.6 m trailer takes 26 standard pallets." /></div>
                    <x-field name="weight_kg" label="Total weight (kg)" type="number" :required="true" min="1" max="150000" inputmode="numeric" />
                </div>
                <div x-show="dimensions" x-cloak class="grid gap-5 sm:grid-cols-3">
                    <x-field name="length_m" label="Length (m)" type="number" step="0.01" min="0.1" max="60" inputmode="decimal" />
                    <x-field name="width_m" label="Width (m)" type="number" step="0.01" min="0.1" max="10" inputmode="decimal" />
                    <x-field name="height_m" label="Height (m)" type="number" step="0.01" min="0.1" max="10" inputmode="decimal" />
                </div>
                <div class="grid gap-3 sm:grid-cols-2">
                    <x-flag name="adr" label="Dangerous goods (ADR)" hint="Hazardous goods under ADR rules" x-model="adr" />
                    <x-flag name="tail_lift" label="Needs a tail lift" hint="No forklift at one end" />
                </div>
                <div x-show="adr" x-cloak><x-field name="adr_details" label="UN number and class" maxlength="160" hint="For example: UN1263, class 3. Our traffic office will check it before pricing." /></div>
            </x-fieldset>

            <x-fieldset n="2" title="Where and when">
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-field name="collection_postcode" label="Collection postcode" :required="true" maxlength="10" autocomplete="off" autocapitalize="characters" hint="For example: DL3 0PH" class="input uppercase" />
                    <x-field name="collection_town" label="Collection town" :optional="true" maxlength="80" />
                    <x-field name="delivery_postcode" label="Delivery postcode" :required="true" maxlength="10" autocomplete="off" autocapitalize="characters" class="input uppercase" />
                    <x-field name="delivery_town" label="Delivery town" :optional="true" maxlength="80" />
                    <x-field name="collection_date" label="Collection date" type="date" :required="true" :min="today()->format('Y-m-d')" />
                    <x-field name="delivery_date" label="Delivery date" type="date" :optional="true" :min="today()->format('Y-m-d')" hint="Leave blank if any day after collection is fine." />
                </div>
                <x-flag name="regular" label="This is a regular load" hint="Weekly, monthly or on contract: we will price the run, not just one trip" />
            </x-fieldset>

            <x-fieldset n="3" title="Your details">
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-field name="contact_name" label="Your name" :required="true" maxlength="120" autocomplete="name" />
                    <x-field name="company" label="Company" :optional="true" maxlength="160" autocomplete="organization" />
                    <x-field name="email" label="Email" type="email" :required="true" maxlength="255" autocomplete="email" hint="We email the quote here." />
                    <x-field name="phone" label="Phone" type="tel" :required="true" maxlength="30" autocomplete="tel" inputmode="tel" />
                </div>
                <x-field name="message" label="Anything else?" type="textarea" :optional="true" maxlength="3000" rows="4" hint="Access restrictions, booking-in times, load sequence or special handling." />
                <div>
                    <label for="f-files" class="label">Drawings, packing lists or photos <span class="opt">(optional, up to {{ \App\Models\Quote::MAX_FILES }} files, 10 MB each)</span></label>
                    <input id="f-files" name="files[]" type="file" multiple class="input" accept=".{{ implode(',.', \App\Models\Quote::FILE_TYPES) }}">
                    <x-held-upload form="quote" field="files" />
                    <x-error for="files" /><x-error for="files.0" /><x-error for="files.1" /><x-error for="files.2" />
                </div>
            </x-fieldset>

            <div class="border-t-[1.5px] border-ink pt-6">
                <x-submit label="Send quote request" />
                <p class="hint mt-3">We use these details only to price and arrange this job. <a class="text-link" href="{{ route('privacy') }}">Privacy notice</a>.</p>
            </div>
        </form>

        <aside class="space-y-6 lg:sticky lg:top-28 lg:self-start">
            <x-consignment head="What happens next" :barcode="false" :cols="1" :fields="[
                '1 · Routed' => 'To the depot nearest the collection',
                '2 · Priced' => 'By that traffic office',
                '3 · Emailed' => 'With a link to accept online',
            ]" />
            <div class="panel p-5">
                <p class="font-semibold">Collecting today or tomorrow?</p>
                <p class="mt-1 text-ink-2">Phone it through so we can check a vehicle first.</p>
                <a href="{{ tel_href(ga('phone')) }}" class="btn btn-outline mt-4 w-full"><x-icon name="phone" class="h-4 w-4" />{{ format_phone(ga('phone')) }}</a>
            </div>
        </aside>
    </section>
</x-layouts.site>
