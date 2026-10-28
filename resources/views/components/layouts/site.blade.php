@props(['title' => null, 'description' => null, 'jsonLd' => [], 'mobileBar' => true, 'ogImage' => null])
@php
    $pageTitle = $title ? $title.' · '.ga('short_name') : ga('short_name').' · Road haulage, warehousing and logistics, Darlington';
    $metaDescription = $description ?? ga('description');
    $phone = ga('phone');
    $email = ga('email');
    $address = ga('address');
    $og = $ogImage ?? ga('og_photo');
    $nav = [
        ['route' => 'services', 'match' => 'services', 'label' => 'Services'],
        ['route' => 'fleet', 'match' => ['fleet', 'warehousing.*'], 'label' => 'Fleet & warehousing'],
        ['route' => 'depots', 'match' => 'depots', 'label' => 'Depots'],
        ['route' => 'about', 'match' => 'about', 'label' => 'About'],
        ['route' => 'careers.index', 'match' => 'careers.*', 'label' => 'Careers'],
        ['route' => 'contact', 'match' => 'contact', 'label' => 'Contact'],
    ];
    $organization = [
        '@context' => 'https://schema.org',
        '@type' => 'MovingCompany',
        'name' => ga('name'),
        'legalName' => ga('legal_name'),
        'alternateName' => ga('alternate_names'),
        'foundingDate' => (string) ga('founded'),
        'url' => route('home'),
        'telephone' => '+44'.substr((string) uk_digits($phone), 1),
        'email' => $email,
        'image' => asset('img/'.ga('og_photo').'-960.webp'),
        'address' => [
            '@type' => 'PostalAddress',
            'streetAddress' => $address['street'],
            'addressLocality' => $address['city'],
            'addressRegion' => $address['county'],
            'postalCode' => $address['postcode'],
            'addressCountry' => 'GB',
        ],
        'areaServed' => 'United Kingdom',
    ];
