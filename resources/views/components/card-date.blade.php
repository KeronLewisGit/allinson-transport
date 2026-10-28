@props(['date', 'state' => null])
{{-- A card or licence expiry date with its state: expired, expiring (within the warning window), valid or missing. --}}
@php
    $state ??= \App\Models\JobApplication::cardState($date);
    [$tone, $icon, $text] = match ($state) {
        'expired' => ['critical', 'alert', 'Expired '.$date?->format('j M Y')],
        'expiring', 'due' => ['attention', 'clock', $date?->format('j M Y').' · '.(int) today()->diffInDays($date, false).' days'],
        'missing' => ['muted', 'dot', 'Not given'],
        default => ['good', 'check-circle', $date?->format('j M Y')],
    };
@endphp
<x-chip :tone="$tone" :icon="$icon" {{ $attributes->merge(['class' => 'num']) }}>{{ $text }}</x-chip>
