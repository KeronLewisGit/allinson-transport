<x-layouts.site title="Depots" description="George Allinson (Transport) Ltd: head office, warehousing and container yard at Faverdale, Darlington, with facilities in Yorkshire, Gloucestershire, Cambridgeshire and Liverpool.">
    <x-page-header photo="own-depot-night" position="center 55%" kicker="Depots" title="Darlington and four regions" lead="Head office is at Faverdale, Darlington. The company's own site lists facilities in Yorkshire, Gloucestershire, Cambridgeshire and Liverpool as well." :label="['Head office' => 'Darlington', 'Postcode' => ga('address.postcode'), '*Regions' => 'Durham · Yorks · Glos · Cambs · Merseyside']" />

    <section class="wrap mt-14 md:mt-20" aria-labelledby="hq-title">
        <div class="grid gap-10 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)] lg:items-center">
            <div>
                <x-lane :n="1">Head office</x-lane>
                <h2 id="hq-title" class="display-2 mt-4">Faverdale, Darlington</h2>
                <address class="mt-5 text-lg not-italic leading-relaxed">{{ ga('name') }}<br>{{ ga('address.street') }}<br>{{ ga('address.city') }}, {{ ga('address.county') }}<br>{{ ga('address.postcode') }}</address>
                <p class="mt-4 measure text-ink-2">{{ ga('depots.darlington.note') }}</p>
                <div class="mt-6 flex flex-wrap gap-3">
                    <a href="{{ tel_href(ga('phone')) }}" class="btn btn-primary"><x-icon name="phone" class="h-4 w-4" />{{ format_phone(ga('phone')) }}</a>
                    <a href="{{ ga('map_url') }}" class="btn btn-outline" rel="noopener" target="_blank">Get directions<x-icon name="external" class="h-4 w-4" /><span class="sr-only">(opens Google Maps)</span></a>
                </div>
            </div>
            <x-photo key="own-loading" alt="Loading an Allinson curtainsider by forklift" sizes="(min-width: 1024px) 36rem, 100vw" class="aspect-[3/2]" />
        </div>
    </section>

    <section class="wrap mt-20 md:mt-28" aria-labelledby="regions-title">
        <x-lane :n="2">Regional facilities</x-lane>
        <h2 id="regions-title" class="display-2 mt-4">Where else we are</h2>
        <p class="mt-4 measure text-ink-2">Quote requests are sent to the depot nearest the collection, so the people pricing the job know the roads. Call head office for a depot's direct line.</p>
        <ul class="mt-10 grid gap-5 md:grid-cols-2">
            @foreach (ga('depots') as $key => $d)
                @continue($key === 'darlington')
                <li class="sheet p-6">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h3 class="font-display text-3xl font-extrabold uppercase leading-none">{{ $d['name'] }}</h3>
                            <p class="mt-1.5 text-ink-3">{{ $d['region'] }}</p>
                        </div>
                        @if (($jobCounts[$key] ?? 0) > 0)
                            <a href="{{ route('careers.index', ['depot' => $key]) }}" class="chip relative z-10" data-tone="good"><x-icon name="id-card" />{{ $jobCounts[$key] }} {{ str('vacancy')->plural($jobCounts[$key]) }}</a>
                        @endif
                    </div>
                    <p class="mt-4 text-ink-2">{{ $d['address'] ?? 'Listed on the company\'s website. Call head office on '.format_phone(ga('phone')).' for directions.' }}</p>
                </li>
            @endforeach
        </ul>
        <p class="mt-6 text-sm text-ink-3">Source: <a class="text-link" href="{{ ga('sources.home.url') }}" rel="noopener">{{ ga('sources.home.label') }}</a>; <a class="text-link" href="{{ ga('sources.uk.url') }}" rel="noopener">{{ ga('sources.uk.label') }}</a></p>
    </section>

    <section class="wrap mt-20 md:mt-28">
        <div class="bg-cab-900 p-6 text-white on-dark md:p-10">
            <div class="flex flex-col gap-6 md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 class="display-3">Collecting near one of these?</h2>
                    <p class="mt-2 text-white/80">Give us the postcodes and we route the request to the right traffic office.</p>
                </div>
                <a href="{{ route('quotes.create') }}" class="btn btn-red flex-none">Request a quote<x-icon name="arrow-right" class="nudge h-4 w-4" /></a>
            </div>
        </div>
    </section>
</x-layouts.site>
