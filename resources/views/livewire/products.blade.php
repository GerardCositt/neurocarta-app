<x-slot name="header">
    <h2 class="admin-page-title-carta text-xl font-semibold">{{ __('admin.products.page_title') }}</h2>
</x-slot>

<div>
    {{-- Nonce: Livewire 2 puede omitir effects.html si el CRC32 del markup no cambia; al cerrar la ficha hay que forzar diff. --}}
    <span class="hidden" aria-hidden="true" data-panel-nonce="{{ $panelRenderNonce }}"></span>

    @if (session()->has('message'))
        <x-admin.banner variant="success" :auto-dismiss="5000">{{ session('message') }}</x-admin.banner>
    @endif

    {{-- Barra de acciones --}}
    <div class="flex flex-col gap-4 xl:flex-row xl:justify-between xl:items-start mb-5 min-w-0">
        <div class="flex flex-col sm:flex-row sm:flex-wrap gap-3 min-w-0 flex-1">
            <input wire:model.debounce.500ms="q" type="search"
                   placeholder="{{ __('admin.products.search_placeholder') }}"
                   class="w-full sm:w-auto sm:min-w-[12rem] sm:flex-1 sm:max-w-md border border-gray-200 bg-white rounded-xl py-2 px-4 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-amber-300 focus:border-transparent shadow-sm" />

            <select wire:model="selectedCategory"
                    class="w-full sm:w-auto min-w-0 border border-gray-200 bg-white rounded-xl py-2 px-4 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-amber-300 focus:border-transparent shadow-sm">
                <option value="">{{ __('admin.products.all_categories') }}</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                @endforeach
            </select>

            <select wire:model="commercialFilter" title="{{ __('admin.products.filter_catalog_title') }}"
                    class="w-full sm:w-auto min-w-0 border border-gray-200 bg-white rounded-xl py-2 px-4 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-amber-300 focus:border-transparent shadow-sm sm:max-w-[18rem]">
                <option value="">{{ __('admin.products.filter_all') }}</option>
                <option value="featured">{{ __('admin.products.filter_featured') }}</option>
                <option value="recommended">{{ __('admin.products.filter_recommended') }}</option>
                <option value="offer_flag">{{ __('admin.products.filter_offer_flag') }}</option>
                <option value="offer_active">{{ __('admin.products.filter_offer_active') }}</option>
                <option value="hidden">{{ __('admin.products.filter_hidden') }}</option>
            </select>

            <label class="flex flex-col sm:flex-row sm:items-center gap-2 text-sm text-gray-600 sm:whitespace-nowrap w-full sm:w-auto">
                <span>{{ __('admin.products.per_page') }}</span>
                <select wire:model="perPageOption"
                        class="w-full sm:w-auto border border-gray-200 bg-white rounded-xl py-2 pl-3 pr-10 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-amber-300 focus:border-transparent shadow-sm min-w-0 sm:min-w-[11rem]">
                    <option value="15">{{ __('admin.products.per_page_15') }}</option>
                    <option value="30">{{ __('admin.products.per_page_30') }}</option>
                    <option value="50">{{ __('admin.products.per_page_50') }}</option>
                    <option value="all">{{ __('admin.products.per_page_all') }}</option>
                </select>
            </label>
        </div>

        <details class="relative shrink-0" id="actions-menu-products">
            <summary class="list-none cursor-pointer select-none inline-flex items-center gap-2 bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 text-sm font-semibold py-2 px-4 rounded-xl shadow-sm transition-colors">
                {{ __('admin.products.actions_menu') }}
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                </svg>
            </summary>
            <div class="absolute right-0 mt-2 w-64 rounded-2xl border border-gray-100 bg-white shadow-xl z-50 overflow-hidden py-1">
                <a href="{{ route('settings.import-products') }}"
                   class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                    <svg class="w-4 h-4 text-amber-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                    </svg>
                    {{ __('admin.products.import_csv') }}
                </a>
                <a href="{{ route('settings.import-ai') }}"
                   class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                    <svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18l-1.813-2.096L5 15l2.187-.904L9 12l.813 2.096L12 15l-2.187.904zM18 13l.74 1.704L20.5 15.5l-1.76.796L18 18l-.74-1.704L15.5 15.5l1.76-.796L18 13zM12 3l1.252 2.876L16 7.128l-2.748 1.252L12 11.256 10.748 8.38 8 7.128l2.748-1.252L12 3z"/>
                    </svg>
                    {{ __('admin.products.add_with_ai') }}
                </a>
                <div class="border-t border-gray-100 my-1"></div>
                <button type="button" wire:click="create()" onclick="document.getElementById('actions-menu-products').removeAttribute('open')"
                        class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 transition-colors text-left">
                    <svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                    {{ __('admin.products.add_product') }}
                </button>
                <button type="button" wire:click="confirmGenerateMissingProductPhotos" wire:loading.attr="disabled" wire:target="generateMissingProductPhotos,confirmAiAction"
                        onclick="document.getElementById('actions-menu-products').removeAttribute('open')"
                        class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 transition-colors text-left">
                    <svg class="w-4 h-4 text-amber-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3l1.9 3.85L18 9l-3 2.93.71 4.07L12 14.77 8.29 16 9 11.93 6 9l4.1-2.15L12 3z"/>
                    </svg>
                    <span wire:loading.remove wire:target="generateMissingProductPhotos">{{ __('admin.products.generate_missing_photos') }} · {{ $aiBulkGenerateCost }}</span>
                    <span wire:loading wire:target="generateMissingProductPhotos">{{ __('admin.products.generating') }}</span>
                </button>
            </div>
        </details>
    </div>

    @if($isOpen)
        <div id="lw-product-edit-modal-root" wire:key="product-edit-modal-{{ $panelRenderNonce }}">
            @include('livewire.productfrm')
        </div>
    @endif

    @php
        if ($products instanceof \Illuminate\Contracts\Pagination\Paginator) {
            $pageIds = $products->getCollection()->pluck('id')->map(fn ($id) => (int) $id)->all();
        } else {
            $pageIds = $products->pluck('id')->map(fn ($id) => (int) $id)->all();
        }
        $sel = array_map('intval', $selectedProducts ?? []);
        $pageIntersection = array_intersect($pageIds, $sel);
        $pageAllSelected = count($pageIds) > 0 && count($pageIntersection) === count($pageIds);
        $pageSomeSelected = count($pageIds) > 0 && count($pageIntersection) > 0 && ! $pageAllSelected;
    @endphp

    <div class="admin-bulk-panel flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between sm:gap-4 min-w-0"
         wire:key="bulk-panel">
        <div class="min-w-0 flex-1">
        @if(count($selectedProducts) > 0)
            <div class="admin-bulk-panel__active rounded-xl border border-gray-200 bg-white shadow-sm px-4 py-3">
                <div class="flex flex-col gap-3 lg:flex-row lg:flex-wrap lg:items-center">
                    <p class="text-sm font-semibold text-gray-800 shrink-0">
                        {{ trans_choice('admin.products.selected_count', count($selectedProducts)) }}
                    </p>
                    <div class="flex flex-wrap gap-2 flex-1 min-w-0">
                        <button type="button" wire:click="bulkSetFeatured(true)"
                                class="px-3 py-1.5 btn-carta-primary text-xs sm:text-sm cursor-pointer shadow-sm">
                            {{ __('admin.products.bulk_feature') }}
                        </button>
                        <button type="button" wire:click="bulkSetFeatured(false)"
                                class="px-3 py-1.5 rounded-lg bg-white border border-amber-400 text-amber-900 text-xs sm:text-sm font-semibold hover:bg-amber-50 cursor-pointer">
                            {{ __('admin.products.bulk_unfeature') }}
                        </button>
                        <button type="button" wire:click="bulkSetRecommended(true)"
                                class="px-3 py-1.5 rounded-lg bg-sky-600 text-white text-xs sm:text-sm font-semibold hover:bg-sky-700 cursor-pointer shadow-sm">
                            {{ __('admin.products.bulk_recommend') }}
                        </button>
                        <button type="button" wire:click="bulkSetRecommended(false)"
                                class="px-3 py-1.5 rounded-lg bg-white border border-sky-400 text-sky-900 text-xs sm:text-sm font-semibold hover:bg-sky-50 cursor-pointer">
                            {{ __('admin.products.bulk_unrecommend') }}
                        </button>
                        <button type="button" wire:click="bulkShowOnMenu"
                                class="px-3 py-1.5 rounded-lg bg-white border border-gray-300 text-gray-800 text-xs sm:text-sm font-semibold hover:bg-gray-50 cursor-pointer">
                            {{ __('admin.products.bulk_show_menu') }}
                        </button>
                        <button type="button" wire:click="bulkHideFromMenu"
                                class="px-3 py-1.5 rounded-lg bg-gray-800 text-white text-xs sm:text-sm font-semibold hover:bg-gray-900 cursor-pointer">
                            {{ __('admin.products.bulk_hide_menu') }}
                        </button>
                        <button type="button" wire:click="bulkClearOfferFlag"
                                class="px-3 py-1.5 rounded-lg bg-white border border-red-300 text-red-700 text-xs sm:text-sm font-semibold hover:bg-red-50 cursor-pointer">
                            {{ __('admin.products.bulk_clear_offer') }}
                        </button>
                    </div>
                    <button type="button" wire:click="clearBulkSelection"
                            class="px-3 py-1.5 rounded-lg text-gray-600 text-xs sm:text-sm font-medium hover:bg-gray-100 cursor-pointer shrink-0 lg:ml-auto">
                        {{ __('admin.products.bulk_clear_selection') }}
                    </button>
                </div>
            </div>
        @else
            <div class="admin-bulk-tooltip">
                <button type="button"
                        class="admin-bulk-tooltip__trigger"
                        aria-describedby="bulk-mass-tooltip"
                        aria-label="{{ __('admin.products.bulk_tooltip_aria') }}">
                    <svg class="admin-bulk-tooltip__icon" xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    {{ __('admin.products.bulk_mass_title') }}
                </button>
                <div id="bulk-mass-tooltip" class="admin-bulk-tooltip__bubble" role="tooltip">
                    <p class="admin-bulk-tooltip__line">{!! __('admin.products.bulk_tooltip_1', ['col' => '<strong>'.e(__('admin.products.bulk_tooltip_col')).'</strong>', 'hdr' => '<strong>'.e(__('admin.products.bulk_tooltip_hdr')).'</strong>']) !!}</p>
                    <p class="admin-bulk-tooltip__line admin-bulk-tooltip__line--fine">{{ __('admin.products.bulk_tooltip_2') }}</p>
                </div>
            </div>
        @endif
        </div>

        <div class="flex flex-shrink-0 items-center justify-end w-full sm:w-auto pt-1 sm:pt-0">
            @if($products instanceof \Illuminate\Pagination\LengthAwarePaginator)
                {{ $products->links('pagination.livewire-products-compact') }}
            @elseif($perPageOption === 'all' && $products instanceof \Illuminate\Support\Collection && $products->isNotEmpty())
                @include('pagination.products-all-showing-footer', ['total' => $products->count()])
            @endif
        </div>
    </div>

    {{-- Tabla: scroll horizontal en móvil (muchas columnas) --}}
    {{-- Bleed horizontal = padding de <main> (app layout). --}}
    <div class="-mx-4 sm:-mx-6 lg:-mx-8">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 max-w-full min-w-0">
            <div class="overflow-x-auto overscroll-x-contain rounded-2xl">
            <table class="w-full min-w-[960px]">
            <thead>
                <tr class="border-b border-gray-100">
                    <th class="pl-2 pr-0 py-3 w-8 text-center align-middle admin-sticky-col admin-sticky-hdr admin-sticky-left-0" title="{{ __('admin.products.th_select_all_title') }}">
                        <span class="sr-only">{{ __('admin.products.th_select_all') }}</span>
                        <button type="button"
                                wire:click="toggleSelectCurrentPage"
                                wire:loading.attr="disabled"
                                wire:key="hdr-bulk-{{ $page }}-{{ count($pageIds) }}"
                                title="{{ __('admin.products.th_select_all_title') }}"
                                aria-label="{{ __('admin.products.th_select_all_title') }}"
                                @if($pageSomeSelected) aria-checked="mixed" @else aria-checked="{{ $pageAllSelected ? 'true' : 'false' }}" @endif
                                role="checkbox"
                                class="inline-flex items-center justify-center w-4 h-4 shrink-0 rounded border-2 border-amber-800 focus:outline-none focus:ring-2 focus:ring-amber-700 focus:ring-offset-1 cursor-pointer mx-auto
                                    @if($pageAllSelected && count($pageIds) > 0) bg-amber-900 text-amber-50 border-amber-900
                                    @elseif($pageSomeSelected) bg-amber-100 text-amber-900 border-amber-800
                                    @else bg-white text-amber-900 border-amber-800 @endif">
                            @if($pageAllSelected && count($pageIds) > 0)
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            @elseif($pageSomeSelected)
                                <span class="block w-2 h-0.5 bg-current rounded-sm" aria-hidden="true"></span>
                            @endif
                        </button>
                    </th>
                    <th class="px-1 py-3 w-5 admin-sticky-col admin-sticky-hdr admin-sticky-left-4"></th>
                    <th class="pl-1 pr-2 py-3 w-12 admin-sticky-col admin-sticky-hdr admin-sticky-left-7"></th>
                    <th class="pl-1 pr-2 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider" style="min-width:200px">
                        {{ __('admin.products.th_name') }}
                    </th>
                    <th class="px-2 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider" style="min-width:9rem">{{ __('admin.products.th_category') }}</th>
                    <th class="px-2 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">{{ __('admin.products.th_price') }}</th>
                    <th class="px-2 py-3 text-center text-xs font-semibold text-gray-400 uppercase tracking-wider">{{ __('admin.products.th_offer') }}</th>
                    <th class="px-2 py-3 text-center text-xs font-semibold text-gray-400 uppercase tracking-wider" title="{{ __('admin.products.th_featured_title') }}">{{ __('admin.products.th_featured_short') }}</th>
                    <th class="px-2 py-3 text-center text-xs font-semibold text-gray-400 uppercase tracking-wider" title="{{ __('admin.products.th_rec_title') }}">{{ __('admin.products.th_rec_short') }}</th>
                    <th class="px-3 py-3 text-center text-xs font-semibold text-gray-400 uppercase tracking-wider">{{ __('admin.products.th_hide') }}</th>
                </tr>
            </thead>
            <tbody id="products-sortable" data-allow-sort="{{ $allowProductDragSort ? '1' : '0' }}">
            @forelse($products as $product)
                <tr wire:key="product-{{ $product->id }}" data-id="{{ $product->id }}"
                    class="border-b border-gray-50 hover:bg-gray-50 transition-colors group {{ $allowProductDragSort ? 'cursor-grab' : '' }}">

                    <td class="pl-2 pr-0 py-3 text-center align-middle bulk-select-cell admin-sticky-col admin-sticky-left-0" wire:key="cb-{{ $product->id }}"
                        title="{{ __('admin.products.row_select_title') }}"
                        onclick="event.stopPropagation()">
                        <input type="checkbox"
                               wire:click.prevent="toggleProductSelection({{ $product->id }})"
                               wire:loading.attr="disabled"
                               @if(in_array((int) $product->id, array_map('intval', $selectedProducts ?? []), true)) checked @endif
                               class="rounded border-amber-800 text-amber-900 focus:ring-amber-700 cursor-pointer w-4 h-4">
                    </td>

                    {{-- Handle (arrastrar) — icono hamburguesa 3 líneas --}}
                    <td class="px-1 py-3 drag-handle admin-sticky-col admin-sticky-left-4" title="{{ __('admin.products.drag_sort') }}">
                        <svg class="w-4 h-4 text-gray-300 group-hover:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7h16M4 12h16M4 17h16"/>
                        </svg>
                    </td>

                    {{-- Imagen --}}
                    <td class="pl-1 pr-2 py-2 admin-sticky-col admin-sticky-left-7">
                        <div class="w-12 h-12 rounded-lg overflow-hidden bg-gray-100 flex-shrink-0">
                            <img src="{{ $product->photo ? asset('storage/'.$product->photo) : asset('img/noimg.png') }}"
                                 alt="{{ $product->name }}"
                                 class="w-full h-full object-cover"
                                 onerror="this.onerror=null;this.src={{ json_encode(asset('img/noimg.png')) }};">
                        </div>
                    </td>

                    {{-- Nombre + alérgenos en línea debajo (compacto) --}}
                    <td class="pl-1 pr-2 py-3 align-top" style="min-width:200px">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-x-1.5 gap-y-1">
                                <button type="button"
                                        wire:click.stop="edit({{ $product->id }})"
                                        title="{{ __('admin.products.name_open_sheet') }}"
                                        class="text-sm font-medium text-left text-gray-800 truncate {{ $product->active ? 'line-through text-gray-400' : '' }} cursor-pointer hover:text-amber-800 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-400 focus-visible:ring-offset-1 rounded-sm bg-transparent border-0 p-0 max-w-full"
                                        title="{{ $product->name }}">
                                    {{ $product->name }}
                                </button>
                                @if($product->offer)
                                    @if($product->offer_end && \Carbon\Carbon::parse($product->offer_end)->isPast())
                                        <span class="text-xs bg-orange-100 text-orange-600 font-semibold px-2 py-0.5 rounded-full shrink-0" title="{{ __('admin.products.badge_expired_title') }}">{{ __('admin.products.badge_expired') }}</span>
                                    @else
                                        <span class="text-xs bg-red-100 text-red-600 font-semibold px-2 py-0.5 rounded-full shrink-0">{{ __('admin.products.badge_offer') }}</span>
                                    @endif
                                @endif
                                @if($product->featured)
                                    <span class="text-xs bg-amber-100 text-amber-800 font-semibold px-2 py-0.5 rounded-full shrink-0">{{ __('admin.products.badge_featured') }}</span>
                                @endif
                                @if($product->recommended)
                                    <span class="text-xs bg-sky-100 text-sky-800 font-semibold px-2 py-0.5 rounded-full shrink-0">{{ __('admin.products.badge_rec_short') }}</span>
                                @endif
                            </div>
                            @if($product->allergens->isNotEmpty())
                                <div class="mt-1.5 flex flex-wrap items-center gap-1.5"
                                     role="list"
                                     aria-label="{{ __('admin.products.th_allergens') }}"
                                     title="{{ __('admin.products.th_allergens_title') }}">
                                    @foreach($product->allergens->sortBy(fn ($a) => sprintf('%05d-%s', $a->sort_order ?? 0, $a->name)) as $al)
                                        @if($al->image)
                                            <span role="listitem"
                                                  class="inline-flex shrink-0 rounded overflow-hidden border border-gray-200 bg-white {{ $al->active ? 'opacity-45 ring-1 ring-amber-200' : '' }}"
                                                  title="{{ $al->name }}{{ $al->active ? ' — ' . __('admin.products.allergen_row_hidden_public') : '' }}">
                                                <img src="{{ $al->image_url }}" alt="" class="w-6 h-6 object-cover"
                                                     loading="lazy"
                                                     onerror="this.onerror=null;this.src={{ json_encode(asset('img/noimg.png')) }}">
                                            </span>
                                        @else
                                            <span role="listitem"
                                                  class="inline-flex text-[10px] font-bold leading-tight px-1.5 py-0.5 rounded bg-gray-100 text-gray-600 border border-gray-200 max-w-[4.25rem] truncate shrink-0 {{ $al->active ? 'opacity-45 ring-1 ring-amber-200' : '' }}"
                                                  title="{{ $al->name }}{{ $al->active ? ' — ' . __('admin.products.allergen_row_hidden_public') : '' }}">
                                                {{ \Illuminate\Support\Str::limit($al->name, 10, '…') }}
                                            </span>
                                        @endif
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </td>

                    {{-- Categoría --}}
                    <td class="px-2 py-3" style="min-width:9rem">
                        <span class="text-sm text-gray-500 {{ $product->active ? 'line-through' : '' }}">
                            {{ $product->category->name }}
                        </span>
                    </td>

                    {{-- Precio --}}
                    <td class="px-2 py-3 whitespace-nowrap">
                        <span class="text-sm font-semibold text-gray-700 {{ $product->offer ? 'line-through text-gray-400' : '' }}">
                            {{ $product->price }}
                        </span>
                        @if(strlen($product->offer_price) >= 1)
                            <span class="ml-1 text-sm font-bold text-red-500 {{ !$product->offer ? 'line-through opacity-40' : '' }}">
                                {{ $product->offer_price }}
                            </span>
                        @endif
                    </td>

                    {{-- Oferta: activar abre la ficha; desactivar quita la oferta en el listado --}}
                    <td class="px-2 py-3 text-center">
                        <label class="inline-flex items-center cursor-pointer" title="{{ $product->offer ? __('admin.products.offer_toggle_title_on') : __('admin.products.offer_toggle_title_off') }}">
                            {{-- wire:key evita que la casilla quede “pegada” en marcada: con wire:click.prevent morphdom no siempre actualiza el estado checked del input. --}}
                            <input type="checkbox"
                                   wire:key="offer-toggle-{{ $product->id }}-{{ $product->offer ? '1' : '0' }}-{{ (int) $offerFormOpenedForId === (int) $product->id ? 'm' : '-' }}"
                                   class="form-checkbox w-4 h-4 rounded text-red-500 border-gray-300 focus:ring-red-300 cursor-pointer"
                                   wire:click.prevent="offerToggleFromTable({{ $product->id }})"
                                   @if($product->offer || (int) $offerFormOpenedForId === (int) $product->id) checked @endif>
                        </label>
                    </td>

                    {{-- Destacado --}}
                    <td class="px-2 py-3 text-center">
                        <label class="inline-flex items-center cursor-pointer" title="{{ __('admin.products.featured_toggle_title') }}">
                            <input type="checkbox"
                                   wire:key="featured-toggle-{{ $product->id }}-{{ $product->featured ? '1' : '0' }}"
                                   class="form-checkbox w-4 h-4 rounded text-amber-500 border-gray-300 focus:ring-amber-300 cursor-pointer"
                                   wire:click.prevent="toggleFeatured({{ $product->id }})"
                                   @if($product->featured) checked @endif>
                        </label>
                    </td>

                    {{-- Recomendado --}}
                    <td class="px-2 py-3 text-center">
                        <label class="inline-flex items-center cursor-pointer" title="{{ __('admin.products.recommended_toggle_title') }}">
                            <input type="checkbox"
                                   wire:key="recommended-toggle-{{ $product->id }}-{{ $product->recommended ? '1' : '0' }}"
                                   class="form-checkbox w-4 h-4 rounded text-sky-500 border-gray-300 focus:ring-sky-300 cursor-pointer"
                                   wire:click.prevent="toggleRecommended({{ $product->id }})"
                                   @if($product->recommended) checked @endif>
                        </label>
                    </td>

                    {{-- Toggle ocultar --}}
                    <td class="px-2 py-3 text-center">
                        <label class="inline-flex items-center cursor-pointer" title="{{ $product->active ? __('admin.products.active_toggle_on') : __('admin.products.active_toggle_off') }}">
                            <input type="checkbox"
                                   wire:key="active-toggle-{{ $product->id }}-{{ $product->active ? '1' : '0' }}"
                                   class="form-checkbox w-4 h-4 rounded text-gray-400 border-gray-300 focus:ring-gray-300 cursor-pointer"
                                   wire:click.prevent="toggleState({{ $product->id }})"
                                   @if($product->active) checked @endif>
                        </label>
                    </td>

                </tr>
            @empty
                <tr>
                    <td colspan="10" class="text-center py-12 text-sm text-gray-400 px-4">
                        @if(($commercialFilterNorm ?? '') !== '')
                            <p class="mb-2">{{ __('admin.products.empty_filtered') }}</p>
                        @else
                            <p class="mb-2">{{ __('admin.products.empty_none') }}</p>
                        @endif
                        <div class="mt-4 flex flex-col sm:flex-row items-center justify-center gap-3">
                            <a href="{{ route('settings.import-products') }}"
                               class="inline-flex items-center justify-center gap-2 bg-white border border-gray-200 hover:border-amber-300 hover:bg-amber-100 text-gray-800 text-sm font-semibold py-2 px-4 rounded-xl shadow-sm transition-colors">
                                {{ __('admin.products.import_csv') }}
                            </a>
                            <a href="{{ route('settings.import-ai') }}"
                               class="inline-flex items-center justify-center gap-2 bg-green-500 hover:bg-green-600 text-white text-sm font-semibold py-2 px-4 rounded-xl shadow-sm transition-colors">
                                {{ __('admin.products.add_with_ai') }}
                            </a>
                            <button type="button" wire:click="create()"
                                    class="inline-flex items-center justify-center gap-2 bg-green-500 hover:bg-green-600 text-white text-sm font-semibold py-2 px-4 rounded-xl shadow-sm transition-colors">
                                {{ __('admin.products.add_one_by_one') }}
                            </button>
                            <button type="button" wire:click="generateMissingProductPhotos" wire:loading.attr="disabled" wire:target="generateMissingProductPhotos"
                                    class="inline-flex items-center justify-center gap-2 bg-white border border-gray-200 hover:border-amber-300 hover:bg-amber-50 text-gray-800 text-sm font-semibold py-2 px-4 rounded-xl shadow-sm transition-colors">
                                <span wire:loading.remove wire:target="generateMissingProductPhotos">{{ __('admin.products.generate_photos_ai') }}</span>
                                <span wire:loading wire:target="generateMissingProductPhotos">{{ __('admin.products.generating') }}</span>
                            </button>
                        </div>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
            </div>
        </div>
    </div>

    {{-- Modal confirmación eliminar producto --}}
    @if($confirmingProductDeletion)
    <div class="fixed inset-0 z-50 flex items-center justify-center">
        <div class="absolute inset-0 bg-black bg-opacity-50" wire:click="$set('confirmingProductDeletion', false)"></div>
        <div class="relative bg-white rounded-2xl shadow-xl p-6 max-w-md w-full mx-4">
            <h3 class="text-lg font-semibold text-gray-800 mb-2">{{ __('admin.products.modal_delete_title') }}</h3>
            <p class="text-sm text-gray-600 mb-6">{{ __('admin.products.modal_delete_body') }}</p>
            <div class="flex justify-end gap-3">
                <button wire:click="$set('confirmingProductDeletion', false)" wire:loading.attr="disabled"
                        class="px-4 py-2 text-sm text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-xl transition-colors cursor-pointer">
                    {{ __('admin.actions.cancel') }}
                </button>
                <button wire:click="deleteProductConfirmed" wire:loading.attr="disabled"
                        class="px-4 py-2 text-sm text-white bg-red-500 hover:bg-red-600 rounded-xl transition-colors font-semibold cursor-pointer">
                    {{ __('admin.products.modal_delete_confirm') }}
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- Modal confirmación quitar foto --}}
    @if($confirmingProductPhotoRemoval)
    <div class="fixed inset-0 z-50 flex items-center justify-center">
        <div class="absolute inset-0 bg-black bg-opacity-50" wire:click="$set('confirmingProductPhotoRemoval', false)"></div>
        <div class="relative bg-white rounded-2xl shadow-xl p-6 max-w-md w-full mx-4">
            <h3 class="text-lg font-semibold text-gray-800 mb-2">{{ __('admin.products.modal_photo_title') }}</h3>
            <p class="text-sm text-gray-600 mb-6">{{ __('admin.products.modal_photo_body') }}</p>
            <div class="flex justify-end gap-3">
                <button wire:click="$set('confirmingProductPhotoRemoval', false)" wire:loading.attr="disabled"
                        class="px-4 py-2 text-sm text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-xl transition-colors cursor-pointer">
                    {{ __('admin.actions.cancel') }}
                </button>
                <button wire:click="removeProductPhotoConfirmed" wire:loading.attr="disabled"
                        class="px-4 py-2 text-sm text-white bg-red-500 hover:bg-red-600 rounded-xl transition-colors font-semibold cursor-pointer">
                    {{ __('admin.products.modal_photo_confirm') }}
                </button>
            </div>
        </div>
    </div>
    @endif

    @if($confirmingAiAction)
    <div class="fixed inset-0 z-50 flex items-center justify-center"
         wire:loading.remove
         wire:target="confirmAiAction">
        <div class="absolute inset-0 bg-black bg-opacity-50" wire:click="cancelAiActionConfirmation"></div>
        <div class="relative bg-white rounded-2xl shadow-xl p-6 max-w-md w-full mx-4">
            <h3 class="text-lg font-semibold text-gray-800 mb-2">
                @if($pendingAiAction === 'generate_current_product_photo')
                    Confirmar generación IA
                @elseif($pendingAiAction === 'improve_current_product_photo')
                    Confirmar mejora IA
                @elseif($pendingAiAction === 'generate_description')
                    Confirmar descripción IA
                @elseif($pendingAiAction === 'generate_allergen_text')
                    Confirmar texto de alérgenos IA
                @else
                    Confirmar generación masiva IA
                @endif
            </h3>
            <p class="text-sm text-gray-600 mb-2">
                @if($pendingAiAction === 'generate_current_product_photo')
                    Vas a generar una imagen con IA para este producto.
                @elseif($pendingAiAction === 'improve_current_product_photo')
                    Vas a arreglar la imagen actual con IA para este producto.
                @elseif($pendingAiAction === 'generate_description')
                    Vas a generar una descripción con IA para este producto.
                @elseif($pendingAiAction === 'generate_allergen_text')
                    Vas a generar un texto alternativo de alérgenos con IA para este producto.
                @else
                    Vas a generar imágenes con IA para los productos que no tienen foto.
                @endif
            </p>
            <p class="text-sm font-semibold text-amber-700 mb-6">
                @if($pendingAiAction === 'generate_current_product_photo')
                    Coste: {{ $aiGenerateCost }} créditos.
                @elseif($pendingAiAction === 'improve_current_product_photo')
                    Coste: {{ $aiImproveCost }} créditos.
                @elseif($pendingAiAction === 'generate_description')
                    Coste: {{ $aiDescriptionCost }} créditos.
                @elseif($pendingAiAction === 'generate_allergen_text')
                    Coste: {{ $aiAllergenTextCost }} créditos.
                @else
                    Coste: {{ $aiBulkGenerateCost }} créditos por cada imagen generada.
                @endif
                @if($aiCredits['uses_client_key'])
                    Se usará la API key del cliente. No se descontarán créditos de la plataforma.
                @elseif($aiCredits['is_demo_unlimited'])
                    En esta demo no se descontará saldo.
                @endif
            </p>
            <div class="flex justify-end gap-3">
                <button wire:click="cancelAiActionConfirmation" wire:loading.attr="disabled"
                        class="px-4 py-2 text-sm text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-xl transition-colors cursor-pointer">
                    {{ __('admin.actions.cancel') }}
                </button>
                <button wire:click="confirmAiAction" wire:loading.attr="disabled"
                        class="px-4 py-2 text-sm text-white bg-amber-500 hover:bg-amber-600 rounded-xl transition-colors font-semibold cursor-pointer">
                    <span wire:loading.remove wire:target="confirmAiAction">Continuar</span>
                    <span wire:loading wire:target="confirmAiAction">Procesando…</span>
                </button>
            </div>
        </div>
    </div>
    @endif

