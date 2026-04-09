@if ($errors->any())
    <div {{ $attributes->merge(['class' => 'mb-5 rounded-2xl border border-[#C52439]/35 bg-[#C52439]/10 px-4 py-3 text-sm text-white/85']) }}>
        <div class="font-black uppercase tracking-widest text-[11px] text-[#ff8896]">
            Revisa estos campos
        </div>

        <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-white/80">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

