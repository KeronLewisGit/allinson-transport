@props(['items' => []])
{{--
    The white "HAULAGE · STORAGE" band from the cab fronts, as a moving strip. CSS-only motion;
    pauses on hover and keyboard focus, and has its own pause button (WCAG 2.2.2). With reduced
    motion it stands still and wraps. The second copy only makes the loop seamless and is hidden
    from assistive technology.
--}}
<div {{ $attributes->merge(['class' => 'marquee']) }} x-data="{ paused: false }" :class="paused && 'is-paused'">
    <div class="marquee-track">
        @foreach ([false, true] as $copy)
            <div class="marquee-group"@if($copy) aria-hidden="true"@endif>
                @foreach ($items as $item)
                    <span @class(['script' => str_starts_with($item, '~')])>{{ ltrim($item, '~') }}</span>
                @endforeach
            </div>
        @endforeach
    </div>
    <button type="button" class="marquee-toggle" @click="paused = !paused" :aria-pressed="paused.toString()" aria-pressed="false">
        <span class="sr-only">Pause the moving band</span>
        <x-icon name="pause" class="h-4 w-4" x-show="!paused" /><x-icon name="play" class="h-4 w-4" x-show="paused" x-cloak />
    </button>
</div>
