<x-layouts.site title="Site map" description="Every page on the Allinson Transport website.">
    <x-page-header photo="a1m" position="center 60%" title="Site map" kicker="Site map" lead="Every public page on this website." />
    <section class="wrap mt-12 grid gap-10 md:mt-16 md:grid-cols-3">
        <div><h2 class="font-display text-2xl font-extrabold uppercase">The company</h2><ul class="mt-3 space-y-1">
            @foreach (['home' => 'Home', 'services' => 'Services', 'fleet' => 'Fleet and warehousing', 'depots' => 'Depots', 'about' => 'About us', 'contact' => 'Contact'] as $r => $l)
                <li><a class="inline-flex min-h-11 items-center text-link" href="{{ route($r) }}">{{ $l }}</a></li>
            @endforeach
        </ul></div>
        <div><h2 class="font-display text-2xl font-extrabold uppercase">Work with us</h2><ul class="mt-3 space-y-1">
            <li><a class="inline-flex min-h-11 items-center text-link" href="{{ route('quotes.create') }}">Request a haulage quote</a></li>
            <li><a class="inline-flex min-h-11 items-center text-link" href="{{ route('warehousing.create') }}">Warehousing enquiry</a></li>
            <li><a class="inline-flex min-h-11 items-center text-link" href="{{ route('careers.index') }}">Careers</a></li>
            <li><a class="inline-flex min-h-11 items-center text-link" href="{{ route('careers.apply') }}">Apply for a job</a></li>
            @foreach ($jobs as $j)<li><a class="inline-flex min-h-11 items-center text-link" href="{{ route('careers.show', $j->slug) }}">{{ $j->title }}</a></li>@endforeach
        </ul></div>
        <div><h2 class="font-display text-2xl font-extrabold uppercase">About this site</h2><ul class="mt-3 space-y-1">
            <li><a class="inline-flex min-h-11 items-center text-link" href="{{ route('privacy') }}">Privacy notice</a></li>
            <li><a class="inline-flex min-h-11 items-center text-link" href="{{ route('credits') }}">Photo credits</a></li>
        </ul></div>
    </section>
</x-layouts.site>
