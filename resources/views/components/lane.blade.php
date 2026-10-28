@props(['n' => null])
{{-- Section label led by a dashed lane marking. With $n, a motorway junction number first. --}}
<p {{ $attributes->class(['lane']) }}>@if($n !== null)<span class="junction text-base">{{ $n }}</span>@endif<span>{{ $slot }}</span></p>
