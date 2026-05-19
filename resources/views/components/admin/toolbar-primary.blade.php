{{-- Misma piel que el summary «Acciones» en productos: borde neutro, sin verde sólido --}}
<button {{ $attributes->merge([
    'type' => 'button',
    'class' => 'inline-flex items-center justify-center gap-2 bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 text-sm font-semibold py-2 px-4 rounded-xl shadow-sm transition-colors cursor-pointer shrink-0 w-full sm:w-auto',
]) }}>
    {{ $slot }}
</button>
