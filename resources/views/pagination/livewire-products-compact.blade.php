{{-- Paginación compacta (Livewire 2): solo gray-* (Tailwind 2 del proyecto no incluye slate en el CSS compilado).
     Nota: no usar solo hasPages(): con una única página Laravel devuelve hasPages() false. --}}
<div>
    @if ($paginator->total() > 0)
        @php
            isset($this->numberOfPaginatorsRendered[$paginator->getPageName()])
                ? $this->numberOfPaginatorsRendered[$paginator->getPageName()]++
                : $this->numberOfPaginatorsRendered[$paginator->getPageName()] = 1;
            $rangeFirst = $paginator->firstItem() ?? 0;
            $rangeLast = $paginator->lastItem() ?? 0;
            $rangeTotal = $paginator->total();
        @endphp

        <nav class="inline-flex items-center gap-2 sm:gap-3"
             role="navigation"
             aria-label="{{ __('admin.products.pagination_nav_label') }}">
            <div role="status"
                 class="text-sm font-medium text-gray-800 tabular-nums tracking-tight whitespace-nowrap"
                 aria-label="{{ __('admin.products.pagination_range_aria', ['first' => $rangeFirst, 'last' => $rangeLast, 'total' => $rangeTotal]) }}">
                <span class="font-semibold text-gray-900">{{ $rangeFirst }}</span><span class="text-gray-500"> - </span><span class="font-semibold text-gray-900">{{ $rangeLast }}</span><span class="text-gray-500"> / </span><span class="font-semibold text-gray-900">{{ $rangeTotal }}</span>
            </div>

            <div class="inline-flex flex-shrink-0 overflow-hidden rounded-lg border border-gray-200 bg-gray-50 shadow-sm">
                @if ($paginator->onFirstPage())
                    <span class="inline-flex items-center justify-center w-10 px-2 py-2 text-gray-300 bg-gray-50 border-r border-gray-200 cursor-not-allowed select-none" aria-disabled="true" title="{{ __('pagination.previous') }}">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                    </span>
                @else
                    <button type="button"
                            wire:click="previousPage('{{ $paginator->getPageName() }}')"
                            wire:loading.attr="disabled"
                            dusk="previousPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}"
                            rel="prev"
                            class="inline-flex items-center justify-center w-10 px-2 py-2 text-gray-700 bg-gray-50 border-r border-gray-200 hover:bg-white hover:text-gray-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-amber-400 transition-colors cursor-pointer"
                            aria-label="{{ __('pagination.previous') }}">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                    </button>
                @endif

                @if ($paginator->hasMorePages())
                    <button type="button"
                            wire:click="nextPage('{{ $paginator->getPageName() }}')"
                            wire:loading.attr="disabled"
                            dusk="nextPage{{ $paginator->getPageName() == 'page' ? '' : '.' . $paginator->getPageName() }}"
                            rel="next"
                            class="inline-flex items-center justify-center w-10 px-2 py-2 text-gray-700 bg-gray-50 hover:bg-white hover:text-gray-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-amber-400 transition-colors cursor-pointer"
                            aria-label="{{ __('pagination.next') }}">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>
                    </button>
                @else
                    <span class="inline-flex items-center justify-center w-10 px-2 py-2 text-gray-300 bg-gray-50 cursor-not-allowed select-none" aria-disabled="true" title="{{ __('pagination.next') }}">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/></svg>
                    </span>
                @endif
            </div>
        </nav>
    @endif
</div>
