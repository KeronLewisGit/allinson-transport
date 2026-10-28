@props(['tone' => 'dark', 'small' => false])
{{--
    Redrawn as vector from the firm's own "GA" badge (black, gold outline, on its website and
    cab doors) until the firm supplies the artwork. The wordmark is set in Big Shoulders.
--}}
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-2.5']) }}>
    <svg viewBox="0 0 213 124" @class(['flex-none', 'h-8 w-[3.45rem]' => $small, 'h-10 w-[4.3rem]' => ! $small]) aria-hidden="true">
        <g fill="#1b1619" stroke="#d9ae5c" stroke-width="3"><path d="M3 62 76 4h53L84 58 62 69l22 19v33H62z"/><path d="M89 121V66L141 3l69 59-32 59h-43V86h-4v35z"/></g>
        <g fill="none" stroke="#d9ae5c" stroke-width="2.2"><path d="M114 11H88L28 62l50 49"/><path d="M144 15l55 47-27 52"/><path d="M97 112V70l12-14"/></g>
        <path d="M133 45v29h27z" fill="#fff" stroke="#d9ae5c" stroke-width="2.2"/>
    </svg>
    <span class="leading-none">
        <span @class(['block font-display font-extrabold uppercase tracking-[0.06em]', 'text-[1.35rem]' => $small, 'text-[1.7rem]' => ! $small, 'text-white' => $tone === 'light', 'text-ink' => $tone !== 'light'])>Allinson</span>
        <span @class(['mt-0.5 block text-[0.6875rem] font-semibold uppercase tracking-[0.14em]', 'text-white/75' => $tone === 'light', 'text-ink-3' => $tone !== 'light'])>Transport · Est. 1955</span>
    </span>
</span>
