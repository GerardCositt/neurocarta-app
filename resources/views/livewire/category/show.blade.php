<x-slot name="header">
    <h2 class="text-xl font-semibold text-gray-800">{{ __('admin.category_page.header') }}</h2>
</x-slot>

<div
    data-lw-close-list-root
    data-lw-close-method="closeExpandedProductList"
    data-lw-expanded="{{ $expandedCategoryId ? '1' : '' }}"
>
    <div class="-mx-4 sm:-mx-6 lg:-mx-8 px-4 sm:px-6 lg:px-8">

    @if (session()->has('message'))
        <x-admin.banner variant="success">{{ session('message') }}</x-admin.banner>
    @endif

    {{-- Barra de acciones --}}
    <div class="flex justify-between items-center mb-5">
        <div>
            <input wire:model.debounce.400ms="q"
                   type="search"
                   size="25"
                   placeholder="{{ __('admin.category_page.search_placeholder') }}"
                   class="border border-gray-200 bg-white rounded-xl py-2 px-4 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-amber-300 focus:border-transparent shadow-sm" />
        </div>

        <button wire:click="openForm()"
                class="bg-green-500 hover:bg-green-600 text-white text-sm font-semibold py-2 px-4 rounded-xl shadow-sm transition-colors flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            {{ __('admin.category_page.add') }}
        </button>
    </div>

    @if($isOpen)
        <div id="lw-category-edit-modal-root" wire:key="category-edit-modal-{{ $category_id ?: 'new' }}" wire:click.stop>
            @include('livewire.categoryfrm')
        </div>
    @endif

    {{-- Mensaje error --}}
    @if($msgError)
        <div class="admin-banner admin-banner--danger mb-4" role="alert">
            <span class="admin-banner__icon" aria-hidden="true">❌</span>
            <div class="admin-banner__content flex items-start justify-between gap-3">
                <span>{{ $msgError }}</span>
                <button type="button" wire:click="$set('msgError', null)" class="opacity-60 hover:opacity-100 flex-shrink-0 font-bold text-base leading-none">✕</button>
            </div>
        </div>
    @endif

    {{-- Lista categorías --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden" data-lw-no-close-list wire:click.stop>
        <div class="overflow-x-auto overscroll-x-contain">
        @if($categories->isNotEmpty())
            {{-- 3 columnas: arrastre | categoría (flex) | ocultar (ancho fijo, alineado con checkboxes) --}}
            <div class="border-b border-gray-100 bg-gray-50/90 px-4 py-2.5 pr-10 sm:pr-12" role="row">
                <div class="flex items-center gap-3 min-w-0">
                    <span class="w-5 shrink-0" aria-hidden="true"></span>
                    <div class="min-w-0 flex-1">
                        <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">{{ __('admin.category_page.th_category') }}</span>
                    </div>
                    <div class="category-col-hide w-20 sm:w-24 shrink-0 flex items-center justify-center border-l border-gray-200 pl-3">
                        <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">{{ __('admin.category_page.th_hide') }}</span>
                    </div>
                </div>
            </div>
        @endif
        <div id="categories-sortable" class="divide-y divide-gray-50">
            @forelse($categories as $category)
                {{-- Fila categoría --}}
                <div wire:key="category-{{ $category->id }}"
                     data-id="{{ $category->id }}"
                     class="flex items-center gap-3 px-4 py-3 pr-10 sm:pr-12 hover:bg-gray-50 transition-colors min-w-0">

                    <span class="text-gray-300 cursor-grab active:cursor-grabbing drag-handle w-5 shrink-0 flex justify-center">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7h16M4 12h16M4 17h16"/>
                        </svg>
                    </span>

                    <div class="flex min-w-0 flex-1 items-center gap-2">
                        <button type="button"
                                wire:click="edit({{ $category->id }})"
                                title="{{ __('admin.category_page.name_open_sheet') }}"
                                class="text-sm font-medium text-left min-w-0 flex-1 truncate {{ $category->active ? 'line-through text-gray-400' : 'text-gray-800' }} cursor-pointer hover:text-amber-800 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-400 focus-visible:ring-offset-1 rounded-sm bg-transparent border-0 p-0">
                            {{ $category->name }}
                        </button>
                        <button type="button" wire:click.stop="toggleProductList({{ $category->id }})"
                                class="text-xs px-2 py-0.5 rounded-full border transition-colors shrink-0
                                       {{ $expandedCategoryId === $category->id
                                          ? 'bg-amber-100 border-amber-300 text-amber-700 font-semibold'
                                          : 'border-gray-200 text-gray-400 hover:border-amber-300 hover:text-amber-600' }}">
                            {{ trans_choice('admin.category_page.product_count', $category->products_count) }}
                        </button>
                    </div>

                    <div class="category-col-hide w-20 sm:w-24 shrink-0 flex items-center justify-center border-l border-gray-100 pl-3">
                        <label class="inline-flex items-center cursor-pointer" title="{{ __('admin.category_page.hide') }}">
                            <input type="checkbox"
                                   wire:click.stop="toggleState({{ $category->id }})"
                                   {{ $category->active ? 'checked' : '' }}
                                   class="form-checkbox w-4 h-4 rounded text-gray-400 border-gray-300 focus:ring-gray-300 cursor-pointer">
                        </label>
                    </div>
                </div>

                {{-- Panel productos expandido: justo debajo de la fila de esta categoría --}}
                @if($expandedCategoryId === $category->id)
                    <div wire:key="cat-products-panel-{{ $category->id }}" class="border-t border-gray-200 admin-inset admin-inset--info px-5 py-4">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">
                            {{ __('admin.category_page.products_in', ['name' => $category->name]) }}
                        </p>
                        @if($expandedProducts->isEmpty())
                            <p class="text-sm text-gray-400">{{ __('admin.category_page.no_products_in_cat') }}</p>
                        @else
                            <ul class="space-y-1 max-h-60 overflow-y-auto pr-1">
                                @foreach($expandedProducts as $product)
                                    <li class="flex items-center gap-3 py-1.5 border-b border-amber-100 last:border-0">
                                        <img src="{{ $product->photo ? asset('storage/'.$product->photo) : asset('img/noimg.png') }}"
                                             alt=""
                                             class="w-8 h-8 rounded-lg object-cover bg-gray-100 flex-shrink-0">
                                        <span class="text-sm {{ $product->active ? 'line-through text-gray-400' : 'text-gray-800' }}">
                                            {{ $product->name }}
                                        </span>
                                        @if($product->offer)
                                            <span class="text-xs bg-red-100 text-red-600 font-semibold px-1.5 py-0.5 rounded-full">{{ __('admin.category_page.badge_offer') }}</span>
                                        @endif
                                        <span class="ml-auto text-xs font-semibold text-gray-500 flex-shrink-0">{{ $product->price }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                        <button type="button" wire:click="$set('expandedCategoryId', null)"
                                class="mt-3 text-xs text-gray-400 hover:text-gray-600 underline cursor-pointer">
                            {{ __('admin.category_page.close_list') }}
                        </button>
                    </div>
                @endif
            @empty
                <div class="text-center py-12 text-sm text-gray-400">
                    @if($q) {{ __('admin.category_page.empty_search', ['q' => $q]) }}
                    @else {{ __('admin.category_page.empty_none') }}
                    @endif
                </div>
            @endforelse
        </div>
        </div>
    </div>

    </div>

    @if($confirmingCategoryDeletion)
    <div class="fixed inset-0 z-50 flex items-center justify-center" wire:click.stop>
        <div class="absolute inset-0 bg-black bg-opacity-50" wire:click="$set('confirmingCategoryDeletion', false)"></div>
        <div class="relative bg-white rounded-2xl shadow-xl p-6 max-w-md w-full mx-4">
            <h3 class="text-lg font-semibold text-gray-800 mb-2">{{ __('admin.category_page.modal_delete') }}</h3>
            <p class="text-sm text-gray-600 mb-6">{{ __('admin.category_page.modal_delete_body') }}</p>
            <div class="flex justify-end gap-3">
                <button wire:click="$set('confirmingCategoryDeletion', false)" wire:loading.attr="disabled"
                        class="px-4 py-2 text-sm text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-xl transition-colors cursor-pointer">
                    {{ __('admin.category_page.cancel_title') }}
                </button>
                <button wire:click="deleteCategoryConfirmed" wire:loading.attr="disabled"
                        class="px-4 py-2 text-sm text-white bg-red-500 hover:bg-red-600 rounded-xl transition-colors font-semibold cursor-pointer">
                    {{ __('admin.category_page.modal_confirm') }}
                </button>
            </div>
        </div>
    </div>
    @endif

</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', initCategoriesSortable);
document.addEventListener('livewire:load', initCategoriesSortable);
document.addEventListener('livewire:update', function () {
    const el = document.getElementById('categories-sortable');
    if (el && el._sortable) {
        el._sortable.destroy();
        el._sortable = null;
    }
    initCategoriesSortable();
});

function initCategoriesSortable() {
    const el = document.getElementById('categories-sortable');
    if (!el || el._sortable) return;

    el._sortable = new Sortable(el, {
        animation: 200,
        handle: '.drag-handle',
        ghostClass: 'bg-amber-50',
        chosenClass: 'shadow-md',
        filter: 'input, button, label',
        preventOnFilter: false,
        onEnd: function () {
            const ids = [...el.children]
                .filter(c => c.dataset.id)
                .map(c => parseInt(c.dataset.id));

            fetch('/api/reorder/categories', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ ids })
            });
        }
    });
}
</script>
@endpush
