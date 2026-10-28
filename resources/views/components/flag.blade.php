@props(['name', 'label', 'hint' => null, 'checked' => false])
{{-- One yes/no checkbox tile posting 1 when ticked. Extra attributes (x-model) go on the input. --}}
<div>
    <label class="choice">
        <input type="checkbox" name="{{ $name }}" value="1" @checked((bool) old($name, $checked)) {{ $attributes }}>
        <span>{{ $label }}@if($hint)<small>{{ $hint }}</small>@endif</span>
    </label>
    <x-error :for="$name" />
</div>
