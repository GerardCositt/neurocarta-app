<div class="relative min-h-screen flex flex-col items-center justify-center px-4 py-10 sm:px-6">
    <div class="w-full max-w-md">
        <div class="text-center">
            {{ $logo }}
            <p class="mt-4 text-sm text-white/70">
                Accede a tu panel. Si aún no tienes cuenta, solicita acceso.
            </p>
        </div>

        <div class="mt-8 rounded-2xl border border-white/10 bg-white/[0.04] p-6 shadow-[0_30px_80px_-40px_rgba(0,0,0,0.75)] backdrop-blur sm:p-8">
            {{ $slot }}
        </div>

        <div class="mt-6 text-center text-xs text-white/45">
            © {{ date('Y') }} NeuroCarta<span class="text-[#FFC107]">.ai</span><span class="align-super text-[10px] text-white/55" aria-label="marca registrada">®</span>
        </div>
    </div>
</div>

