<div style="position:fixed;inset:0;z-index:20000;background:rgba(0,0,0,0.75);overflow-y:auto;" wire:click.self="closeModal()">

    <style>
        @keyframes pairing-ai-progress-slide {
            0%   { left: -40%; }
            100% { left: 100%; }
        }
        @keyframes pairing-ai-butterfly-slide {
            0%   { left: 0%; }
            100% { left: 100%; }
        }
        @keyframes pairing-ai-butterfly-flap {
            from { transform: scaleX(1); }
            to   { transform: scaleX(0.22); }
        }
        .pairing-ai-progress-track {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            left: 0;
            right: 0;
            height: 0.45rem;
            border-radius: 9999px;
            background: #e5e7eb;
            overflow: hidden;
        }
        .pairing-ai-progress-fill {
            position: absolute;
            top: 0; bottom: 0;
            width: 40%;
            border-radius: 9999px;
            background: linear-gradient(90deg, #f59e0b, #1D1D1B);
            animation: pairing-ai-progress-slide 1.2s cubic-bezier(0.4, 0, 0.2, 1) infinite;
        }
        .pairing-ai-butterfly-wrap {
            position: absolute;
            top: 0; bottom: 0;
            width: 2rem;
            margin-left: -1rem;
            animation: pairing-ai-butterfly-slide 1.2s cubic-bezier(0.4, 0, 0.2, 1) infinite;
        }
        .pairing-ai-butterfly-wrap svg {
            width: 100%; height: 100%;
            display: block;
            transform-origin: center center;
            animation: pairing-ai-butterfly-flap 0.28s ease-in-out infinite alternate;
        }
    </style>

    {{-- Mismo contenedor y ancho que la ficha de producto (productfrm: max-w-4xl, p-4) --}}
    <div class="flex min-h-full items-center justify-center p-4" wire:click="closeModal()">
        <div class="relative bg-white rounded-2xl w-full max-w-4xl overflow-hidden"
             wire:click.stop
             style="box-shadow:0 25px 50px rgba(0,0,0,0.4);border:2px solid #d1d5db;"
             role="dialog" aria-modal="true">

            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 bg-gray-50">
                <h2 style="color:#111827;font-weight:600;font-size:1rem;margin:0;">
                    {{ $pairing_id ? __('admin.pairing_page.edit_title') : __('admin.pairing_page.new_title') }}
                </h2>
                <button type="button" wire:click="closeModal()"
                        class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <form onsubmit="return false">
                <div class="px-6 py-5 overflow-y-auto overscroll-contain" style="max-height: 70vh;">
                    <div class="space-y-5">

                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">{{ __('admin.pairing_page.th_name') }}</label>
                            <input type="text" wire:model="name" placeholder="{{ __('admin.pairing_page.name_placeholder') }}"
                                   class="w-full border border-gray-200 rounded-xl py-2 px-3 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-amber-300 focus:border-transparent shadow-sm">
                            @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        <label class="flex items-center gap-2.5 cursor-pointer">
                            <input type="checkbox" wire:model="active"
                                   class="form-checkbox w-4 h-4 rounded text-gray-400 border-gray-300 focus:ring-gray-300 cursor-pointer">
                            <span class="text-sm font-medium text-gray-700">{{ __('admin.pairing_page.label_hide_in_menu') }}</span>
                        </label>

                        <div>
                            <div class="mb-1.5 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between sm:gap-4">
                                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide shrink-0">{{ __('admin.pairing_page.th_description') }}</label>
                                @if ($canUseAi ?? false)
                                <button type="button"
                                        wire:click="confirmGeneratePairingDescriptionWithAi"
                                        wire:loading.attr="disabled"
                                        wire:target="confirmGeneratePairingDescriptionWithAi,generatePairingDescriptionWithAi,confirmPairingAiDescription"
                                        class="inline-flex w-full sm:w-auto justify-center sm:justify-end items-center gap-2 rounded-lg border-2 border-green-600 bg-green-50 px-3 py-2 text-xs font-semibold text-gray-700 hover:border-green-700 hover:bg-green-100 shrink-0">
                                    <span wire:loading.remove wire:target="generatePairingDescriptionWithAi,confirmPairingAiDescription">{{ __('admin.pairing_page.generate_description_ai') }} · {{ $aiPairingDescriptionCost }}</span>
                                    <span wire:loading wire:target="generatePairingDescriptionWithAi,confirmPairingAiDescription">{{ __('admin.pairing_page.generating_description_ai') }}</span>
                                </button>
                                @endif
                            </div>
                            <p class="mb-1.5 text-xs {{ $aiWritingGuideConnected ? 'text-sky-600' : 'text-gray-400' }}">
                                {{ __('admin.pairing_page.writing_guide_hint') }}
                            </p>
                            <textarea wire:model="description"
                                      placeholder="{{ __('admin.pairing_page.description_placeholder') }}"
                                      rows="6"
                                      class="w-full border border-gray-200 rounded-xl py-2.5 px-3 text-sm text-gray-800 leading-relaxed focus:outline-none focus:ring-2 focus:ring-amber-300 focus:border-transparent shadow-sm resize-y min-h-[8rem] max-h-[min(38vh,300px)]"></textarea>
                            <div wire:loading wire:target="generatePairingDescriptionWithAi,confirmPairingAiDescription" style="position:relative;height:2rem;margin-top:0.375rem;">
                                <div class="pairing-ai-progress-track">
                                    <div class="pairing-ai-progress-fill"></div>
                                </div>
                                <div class="pairing-ai-butterfly-wrap">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100">
                                        <path d="M48,44 C38,16 5,12 5,34 C5,54 26,62 48,60" fill="#FF7A00" stroke="#1D1D1B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M52,44 C62,16 95,12 95,34 C95,54 74,62 52,60" fill="#FF7A00" stroke="#1D1D1B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M48,61 C35,65 8,74 8,86 C8,95 32,92 48,72" fill="#FF7A00" stroke="#1D1D1B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M52,61 C65,65 92,74 92,86 C92,95 68,92 52,72" fill="#FF7A00" stroke="#1D1D1B" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        <circle cx="30" cy="40" r="5.5" fill="#FFC107" opacity="0.55"/>
                                        <circle cx="70" cy="40" r="5.5" fill="#FFC107" opacity="0.55"/>
                                        <circle cx="25" cy="76" r="4" fill="#FFC107" opacity="0.4"/>
                                        <circle cx="75" cy="76" r="4" fill="#FFC107" opacity="0.4"/>
                                        <ellipse cx="50" cy="58" rx="3.5" ry="15" fill="#1D1D1B"/>
                                        <circle cx="50" cy="42" r="3.5" fill="#1D1D1B"/>
                                        <path d="M48,39 Q40,25 34,18" fill="none" stroke="#1D1D1B" stroke-width="1.5" stroke-linecap="round"/>
                                        <circle cx="34" cy="18" r="2.5" fill="#1D1D1B"/>
                                        <path d="M52,39 Q60,25 66,18" fill="none" stroke="#1D1D1B" stroke-width="1.5" stroke-linecap="round"/>
                                        <circle cx="66" cy="18" r="2.5" fill="#1D1D1B"/>
                                    </svg>
                                </div>
                            </div>
                            @error('description') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            @if ($canUseAi ?? false)
                            <div class="mt-2 rounded-xl border {{ $aiCredits['uses_client_key'] || $aiCredits['is_demo_unlimited'] ? 'border-sky-200 bg-sky-50 text-sky-800' : 'border-gray-200 bg-gray-50 text-gray-700' }} px-3 py-2 text-xs">
                                <span class="font-semibold">Saldo IA:</span> {{ $aiCredits['label'] }}
                                @if($aiCredits['uses_client_key'])
                                    <span class="ml-1">Se está usando la API key del cliente. No se descuentan créditos.</span>
                                @elseif($aiCredits['is_demo_unlimited'])
                                    <span class="ml-1">Esta demo no descuenta créditos.</span>
                                @endif
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Pie: mismo patrón que productfrm (eliminar a la izquierda, acciones a la derecha) --}}
                <div class="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-between gap-3 px-6 py-4 border-t border-gray-100 bg-gray-50 rounded-b-2xl">
                    <div class="flex justify-center sm:justify-start">
                        @if($pairing_id)
                            <button type="button"
                                    wire:click="confirmDeleteCurrentPairing"
                                    wire:loading.attr="disabled"
                                    wire:target="confirmDeleteCurrentPairing,deletePairingConfirmed"
                                    class="px-4 py-2 text-sm font-semibold text-red-600 bg-white border border-red-200 hover:bg-red-50 rounded-xl transition-colors cursor-pointer">
                                {{ __('admin.pairing_page.delete_pairing') }}
                            </button>
                        @endif
                    </div>
                    <div class="flex flex-wrap items-center justify-end gap-3">
                        <button type="button" wire:click="closeModal()"
                                class="px-4 py-2 text-sm text-gray-600 bg-white border border-gray-200 hover:bg-gray-50 rounded-xl transition-colors cursor-pointer">
                            {{ __('admin.actions.cancel') }}
                        </button>
                        <button type="button" wire:click="store"
                                wire:loading.attr="disabled"
                                wire:target="store,storeAndClose"
                                class="px-5 py-2 text-sm font-semibold text-gray-800 bg-white border border-gray-200 hover:bg-gray-50 rounded-xl shadow-sm transition-colors cursor-pointer">
                            {{ __('admin.pairing_page.save_keep_open') }}
                        </button>
                        <button type="button" wire:click="storeAndClose"
                                wire:loading.attr="disabled"
                                wire:target="store,storeAndClose"
                                class="px-5 py-2 text-sm font-semibold text-white bg-green-500 hover:bg-green-600 rounded-xl shadow-sm transition-colors cursor-pointer">
                            {{ __('admin.pairing_page.save_and_close') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
