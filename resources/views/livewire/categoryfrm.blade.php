<div style="position:fixed;inset:0;z-index:20000;background:rgba(0,0,0,0.75);overflow-y:auto;" wire:keydown.escape="closeForm()">

    <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative bg-white rounded-2xl w-full max-w-md overflow-hidden"
             wire:click.stop
             style="pointer-events:auto;box-shadow:0 25px 50px rgba(0,0,0,0.4);border:2px solid #d1d5db;"
             role="dialog" aria-modal="true">

            <div class="flex items-center justify-between px-4 sm:px-6 py-4 border-b border-gray-200 bg-gray-50">
                <h2 style="color:#111827;font-weight:600;font-size:1rem;margin:0;">
                    {{ $category_id ? __('admin.category_page.edit_title') : __('admin.category_page.new_title') }}
                </h2>
                <div style="display:flex;align-items:center;gap:6px;">
                    @if(($navPosition ?? null) !== null && ($navTotal ?? 0) > 1)
                    <div style="display:flex;align-items:center;gap:4px;background:#f3f4f6;border-radius:10px;padding:3px 6px;">
                        <button type="button" wire:click="saveAndPrev" wire:loading.attr="disabled" wire:target="saveAndPrev,saveAndNext" @disabled($navPosition <= 1)
                                style="display:flex;align-items:center;justify-content:center;width:26px;height:26px;border-radius:6px;border:1px solid #e5e7eb;background:#fff;color:#374151;cursor:pointer;" title="Guardar y anterior">
                            <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                        </button>
                        <span style="font-size:0.78rem;color:#6b7280;padding:0 3px;white-space:nowrap;font-variant-numeric:tabular-nums;">{{ $navPosition }} / {{ $navTotal }}</span>
                        <button type="button" wire:click="saveAndNext" wire:loading.attr="disabled" wire:target="saveAndPrev,saveAndNext" @disabled($navPosition >= $navTotal)
                                style="display:flex;align-items:center;justify-content:center;width:26px;height:26px;border-radius:6px;border:1px solid #e5e7eb;background:#fff;color:#374151;cursor:pointer;" title="Guardar y siguiente">
                            <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                        </button>
                    </div>
                    @endif
                    <button type="button" wire:click="closeForm()"
                            class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>

            <form onsubmit="return false">
                <div class="px-4 sm:px-6 py-5 space-y-4">

                    @if($msgError)
                        <div class="rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700 flex items-start justify-between gap-3">
                            <span>
                                {{ $msgError }}
                                <a href="{{ route('subscription.expired') }}"
                                   class="ml-1 font-semibold underline underline-offset-2 hover:opacity-80 whitespace-nowrap">
                                    Ver planes →
                                </a>
                            </span>
                            <button type="button" wire:click="$set('msgError', null)" class="opacity-60 hover:opacity-100 flex-shrink-0 font-bold text-base leading-none">✕</button>
                        </div>
                    @endif

                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">{{ __('admin.category_page.field_name') }}</label>
                        <input type="text" wire:model="name" placeholder="{{ __('admin.category_page.name_placeholder') }}"
                               class="w-full border border-gray-200 rounded-xl py-2 px-3 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-amber-300 focus:border-transparent shadow-sm">
                        @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <label class="flex items-center gap-2.5 cursor-pointer">
                        <input type="checkbox" wire:model="hidden"
                               class="form-checkbox w-4 h-4 rounded text-gray-400 border-gray-300 focus:ring-gray-300 cursor-pointer">
                        <span class="text-sm font-medium text-gray-700">{{ __('admin.category_page.label_hide') }}</span>
                    </label>
                </div>

                {{-- Pie: mismo patrón que maridaje / producto --}}
                <div class="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-between gap-3 px-4 sm:px-6 py-4 border-t border-gray-100 bg-gray-50 rounded-b-2xl">
                    <div class="flex justify-center sm:justify-start">
                        @if($category_id)
                            <button type="button"
                                    wire:click="confirmDeleteCurrentCategory"
                                    wire:loading.attr="disabled"
                                    wire:target="confirmDeleteCurrentCategory,deleteCategoryConfirmed"
                                    class="px-4 py-2 text-sm font-semibold text-red-600 bg-white border border-red-200 hover:bg-red-50 rounded-xl transition-colors cursor-pointer">
                                {{ __('admin.category_page.delete_category') }}
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
                            {{ __('admin.category_page.save_keep_open') }}
                        </button>
                        <button type="button" wire:click="storeAndClose"
                                wire:loading.attr="disabled"
                                wire:target="save,storeAndClose"
                                class="px-5 py-2 text-sm font-semibold text-white bg-green-500 hover:bg-green-600 rounded-xl shadow-sm transition-colors cursor-pointer">
                            {{ __('admin.category_page.save_and_close') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

