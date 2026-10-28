@php($f = ga('figures'))
<x-layouts.site title="About us" description="George Allinson (Transport) Ltd: established in 1955, family run and owned, combining traditional values of customer service, integrity and excellence with a modern approach to logistics.">
    <x-page-header photo="a40" position="60% 55%" kicker="About us" title="Family run and owned since 1955" :lead="ga('about.intro')" :label="['Established' => '1955', 'Incorporated' => '1969', '*Company no.' => ga('company_number')]" />

    <section class="wrap mt-14 md:mt-20" aria-labelledby="figures-title">
        <x-lane>The company at a glance</x-lane>
        <h2 id="figures-title" class="display-3 mt-4">As published by the company</h2>
        <dl class="mt-8 grid grid-cols-2 gap-x-6 gap-y-10 lg:grid-cols-4">
            @foreach (['founded', 'vehicles', 'trailers', 'warehousing'] as $key)
                <div class="figure"><dt class="sr-only">{{ $f[$key]['label'] }}</dt><dd><b class="num">{{ $f[$key]['value'] }}@isset($f[$key]['unit'])<small>{{ $f[$key]['unit'] }}</small>@endisset</b><span class="mt-2 block text-ink-2">{{ $f[$key]['label'] }}</span></dd></div>
            @endforeach
        </dl>
        <p class="mt-6 text-sm text-ink-3">Sources: <a class="text-link" href="{{ ga('sources.about.url') }}" rel="noopener">{{ ga('sources.about.label') }}</a>; <a class="text-link" href="{{ ga('sources.home.url') }}" rel="noopener">{{ ga('sources.home.label') }}</a>. Incorporated as {{ ga('legal_name') }} on {{ ga('incorporated') }} (<a class="text-link" href="{{ ga('sources.companies_house.url') }}" rel="noopener">Companies House</a>).</p>
    </section>

    <section class="wrap mt-20 grid gap-10 md:mt-28 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)] lg:items-start" aria-labelledby="values-title">
        <div class="lg:sticky lg:top-28">
            <x-lane>What matters to us</x-lane>
            <h2 id="values-title" class="display-2 mt-4">Traditional values, modern logistics</h2>
            <x-photo key="own-lineup" alt="Allinson tractor units lined up in the company's red livery" sizes="(min-width: 1024px) 36rem, 100vw" class="mt-8 aspect-[700/461]" />
        </div>
        <ol class="space-y-5">
            @foreach (ga('about.values') as $i => [$title, $body])
                <li class="sheet p-6">
                    <h3 class="font-display flex items-center gap-3 text-2xl font-extrabold uppercase leading-none"><x-junction :n="$i + 1" />{{ $title }}</h3>
                    <p class="mt-3 text-ink-2">{{ $body }}</p>
                </li>
            @endforeach
            <li class="text-sm text-ink-3">Source: <a class="text-link" href="{{ ga('sources.about.url') }}" rel="noopener">{{ ga('sources.about.label') }}</a></li>
        </ol>
    </section>

    <section class="mt-20 bg-cab-900 text-white on-dark md:mt-28" aria-labelledby="green-title">
        <div class="tacho overflow-hidden">
            <div class="wrap grid gap-10 py-16 md:py-24 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)] lg:items-center">
                <div>
                    <x-lane>Corporate responsibility</x-lane>
                    <h2 id="green-title" class="display-2 mt-4">Lean is green</h2>
                    <p class="mt-5 max-w-xl text-white/80">Efficient supply-chain management saves customers money, and the same efficiency cuts fuel and emissions.</p>
                </div>
                <ul class="ticked space-y-3 text-white/85">
                    @foreach (ga('about.green') as $g)<li>{{ $g }}</li>@endforeach
                    <li class="!pl-0 text-sm text-white/70 before:hidden">Source: <a class="underline underline-offset-2 hover:text-white" href="{{ ga('sources.csr.url') }}" rel="noopener">{{ ga('sources.csr.label') }}</a></li>
                </ul>
            </div>
        </div>
    </section>

    <section class="wrap mt-20 md:mt-28">
        <div class="grid gap-6 md:grid-cols-2">
            <a href="{{ route('services') }}" class="sheet group block p-6 md:p-8">
                <x-lane>Next</x-lane>
                <p class="font-display mt-3 text-3xl font-extrabold uppercase">Our services</p>
                <span class="mt-3 inline-flex items-center gap-1.5 font-semibold text-link">Haulage, containers and warehousing<x-icon name="arrow-right" class="h-4 w-4" /></span>
            </a>
            <a href="{{ route('careers.index') }}" class="sheet group block p-6 md:p-8">
                <x-lane>Join us</x-lane>
                <p class="font-display mt-3 text-3xl font-extrabold uppercase">Careers</p>
                <span class="mt-3 inline-flex items-center gap-1.5 font-semibold text-link">Driving, warehouse and office roles<x-icon name="arrow-right" class="h-4 w-4" /></span>
            </a>
        </div>
    </section>
</x-layouts.site>
