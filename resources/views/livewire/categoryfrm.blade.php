<div class="fixed inset-0 z-50 overflow-y-auto" wire:click.self="closeForm()">

    <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" wire:click="closeForm()"></div>

    <div class="flex min-h-full items-center justify-center p-4" wire:click="closeForm()">
        <div class="relative bg-white rounded-2xl shadow-xl ring-1 ring-gray-100 w-full max-w-4xl overflow-hidden"
             wire:click.stop
             role="dialog" aria-modal="true">

            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <h2 class="text-base font-semibold text-gray-800">
                    {{ $category_id ? __('admin.category_page.edit_title') : __('admin.category_page.new_title') }}
                </h2>
                <button type="button" wire:click="closeForm()"
                        class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <form onsubmit="return false">
                <div class="px-6 py-5 space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">{{ __('admin.category_page.field_name') }}</label>
                        <input type="text" wire:model="name" placeholder="{{ __('admin.category_page.name_placeholder') }}"
                               class="w-full border border-gray-200 rounded-xl py-2 px-3 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-amber-300 focus:border-transparent shadow-sm">
                        @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <label class="flex items-center gap-2.5 cursor-pointer">
                        <input type="checkbox" wire:model="active"
                               class="form-checkbox w-4 h-4 rounded text-gray-400 border-gray-300 focus:ring-gray-300 cursor-pointer">
                        <span class="text-sm font-medium text-gray-700">{{ __('admin.category_page.label_hide') }}</span>
                    </label>
                </div>

                {{-- Pie: mismo patrón que maridaje / producto --}}
                <div class="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-between gap-3 px-6 py-4 border-t border-gray-100 bg-gray-50 rounded-b-2xl">
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
