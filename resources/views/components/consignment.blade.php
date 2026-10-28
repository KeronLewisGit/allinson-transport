@props(['fields' => [], 'cols' => 2, 'head' => null, 'ref' => null, 'flat' => false, 'barcode' => true])
{{--
    Consignment label: label → value cells under a livery-red strip, with a barcode at the foot.
    A field whose label starts with "*" spans the full width.
--}}
<dl {{ $attributes->class(['consignment', 'flat' => $flat]) }} style="--cols: {{ $cols }}">
    @if ($head)<div class="head"><span>{{ $head }}</span>@if($ref)<span class="num">{{ $ref }}</span>@endif</div>@endif
    @foreach ($fields as $label => $value)
        @continue($value === null || $value === '')
        <div @class(['wide' => str_starts_with($label, '*')])><dt>{{ ltrim($label, '*') }}</dt><dd>{{ $value }}</dd></div>
    @endforeach
    @if ($barcode)<div class="barcode" aria-hidden="true"></div>@endif
</dl>
