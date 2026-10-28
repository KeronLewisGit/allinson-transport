@props(['tone' => 'dark', 'small' => false])
{{--
    The GA badge with the company name set as on its website header: "GEORGE ALLINSON
    (TRANSPORT) LTD" in bold condensed caps. Roboto Condensed stands in for the header face.
--}}
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-2.5']) }}>
    <x-badge @class(['flex-none', 'h-8 w-[3.45rem]' => $small, 'h-10 w-[4.3rem] sm:h-11 sm:w-[4.75rem]' => ! $small]) />
    <span class="leading-none">
        <span @class(['block font-display font-bold uppercase tracking-[0.01em]', 'text-[1.05rem] whitespace-nowrap' => $small, 'text-[1.3rem] sm:text-[1.55rem]' => ! $small, 'text-white' => $tone === 'light', 'text-ink' => $tone !== 'light'])>George Allinson</span>
        <span @class(['mt-1 block font-display text-[0.8125rem] font-bold uppercase tracking-[0.12em]', 'text-gold' => $tone === 'light', 'text-ink-3' => $tone !== 'light'])>(Transport) Ltd</span>
    </span>
</span>
