@props(['disabled' => false])

<input {{ $disabled ? 'disabled' : '' }} {!! $attributes->merge([
    'class' => 'w-full rounded-xl border border-white/10 bg-[#0F0F0F] px-4 py-3 text-sm text-white placeholder:text-white/35 ' .
               'shadow-[inset_0_0_0_1px_rgba(255,255,255,0.03)] ' .
               'focus:border-[#FFC107]/45 focus:ring-4 focus:ring-[#FFC107]/10'
]) !!}>

