<div class="fixed inset-0 z-50 overflow-y-auto" wire:click.self="closeModal()">

    <style>
        @keyframes product-ai-progress-slide {
            0% { left: -40%; }
            100% { left: 100%; }
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
            background: linear-gradient(90deg, #f59e0b, #111827);
            animation: product-ai-progress-slide 1.2s cubic-bezier(0.4, 0, 0.2, 1) infinite;
        }
    </style>

    {{-- Fondo oscuro --}}
    <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" wire:click="closeModal()"></div>

    {{-- Contenedor centrado --}}
    <div class="flex min-h-full items-center justify-center p-4" wire:click="closeModal()">

        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-4xl"
             wire:click.stop
             role="dialog" aria-modal="true">

            {{-- Cabecera --}}
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <h2 class="text-base font-semibold text-gray-800">
                    {{ $product_id ? __('admin.product_form.edit_title') : __('admin.product_form.new_title') }}
                </h2>
                <button type="button" wire:click="closeModal()"
                        class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <form onsubmit="return false">
                <div class="px-6 py-5 overflow-y-auto" style="max-height: 70vh;">
                    <div class="grid grid-cols-2 gap-x-6 gap-y-5">

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
                                    <input type="text" wire:model="price" placeholder="0.00"
                                           class="w-full border border-gray-200 rounded-xl py-2 px-3 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-amber-300 focus:border-transparent shadow-sm">
                                    @error('price') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">{{ __('admin.product_form.label_offer_price') }}</label>
                                    <input type="text" wire:model="offer_price" placeholder="0.00"
                                           class="w-full border border-gray-200 rounded-xl py-2 px-3 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-amber-300 focus:border-transparent shadow-sm">
                                </div>
                            </div>

                            {{-- Oferta --}}
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
                                <label class="flex items-center gap-2.5 cursor-pointer">
                                    <input type="checkbox" wire:model="featured"
                                           class="form-checkbox w-4 h-4 rounded text-amber-600 border-gray-300 focus:ring-amber-300 cursor-pointer">
                                    <span class="text-sm font-medium text-gray-800">{{ __('admin.product_form.featured') }}</span>
                                </label>
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
                                            <img src="{{ asset('storage/'.$photo) }}" alt=""
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
                                    <input type="file" wire:model="filename"
                                           class="flex-1 text-sm text-gray-500 border border-gray-200 rounded-xl py-2 px-3 bg-white file:mr-2 file:py-1 file:px-2 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-amber-50 file:text-amber-700 hover:file:bg-amber-100 cursor-pointer">
                                </div>
                                <div class="mt-3 flex flex-wrap items-center gap-2">
                                    @if($product_id && !$photo)
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
                                    @if($product_id && $photo)
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
                                    @if(!$product_id)
                                        <p class="text-xs text-gray-400">{{ __('admin.product_form.save_first_for_ai') }}</p>
                                    @endif
                                </div>
                                @if($product_id)
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
                                    <button type="button" wire:click="confirmGenerateDescriptionWithAi" wire:loading.attr="disabled" wire:target="confirmGenerateDescriptionWithAi,generateDescriptionWithAi,confirmAiAction"
                                            class="inline-flex items-center gap-2 rounded-lg border-2 border-green-600 bg-green-50 px-3 py-1.5 text-xs font-semibold text-gray-700 hover:border-green-700 hover:bg-green-100">
                                        <span wire:loading.remove wire:target="generateDescriptionWithAi">{{ __('admin.product_form.generate_description_ai') }} · {{ $aiDescriptionCost }}</span>
                                        <span wire:loading wire:target="generateDescriptionWithAi">{{ __('admin.product_form.generating_description_ai') }}</span>
                                    </button>
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
                                    <button type="button" wire:click="confirmGenerateAllergenTextWithAi" wire:loading.attr="disabled" wire:target="confirmGenerateAllergenTextWithAi,generateAllergenTextWithAi,confirmAiAction"
                                            class="inline-flex items-center gap-2 rounded-lg border-2 border-green-600 bg-green-50 px-3 py-1.5 text-xs font-semibold text-gray-700 hover:border-green-700 hover:bg-green-100">
                                        <span wire:loading.remove wire:target="generateAllergenTextWithAi">{{ __('admin.product_form.generate_allergen_text_ai') }} · {{ $aiAllergenTextCost }}</span>
                                        <span wire:loading wire:target="generateAllergenTextWithAi">{{ __('admin.product_form.generating_allergen_text_ai') }}</span>
                                    </button>
                                </div>
                                <p class="text-xs text-gray-400 mb-1.5">{{ __('admin.product_form.aller_hint') }}</p>
                                <textarea wire:model="aller" rows="4"
                                          placeholder="{{ __('admin.product_form.aller_placeholder') }}"
                                          class="w-full border border-gray-200 rounded-xl py-2 px-3 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-amber-300 focus:border-transparent shadow-sm resize-none"></textarea>
                                @error('aller') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>

                        </div>

                    </div>
                </div>

                {{-- Footer --}}
                <div class="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-between gap-3 px-6 py-4 border-t border-gray-100 bg-gray-50 rounded-b-2xl">
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
