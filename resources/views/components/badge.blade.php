@props(['draw' => false])
{{--
    The company's GA badge (black, gold-edged), redrawn as vector from the logo on its website and
    cab doors until the artwork is supplied. With :draw, the gold outlines draw in and the fill
    follows (hero only; skipped for reduced motion and without JavaScript).
--}}
<svg viewBox="0 0 213 124" {{ $attributes->class(['badge', 'badge-draw' => $draw]) }} aria-hidden="true" focusable="false">
    <g class="badge-fill" fill="#231f20"><path d="M3 62 76 4h53L84 58 62 69l22 19v33H62z"/><path d="M89 121V66L141 3l69 59-32 59h-43V86h-4v35z"/></g>
    <g class="badge-line" fill="none" stroke="#d7b273" stroke-width="3" stroke-linejoin="miter"><path pathLength="1" d="M3 62 76 4h53L84 58 62 69l22 19v33H62z"/><path pathLength="1" d="M89 121V66L141 3l69 59-32 59h-43V86h-4v35z"/></g>
    <g class="badge-line" fill="none" stroke="#d7b273" stroke-width="2.2"><path pathLength="1" d="M114 11H88L28 62l50 49"/><path pathLength="1" d="M144 15l55 47-27 52"/><path pathLength="1" d="M97 112V70l12-14"/></g>
    <path class="badge-fill" d="M133 45v29h27z" fill="#fff" stroke="#d7b273" stroke-width="2.2"/>
</svg>
