<x-layouts.site title="Photo credits" description="Sources and licences for the photographs on the Allinson Transport website.">
    <x-page-header photo="a40" position="60% 55%" title="Photo credits" kicker="Credits" lead="Most photos are the company's own, from its current website. The rest come from Wikimedia Commons; those that do not show the company are marked “Illustrative photo”." />
    <section class="wrap mt-12 md:mt-16">
        <ul class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            @foreach (config('photo-credits') as $key => $c)
                <li class="sheet flex flex-col">
                    <x-photo :key="$key" alt="" sizes="(min-width: 1024px) 400px, 100vw" class="aspect-[16/10] border-b-[1.5px] border-ink" />
                    <div class="p-5 text-[0.9375rem]">
                        <p class="font-semibold">{{ $c['title'] }}</p>
                        <p class="mt-1 text-ink-2">{{ $c['author'] }} · @if($c['licence_url'])<a class="text-link" href="{{ $c['licence_url'] }}" rel="noopener license" target="_blank">{{ $c['licence'] }}</a>@else{{ $c['licence'] }}@endif</p>
                        <p class="mt-1 text-ink-2"><a class="text-link" href="{{ $c['url'] }}" rel="noopener" target="_blank">{{ $c['source'] }}</a></p>
                        <p class="hint mt-2">{{ $c['changes'] }} Used on: {{ $c['used_for'] }}.</p>
                        @unless (is_own_photo($key))<p class="mt-2"><span class="demo-badge">Illustrative photo</span></p>@endunless
                    </div>
                </li>
            @endforeach
        </ul>
    </section>
</x-layouts.site>
