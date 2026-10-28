@props(['n'])
{{-- A step or section number drawn like a motorway junction number: white figures in a black box. --}}
<span {{ $attributes->merge(['class' => 'junction']) }} aria-hidden="true">{{ $n }}</span>