</div>

@push('scripts')
<script>
document.addEventListener('admin-panel-replace-url', function (e) {
    if (e.detail && e.detail.url) {
        window.history.replaceState(null, '', e.detail.url);
    }
});
// Livewire 2 dispara dispatchBrowserEvent sobre el nodo del componente (burbuja a document, no a window).
function navigateToProductsAfterSave(e) {
    var url = e.detail && e.detail.url;
    if (!url) return;
    window.location.assign(url);
}
document.addEventListener('product-stored-navigate', navigateToProductsAfterSave);
document.addEventListener('DOMContentLoaded', initProductsSortable);
document.addEventListener('livewire:load', initProductsSortable);
document.addEventListener('livewire:update', function () {
    const el = document.getElementById('products-sortable');
    if (el && el._sortable) {
        el._sortable.destroy();
        el._sortable = null;
    }
    initProductsSortable();
});

function initProductsSortable() {
    const el = document.getElementById('products-sortable');
    if (!el || el._sortable) return;
    if (el.dataset.allowSort === '0') return;

    el._sortable = new Sortable(el, {
        animation: 200,
        handle: '.drag-handle',
        filter: '.bulk-select-cell, .cursor-pointer, input, button, label',
        preventOnFilter: false,
        ghostClass: 'bg-amber-50',
        onEnd: function () {
            const ids = [...el.querySelectorAll('tr[data-id]')]
                .map(tr => parseInt(tr.dataset.id));

            fetch('/api/reorder/products', {
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
