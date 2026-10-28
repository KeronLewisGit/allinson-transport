@php
    $figures = ga('figures');
    $source = ga('sources.home');
@endphp
<x-layouts.site>
    {{--
        Home hero, after the company's own header: the night-time photo of the Faverdale offices
        and a lorry, the GA badge, the company name in condensed caps and "We care for your cargo".
        Motion (photo push-in, badge outline drawing, words rising, label dropping) is added by
        CSS and JS; without either, everything is simply there.
    --}}
    <section class="hero-home relative isolate overflow-hidden bg-cab-900 text-white on-dark">
        <div class="hero-photo absolute inset-0 -z-10">
            <x-photo key="own-depot-night" alt="" :eager="true" sizes="100vw" class="h-full w-full" position="62% 18%" />
            <div class="hero-scrim hero-scrim-night absolute inset-0"></div>
        </div>
        <div class="wrap grid gap-10 pb-16 pt-[min(52vw,15rem)] md:pt-20 lg:grid-cols-[minmax(0,1fr)_19rem] lg:items-end lg:gap-14 lg:pb-24 lg:pt-24">
            <div class="min-w-0">
                <x-badge :draw="true" class="h-16 w-[6.9rem] md:h-24 md:w-[10.3rem]" />
                <p class="font-display mt-5 text-[1.3rem] font-bold uppercase tracking-[0.02em] text-white md:text-[1.75rem]" data-enter style="--d: 0.35s">George Allinson (Transport) Ltd</p>
                <h1 class="display-1 mt-2 max-w-4xl" data-words>{{ ga('tagline') }}</h1>
                <p class="lead mt-6 text-white/85" data-enter style="--d: 1.25s">Road haulage, container handling and {{ $figures['warehousing']['value'] }} sq ft of warehousing, from a single pallet to 15-metre crane-lifted extrusions, run from Faverdale with depots in Yorkshire, Gloucestershire, Cambridgeshire and Liverpool.</p>
                <div class="mt-8 flex flex-wrap gap-3" data-enter style="--d: 1.45s">
                    <a href="{{ route('quotes.create') }}" class="btn btn-red">Request a haulage quote<x-icon name="arrow-right" class="nudge h-4 w-4" /></a>
                    <a href="{{ route('warehousing.create') }}" class="btn btn-on-dark">Warehousing enquiry</a>
                    <a href="{{ tel_href(ga('phone')) }}" class="btn btn-on-dark"><x-icon name="phone" class="h-4 w-4" />{{ format_phone(ga('phone')) }}</a>
                </div>
            </div>
            <x-consignment class="hidden rotate-[1.5deg] lg:grid" data-enter head="Allinson Transport" ref="EST. 1955" :fields="[
                'Vehicles' => $figures['vehicles']['value'],
                'Trailers' => $figures['trailers']['value'],
                '*Warehousing' => $figures['warehousing']['value'].' sq ft',
                '*Depots' => 'Durham · Yorks · Glos · Cambs · L\'pool',
            ]" />
        </div>
        <p class="wrap pb-4 text-right text-xs text-white/70">Photo: the company's own, from its current website</p>
    </section>
    <x-marquee :items="['~'.ga('tagline'), 'Haulage', 'Storage', 'Containers', 'Hiab', 'Pallets to full loads', 'Warehousing', 'Logistics', 'Since '.ga('founded')]" />

    {{-- The firm's published figures, quoted exactly, with the source. --}}
    <section class="wrap mt-16 md:mt-24" aria-labelledby="figures-title">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <x-lane>In the company's own words</x-lane>
                <h2 id="figures-title" class="display-3 mt-4 max-w-2xl">“A modern fleet of 120 vehicles, 250 trailers, 250,000 sq ft of flexible warehousing”</h2>
            </div>
            <a href="{{ route('fleet') }}" class="btn btn-outline btn-sm">Fleet and warehousing<x-icon name="arrow-right" class="nudge h-4 w-4" /></a>
        </div>
        <dl class="mt-10 grid grid-cols-2 gap-x-6 gap-y-10 lg:grid-cols-4">
            @foreach (['vehicles', 'trailers', 'warehousing', 'founded'] as $i => $key)
                @php($f = $figures[$key])
                <div class="figure" data-reveal style="--reveal-delay: {{ $i * 80 }}ms">
                    <dt class="sr-only">{{ $f['label'] }}</dt>
                    <dd>
                        <b class="num">
                            @if ($key === 'founded')
                                {{ $f['value'] }}
                            @else
                                <span class="count" data-count>{{ $f['value'] }}</span>
                            @endif
                            @isset($f['unit'])<small>{{ $f['unit'] }}</small>@endisset
                        </b>
                        <span class="mt-2 block text-ink-2">{{ $f['label'] }}</span>
                    </dd>
                </div>
            @endforeach
        </dl>
        <p class="mt-6 text-sm text-ink-3">Source: <a class="text-link" href="{{ $source['url'] }}" rel="noopener">{{ $source['label'] }}</a> and “About Us”. Figures as the company publishes them.</p>
    </section>

    {{-- Services --}}
    <section class="mt-20 bg-surface-2 py-16 md:mt-28 md:py-24" aria-labelledby="services-title">
        <div class="wrap">
            <x-lane>What we move</x-lane>
            <h2 id="services-title" class="display-2 mt-4 max-w-3xl">From one pallet to a crane-lifted extrusion</h2>
            <div class="mt-10 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                @foreach (ga('services') as $key => $s)
                    <article class="card-link group flex flex-col bg-surface shadow-sm" data-reveal style="--reveal-delay: {{ ($loop->index % 3) * 70 }}ms">
                        <div class="relative"><span class="tab-label">{{ $s['tab'] }}</span><x-photo :key="$s['photo']" :alt="$s['title']" sizes="(min-width: 1024px) 26rem, (min-width: 768px) 50vw, 100vw" class="card-img aspect-[3/2]" /></div>
                        <div class="flex flex-1 flex-col border-t-4 border-livery p-6">
                            <h3 class="font-display flex items-center gap-2.5 text-2xl font-extrabold uppercase leading-none"><x-icon :name="$s['icon']" class="h-6 w-6 flex-none text-primary" />{{ $s['title'] }}</h3>
                            <p class="mt-3 flex-1 text-ink-2">{{ $s['summary'] }}</p>
                            <a href="{{ route('services') }}#{{ $key }}" class="stretch mt-5 inline-flex min-h-11 items-center gap-1.5 font-semibold text-link">Read more<span class="sr-only"> about {{ $s['title'] }}</span><x-icon name="arrow-right" class="h-4 w-4" /></a>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Three ways in --}}
    <section class="wrap mt-20 md:mt-28" aria-labelledby="start-title">
        <x-lane>Start here</x-lane>
        <h2 id="start-title" class="display-2 mt-4">Three ways to get moving</h2>
        <ol class="mt-10 grid gap-6 lg:grid-cols-3">
            @foreach ([
                ['Request a quote', 'Haulage quote', 'Full load, part load, pallets, Hiab or abnormal: give us the postcodes, dates and weight and the nearest depot prices it.', 'quotes.create', 'Request a quote', 'calculator', null],
                ['Ask about warehousing', 'Warehousing', 'Tell us how many pallet spaces, for how long and what the goods are. We will call you back about space and handling.', 'warehousing.create', 'Warehousing enquiry', 'warehouse', null],
                ['Take the Allinson challenge', 'Free consultancy', 'Share six months of your order profile in confidence and our managers will compare it with what is possible.', 'contact', 'Ask for a benchmark', 'chart', '“Show us your order book and we\'ll show you logistics savings.”'],
            ] as $i => [$title, $head, $body, $route, $cta, $icon, $quote])
                <li class="box flex flex-col" data-reveal style="--reveal-delay: {{ $i * 90 }}ms">
                    <div class="boxhead"><span>{{ $head }}</span><x-junction :n="$i + 1" /></div>
                    <div class="flex flex-1 flex-col p-6 md:p-7">
                        <x-icon :name="$icon" class="h-7 w-7 text-primary" />
                        <h3 class="font-display mt-4 text-3xl font-bold uppercase leading-none">{{ $title }}</h3>
                        @if ($quote)<p class="mt-3 font-bold text-ink">{{ $quote }}</p>@endif
                        <p class="mt-3 flex-1 text-ink-2">{{ $body }}</p>
                        <a href="{{ route($route, $route === 'contact' ? ['topic' => 'benchmark'] : []) }}" class="btn btn-primary mt-6 self-start">{{ $cta }}<x-icon name="arrow-right" class="nudge h-4 w-4" /></a>
                    </div>
                </li>
            @endforeach
        </ol>
    </section>

    {{-- Depots --}}
    <section class="mt-20 bg-cab-900 text-white on-dark md:mt-28" aria-labelledby="depots-title">
        <div class="tacho overflow-hidden">
            <div class="wrap grid gap-10 py-16 md:py-24 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.1fr)] lg:items-center">
                <div>
                    <x-lane>Five regions, one family firm</x-lane>
                    <h2 id="depots-title" class="display-2 mt-4">Depots across the country</h2>
                    <p class="mt-5 max-w-xl text-white/80">Head office, warehousing and the container yard are at Faverdale, Darlington, close to Teesport and the Port of Tyne. The company's site lists facilities in Yorkshire, Gloucestershire, Cambridgeshire and Liverpool too, so a quote request goes straight to the depot nearest the collection.</p>
                    <a href="{{ route('depots') }}" class="btn btn-light mt-8">See the depots<x-icon name="arrow-right" class="nudge h-4 w-4" /></a>
                </div>
                <div class="grid items-center gap-6 sm:grid-cols-[minmax(0,1fr)_14rem]">
                    <x-depot-map class="mx-auto max-w-[24rem] text-white" />
                    <ul class="grid gap-2 text-sm">
                        @foreach (ga('depots') as $key => $d)
                            <li @class(['border border-white/15 bg-white/[0.04] px-4 py-3', 'border-l-4 !border-l-livery' => $key === 'darlington'])>
                                <p class="font-display text-xl font-bold uppercase leading-none">{{ $d['name'] }}</p>
                                @if ($key === 'darlington' || $d['region'] !== $d['name'])<p class="mt-1 text-white/75">{{ $key === 'darlington' ? 'Head office, Faverdale DL3 0PH' : $d['region'] }}</p>@endif
                            </li>
                        @endforeach
                        <li class="px-1 pt-1 text-white/70">Regional markers show the region, not a site.</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    {{-- Careers --}}
    <section class="wrap mt-20 grid gap-10 md:mt-28 lg:grid-cols-[minmax(0,1.05fr)_minmax(0,1fr)] lg:items-center" aria-labelledby="careers-title">
        <div class="relative">
            <x-photo key="own-hiab" alt="An Allinson Hiab operator working the lorry-mounted crane controls" sizes="(min-width: 1024px) 38rem, 100vw" class="aspect-[3/2]" />
            <div class="chevrons thin absolute inset-x-0 bottom-0" aria-hidden="true"></div>
        </div>
        <div>
            <x-lane>Careers</x-lane>
            <h2 id="careers-title" class="display-2 mt-4">Drive for a family firm</h2>
            <p class="mt-5 measure text-ink-2">{{ ga('careers.intro') }}</p>
            <p class="mt-5 flex flex-wrap items-center gap-3"><span class="word-doc" data-strike><x-icon name="file-word" class="h-4 w-4" />Employment-Application-Form-2012-Website.doc</span><span class="inline-flex items-center gap-1.5 font-bold text-ink"><x-icon name="smartphone" class="h-5 w-5 text-primary" />Five minutes on your phone</span></p>
            <p class="mt-4 measure text-ink-2">Apply online in about five minutes: no Word form to download, print or post.</p>
            <div class="mt-7 flex flex-wrap gap-3">
                <a href="{{ route('careers.index') }}" class="btn btn-primary">{{ $openJobs > 0 ? 'See '.$openJobs.' open '.str('vacancy')->plural($openJobs) : 'Careers' }}<x-icon name="arrow-right" class="nudge h-4 w-4" /></a>
                <a href="{{ route('careers.apply') }}" class="btn btn-outline">Apply now</a>
            </div>
        </div>
    </section>

    {{-- Sectors --}}
    <section class="wrap mt-20 md:mt-28" aria-labelledby="sectors-title">
        <div class="deck border-y-[3px] border-ink py-10 text-ink md:py-14">
            <x-lane>Particular expertise</x-lane>
            <h2 id="sectors-title" class="display-3 mt-4">Sectors we know well</h2>
            <ul class="mt-6 flex flex-wrap gap-2.5">
                @foreach (ga('sectors') as $sector)
                    <li class="plate text-base">{{ $sector }}</li>
                @endforeach
            </ul>
            <p class="mt-5 text-sm text-ink-3">Source: <a class="text-link" href="{{ ga('sources.why.url') }}" rel="noopener">{{ ga('sources.why.label') }}</a></p>
        </div>
    </section>
</x-layouts.site>
