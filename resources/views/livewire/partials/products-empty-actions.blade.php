{{--
    Acciones del estado vacío (lista + tabla): chips con borde, sin botón naranja sólido.
--}}
@php
    $chip = 'inline-flex shrink-0 items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm font-semibold text-gray-800 shadow-sm hover:border-amber-300 hover:bg-amber-50 hover:text-amber-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-400 focus-visible:ring-offset-2 transition-colors cursor-pointer whitespace-nowrap';
@endphp
<div class="mt-4 w-full min-w-0 overflow-x-auto overscroll-x-contain pb-1">
    <div class="flex flex-nowrap items-stretch justify-center gap-2 sm:gap-3 w-max max-w-none mx-auto px-0.5">
        @if ($canUseCsvImport ?? false)
            <button type="button" wire:click="interceptIfEmpty('csv')" class="{{ $chip }}">
                <svg class="w-4 h-4 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                </svg>
                {{ __('admin.products.import_csv') }}
            </button>
        @endif
        @if ($canUseAi ?? false)
            <button type="button" wire:click="interceptIfEmpty('ai')" class="{{ $chip }}">
                <svg class="w-4 h-4 text-green-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18l-1.813-2.096L5 15l2.187-.904L9 12l.813 2.096L12 15l-2.187.904zM18 13l.74 1.704L20.5 15.5l-1.76.796L18 18l-.74-1.704L15.5 15.5l1.76-.796L18 13zM12 3l1.252 2.876L16 7.128l-2.748 1.252L12 11.256 10.748 8.38 8 7.128l2.748-1.252L12 3z"/>
                </svg>
                {{ __('admin.products.add_with_ai') }}
            </button>
        @endif
        <button type="button" wire:click="create()" class="{{ $chip }}">
            <svg class="w-4 h-4 text-green-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            {{ __('admin.products.add_one_by_one') }}
        </button>
        @if ($hasNoProducts ?? false)
            <button type="button" wire:click="confirmLoadDemo" class="{{ $chip }}">
                <svg class="w-4 h-4 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 5.5A2.5 2.5 0 016.5 3h11A2.5 2.5 0 0120 5.5v13a1.5 1.5 0 01-2.25 1.3L12 16.5l-5.75 3.3A1.5 1.5 0 014 18.5v-13z"/>
                </svg>
                {{ __('admin.products.load_demo') }}
            </button>
        @endif
        @if ($canUseAi ?? false)
            <button type="button" wire:click="interceptIfEmpty('photos')" wire:loading.attr="disabled" wire:target="interceptIfEmpty"
                    class="{{ $chip }} disabled:opacity-50 disabled:cursor-not-allowed">
                <svg class="w-4 h-4 text-violet-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                {{ __('admin.products.generate_photos_ai') }}
            </button>
        @endif
    </div>
</div>
