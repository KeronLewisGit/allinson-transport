{{--
    An articulated lorry in the company's livery, side on: red cab with the white roof panel,
    red curtain trailer with the white name band, red-and-white rear chevrons. Decorative.
--}}
<svg viewBox="0 0 240 110" {{ $attributes->merge(['class' => 'lorry']) }} aria-hidden="true" focusable="false">
    {{-- trailer --}}
    <rect x="4" y="8" width="164" height="68" fill="#d92026"/>
    <rect x="4" y="22" width="164" height="16" fill="#fff"/>
    <text x="86" y="34.5" text-anchor="middle" font-family="Roboto Condensed, Arial Narrow, sans-serif" font-weight="700" font-size="12.5" fill="#231f20">GEORGE ALLINSON (TRANSPORT) LTD</text>
    <g fill="#fff"><rect x="4" y="76" width="164" height="4" fill="#231f20"/></g>
    <g><rect x="0" y="62" width="6" height="16" fill="#fff"/><path d="M0 62h6v4L0 70zm0 8 6-4v6l-6 4z" fill="#d92026"/></g>
    {{-- cab --}}
    <path d="M176 22h34l14 22v36h-48z" fill="#d92026"/>
    <path d="M176 22h34l5 8h-39z" fill="#fff"/>
    <path d="M196 32h14l9 14h-23z" fill="#231f20" opacity=".85"/>
    <rect x="176" y="60" width="48" height="6" fill="#fff"/>
    <rect x="170" y="76" width="56" height="4" fill="#231f20"/>
    <rect x="222" y="70" width="8" height="10" fill="#231f20"/>
    {{-- wheels --}}
    <g fill="#231f20"><circle cx="30" cy="88" r="11"/><circle cx="56" cy="88" r="11"/><circle cx="82" cy="88" r="11"/><circle cx="160" cy="88" r="11"/><circle cx="206" cy="88" r="11"/></g>
    <g fill="#9b9396"><circle cx="30" cy="88" r="4"/><circle cx="56" cy="88" r="4"/><circle cx="82" cy="88" r="4"/><circle cx="160" cy="88" r="4"/><circle cx="206" cy="88" r="4"/></g>
</svg>
