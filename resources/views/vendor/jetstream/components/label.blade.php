@props(['value'])

<label {{ $attributes->merge(['class' => 'block text-xs font-black uppercase tracking-widest text-white/70']) }}>
    {{ $value ?? $slot }}
</label>

