<div style="position:fixed;inset:0;z-index:20000;background:rgba(0,0,0,0.75);overflow-y:auto;" wire:click.self="closeModal()">

    <style>
        @keyframes product-ai-progress-slide {
            0%   { left: -40%; }
            100% { left: 100%; }
        }
        @keyframes product-ai-butterfly-slide {
            0%   { left: 0%; }
            100% { left: 100%; }
        }
        @keyframes product-ai-butterfly-flap {
            from { transform: scaleX(1); }
            to   { transform: scaleX(0.22); }
        }
        .product-ai-progress-track {
            position: relative;
            height: 0.45rem;
            border-radius: 9999px;
            background: #e5e7eb;
            overflow: hidden;
        }
        .product-ai-progress-fill {
            position: absolute;
            top: 0;
            bottom: 0;
            width: 40%;
            border-radius: 9999px;
            background: linear-gradient(90deg, #f59e0b, #1D1D1B);
            animation: product-ai-progress-slide 1.2s cubic-bezier(0.4, 0, 0.2, 1) infinite;
        }
        .product-ai-butterfly-wrap {
            position: absolute;
            top: 0;
            bottom: 0;
            width: 2rem;
            margin-left: -1rem;
            animation: product-ai-butterfly-slide 1.2s cubic-bezier(0.4, 0, 0.2, 1) infinite;
        }
        .product-ai-butterfly-wrap svg {
            width: 100%;
            height: 100%;
            display: block;
            transform-origin: center center;
            animation: product-ai-butterfly-flap 0.28s ease-in-out infinite alternate;
        }
    </style>

    {{-- Contenedor centrado --}}
    <div class="flex min-h-full items-center justify-center p-4" wire:click="closeModal()">

        <div class="relative bg-white rounded-2xl w-full max-w-4xl overflow-hidden"
             wire:click.stop
             style="box-shadow:0 25px 50px rgba(0,0,0,0.4);border:2px solid #d1d5db;"
             role="dialog" aria-modal="true">

            {{-- Cabecera --}}
            <div class="flex items-center justify-between px-4 sm:px-6 py-4 border-b border-gray-200 bg-gray-50">
                <h2 style="color:#111827;font-weight:600;font-size:1rem;margin:0;">
                    {{ $product_id ? __('admin.product_form.edit_title') : __('admin.product_form.new_title') }}
                </h2>
                <div style="display:flex;align-items:center;gap:6px;">
                    @if(($navPosition ?? null) !== null && ($navTotal ?? 0) > 1)
                    <div style="display:flex;align-items:center;gap:4px;background:#f3f4f6;border-radius:10px;padding:3px 6px;">
                        <button type="button" wire:click="saveAndPrev" wire:loading.attr="disabled" wire:target="saveAndPrev,saveAndNext"
                                @disabled($navPosition <= 1)
                                style="display:flex;align-items:center;justify-content:center;width:26px;height:26px;border-radius:6px;border:1px solid #e5e7eb;background:#fff;color:#374151;cursor:pointer;"
                                title="Guardar y anterior">
                            <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                        </button>
                        <span style="font-size:0.78rem;color:#6b7280;padding:0 3px;white-space:nowrap;font-variant-numeric:tabular-nums;">{{ $navPosition }} / {{ $navTotal }}</span>
                        <button type="button" wire:click="saveAndNext" wire:loading.attr="disabled" wire:target="saveAndPrev,saveAndNext"
                                @disabled($navPosition >= $navTotal)
                                style="display:flex;align-items:center;justify-content:center;width:26px;height:26px;border-radius:6px;border:1px solid #e5e7eb;background:#fff;color:#374151;cursor:pointer;"
                                title="Guardar y siguiente">
                            <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                        </button>
                    </div>
                    @endif
                    <button type="button" wire:click="closeModal()"
                            class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>

            <form onsubmit="return false">
                <div class="px-4 sm:px-6 py-4 sm:py-5 overflow-y-auto" style="max-height: 70vh;">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-5">

                        {{-- COLUMNA IZQUIERDA --}}
                        <div class="space-y-5">

                            {{-- Nombre --}}
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">{{ __('admin.product_form.label_name') }}</label>
                                <input type="text" wire:model="name" placeholder="{{ __('admin.product_form.placeholder_name') }}"
                                       class="w-full border border-gray-200 rounded-xl py-2 px-3 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-amber-300 focus:border-transparent shadow-sm">
                                @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>

                            {{-- Categoría + Maridaje --}}
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">{{ __('admin.product_form.label_category') }}</label>
                                    @if($categories->isEmpty())
                                        <div class="mb-2 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-900">
                                            <p class="font-semibold">{{ __('admin.product_form.no_categories_title') }}</p>
                                            <p class="mt-0.5">{{ __('admin.product_form.no_categories_body') }}</p>
                                            <a href="{{ route('category_list') }}"
                                               class="mt-2 inline-flex items-center rounded-lg border border-amber-300 bg-white px-2.5 py-1 font-semibold text-amber-900 hover:bg-amber-100 transition-colors">
                                                {{ __('admin.product_form.go_to_categories') }}
                                            </a>
                                        </div>
                                    @endif
                                    <select wire:model="category_id"
                                            class="w-full border border-gray-200 rounded-xl py-2 px-3 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-amber-300 focus:border-transparent shadow-sm">
                                        <option value="">{{ __('admin.product_form.select_category') }}</option>
                                        @foreach($categories as $category)
                                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('category_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">{{ __('admin.product_form.label_pairing') }}</label>
                                    <select wire:model="pairing_id"
                                            class="w-full border border-gray-200 rounded-xl py-2 px-3 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-amber-300 focus:border-transparent shadow-sm">
                                        <option value="">{{ __('admin.product_form.pairing_none') }}</option>
                                        @foreach($pairings as $pairing)
                                            <option value="{{ $pairing->id }}">{{ $pairing->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            {{-- Precios --}}
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">{{ __('admin.product_form.label_price') }}</label>
                                    <div class="flex items-center border border-gray-200 rounded-xl shadow-sm focus-within:ring-2 focus-within:ring-amber-300 focus-within:border-transparent">
                                        <input type="text" wire:model="price" placeholder="0.00"
                                               class="flex-1 py-2 px-3 text-sm text-gray-800 bg-transparent focus:outline-none rounded-l-xl">
                                        @if(!\App\Models\Restaurant::priceContainsSymbol((string)($price ?? '')))
                                        <span class="pr-3 text-sm text-gray-400 select-none">{{ $currencySymbol ?? '€' }}</span>
                                        @endif
                                    </div>
                                    @error('price') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">{{ __('admin.product_form.label_offer_price') }}</label>
                                    <div class="flex items-center border border-gray-200 rounded-xl shadow-sm focus-within:ring-2 focus-within:ring-amber-300 focus-within:border-transparent">
                                        <input type="text" wire:model="offer_price" placeholder="0.00"
                                               class="flex-1 py-2 px-3 text-sm text-gray-800 bg-transparent focus:outline-none rounded-l-xl">
                                        @if(!\App\Models\Restaurant::priceContainsSymbol((string)($offer_price ?? '')))
                                        <span class="pr-3 text-sm text-gray-400 select-none">{{ $currencySymbol ?? '€' }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            {{-- Oferta --}}
                            @if($canUseOffers ?? true)
                            <div class="admin-inset admin-inset--danger p-3 space-y-2.5">
                                <label class="flex items-center gap-2.5 cursor-pointer">
                                    <input type="checkbox" wire:model="offer"
                                           class="form-checkbox w-4 h-4 rounded text-red-500 border-gray-300 focus:ring-red-300 cursor-pointer">
                                    <span class="text-sm font-semibold text-gray-700">{{ __('admin.product_form.offer_show') }}</span>
                                </label>
                                <div class="grid grid-cols-3 gap-2">
                                    <div>
                                        <label class="block text-xs text-gray-500 mb-1">{{ __('admin.product_form.offer_badge') }}</label>
                                        <input type="text" wire:model="offer_badge" placeholder="{{ __('admin.products.badge_offer') }}" maxlength="20"
                                               class="w-full border border-red-200 rounded-lg py-1.5 px-2 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-red-200 bg-white">
                                    </div>
                                    <div>
                                        <label class="block text-xs text-gray-500 mb-1">{{ __('admin.product_form.offer_start') }}</label>
                                        <input type="date" wire:model="offer_start"
                                               class="w-full border border-red-200 rounded-lg py-1.5 px-2 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-red-200 bg-white">
                                    </div>
                                    <div>
                                        <label class="block text-xs text-gray-500 mb-1">{{ __('admin.product_form.offer_end') }}</label>
                                        <input type="date" wire:model="offer_end"
                                               class="w-full border border-red-200 rounded-lg py-1.5 px-2 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-red-200 bg-white">
                                    </div>
                                </div>
                            </div>
                            @else
                            <div class="admin-inset p-3 flex items-center gap-2 text-xs text-gray-400 border border-gray-200 rounded-xl bg-gray-50">
                                <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/></svg>
                                <span>{{ __('admin.plan.offers_required') }}</span>
                            </div>
                            @endif

                            {{-- Visibilidad comercial (carta pública) --}}
                            <div class="admin-inset admin-inset--info p-3 space-y-2.5">
                                <p class="text-xs font-semibold uppercase tracking-wide flex items-center gap-1.5">
                                    <span aria-hidden="true">💡</span> {{ __('admin.product_form.visibility_title') }}
                                </p>
                                <p class="text-xs opacity-90">{!! __('admin.product_form.visibility_hint', [
                                    'featured' => '<strong>'.e(__('admin.product_form.visibility_featured_word')).'</strong>',
                                    'recommended' => '<strong>'.e(__('admin.product_form.visibility_recommended_word')).'</strong>',
                                    'offer' => '<strong>'.e(__('admin.product_form.visibility_offer_word')).'</strong>',
                                ]) !!}</p>
                                @if($canUseOffers ?? true)
                                <label class="flex items-center gap-2.5 cursor-pointer">
                                    <input type="checkbox" wire:model="featured"
                                           class="form-checkbox w-4 h-4 rounded text-amber-600 border-gray-300 focus:ring-amber-300 cursor-pointer">
                                    <span class="text-sm font-medium text-gray-800">{{ __('admin.product_form.featured') }}</span>
                                </label>
                                @else
                                <label class="flex items-center gap-2.5 opacity-40 cursor-not-allowed" title="{{ __('admin.plan.offers_required') }}">
                                    <input type="checkbox" disabled class="form-checkbox w-4 h-4 rounded text-amber-600 border-gray-300 cursor-not-allowed">
                                    <span class="text-sm font-medium text-gray-800">{{ __('admin.product_form.featured') }}</span>
                                </label>
                                @endif
                                <label class="flex items-center gap-2.5 cursor-pointer">
                                    <input type="checkbox" wire:model="recommended"
                                           class="form-checkbox w-4 h-4 rounded text-amber-600 border-gray-300 focus:ring-amber-300 cursor-pointer">
                                    <span class="text-sm font-medium text-gray-800">{{ __('admin.product_form.recommended_client') }}</span>
                                </label>
                            </div>

                            {{-- Foto --}}
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">{{ __('admin.product_form.label_photo') }}</label>
                                <div class="flex items-start gap-3">
                                    @if($photo && !$filename)
                                        <div class="flex flex-col items-center gap-1">
                                            <img src="{{ \App\Support\ProductPhotoUrl::publicUrl($photo) }}" alt=""
                                                 class="w-14 h-14 rounded-xl object-cover border border-gray-200 shadow-sm flex-shrink-0">
                                            @if($product_id)
                                                <button type="button" wire:click="confirmRemoveProductPhoto"
                                                        class="text-xs text-red-500 hover:text-red-700 underline cursor-pointer">
                                                    {{ __('admin.product_form.remove_photo') }}
                                                </button>
                                            @endif
                                        </div>
                                    @endif
                                    @if($filename)
                                        <img src="{{ $filename->temporaryUrl() }}" alt=""
                                             class="w-14 h-14 rounded-xl object-cover border border-gray-200 shadow-sm flex-shrink-0">
                                    @endif
                                    <input type="file" wire:model="filename" accept="image/jpeg,image/png,image/webp"
                                           class="flex-1 text-sm text-gray-500 border border-gray-200 rounded-xl py-2 px-3 bg-white file:mr-2 file:py-1 file:px-2 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-amber-50 file:text-amber-700 hover:file:bg-amber-100 cursor-pointer">
                                </div>
                                <div class="mt-3 flex flex-wrap items-center gap-2">
                                    @if(($canUseAi ?? false) && $product_id && !$photo)
                                        <button type="button" wire:click="confirmGenerateCurrentProductPhotoWithAi" wire:loading.attr="disabled" wire:target="confirmGenerateCurrentProductPhotoWithAi,generateCurrentProductPhotoWithAi,confirmAiAction"
                                                class="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold bg-green-50 border-2 border-green-600 hover:border-green-700 hover:bg-green-100 text-gray-800 transition-colors cursor-pointer">
                                            <span wire:loading.remove wire:target="generateCurrentProductPhotoWithAi">{{ __('admin.product_form.gen_image_ai') }} · {{ $aiGenerateCost }}</span>
                                            <span wire:loading wire:target="generateCurrentProductPhotoWithAi">{{ __('admin.products.generating') }}</span>
                                        </button>
                                        <div wire:loading.flex wire:target="confirmAiAction,generateCurrentProductPhotoWithAi"
                                             style="display:none;"
                                             class="items-center gap-3 min-w-[18rem] flex-1 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2">
                                            <div class="product-ai-progress-track flex-1" role="progressbar" aria-valuetext="{{ __('admin.product_form.gen_image_ai') }}">
                                                <div class="product-ai-progress-fill"></div>
                                            </div>
                                            <span class="text-xs font-medium text-amber-900 whitespace-nowrap">{{ __('admin.products.generating') }}</span>
                                        </div>
                                    @endif
                                    @if(($canUseAi ?? false) && $product_id && $photo)
                                        <button type="button" wire:click="confirmImproveCurrentProductPhotoWithAi" wire:loading.attr="disabled" wire:target="confirmImproveCurrentProductPhotoWithAi,improveCurrentProductPhotoWithAi,confirmAiAction"
                                                class="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold bg-green-50 border-2 border-green-600 hover:border-green-700 hover:bg-green-100 text-gray-800 transition-colors cursor-pointer">
                                            <span wire:loading.remove wire:target="improveCurrentProductPhotoWithAi">{{ __('admin.product_form.fix_image_ai') }} · {{ $aiImproveCost }}</span>
                                            <span wire:loading wire:target="improveCurrentProductPhotoWithAi">{{ __('admin.product_form.fixing') }}</span>
                                        </button>
                                        <div wire:loading.flex wire:target="confirmAiAction,improveCurrentProductPhotoWithAi"
                                             style="display:none;"
                                             class="items-center gap-3 min-w-[18rem] flex-1 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2">
                                            <div class="product-ai-progress-track flex-1" role="progressbar" aria-valuetext="{{ __('admin.product_form.fix_image_ai') }}">
                                                <div class="product-ai-progress-fill"></div>
                                            </div>
                                            <span class="text-xs font-medium text-amber-900 whitespace-nowrap">{{ __('admin.product_form.fixing') }}</span>
                                        </div>
                                    @endif
                                    @if(($canUseAi ?? false) && !$product_id)
                                        <p class="text-xs text-gray-400">{{ __('admin.product_form.save_first_for_ai') }}</p>
                                    @endif
                                </div>
                                @if(($canUseAi ?? false) && $product_id)
                                    <div class="mt-2 rounded-xl border {{ $aiCredits['uses_client_key'] || $aiCredits['is_demo_unlimited'] ? 'border-sky-200 bg-sky-50 text-sky-800' : 'border-gray-200 bg-gray-50 text-gray-700' }} px-3 py-2 text-xs">
                                        <span class="font-semibold">Saldo IA:</span> {{ $aiCredits['label'] }}
                                        @if($aiCredits['uses_client_key'])
                                            <span class="ml-1">Se está usando la API key del cliente. No se descuentan créditos.</span>
                                        @elseif($aiCredits['is_demo_unlimited'])
                                            <span class="ml-1">Esta demo no descuenta créditos.</span>
                                        @else
                                            <span class="ml-1">Generar: {{ $aiGenerateCost }} · Arreglar: {{ $aiImproveCost }}</span>
                                        @endif
                                    </div>
                                @endif
                                @error('filename') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>

                        </div>

                        {{-- COLUMNA DERECHA --}}
                        <div class="space-y-5">

                            {{-- Descripción --}}
                            <div>
                                <div class="mb-1.5 flex items-center justify-between gap-3">
                                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide">{{ __('admin.product_form.label_description') }}</label>
                                    @if ($canUseAi ?? false)
                                    <button type="button" wire:click="confirmGenerateDescriptionWithAi" wire:loading.attr="disabled" wire:target="confirmGenerateDescriptionWithAi,generateDescriptionWithAi,confirmAiAction"
                                            class="inline-flex items-center gap-2 rounded-lg border-2 border-green-600 bg-green-50 px-3 py-1.5 text-xs font-semibold text-gray-700 hover:border-green-700 hover:bg-green-100">
                                        <span wire:loading.remove wire:target="generateDescriptionWithAi,confirmAiAction">{{ __('admin.product_form.generate_description_ai') }} · {{ $aiDescriptionCost }}</span>
                                        <span wire:loading wire:target="generateDescriptionWithAi,confirmAiAction">{{ __('admin.product_form.generating_description_ai') }}</span>
                                    </button>
                                    @endif
                                </div>
                                <p class="mb-1.5 text-xs {{ $aiWritingGuideConnected ? 'text-sky-600' : 'text-gray-400' }}">
                                    @if($aiWritingGuideConnected)
                                        Se usara la guia conectada del restaurante para el tono y el estilo.
                                    @else
                                        Si conectas una guia de estilo del restaurante, la IA la tendra en cuenta aqui.
                                    @endif
                                </p>
                                <textarea wire:model="description" rows="4" placeholder="{{ __('admin.product_form.placeholder_description') }}"
                                          class="w-full border border-gray-200 rounded-xl py-2 px-3 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-amber-300 focus:border-transparent shadow-sm resize-none"></textarea>
                                <div wire:loading wire:target="generateDescriptionWithAi,confirmAiAction" style="position:relative;height:2rem;margin-top:0.375rem;">
                                    <div style="position:absolute;top:50%;transform:translateY(-50%);left:0;right:0;" class="product-ai-progress-track">
                                        <div class="product-ai-progress-fill"></div>
                                    </div>
                                    <div class="product-ai-butterfly-wrap">
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
                            </div>

                            {{-- Alérgenos --}}
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">{{ __('admin.product_form.label_allergens') }}</label>
                                <div class="border border-gray-200 rounded-xl p-3 max-h-44 overflow-y-auto bg-gray-50 grid grid-cols-2 gap-x-3 gap-y-2">
                                    @forelse($allergens as $allergen)
                                        <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                                            <input type="checkbox" wire:model="selectedAllergens" value="{{ $allergen->id }}"
                                                   class="rounded border-gray-300 text-amber-500 focus:ring-amber-300 cursor-pointer flex-shrink-0">
                                            @if($allergen->image)
                                                <img src="{{ $allergen->image_url }}" alt="" class="w-5 h-5 rounded object-cover flex-shrink-0">
                                            @endif
                                            <span class="truncate">{{ $allergen->name }}</span>
                                        </label>
                                    @empty
                                        <p class="text-sm text-gray-400 col-span-2">{{ __('admin.product_form.allergens_empty') }}</p>
                                    @endforelse
                                </div>
                                @error('selectedAllergens') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>

                            {{-- Texto alérgenos --}}
                            <div>
                                <div class="mb-1.5 flex items-center justify-between gap-3">
                                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide">{{ __('admin.product_form.label_aller_text') }}</label>
                                    @if ($canUseAi ?? false)
                                    <button type="button" wire:click="confirmGenerateAllergenTextWithAi" wire:loading.attr="disabled" wire:target="confirmGenerateAllergenTextWithAi,generateAllergenTextWithAi,confirmAiAction"
                                            class="inline-flex items-center gap-2 rounded-lg border-2 border-green-600 bg-green-50 px-3 py-1.5 text-xs font-semibold text-gray-700 hover:border-green-700 hover:bg-green-100">
                                        <span wire:loading.remove wire:target="generateAllergenTextWithAi,confirmAiAction">{{ __('admin.product_form.generate_allergen_text_ai') }} · {{ $aiAllergenTextCost }}</span>
                                        <span wire:loading wire:target="generateAllergenTextWithAi,confirmAiAction">{{ __('admin.product_form.generating_allergen_text_ai') }}</span>
                                    </button>
                                    @endif
                                </div>
                                <p class="text-xs text-gray-400 mb-1.5">{{ __('admin.product_form.aller_hint') }}</p>
                                <textarea wire:model="aller" rows="4"
                                          placeholder="{{ __('admin.product_form.aller_placeholder') }}"
                                          class="w-full border border-gray-200 rounded-xl py-2 px-3 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-amber-300 focus:border-transparent shadow-sm resize-none"></textarea>
                                <div wire:loading wire:target="generateAllergenTextWithAi,confirmAiAction" style="position:relative;height:2rem;margin-top:0.375rem;">
                                    <div style="position:absolute;top:50%;transform:translateY(-50%);left:0;right:0;" class="product-ai-progress-track">
                                        <div class="product-ai-progress-fill"></div>
                                    </div>
                                    <div class="product-ai-butterfly-wrap">
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
                                @error('aller') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>

                        </div>

                    </div>
                </div>

                {{-- Footer --}}
                <div class="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-between gap-3 px-4 sm:px-6 py-4 border-t border-gray-100 bg-gray-50 rounded-b-2xl">
                    <div class="flex justify-center sm:justify-start">
                        @if($product_id)
                            <button type="button"
                                    wire:click="confirmDeleteCurrentProduct"
                                    wire:loading.attr="disabled"
                                    wire:target="confirmDeleteCurrentProduct,deleteProductConfirmed"
                                    class="px-4 py-2 text-sm font-semibold text-red-600 bg-white border border-red-200 hover:bg-red-50 rounded-xl transition-colors cursor-pointer">
                                {{ __('admin.product_form.delete_product') }}
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
                            {{ __('admin.product_form.save_keep_open') }}
                        </button>
                        <button type="button" wire:click="storeAndClose"
                                wire:loading.attr="disabled"
                                wire:target="store,storeAndClose"
                                class="px-5 py-2 text-sm font-semibold text-white bg-green-500 hover:bg-green-600 rounded-xl shadow-sm transition-colors cursor-pointer">
                            {{ __('admin.product_form.save_and_close') }}
                        </button>
                    </div>
                </div>
            </form>

        </div>
    </div>
</div>
