<button {{ $attributes->merge([
        'type' => 'submit',
        'class' => 'inline-flex items-center justify-center rounded-xl px-4 py-2.5 text-sm font-black uppercase tracking-wide transition ' .
                   'bg-[#C52439] text-white shadow-lg shadow-[#C52439]/25 hover:bg-[#a01d2e] ' .
                   'focus:outline-none focus:ring-4 focus:ring-[#C52439]/25 disabled:opacity-50 disabled:cursor-not-allowed'
    ]) }}>
    {{ $slot }}
</button>

