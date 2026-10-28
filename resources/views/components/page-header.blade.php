@props(['title', 'lead' => null, 'kicker' => null, 'ref' => null, 'photo' => null, 'position' => 'center', 'label' => null, 'to' => 'UK-wide'])
{{--
    The one header band every inner page uses, so titles start at the same height and left edge
    site-wide. Charcoal band with a faint tachograph disc (or a photo behind a scrim), a dashed
    lane-marking kicker, and a consignment label on the right. The trailer's red-and-white rear
    chevrons close it. Breadcrumbs, actions and chips go in the slot.
--}}
@php
    $fields = $label ?? [
        'From' => 'Darlington DL3',
        'To' => $to,
        '*Contents' => $kicker ?? $title,
    ];
@endphp
<section {{ $attributes->merge(['class' => 'tacho relative isolate overflow-hidden bg-cab-900 text-white on-dark']) }}>
    @if ($photo)
        <div class="hero-photo absolute inset-0 -z-10">
            <x-photo :key="$photo" alt="" :eager="true" sizes="100vw" class="h-full w-full" :position="$position" />
            <div class="hero-scrim absolute inset-0"></div>
        </div>
    @endif
    <div class="wrap grid gap-8 pb-10 pt-10 md:pb-14 md:pt-16 lg:grid-cols-[minmax(0,1fr)_18rem] lg:items-end lg:gap-12">
        <div class="min-w-0">
            @if ($kicker)<x-lane>{{ $kicker }}</x-lane>@endif
            <h1 class="display-2 mt-5 max-w-4xl">{{ $title }}</h1>
            @if ($lead)<p class="lead mt-4 text-white/85 md:mt-5">{{ $lead }}</p>@endif
            {{ $slot }}
        </div>
        <x-consignment :fields="$fields" head="Allinson Transport" :ref="$ref ?? 'GA · '.now()->format('d/m')" class="hidden rotate-[1.5deg] lg:grid" />
    </div>
    @if ($photo && ! is_own_photo($photo))
        <span class="photo-note" style="bottom: 1.75rem">Illustrative photo</span>
    @endif
    <div class="chevrons" aria-hidden="true"></div>
</section>
