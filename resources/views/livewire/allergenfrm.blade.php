<div class="fixed inset-0 z-50 overflow-y-auto" wire:click.self="closeForm()">

    <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" wire:click="closeForm()"></div>

    {{-- Mismo ancho y comportamiento que maridaje / producto (max-w-4xl) --}}
    <div class="flex min-h-full items-center justify-center p-4" wire:click="closeForm()">
        <div class="relative bg-white rounded-2xl shadow-xl ring-1 ring-gray-100 w-full max-w-4xl overflow-hidden"
             wire:click.stop
             role="dialog" aria-modal="true">

            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <h2 class="text-base font-semibold text-gray-800">
                    {{ $allergen_id ? __('admin.allergen_page.edit_title') : __('admin.allergen_page.new_title') }}
                </h2>
                <button type="button" wire:click="closeForm()"
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
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">{{ __('admin.allergen_page.th_name') }}</label>
                            <input type="text" wire:model="name" placeholder="{{ __('admin.allergen_page.name_placeholder') }}"
                                   class="w-full border border-gray-200 rounded-xl py-2 px-3 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-amber-300 focus:border-transparent shadow-sm">
                            @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">{{ __('admin.allergen_page.image_optional') }}</label>
                            <div class="flex flex-wrap items-center gap-3">
                                <input type="file" wire:model="image"
                                       accept="image/jpeg,image/png,image/gif"
                                       class="text-sm text-gray-600 border border-gray-200 rounded-xl py-1.5 px-3 bg-white max-w-full">
                                @if($image)
                                    <img src="{{ $image->temporaryUrl() }}" alt="" class="w-12 h-12 object-cover rounded-lg border border-gray-200 flex-shrink-0">
                                @elseif($editingAllergenImageUrl)
                                    <img src="{{ $editingAllergenImageUrl }}" alt="" class="w-12 h-12 object-cover rounded-lg border border-gray-200 flex-shrink-0">
                                    <button type="button"
                                            wire:click="confirmRemoveAllergenImage"
                                            wire:loading.attr="disabled"
                                            wire:target="confirmRemoveAllergenImage,deleteImageConfirmed"
                                            class="text-xs font-semibold text-orange-600 hover:text-orange-800 underline">
                                        {{ __('admin.allergen_page.remove_image') }}
                                    </button>
                                @endif
                            </div>
                            @error('image') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        <label class="flex items-center gap-2.5 cursor-pointer">
                            <input type="checkbox" wire:model="active"
                                   class="form-checkbox w-4 h-4 rounded text-gray-400 border-gray-300 focus:ring-gray-300 cursor-pointer">
                            <span class="text-sm font-medium text-gray-700">{{ __('admin.allergen_page.label_hide') }}</span>
                        </label>

                        <div class="border-t border-gray-100 pt-5">
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">{{ __('admin.allergen_page.link_products') }}</p>
                            <p class="text-xs text-gray-500 mb-3">{{ __('admin.allergen_page.link_panel_hint') }}</p>
                            <div class="mb-3">
                                <input type="search"
                                       wire:model.debounce.300ms="linkProductQ"
                                       placeholder="{{ __('admin.allergen_page.link_search_placeholder') }}"
                                       class="w-full max-w-md border border-gray-200 bg-white rounded-xl py-2 px-4 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-amber-300 focus:border-transparent shadow-sm" />
                            </div>
                            <div class="max-h-52 overflow-y-auto rounded-xl border border-gray-100 bg-gray-50/80 p-3 grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-2">
                                @forelse($productsForLink as $product)
                                    <label class="flex items-start gap-2 text-sm text-gray-700 cursor-pointer">
                                        <input type="checkbox"
                                               wire:model="linkedProductIds"
                                               value="{{ $product->id }}"
                                               class="mt-1 rounded border-gray-300 text-amber-600 focus:ring-amber-500">
                                        <span>
                                            <span class="font-medium">{{ $product->name }}</span>
                                            <span class="text-gray-400 text-xs"> — {{ optional($product->category)->name ?? __('admin.allergen_page.uncategorized') }}</span>
                                        </span>
                                    </label>
                                @empty
                                    <p class="text-sm text-gray-400 col-span-full py-2">
                                        @if($linkProductQ !== '')
                                            {{ __('admin.allergen_page.link_empty_search', ['q' => $linkProductQ]) }}
                                        @else
                                            {{ __('admin.allergen_page.link_empty') }}
                                        @endif
                                    </p>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Pie: mismo patrón que maridaje / producto --}}
                <div class="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-between gap-3 px-6 py-4 border-t border-gray-100 bg-gray-50 rounded-b-2xl">
                    <div class="flex justify-center sm:justify-start">
                        @if($allergen_id)
                            <button type="button"
                                    wire:click="confirmDeleteCurrentAllergen"
                                    wire:loading.attr="disabled"
                                    wire:target="confirmDeleteCurrentAllergen,deleteAllergenConfirmed"
                                    class="px-4 py-2 text-sm font-semibold text-red-600 bg-white border border-red-200 hover:bg-red-50 rounded-xl transition-colors cursor-pointer">
                                {{ __('admin.allergen_page.delete_allergen') }}
                            </button>
                        @endif
                    </div>
                    <div class="flex flex-wrap items-center justify-end gap-3">
                        <button type="button" wire:click="closeForm()"
                                class="px-4 py-2 text-sm text-gray-600 bg-white border border-gray-200 hover:bg-gray-50 rounded-xl transition-colors cursor-pointer">
                            {{ __('admin.actions.cancel') }}
                        </button>
                        <button type="button" wire:click="save"
                                wire:loading.attr="disabled"
                                wire:target="save,storeAndClose"
                                class="px-5 py-2 text-sm font-semibold text-gray-800 bg-white border border-gray-200 hover:bg-gray-50 rounded-xl shadow-sm transition-colors cursor-pointer">
                            {{ __('admin.allergen_page.save_keep_open') }}
                        </button>
                        <button type="button" wire:click="storeAndClose"
                                wire:loading.attr="disabled"
                                wire:target="save,storeAndClose"
                                class="px-5 py-2 text-sm font-semibold text-white bg-green-500 hover:bg-green-600 rounded-xl shadow-sm transition-colors cursor-pointer">
                            {{ __('admin.allergen_page.save_and_close') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
