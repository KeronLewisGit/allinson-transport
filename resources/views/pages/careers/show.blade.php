@php
    $address = $job->depot === \App\Support\Depots::HEAD_OFFICE
        ? ['@type' => 'PostalAddress', 'streetAddress' => ga('address.street'), 'addressLocality' => ga('address.city'), 'postalCode' => ga('address.postcode'), 'addressCountry' => 'GB']
        : ['@type' => 'PostalAddress', 'addressRegion' => ga('depots.'.$job->depot.'.region'), 'addressCountry' => 'GB'];
    $ld = ['@context' => 'https://schema.org', '@type' => 'JobPosting', 'title' => $job->title, 'description' => $job->summary,
        'datePosted' => $job->created_at->toDateString(), 'employmentType' => $job->employment_type->schema(),
        'hiringOrganization' => ['@type' => 'Organization', 'name' => ga('name'), 'sameAs' => route('home')],
        'jobLocation' => ['@type' => 'Place', 'address' => $address]]
        + ($job->closes_on ? ['validThrough' => $job->closes_on->endOfDay()->toIso8601String()] : []);
@endphp
<x-layouts.site :title="$job->title" :description="$job->summary" :json-ld="[$ld]">
    <x-page-header photo="tacho" :title="$job->title" :kicker="$job->role->label()" :lead="$job->summary" :ref="'JOB '.$job->id"
        :label="['*Depot' => $job->depotName(), '*Pay' => $job->pay ?? 'To be discussed', 'Openings' => (string) $job->openings, 'Closes' => $job->closes_on?->format('j F Y') ?? 'Open until filled']">
        <div class="mt-6 flex flex-wrap gap-3"><a href="{{ route('careers.apply', ['job' => $job->slug]) }}" class="btn btn-red">Apply for this job<x-icon name="arrow-right" class="nudge h-4 w-4" /></a><a href="{{ route('careers.index') }}" class="btn btn-on-dark">All vacancies</a></div>
    </x-page-header>
    <section class="wrap mt-12 md:mt-16">
        <dl class="grid gap-x-8 gap-y-4 border-y border-line py-6 sm:grid-cols-2 lg:grid-cols-4">
            <div><dt class="kicker text-ink-3">Depot</dt><dd class="mt-1 font-semibold">{{ $job->depotName() }}</dd></div>
            <div><dt class="kicker text-ink-3">Contract</dt><dd class="mt-1 font-semibold">{{ $job->employment_type->label() }}</dd></div>
            <div><dt class="kicker text-ink-3">Pay</dt><dd class="mt-1 font-semibold">{{ $job->pay ?? 'To be discussed' }}</dd></div>
            <div><dt class="kicker text-ink-3">Hours</dt><dd class="mt-1 font-semibold">{{ $job->hours ?? 'To be discussed' }}</dd></div>
        </dl>
    </section>
    <section class="wrap mt-12 grid gap-12 lg:grid-cols-2">
        @if ($job->dutyList())
            <div><x-lane :n="1">The work</x-lane><ul class="ticked mt-5 space-y-2.5">@foreach ($job->dutyList() as $d)<li>{{ $d }}</li>@endforeach</ul></div>
        @endif
        @if ($job->requirementList())
            <div><x-lane :n="2">You bring</x-lane><ul class="ticked mt-5 space-y-2.5">@foreach ($job->requirementList() as $d)<li>{{ $d }}</li>@endforeach</ul></div>
        @endif
    </section>
    @if ($job->is_demo)<p class="wrap mt-10"><span class="demo-badge">Sample posting for the demo</span></p>@endif
    <section class="wrap mt-12"><a href="{{ route('careers.apply', ['job' => $job->slug]) }}" class="btn btn-primary">Apply for this job<x-icon name="arrow-right" class="nudge h-4 w-4" /></a></section>
</x-layouts.site>
