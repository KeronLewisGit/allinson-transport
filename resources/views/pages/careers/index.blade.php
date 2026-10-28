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

    <section class="wrap mt-20 grid gap-10 md:mt-28 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)] lg:items-center">
        <div class="grid grid-cols-2 gap-3">
            <x-photo key="own-loading-2" alt="Loading an Allinson curtainsider at the container yard" sizes="(min-width: 1024px) 18rem, 50vw" class="aspect-[3/2]" />
            <x-photo key="own-forklift" alt="A forklift on the loading ramp at an Allinson warehouse" sizes="(min-width: 1024px) 18rem, 50vw" class="aspect-[3/2]" />
        </div>
        <div>
            <x-lane>How to apply</x-lane>
            <h2 class="display-3 mt-4">No Word form, no post</h2>
            <ol class="mt-6 space-y-4">
                @foreach (['Apply online: role, depot, licence categories, Driver CPC and tacho card expiry dates.', 'HR calls you back and, for driving roles, emails a secure link to upload copies of your licence and cards.', 'Driving roles take an assessment drive at the depot before an offer.'] as $i => $step)
                    <li class="flex gap-4"><x-junction :n="$i + 1" /><span class="pt-1 text-ink-2">{{ $step }}</span></li>
                @endforeach
            </ol>
            <p class="mt-6 text-ink-2">Training goes beyond compliance: the company's Driver CPC programme started in 2010.</p>
            <p class="mt-2 text-sm text-ink-3">Sources: <a class="text-link" href="{{ ga('sources.careers.url') }}" rel="noopener">{{ ga('sources.careers.label') }}</a>; <a class="text-link" href="{{ ga('sources.about.url') }}" rel="noopener">{{ ga('sources.about.label') }}</a></p>
        </div>
    </section>
</x-layouts.site>
