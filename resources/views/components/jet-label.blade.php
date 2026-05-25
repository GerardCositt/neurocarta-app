@props(['value'])

<label {{ $attributes->merge(['class' => 'block font-medium text-sm']) }} style="color:rgba(255,255,255,0.55);margin-bottom:4px;">
    {{ $value ?? $slot }}
</label>
