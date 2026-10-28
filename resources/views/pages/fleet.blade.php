@php($f = ga('figures'))
<x-layouts.site title="Fleet and warehousing" description="A fleet of 120 vehicles and 250 trailers, 250,000 sq ft of warehousing and a 500 TEU container yard at Darlington: George Allinson (Transport) Ltd.">
    <x-page-header photo="warehouse" position="center 45%" kicker="Fleet and warehousing" title="120 vehicles. 250 trailers. 250,000 sq ft." lead="The figures are the company's own, from its website. Here is what sits behind them." :label="['Vehicles' => $f['vehicles']['value'], 'Trailers' => $f['trailers']['value'], '*Warehousing' => $f['warehousing']['value'].' sq ft']" />

    {{-- Fleet --}}
    <section class="wrap mt-14 grid gap-10 md:mt-20 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)] lg:items-center" aria-labelledby="fleet-title">
        <div>
            <x-lane :n="1">The fleet</x-lane>
            <h2 id="fleet-title" class="display-2 mt-4">Built for everything from chilled to crane-lifted</h2>
            <p class="mt-5 measure text-ink-2">The company describes “a modern fleet of 120 vehicles, 250 trailers” that handles everything from refrigerated goods to 15-metre crane-lifted extrusions. Curtainsiders, refrigerated trailers, flatbeds, bulk trailers and lorry-mounted cranes all appear in its own photographs, in the deep red livery lettered “{{ ga('tagline') }}”.</p>
            <dl class="mt-8 grid grid-cols-2 gap-6">
                <div class="figure"><dt class="sr-only">Vehicles</dt><dd><b class="num">{{ $f['vehicles']['value'] }}</b><span class="mt-2 block text-ink-2">vehicles</span></dd></div>
                <div class="figure"><dt class="sr-only">Trailers</dt><dd><b class="num">{{ $f['trailers']['value'] }}</b><span class="mt-2 block text-ink-2">trailers</span></dd></div>
            </dl>
            <p class="mt-4 text-sm text-ink-3">Source: <a class="text-link" href="{{ ga('sources.home.url') }}" rel="noopener">{{ ga('sources.home.label') }}</a></p>
        </div>
        <div class="grid grid-cols-2 gap-3">
            <x-photo key="own-lineup" alt="Allinson Scania tractor units lined up in the company's red livery" sizes="(min-width: 1024px) 36rem, 100vw" class="col-span-2 aspect-[700/461]" />
            <x-photo key="own-reefer" alt="Allinson tractor units with a refrigerated trailer" sizes="(min-width: 1024px) 18rem, 50vw" class="aspect-[3/2]" />
            <x-photo key="own-coils" alt="Coiled pipe secured on an Allinson flatbed trailer" sizes="(min-width: 1024px) 18rem, 50vw" class="aspect-[3/2]" />
        </div>
    </section>

    {{-- Safety, green and technology --}}
    <section class="mt-20 bg-cab-900 text-white on-dark md:mt-28" aria-labelledby="run-title">
        <div class="tacho overflow-hidden">
            <div class="wrap py-16 md:py-24">
                <x-lane>How the fleet is run</x-lane>
                <h2 id="run-title" class="display-2 mt-4 max-w-3xl">Tracked, limited and loaded properly</h2>
                <div class="mt-10 grid gap-6 md:grid-cols-2">
                    <div class="border border-white/15 p-6">
                        <h3 class="font-display flex items-center gap-2.5 text-2xl font-extrabold uppercase"><x-icon name="leaf" class="h-6 w-6 text-gold" />Lean and green</h3>
                        <ul class="ticked mt-4 space-y-2 text-white/85">@foreach (ga('about.green') as $g)<li>{{ $g }}</li>@endforeach</ul>
                        <p class="mt-4 text-sm text-white/70">Source: <a class="underline underline-offset-2 hover:text-white" href="{{ ga('sources.csr.url') }}" rel="noopener">{{ ga('sources.csr.label') }}</a></p>
                    </div>
                    <div class="border border-white/15 p-6">
                        <h3 class="font-display flex items-center gap-2.5 text-2xl font-extrabold uppercase"><x-icon name="route" class="h-6 w-6 text-gold" />Technology</h3>
                        <ul class="ticked mt-4 space-y-2 text-white/85">
                            <li>Mandata transport management for planning and job records</li>
                            <li>GPS tracking, with geo-fencing that can alert the consignee with a delivery ETA</li>
                            <li>Orders placed by EDI, straight into the system</li>
                            <li>Proof of delivery available online</li>
                        </ul>
                        <p class="mt-4 text-sm text-white/70">Source: <a class="underline underline-offset-2 hover:text-white" href="{{ ga('sources.technology.url') }}" rel="noopener">{{ ga('sources.technology.label') }}</a></p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Warehousing --}}
    <section class="wrap mt-20 grid gap-10 md:mt-28 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)] lg:items-center" aria-labelledby="warehouse-title">
        <div class="grid gap-3 lg:order-2">
            <x-photo key="own-warehouse-hall" alt="Inside an Allinson warehouse hall" sizes="(min-width: 1024px) 36rem, 100vw" class="aspect-[10/3]" />
            <div class="grid grid-cols-2 gap-3">
                <x-photo key="own-warehouse" alt="Bulk bags stored in an Allinson warehouse" sizes="(min-width: 1024px) 18rem, 50vw" class="aspect-[3/2]" />
                <x-photo key="own-forklift" alt="A forklift on the loading ramp at an Allinson warehouse" sizes="(min-width: 1024px) 18rem, 50vw" class="aspect-[3/2]" />
            </div>
        </div>
        <div>
            <x-lane :n="2">Warehousing</x-lane>
            <h2 id="warehouse-title" class="display-2 mt-4">{{ $f['warehousing']['value'] }} sq ft, laid out around your product</h2>
            <p class="mt-5 measure text-ink-2">{{ ga('services.warehousing.body') }}</p>
            <ul class="ticked mt-5 space-y-2">@foreach (ga('services.warehousing.points') as $p)<li>{{ $p }}</li>@endforeach</ul>
            <div class="mt-7 flex flex-wrap gap-3">
                <a href="{{ route('warehousing.create') }}" class="btn btn-primary">Warehousing enquiry<x-icon name="arrow-right" class="nudge h-4 w-4" /></a>
            </div>
            <p class="mt-4 text-sm text-ink-3">Source: <a class="text-link" href="{{ ga('sources.warehousing.url') }}" rel="noopener">{{ ga('sources.warehousing.label') }}</a></p>
        </div>
    </section>

    {{-- Container yard --}}
    <section class="wrap mt-20 md:mt-28" aria-labelledby="yard-title">
        <div class="sheet grid gap-10 p-6 md:p-10 lg:grid-cols-[minmax(0,1.1fr)_minmax(0,1fr)] lg:items-center">
            <div>
                <x-lane :n="3">Container yard</x-lane>
                <h2 id="yard-title" class="display-2 mt-4">Off the quay, into the yard</h2>
                <p class="mt-5 measure text-ink-2">{{ ga('services.containers.body') }}</p>
                <dl class="mt-8 grid gap-6 sm:grid-cols-2">
                    <div class="figure"><dt class="sr-only">Container yard</dt><dd><b class="num">{{ $f['container_yard']['value'] }}<small>sq ft</small></b><span class="mt-2 block text-ink-2">{{ $f['container_yard']['label'] }}</span></dd></div>
                    <div class="figure"><dt class="sr-only">Stacker capacity</dt><dd><b class="num">{{ $f['stacker']['value'] }}<small>kg</small></b><span class="mt-2 block text-ink-2">{{ $f['stacker']['label'] }}</span></dd></div>
                </dl>
                <p class="mt-4 text-sm text-ink-3">Source: <a class="text-link" href="{{ ga('sources.containers.url') }}" rel="noopener">{{ ga('sources.containers.label') }}</a></p>
            </div>
            <x-photo key="own-container" alt="A container stacker loading a container onto an Allinson skeletal trailer" sizes="(min-width: 1024px) 34rem, 100vw" class="aspect-[3/2]" />
        </div>
    </section>
</x-layouts.site>
