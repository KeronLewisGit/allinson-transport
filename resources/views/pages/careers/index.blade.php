<x-layouts.site title="Careers" description="Driving, warehouse and transport office jobs at George Allinson (Transport) Ltd. C+E and Hiab drivers welcome. Apply online in a few minutes.">
    <x-page-header photo="tacho" position="center 50%" kicker="Careers" title="Drive for a family firm" :lead="ga('careers.intro')" :label="['Licence' => 'C+E', 'Also' => 'Hiab', '*Apply' => 'Online, about 5 minutes']">
        <div class="mt-7 flex flex-wrap gap-3">
            <a href="{{ route('careers.apply') }}" class="btn btn-red">Apply now<x-icon name="arrow-right" class="nudge h-4 w-4" /></a>
            @if ($jobs->isNotEmpty())<a href="#vacancies" class="btn btn-on-dark">{{ $jobs->count() }} open {{ str('vacancy')->plural($jobs->count()) }}</a>@endif
        </div>
    </x-page-header>

    <section class="wrap mt-14 md:mt-20" aria-labelledby="roles-title">
        <x-lane>Roles</x-lane>
        <h2 id="roles-title" class="display-2 mt-4">Where you could fit</h2>
        <ul class="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
            @foreach (\App\Enums\JobRole::cases() as $role)
                <li class="sheet flex flex-col p-6">
                    <x-icon :name="$role->icon()" class="h-8 w-8 text-primary" />
                    <h3 class="font-display mt-4 text-2xl font-extrabold uppercase leading-none">{{ $role->label() }}</h3>
                    <p class="mt-3 flex-1 text-ink-2">{{ $role->blurb() }}</p>
                    <a href="{{ route('careers.apply', ['role' => $role->value]) }}" class="mt-5 inline-flex min-h-11 items-center gap-1.5 font-semibold text-link">Apply<span class="sr-only"> as {{ $role->label() }}</span><x-icon name="arrow-right" class="h-4 w-4" /></a>
                </li>
            @endforeach
        </ul>
    </section>

    <section id="vacancies" class="wrap mt-20 md:mt-28" aria-labelledby="vacancies-title">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <x-lane>Vacancies</x-lane>
                <h2 id="vacancies-title" class="display-2 mt-4">Open now</h2>
            </div>
        </div>
        <nav class="tag-row mt-6" aria-label="Filter by depot">
            <a href="{{ route('careers.index') }}#vacancies" class="tag" @if(! $depot) aria-current="true" @endif>All depots <span class="count">{{ $depotCounts->sum() }}</span></a>
            @foreach (ga('depots') as $key => $d)
                <a href="{{ route('careers.index', ['depot' => $key]) }}#vacancies" class="tag" @if($depot === $key) aria-current="true" @endif>{{ $d['name'] }} <span class="count">{{ $depotCounts[$key] ?? 0 }}</span></a>
            @endforeach
        </nav>
        @if ($jobs->isEmpty())
            <div class="panel mt-6 p-6">
                <p class="font-semibold">No advertised vacancies{{ $depot ? ' at '.\App\Support\Depots::name($depot) : '' }} right now.</p>
                <p class="mt-1 text-ink-2">We always welcome applications from drivers and logistics people. Send one in and we will keep it on file.</p>
                <a href="{{ route('careers.apply') }}" class="btn btn-primary mt-4">Send an application</a>
            </div>
        @else
            <ul class="mt-6 divide-y divide-line border-y border-line">
                @foreach ($jobs as $job)
                    <li class="card-link relative grid gap-3 py-5 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-center">
                        <div>
                            <h3 class="text-xl font-bold"><a class="stretch hover:text-link" href="{{ route('careers.show', $job) }}">{{ $job->title }}</a></h3>
                            <p class="mt-1 flex flex-wrap gap-x-4 gap-y-1 text-ink-2">
                                <span class="inline-flex items-center gap-1.5"><x-icon name="map-pin" class="h-4 w-4" />{{ $job->depotName() }}</span>
                                <span>{{ $job->employment_type->label() }}</span>
                                @if ($job->pay)<span>{{ $job->pay }}</span>@endif
                                @if ($job->is_demo)<span class="demo-badge">Sample</span>@endif
                            </p>
                        </div>
                        <span class="inline-flex items-center gap-1.5 font-semibold text-link">View and apply<x-icon name="arrow-right" class="h-4 w-4" /></span>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    {{--
        How applying works, told as a road: the red line fills and the lorry drives down the
        steps as the page scrolls (with reduced motion or no JavaScript the road is simply full).
    --}}
    <section class="wrap mt-20 grid gap-12 md:mt-28 lg:grid-cols-[minmax(0,0.9fr)_minmax(0,1.1fr)]" aria-labelledby="how-title">
        <div class="lg:sticky lg:top-24 lg:self-start">
            <x-lane>How to apply</x-lane>
            <h2 id="how-title" class="display-2 mt-4">From a 2012 Word form to five minutes on your phone</h2>
            <p class="mt-5 measure text-ink-2">Until now, applying meant downloading a Word document, printing it, filling it in and sending it back. Now it is one form on a phone, and HR does the chasing.</p>
            <div class="mt-6 grid grid-cols-2 gap-3">
                <x-photo key="own-loading-2" alt="Loading an Allinson curtainsider at the container yard" sizes="(min-width: 1024px) 16rem, 50vw" class="aspect-[3/2]" />
                <x-photo key="own-forklift" alt="A forklift on the loading ramp at an Allinson warehouse" sizes="(min-width: 1024px) 16rem, 50vw" class="aspect-[3/2]" />
            </div>
        </div>
        <div class="story" data-story>
            <span class="story-road" aria-hidden="true"><span class="story-lorry"><x-icon name="truck" class="h-4 w-4" /></span></span>
            <ol class="grid gap-10">
            @foreach ([
                ['Before', 'The old way', 'Download the Word form, print it, fill it in by hand and post or email it back.', 'word'],
                ['Step 1', 'Apply on your phone', 'Role, depot, licence categories, points, Driver CPC and tacho card expiry dates. About five minutes, no CV needed.', null],
                ['Step 2', 'HR calls you back', 'Every application lands in one list at HR, so nobody is missed and waiting applicants are flagged.', null],
                ['Step 3', 'Upload your licence and cards', 'For driving roles, HR emails a secure link to send photos of your licence, CPC card, tacho card and right-to-work documents.', null],
                ['Step 4', 'Assessment drive', 'A drive at the depot with one of the team before an offer is made.', null],
                ['Step 5', 'Welcome aboard', 'Your card dates go into the compliance register, so HR is reminded well before your CPC, tacho card or licence runs out.', null],
            ] as [$tag, $title, $body, $extra])
                <li class="story-step">
                    <span class="dot" aria-hidden="true"></span>
                    <p class="kicker text-primary">{{ $tag }}</p>
                    <h3 class="font-display mt-1 text-2xl font-bold uppercase leading-none md:text-3xl">{{ $title }}</h3>
                    @if ($extra === 'word')<p class="mt-3"><span class="word-doc" data-strike><x-icon name="file-word" class="h-4 w-4" />Employment-Application-Form-2012-Website.doc</span></p>@endif
                    <p class="mt-2 measure text-ink-2">{{ $body }}</p>
                    @if ($loop->index === 1)<a href="{{ route('careers.apply') }}" class="btn btn-primary btn-sm mt-4">Apply now<x-icon name="arrow-right" class="nudge h-4 w-4" /></a>@endif
                </li>
            @endforeach
            </ol>
        </div>
    </section>
    <p class="wrap mt-8 text-ink-2">Training goes beyond compliance: the company's Driver CPC programme started in 2010.</p>
    <p class="wrap mt-2 text-sm text-ink-3">Sources: <a class="text-link" href="{{ ga('sources.careers.url') }}" rel="noopener">{{ ga('sources.careers.label') }}</a>; <a class="text-link" href="{{ ga('sources.about.url') }}" rel="noopener">{{ ga('sources.about.label') }}</a></p>
</x-layouts.site>