@endphp
<!DOCTYPE html>
<html lang="en-GB">
<head>
    @include('partials.head')
    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ $metaDescription }}">
    @if (ga('preview'))
        <meta name="robots" content="noindex, nofollow">
    @endif
    <link rel="canonical" href="{{ url()->current() }}">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ ga('short_name') }}">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:description" content="{{ $metaDescription }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ photo($og)['src'] }}">
    <meta property="og:locale" content="en_GB">
    <meta name="twitter:card" content="summary_large_image">
    @foreach (array_merge([$organization], $jsonLd) as $ld)
        <script type="application/ld+json">{!! json_encode($ld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
    @endforeach
</head>
<body class="min-h-screen {{ $mobileBar ? 'pb-[5.25rem] lg:pb-0' : '' }}" x-data="{ menu: false }" @keydown.escape.window="menu = false">
    <a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:bg-surface focus:px-4 focus:py-3 focus:shadow-md">Skip to content</a>

    @if (ga('preview'))
        <div class="bg-gold text-sm text-[#231f20]">
            <p class="wrap flex items-start gap-2 py-2.5"><x-icon name="alert" class="mt-0.5 h-4 w-4 flex-none" /><span><strong class="font-bold">Concept preview prepared for George Allinson (Transport) Ltd.</strong> This is not the company's official website. Forms sent here do not reach the company.</span></p>
        </div>
    @endif

    {{--
        Header, after the company's own: the name and GA badge on a dark band (its night-time depot
        header), then the gold navigation bar with gold rules between links and "t: / e:" on the
        right. On desktop the gold bar sticks; once the dark band has scrolled away the GA badge
        takes the place of the home icon. On phones the dark band sticks and holds the menu.
    --}}
    <div class="brandbar sticky top-0 z-40 bg-cab-900 text-white lg:static" data-brandbar>
        <div class="wrap flex h-[4.25rem] items-center gap-2 min-[400px]:gap-3 lg:h-[5.5rem]">
            <a href="{{ route('home') }}" class="mr-auto inline-flex min-h-11 min-w-0 items-center" aria-label="George Allinson (Transport) Ltd, home"><x-logo tone="light" /></a>
            <p class="hidden text-right text-[0.9375rem] leading-snug text-white/80 lg:block">
                <span class="block">t: <a href="{{ tel_href($phone) }}" class="font-bold text-white hover:text-gold">{{ format_phone($phone) }}</a></span>
                <span class="block">e: <a href="mailto:{{ $email }}" class="font-bold text-white hover:text-gold">{{ $email }}</a></span>
            </p>
            <span class="hidden h-10 w-px bg-white/15 lg:block" aria-hidden="true"></span>
            <button type="button" data-theme-toggle onclick="toggleTheme(event)" class="hidden h-11 w-11 flex-none items-center justify-center text-white/80 hover:bg-white/10 hover:text-white sm:inline-flex" aria-label="Switch light or dark theme">
                <x-icon name="moon" class="h-5 w-5 dark-hide" /><x-icon name="sun" class="hidden h-5 w-5 dark-show" />
            </button>
            <button type="button" class="inline-flex h-11 w-11 flex-none items-center justify-center text-white ring-1 ring-white/40 lg:hidden" @click="menu = !menu" :aria-expanded="menu.toString()" aria-controls="mobile-menu" aria-label="Menu">
                <x-icon name="menu" x-show="!menu" /><x-icon name="x" x-show="menu" x-cloak />
            </button>
        </div>
        <div id="mobile-menu" class="max-h-[calc(100dvh-4.25rem)] overflow-y-auto bg-surface text-ink lg:hidden" x-show="menu" x-cloak x-transition.opacity>
            <div class="goldbar-strip h-2" aria-hidden="true"></div>
            <nav class="wrap flex flex-col py-3" aria-label="Mobile">
                <a href="{{ route('home') }}" class="mnav" @if(request()->routeIs('home')) aria-current="page" @endif>Home</a>
                @foreach ($nav as $item)
                    <a href="{{ route($item['route']) }}" class="mnav" @if(request()->routeIs($item['match'])) aria-current="page" @endif>{{ $item['label'] }}</a>
                @endforeach
                <a href="{{ route('warehousing.create') }}" class="mnav">Warehousing enquiry</a>
                <div class="mt-3 grid gap-3 border-t border-line pt-5 sm:grid-cols-2">
                    <a href="{{ route('quotes.create') }}" class="btn btn-primary">Get a quote</a>
                    <a href="{{ tel_href($phone) }}" class="btn btn-outline"><x-icon name="phone" class="h-4 w-4" />{{ format_phone($phone) }}</a>
                </div>
                <button type="button" data-theme-toggle onclick="toggleTheme(event)" class="mt-3 flex min-h-11 items-center gap-2 px-1 text-left font-semibold text-ink-2"><x-icon name="moon" class="h-5 w-5" />Switch light or dark theme</button>
            </nav>
        </div>
    </div>

    <nav class="goldbar sticky top-0 z-40 hidden lg:block" aria-label="Main" data-goldbar>
        <div class="wrap flex h-[3.25rem] items-stretch">
            <a href="{{ route('home') }}" class="gnav gnav-home" @if(request()->routeIs('home')) aria-current="page" @endif>
                <x-icon name="home" class="gnav-house h-5 w-5" /><x-badge class="gnav-badge h-6 w-[2.6rem]" /><span class="sr-only">Home</span>
            </a>
            @foreach ($nav as $item)
                @php($current = request()->routeIs($item['match']))
                <a href="{{ route($item['route']) }}" class="gnav" @if($current) aria-current="page" @endif>{{ $item['label'] }}</a>
            @endforeach
            <a href="{{ route('warehousing.create') }}" class="gnav ml-auto border-l border-l-[var(--goldbar-rule)]"><x-icon name="warehouse" class="h-4 w-4" />Warehousing</a>
            <a href="{{ route('quotes.create') }}" class="btn btn-primary my-1.5 ml-3 min-h-0 whitespace-nowrap px-4 py-0 text-[0.9375rem]">Get a quote<x-icon name="arrow-right" class="nudge h-4 w-4" /></a>
        </div>
        <span class="scroll-progress" aria-hidden="true"></span>
    </nav>

    <main id="main">
        {{ $slot }}
    </main>

    <footer class="site-footer mt-24 bg-cab-900 text-white/75">
        <div class="goldbar-strip h-1.5" aria-hidden="true"></div>
        <div class="chevrons" aria-hidden="true"></div>
        <div class="tacho overflow-hidden">
            <div class="wrap grid gap-12 py-16 md:grid-cols-2 lg:grid-cols-[1.4fr_1fr_1fr_1.15fr]">
                <div>
                    <x-logo tone="light" />
                    <p class="mt-5 max-w-xs text-white/75">{{ ga('footer_line') }}</p>
                    <p class="mt-4 text-xl font-light tracking-wide text-white">{{ str(ga('tagline'))->title() }}</p>
                    <a href="{{ tel_href($phone) }}" class="font-display mt-6 flex min-h-12 items-center gap-2.5 text-3xl font-extrabold text-white hover:text-gold"><x-icon name="phone" class="h-5 w-5" />{{ format_phone($phone) }}</a>
                    <p class="mt-1"><a class="inline-flex min-h-11 items-center hover:text-white" href="mailto:{{ $email }}">{{ $email }}</a></p>
                </div>
                <div>
                    <h2 class="kicker font-sans text-white/65">Services</h2>
                    <ul class="mt-4 space-y-1">
                        <li><a class="inline-flex min-h-11 items-center hover:text-white" href="{{ route('services') }}">Haulage and distribution</a></li>
                        <li><a class="inline-flex min-h-11 items-center hover:text-white" href="{{ route('fleet') }}">Fleet and warehousing</a></li>
                        <li><a class="inline-flex min-h-11 items-center hover:text-white" href="{{ route('depots') }}">Depots</a></li>
                        <li><a class="inline-flex min-h-11 items-center hover:text-white" href="{{ route('about') }}">About the company</a></li>
                    </ul>
                </div>
                <div>
                    <h2 class="kicker font-sans text-white/65">Get in touch</h2>
                    <ul class="mt-4 space-y-1">
                        <li><a class="inline-flex min-h-11 items-center hover:text-white" href="{{ route('quotes.create') }}">Request a haulage quote</a></li>
                        <li><a class="inline-flex min-h-11 items-center hover:text-white" href="{{ route('warehousing.create') }}">Warehousing enquiry</a></li>
                        <li><a class="inline-flex min-h-11 items-center hover:text-white" href="{{ route('careers.index') }}">Careers and driving jobs</a></li>
                        <li><a class="inline-flex min-h-11 items-center hover:text-white" href="{{ route('contact') }}">Contact</a></li>
                    </ul>
                </div>
                <div>
                    <h2 class="kicker font-sans text-white/65">Head office</h2>
                    <address class="mt-5 not-italic leading-relaxed">
                        {{ ga('name') }}<br>{{ $address['street'] }}<br>{{ $address['city'] }}, {{ $address['county'] }}<br>{{ $address['postcode'] }}
                    </address>
                    <a class="mt-1 inline-flex min-h-11 items-center gap-1.5 hover:text-white" href="{{ ga('map_url') }}" rel="noopener" target="_blank">Get directions<x-icon name="external" class="h-3.5 w-3.5" /><span class="sr-only">(opens Google Maps)</span></a>
                </div>
            </div>
            <div class="border-t border-white/10">
                <div class="wrap flex flex-col gap-3 py-6 text-sm text-white/70 md:flex-row md:items-center md:justify-between">
                    <p>© {{ date('Y') }} {{ ga('legal_name') }}. Registered in England and Wales, company {{ ga('company_number') }}.</p>
                    <p class="flex flex-wrap gap-x-5">
                        <a class="inline-flex min-h-11 items-center hover:text-white" href="{{ route('privacy') }}">Privacy</a>
                        <a class="inline-flex min-h-11 items-center hover:text-white" href="{{ route('credits') }}">Photo credits</a>
                        <a class="inline-flex min-h-11 items-center hover:text-white" href="{{ route('sitemap.page') }}">Site map</a>
                        <a class="inline-flex min-h-11 items-center hover:text-white" href="{{ route('login') }}">Staff sign in</a>
                    </p>
                </div>
            </div>
        </div>
    </footer>

    @if ($mobileBar)
        {{-- Thumb-reach actions on phones: call the office, ask for a quote. --}}
        <nav aria-label="Quick actions" class="fixed inset-x-0 bottom-0 z-30 border-t border-white/10 bg-cab-900/95 px-3 pb-[max(0.75rem,env(safe-area-inset-bottom))] pt-3 backdrop-blur lg:hidden">
            <div class="flex gap-2">
                <a href="{{ tel_href($phone) }}" class="btn btn-on-dark flex-1 px-2 text-[0.9375rem]"><x-icon name="phone" class="h-4 w-4" />Call</a>
                <a href="{{ route('careers.index') }}" class="btn btn-on-dark flex-1 px-2 text-[0.9375rem]"><x-icon name="id-card" class="h-4 w-4" />Jobs</a>
                <a href="{{ route('quotes.create') }}" class="btn btn-red flex-[1.5] whitespace-nowrap px-2 text-[0.9375rem]">Get a quote</a>
            </div>
        </nav>
    @endif
</body>
</html>
