@php($opts = fn (string $enum) => collect($enum::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all())
<x-layouts.site title="Warehousing enquiry" description="Ask George Allinson (Transport) Ltd about warehousing: pallet spaces, storage period, goods type and handling, from storage to order fulfilment.">
    <x-page-header photo="warehouse" position="center 45%" kicker="Warehousing" title="Ask about space" lead="Tell us how many pallet spaces, for how long and what the goods are. Someone from the warehouse team will call you back." :label="['Space' => ga('figures.warehousing.value').' sq ft', 'Stock' => 'Online', '*Contents' => 'Warehousing enquiry']" />

    <section class="wrap mt-12 grid gap-12 md:mt-16 lg:grid-cols-[minmax(0,1fr)_20rem]">
        <form method="POST" action="{{ route('warehousing.store') }}" class="min-w-0 space-y-10" x-data="sendForm" @submit="submit">
            @csrf
            <x-form-errors />
            <x-honeypot />
            <x-fieldset n="1" title="What you need to store">
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-field name="pallet_spaces" label="Pallet spaces" type="number" :required="true" min="1" max="20000" inputmode="numeric" hint="Your best estimate; a range in the notes is fine." />
                    <x-field name="start_on" label="From" type="date" :optional="true" :min="today()->format('Y-m-d')" />
                </div>
                <x-radios name="duration" label="For how long?" :options="$opts(\App\Enums\StorageDuration::class)" :required="true" cols="sm:grid-cols-3" />
                <x-select name="goods_type" label="Type of goods" :options="$opts(\App\Enums\GoodsType::class)" :required="true" />
                <x-field name="goods_description" label="Describe the goods" type="textarea" :required="true" maxlength="2000" rows="3" hint="Product, packaging, pallet size and anything temperature or stacking sensitive." />
                <x-checks name="handling" label="What should we do with it?" :options="\App\Models\WarehousingEnquiry::HANDLING" :optional="true" />
                <x-flag name="hazardous" label="Some goods are hazardous" hint="Classified under ADR or COSHH" />
                <x-select name="depot" label="Preferred location" :options="\App\Support\Depots::options()" :optional="true" placeholder="No preference" />
            </x-fieldset>
            <x-fieldset n="2" title="Your details">
                <div class="grid gap-5 sm:grid-cols-2">
                    <x-field name="company" label="Company" :required="true" maxlength="160" autocomplete="organization" />
                    <x-field name="contact_name" label="Your name" :required="true" maxlength="120" autocomplete="name" />
                    <x-field name="email" label="Email" type="email" :required="true" maxlength="255" autocomplete="email" />
                    <x-field name="phone" label="Phone" type="tel" :required="true" maxlength="30" autocomplete="tel" inputmode="tel" />
                </div>
                <x-field name="message" label="Anything else?" type="textarea" :optional="true" maxlength="3000" rows="4" />
            </x-fieldset>
            <div class="border-t-[1.5px] border-ink pt-6">
                <x-submit label="Send enquiry" />
                <p class="hint mt-3">We use these details only to reply about warehousing. <a class="text-link" href="{{ route('privacy') }}">Privacy notice</a>.</p>
            </div>
        </form>
        <aside class="space-y-6 lg:sticky lg:top-28 lg:self-start">
            <x-photo key="own-warehouse" alt="Bulk bags stored in an Allinson warehouse" sizes="20rem" class="aspect-[3/2]" />
            <ul class="ticked space-y-2 text-ink-2">@foreach (ga('services.warehousing.points') as $p)<li>{{ $p }}</li>@endforeach</ul>
            <p class="text-sm text-ink-3">Source: <a class="text-link" href="{{ ga('sources.warehousing.url') }}" rel="noopener">{{ ga('sources.warehousing.label') }}</a></p>
        </aside>
    </section>
</x-layouts.site>
