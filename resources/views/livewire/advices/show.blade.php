<x-slot name="header">
    <h2 class="text-xl font-semibold text-gray-800">{{ __('admin.advice_page.header') }}</h2>
</x-slot>

<div>

    @if (session()->has('message'))
        <x-admin.banner variant="success">{{ session('message') }}</x-admin.banner>
    @endif

    {{-- Barra de acciones --}}
    <div class="flex justify-between items-center mb-5">
        <div>
            <input wire:model.debounce.500ms="q"
                   type="search"
                   size="25"
                   placeholder="Buscar aviso..."
                   class="border border-gray-200 bg-white rounded-xl py-2 px-4 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-amber-300 focus:border-transparent shadow-sm" />
        </div>
        <button wire:click="confirmItemAdd"
                class="bg-green-500 hover:bg-green-600 text-white text-sm font-semibold py-2 px-4 rounded-xl shadow-sm transition-colors flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            Añadir aviso
        </button>
    </div>

    {{-- Tabla --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto overscroll-x-contain sm:overflow-visible">
        <table class="w-full min-w-full">
            <thead>
                <tr class="border-b border-gray-100">
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Título</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">Contenido</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-400 uppercase tracking-wider">{{ __('admin.advice_page.th_dates') }}</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-400 uppercase tracking-wider">{{ __('admin.advice_page.th_active') }}</th>
                </tr>
            </thead>
            <tbody>
            @forelse($items as $item)
                <tr wire:key="advice-{{ $item->id }}" class="border-b border-gray-50 hover:bg-gray-50 transition-colors">
                    <td class="px-4 py-3">
                        <button type="button"
                                wire:click="confirmItemEdit({{ $item->id }})"
                                title="{{ __('admin.advice_page.dialog_edit') }}"
                                class="text-sm font-medium text-left text-gray-800 cursor-pointer hover:text-amber-800 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-400 focus-visible:ring-offset-1 rounded-sm bg-transparent border-0 p-0 max-w-full">
                            {{ $item->title }}
                        </button>
                    </td>
                    <td class="px-4 py-3 max-w-xs">
                        <span class="text-sm text-gray-500 line-clamp-2">{{ $item->advice }}</span>
                    </td>
                    <td class="px-4 py-3">
                        <span class="text-sm text-gray-500">
                            @if($item->starts_at || $item->ends_at)
                                {{ $item->starts_at ? $item->starts_at->format('d/m/Y H:i') : '…' }}
                                –
                                {{ $item->ends_at ? $item->ends_at->format('d/m/Y H:i') : '…' }}
                            @else
                                {{ __('admin.advice_page.dates_always') }}
                            @endif
                        </span>
                    </td>
                    <td class="px-4 py-3 text-center">
                        @php
                            $onCarta = $item->isVisibleOnPublicCarta();
                        @endphp
                        <label class="inline-flex items-center cursor-pointer">
                            <input type="checkbox"
                                   wire:click.prevent="toggleState({{ $item->id }})"
                                   @if($item->status) checked @endif
                                   class="form-checkbox w-4 h-4 rounded text-amber-500 border-gray-300 focus:ring-amber-300 cursor-pointer">
                        </label>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center py-12 text-sm text-gray-400">
                        @if($q) No hay avisos que coincidan con "{{ $q }}"
                        @else Aún no has creado avisos
                        @endif
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
        </div>

        @if($items->hasPages())
            <div class="px-4 py-3 border-t border-gray-100">
                {{ $items->links() }}
            </div>
        @endif
    </div>

    {{-- Modal confirmación eliminar --}}
    @if($confirmingItemDeletion)
    <div class="fixed inset-0 z-50 flex items-center justify-center">
        <div class="absolute inset-0 bg-black bg-opacity-50" wire:click="$set('confirmingItemDeletion', false)"></div>
        <div class="relative bg-white rounded-2xl shadow-xl p-6 max-w-md w-full mx-4">
            <h3 class="text-lg font-semibold text-gray-800 mb-2">Eliminar aviso</h3>
            <p class="text-sm text-gray-600 mb-6">¿Estás seguro de que quieres eliminar este aviso?</p>
            <div class="flex justify-end gap-3">
                <button wire:click="$set('confirmingItemDeletion', false)" wire:loading.attr="disabled"
                        class="px-4 py-2 text-sm text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-xl transition-colors cursor-pointer">
                    Cancelar
                </button>
                <button wire:click="deleteItem({{ $confirmingItemDeletion ?: 0 }})" wire:loading.attr="disabled"
                        class="px-4 py-2 text-sm text-white bg-red-500 hover:bg-red-600 rounded-xl transition-colors font-semibold cursor-pointer">
                    Sí, eliminar
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- Modal añadir / editar --}}
    @if($confirmingItemAdd)
    <div class="fixed inset-0 z-[60] overflow-y-auto">
        <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity"></div>

        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative bg-white rounded-2xl shadow-xl ring-1 ring-gray-100 w-full max-w-4xl overflow-hidden"
                 role="dialog" aria-modal="true">

                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                    <h2 class="text-base font-semibold text-gray-800">
                        {{ $editingItemId ? __('admin.advice_page.dialog_edit') : __('admin.advice_page.dialog_new') }}
                    </h2>
                    <button type="button" wire:click="$set('confirmingItemAdd', false)"
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
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">{{ __('admin.advice_page.label_title') }}</label>
                            <input id="title"
                                   type="text"
                                   wire:model.defer="item.title"
                                   class="w-full border border-gray-200 rounded-xl py-2 px-3 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-amber-300 focus:border-transparent shadow-sm">
                            @error('item.title') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">{{ __('admin.advice_page.label_content') }}</label>
                            <textarea id="advice"
                                      wire:model.defer="item.advice"
                                      rows="5"
                                      placeholder="{{ __('admin.advice_page.content_placeholder') }}"
                                      class="w-full border border-gray-200 rounded-xl py-2 px-3 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-amber-300 focus:border-transparent shadow-sm resize-none"></textarea>
                            @error('item.advice') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        <p class="text-xs text-gray-500 leading-relaxed">{{ __('admin.advice_page.schedule_hint') }}</p>

                        {{-- Livewire sincroniza el oculto; el visible vive en wire:ignore y lo gobierna Flatpickr (evita re-render que rompa el plugin). --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">{{ __('admin.advice_page.label_start_date') }}</label>
                                <input type="hidden"
                                       wire:model.defer="item.starts_at"
                                       data-flatpickr-model="item.starts_at">
                                <div wire:ignore class="relative" data-flatpickr-field>
                                    <input type="text"
                                           value="{{ $item['starts_at'] ?? '' }}"
                                           data-flatpickr
                                           data-flatpickr-mode="datetime"
                                           data-flatpickr-target="item.starts_at"
                                           data-flatpickr-placeholder="dd/mm/aaaa hh:mm"
                                           placeholder="dd/mm/aaaa hh:mm"
                                           class="w-full border border-gray-200 rounded-xl py-2 px-3 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-amber-300 focus:border-transparent shadow-sm">
                                </div>
                                @error('item.starts_at') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">{{ __('admin.advice_page.label_end_date') }}</label>
                                <input type="hidden"
                                       wire:model.defer="item.ends_at"
                                       data-flatpickr-model="item.ends_at">
                                <div wire:ignore class="relative" data-flatpickr-field>
                                    <input type="text"
                                           value="{{ $item['ends_at'] ?? '' }}"
                                           data-flatpickr
                                           data-flatpickr-mode="datetime"
                                           data-flatpickr-target="item.ends_at"
                                           data-flatpickr-placeholder="dd/mm/aaaa hh:mm"
                                           placeholder="dd/mm/aaaa hh:mm"
                                           class="w-full border border-gray-200 rounded-xl py-2 px-3 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-amber-300 focus:border-transparent shadow-sm">
                                </div>
                                @error('item.ends_at') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>
                        <div class="rounded-xl border border-gray-100 bg-gray-50/80 px-4 py-3">
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">{{ __('admin.advice_page.th_active') }}</p>
                            <label class="inline-flex items-center gap-2 cursor-pointer">
                                <input type="checkbox"
                                       wire:model="item.status"
                                       class="form-checkbox w-4 h-4 rounded text-amber-500 border-gray-300 focus:ring-amber-300 cursor-pointer">
                                <span class="text-sm text-gray-700">{{ __('admin.advice_page.active_manual') }}</span>
                            </label>
                        </div>
                        </div>
                    </div>

                    <div class="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-between gap-3 px-6 py-4 border-t border-gray-100 bg-gray-50 rounded-b-2xl">
                        <div class="flex justify-center sm:justify-start">
                            @if($editingItemId)
                                <button type="button"
                                        wire:click="confirmItemDeletion({{ (int) $editingItemId }})"
                                        wire:loading.attr="disabled"
                                        class="px-4 py-2 text-sm font-semibold text-red-600 bg-white border border-red-200 hover:bg-red-50 rounded-xl transition-colors cursor-pointer">
                                    {{ __('admin.advice_page.delete_advice') }}
                                </button>
                            @endif
                        </div>
                        <div class="flex flex-wrap items-center justify-end gap-3">
                            <button type="button" wire:click="$set('confirmingItemAdd', false)"
                                    class="px-4 py-2 text-sm text-gray-600 bg-white border border-gray-200 hover:bg-gray-50 rounded-xl transition-colors cursor-pointer">
                                {{ __('admin.actions.cancel') }}
                            </button>
                            <button type="button" wire:click="saveItemKeepOpen" wire:loading.attr="disabled"
                                    class="px-5 py-2 text-sm font-semibold text-gray-800 bg-white border border-gray-200 hover:bg-gray-50 rounded-xl shadow-sm transition-colors cursor-pointer">
                                {{ __('admin.advice_page.save_keep_open') }}
                            </button>
                            <button type="button" wire:click="saveItemAndClose" wire:loading.attr="disabled"
                                    class="px-5 py-2 text-sm font-semibold text-white bg-green-500 hover:bg-green-600 rounded-xl shadow-sm transition-colors cursor-pointer">
                                {{ __('admin.advice_page.save_and_close') }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

</div>
