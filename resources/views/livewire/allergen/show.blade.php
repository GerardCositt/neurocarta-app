<x-slot name="header">
    <h2 class="text-xl font-semibold text-gray-800">{{ __('admin.allergen_page.header') }}</h2>
</x-slot>

<div
    data-lw-close-list-root
    data-lw-close-method="closeExpandedLinkedProductsList"
    data-lw-expanded="{{ $expandedLinkedProductsAllergenId ? '1' : '' }}"
>
    <div class="-mx-4 sm:-mx-6 lg:-mx-8 px-4 sm:px-6 lg:px-8">

    @if (session()->has('message'))
        <x-admin.banner variant="success">{{ session('message') }}</x-admin.banner>
    @endif

    <div class="flex justify-between items-center mb-5">
        <div>
            <input wire:model.debounce.400ms="q"
                   type="search"
                   size="25"
                   placeholder="{{ __('admin.allergen_page.search_placeholder') }}"
                   class="border border-gray-200 bg-white rounded-xl py-2 px-4 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-amber-300 focus:border-transparent shadow-sm" />
        </div>

        <button wire:click="openForm()"
                class="bg-green-500 hover:bg-green-600 text-white text-sm font-semibold py-2 px-4 rounded-xl shadow-sm transition-colors flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            {{ __('admin.allergen_page.add') }}
        </button>
    </div>

    @if($isOpen)
        <div id="lw-allergen-edit-modal-root" wire:key="allergen-edit-modal-{{ $allergen_id ?: 'new' }}" wire:click.stop>
            @include('livewire.allergenfrm')
        </div>
    @endif

    @if($msgError)
        <div class="admin-banner admin-banner--danger mb-4" role="alert">
            <span class="admin-banner__icon" aria-hidden="true">❌</span>
            <div class="admin-banner__content flex items-start justify-between gap-3">
                <span>{{ $msgError }}</span>
                <button type="button" wire:click="$set('msgError', null)" class="opacity-60 hover:opacity-100 flex-shrink-0 font-bold text-base leading-none">✕</button>
            </div>
        </div>
    @endif

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden" data-lw-no-close-list wire:click.stop>
        <div class="overflow-x-auto overscroll-x-contain sm:overflow-visible">
        <table class="w-full min-w-full">
            <thead>
                <tr class="border-b border-gray-100">
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider w-16">{{ __('admin.allergen_page.th_image') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">{{ __('admin.allergen_page.th_name') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">{{ __('admin.allergen_page.th_products') }}</th>
                    <th class="w-24 px-4 py-3 text-center text-xs font-semibold text-gray-400 uppercase tracking-wider">{{ __('admin.allergen_page.th_hide') }}</th>
                </tr>
            </thead>
            <tbody>
            @forelse($allergens as $allergen)
                <tr wire:key="allergen-row-{{ $allergen->id }}" class="border-b border-gray-50 hover:bg-gray-50 transition-colors">
                    <td class="px-4 py-3 align-top">
                        @if($allergen->image)
                            <img src="{{ $allergen->image_url }}"
                                 alt=""
                                 class="w-10 h-10 object-cover rounded-lg border border-gray-200">
                        @else
                            <div class="w-10 h-10 rounded-lg border border-dashed border-gray-200 flex items-center justify-center">
                                <svg class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                        @endif
                    </td>
                    <td class="px-4 py-3 align-top">
                        <button type="button"
                                wire:click="edit({{ $allergen->id }})"
                                title="{{ __('admin.allergen_page.name_open_sheet') }}"
                                class="text-sm font-medium text-left {{ $allergen->active ? 'line-through text-gray-400' : 'text-gray-800' }} cursor-pointer hover:text-amber-800 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-400 focus-visible:ring-offset-1 rounded-sm bg-transparent border-0 p-0 max-w-full">
                            {{ $allergen->name }}
                        </button>
                    </td>
                    <td class="px-4 py-3 align-top">
                        <button type="button"
                                wire:click.stop="toggleLinkedProductsList({{ $allergen->id }})"
                                style="width: fit-content;"
                                class="text-xs px-1.5 py-0.5 rounded-full border whitespace-nowrap transition-colors
                                       {{ $expandedLinkedProductsAllergenId === $allergen->id
                                          ? 'bg-amber-100 border-amber-300 text-amber-700 font-semibold'
                                          : 'border-gray-200 text-gray-400 hover:border-amber-300 hover:text-amber-600' }}">
                            {{ trans_choice('admin.allergen_page.product_count', $allergen->products_count) }}
                        </button>
                    </td>
                    <td class="w-24 px-4 py-3 text-center align-top">
                        <label class="inline-flex items-center cursor-pointer">
                            <input type="checkbox"
                                   wire:click.stop="toggleState({{ $allergen->id }})"
                                   {{ $allergen->active ? 'checked' : '' }}
                                   class="form-checkbox w-4 h-4 rounded text-gray-400 border-gray-300 focus:ring-gray-300 cursor-pointer">
                        </label>
                    </td>
                </tr>

                @if($expandedLinkedProductsAllergenId === $allergen->id)
                    <tr wire:key="allergen-linked-list-{{ $allergen->id }}">
                        <td colspan="4" class="admin-inset admin-inset--info border-t border-gray-200 px-5 py-4">
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">
                                {{ __('admin.allergen_page.linked_with', ['name' => $allergen->name]) }}
                            </p>
                            @if($expandedLinkedProducts->isEmpty())
                                <p class="text-sm text-gray-400">{{ __('admin.allergen_page.no_linked') }}</p>
                            @else
                                <ul class="space-y-1 max-h-60 overflow-y-auto pr-1">
                                    @foreach($expandedLinkedProducts as $p)
                                        <li class="flex items-center gap-3 py-1.5 border-b border-amber-100 last:border-0">
                                            <img src="{{ $p->photo ? asset('storage/'.$p->photo) : asset('img/noimg.png') }}"
                                                 alt=""
                                                 class="w-8 h-8 rounded-lg object-cover bg-gray-100 flex-shrink-0">
                                            <a href="{{ route('product', ['edit' => $p->id, 'from' => 'allergen']) }}"
                                               class="text-sm text-gray-800 hover:text-amber-700 hover:underline {{ $p->active ? 'line-through text-gray-400' : '' }}">
                                                {{ $p->name }}
                                            </a>
                                            <span class="text-xs text-gray-400">— {{ optional($p->category)->name ?? __('admin.allergen_page.uncategorized') }}</span>
                                            @if($p->offer)
                                                <span class="text-xs bg-red-100 text-red-600 font-semibold px-1.5 py-0.5 rounded-full">{{ __('admin.allergen_page.badge_offer') }}</span>
                                            @endif
                                            <span class="ml-auto text-xs font-semibold text-gray-500 flex-shrink-0">{{ $p->price }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                            <button type="button" wire:click="$set('expandedLinkedProductsAllergenId', null)"
                                    class="mt-3 text-xs text-gray-400 hover:text-gray-600 underline cursor-pointer">
                                {{ __('admin.allergen_page.close_list') }}
                            </button>
                        </td>
                    </tr>
                @endif
            @empty
                <tr>
                    <td colspan="4" class="text-center py-12 text-sm text-gray-400">
                        @if($q) {{ __('admin.allergen_page.empty_search', ['q' => $q]) }}
                        @else {{ __('admin.allergen_page.empty_none') }}
                        @endif
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
        </div>
    </div>

    </div>

    @if($showRemoveAllergenImageModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center" wire:click.stop>
        <div class="absolute inset-0 bg-black bg-opacity-50" wire:click="cancelRemoveAllergenImage"></div>
        <div class="relative bg-white rounded-2xl shadow-xl p-6 max-w-md w-full mx-4">
            <h3 class="text-lg font-semibold text-gray-800 mb-2">{{ __('admin.allergen_page.modal_remove_image_title') }}</h3>
            <p class="text-sm text-gray-600 mb-6">{{ __('admin.allergen_page.modal_remove_image_body') }}</p>
            <div class="flex justify-end gap-3">
                <button wire:click="cancelRemoveAllergenImage" wire:loading.attr="disabled"
                        class="px-4 py-2 text-sm text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-xl transition-colors cursor-pointer">
                    {{ __('admin.allergen_page.cancel_title') }}
                </button>
                <button wire:click="deleteImageConfirmed" wire:loading.attr="disabled"
                        class="px-4 py-2 text-sm text-white bg-red-500 hover:bg-red-600 rounded-xl transition-colors font-semibold cursor-pointer">
                    {{ __('admin.allergen_page.modal_remove_image_confirm') }}
                </button>
            </div>
        </div>
    </div>
    @endif

    @if($confirmingAllergenDeletion)
    <div class="fixed inset-0 z-50 flex items-center justify-center" wire:click.stop>
        <div class="absolute inset-0 bg-black bg-opacity-50" wire:click="$set('confirmingAllergenDeletion', false)"></div>
        <div class="relative bg-white rounded-2xl shadow-xl p-6 max-w-md w-full mx-4">
            <h3 class="text-lg font-semibold text-gray-800 mb-2">{{ __('admin.allergen_page.modal_delete') }}</h3>
            <p class="text-sm text-gray-600 mb-6">{{ __('admin.allergen_page.modal_delete_body') }}</p>
            <div class="flex justify-end gap-3">
                <button wire:click="$set('confirmingAllergenDeletion', false)" wire:loading.attr="disabled"
                        class="px-4 py-2 text-sm text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-xl transition-colors cursor-pointer">
                    {{ __('admin.allergen_page.cancel_title') }}
                </button>
                <button wire:click="deleteAllergenConfirmed" wire:loading.attr="disabled"
                        class="px-4 py-2 text-sm text-white bg-red-500 hover:bg-red-600 rounded-xl transition-colors font-semibold cursor-pointer">
                    {{ __('admin.allergen_page.modal_confirm') }}
                </button>
            </div>
        </div>
    </div>
    @endif

</div>
