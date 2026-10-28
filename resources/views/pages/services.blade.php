<x-layouts.site title="Services" description="Regional consolidation, UK trunking and distribution, Hiab and abnormal loads, container handling, warehousing and logistics consultancy from George Allinson (Transport) Ltd, Darlington.">
    <x-page-header photo="a1m" position="center 60%" kicker="Services" title="Haulage, containers and warehousing" lead="Everything from a single pallet to 15-metre crane-lifted extrusions, moved, stored or handled by a family firm that has done it since 1955.">
        <nav class="tag-row mt-7" aria-label="Services on this page">
            @foreach (ga('services') as $key => $s)
                <a href="#{{ $key }}" class="tag">{{ $s['title'] }}</a>
            @endforeach
        </nav>
    </x-page-header>

    <div class="wrap mt-14 space-y-16 md:mt-20 md:space-y-24">
        @foreach (ga('services') as $key => $s)
            @php($src = ga('sources.'.$s['source']))
            <section id="{{ $key }}" class="grid gap-8 lg:grid-cols-2 lg:items-center lg:gap-14" aria-labelledby="{{ $key }}-title">
                <div @class(['relative', 'lg:order-2' => $loop->even])>
                    <x-photo :key="$s['photo']" :alt="$s['title'].', Allinson Transport'" sizes="(min-width: 1024px) 36rem, 100vw" class="aspect-[3/2]" />
                    <div class="chevrons thin absolute inset-x-0 bottom-0" aria-hidden="true"></div>
                </div>
                <div>
                    <x-lane :n="$loop->iteration">{{ str($key)->headline() }}</x-lane>
                    <h2 id="{{ $key }}-title" class="display-3 mt-4">{{ $s['title'] }}</h2>
                    <p class="mt-4 measure text-ink-2">{{ $s['body'] }}</p>
                    <ul class="ticked mt-5 space-y-2">
                        @foreach ($s['points'] as $p)<li>{{ $p }}</li>@endforeach
                    </ul>
                    <div class="mt-7 flex flex-wrap items-center gap-3">
                        @if ($key === 'warehousing')
                            <a href="{{ route('warehousing.create') }}" class="btn btn-primary">Warehousing enquiry<x-icon name="arrow-right" class="nudge h-4 w-4" /></a>
                        @elseif ($key === 'consultancy')
                            <a href="{{ route('contact', ['topic' => 'benchmark']) }}" class="btn btn-primary">Ask for a free benchmark<x-icon name="arrow-right" class="nudge h-4 w-4" /></a>
                        @elseif ($key === 'containers')
                            <a href="{{ route('contact') }}" class="btn btn-primary">Talk to us about containers<x-icon name="arrow-right" class="nudge h-4 w-4" /></a>
                        @else
                            <a href="{{ route('quotes.create', ['service' => match ($key) { 'specialist' => 'hiab', 'regional' => 'pallets', default => 'full_load' }]) }}" class="btn btn-primary">Request a quote<x-icon name="arrow-right" class="nudge h-4 w-4" /></a>
                        @endif
                    </div>
                    <p class="mt-4 text-sm text-ink-3">Source: <a class="text-link" href="{{ $src['url'] }}" rel="noopener">{{ $src['label'] }}</a></p>
                </div>
            </section>
        @endforeach
    </div>

    <section class="wrap mt-20 md:mt-28" aria-labelledby="sectors-title">
        <div class="sheet grid gap-8 p-6 md:grid-cols-[minmax(0,1fr)_minmax(0,1.4fr)] md:p-10">
            <div>
                <x-lane>Sectors</x-lane>
                <h2 id="sectors-title" class="display-3 mt-4">Particular expertise in</h2>
            </div>
            <ul class="grid gap-x-8 gap-y-2 sm:grid-cols-2">
                @foreach (ga('sectors') as $sector)<li class="flex items-center gap-3 border-b border-line py-2.5"><x-icon name="check" class="h-4 w-4 flex-none text-primary" />{{ $sector }}</li>@endforeach
            </ul>
        </div>
    </section>
</x-layouts.site>
