@php
    $isSky = $aiCredits['uses_client_key'] || $aiCredits['is_demo_unlimited'];
    $isZero = ! empty($aiCredits['needs_credit_topup']);
@endphp
<div class="admin-ai-credits-fixed pointer-events-auto px-3 pt-3">
    <div class="rounded-none border px-3 py-3 shadow-sm
        @if($isZero)
            admin-ai-credits-zero-panel border-rose-200 bg-rose-50
        @elseif($isSky)
            border-sky-200 bg-sky-50
        @else
            border-amber-200 bg-amber-50
        @endif
    ">
        <div class="flex flex-col gap-2">
            <div>
                <p class="admin-ai-credits-title @if($isZero) text-rose-900 @elseif($isSky) text-sky-900 @else text-amber-900 @endif">
                    {{ __('admin.ai_credits_banner.balance_label') }} {{ $aiCredits['label'] }}
                </p>

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
    </div>
</div>
