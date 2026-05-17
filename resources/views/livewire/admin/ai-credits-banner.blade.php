@php
    $isSky = $aiCredits['uses_client_key'] || $aiCredits['is_demo_unlimited'];
    $isZero = ! empty($aiCredits['needs_credit_topup']);
    $colorTitle = $isZero ? 'text-rose-900' : ($isSky ? 'text-sky-900' : 'text-amber-900');
    $colorBtn   = $isZero ? 'text-rose-700' : ($isSky ? 'text-sky-700' : 'text-amber-700');
    $borderBg   = $isZero
        ? 'admin-ai-credits-zero-panel border-rose-200 bg-rose-50'
        : ($isSky ? 'border-sky-200 bg-sky-50' : 'border-amber-200 bg-amber-50');
@endphp
<div class="admin-ai-credits-fixed pointer-events-auto px-3 pt-3">
    <div class="relative rounded-none border px-3 py-3 shadow-sm {{ $borderBg }}">

        {{-- Título + botón siempre visibles --}}
        <div class="flex items-center justify-between gap-2">
            <p class="admin-ai-credits-title {{ $colorTitle }}">
                {{ __('admin.ai_credits_banner.balance_label') }} {{ $aiCredits['label'] }}
            </p>
            <button wire:click="toggle"
                    class="flex-shrink-0 p-0.5 rounded opacity-50 hover:opacity-100 transition-opacity {{ $colorBtn }}"
                    aria-label="{{ $minimized ? 'Expandir' : 'Minimizar' }}">
                @if($minimized)
                    {{-- Chevron abajo (expandir) --}}
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                @else
                    {{-- X (minimizar) --}}
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                @endif
            </button>
        </div>

        {{-- Cuerpo: solo visible cuando no está minimizado --}}
        @if(!$minimized)
        <div class="flex flex-col gap-2 mt-2">
            <div>
                @if($isZero)
                    <p class="admin-ai-credits-body mt-1.5 text-rose-800">
                        {{ __('admin.ai_credits_banner.no_credits') }}
                    </p>
                    <a href="{{ route('settings.ai-billing') }}"
                       class="admin-ai-credits-cta mt-2.5 block w-full text-center font-semibold no-underline py-2.5 px-2 bg-amber-600 hover:bg-amber-700 text-white border border-amber-700 shadow-sm transition-colors">
                        {{ __('admin.ai_credits_banner.topup_cta') }}
                    </a>
                @else
                    <p class="admin-ai-credits-body mt-1.5 {{ $aiCredits['is_demo_unlimited'] ? 'admin-ai-credits-body--demo' : '' }} {{ $isSky ? 'text-sky-700' : 'text-amber-700' }}">
                        @if($aiCredits['uses_client_key'])
                            {{ __('admin.ai_credits_banner.uses_client_key') }}
                        @elseif($aiCredits['is_demo_unlimited'])
                            {{ __('admin.ai_credits_banner.demo_unlimited') }}
                        @else
                            {{ __('admin.ai_credits_banner.costs_line', [
                                'gen' => $aiGenerateCost,
                                'improve' => $aiImproveCost,
                                'bulk' => $aiBulkGenerateCost,
                            ]) }}
                        @endif
                    </p>
                @endif
            </div>
            @if($aiCredits['charges_platform_credits'] && ! $isZero)
                <p class="admin-ai-credits-hint text-amber-800">
                    {{ __('admin.ai_credits_banner.batch_hint') }}
                </p>
            @endif
        </div>
        @endif

    </div>
</div>
