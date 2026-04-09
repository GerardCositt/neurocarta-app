@if($mode === 'header')

{{-- ── Modo HEADER: píldora compacta en la barra superior ───────────────── --}}
<details class="relative" id="restaurantHeaderPicker" style="position:relative;z-index:80">
    <summary class="list-none cursor-pointer select-none">
        <div class="flex items-center gap-2 px-3 py-2 rounded-xl border border-gray-200 bg-white shadow-sm hover:bg-gray-50 transition-colors">
            <span class="w-2 h-2 rounded-full bg-gray-600 flex-shrink-0"></span>
            <div class="hidden sm:block text-left min-w-0 max-w-[160px]">
                <p class="text-sm font-medium text-gray-800 truncate">{{ $restaurants->count() > 1 ? __('admin.restaurant_switcher.section_label_plural') : __('admin.restaurant_switcher.section_label') }}</p>
                <p class="text-xs text-gray-400 truncate">{{ optional($restaurants->firstWhere('id', $currentId))->name ?? '—' }}</p>
            </div>
            <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
            </svg>
        </div>
    </summary>

    <div class="absolute right-0 top-full mt-2 w-64 rounded-2xl border border-gray-100 bg-white shadow-xl overflow-hidden" style="z-index:80">

        {{-- Cabecera del desplegable --}}
        <div class="px-3 py-2 border-b border-gray-100 bg-gray-50/80 flex items-center justify-between">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">
                {{ __('admin.restaurant_switcher.section_label') }}
            </p>
            <button type="button" wire:click="$toggle('showForm')"
                    class="w-5 h-5 rounded-full flex items-center justify-center text-gray-400 hover:bg-gray-200 hover:text-gray-800 transition-colors text-sm font-bold leading-none"
                    title="{{ __('admin.restaurant_switcher.add_tooltip') }}">
                {{ $showForm ? '✕' : '+' }}
            </button>
        </div>

        {{-- Lista de restaurantes --}}
        <div class="p-2 space-y-1 max-h-72 overflow-auto">
            @foreach($restaurants as $r)
            <div class="flex items-center gap-1">
                @if($pendingDelete === $r->id)
                    <div class="flex items-center justify-between gap-1 flex-1 px-2 py-1">
                        <span class="text-xs text-gray-500 truncate">{{ $r->name }}</span>
                        <div class="flex items-center gap-1 flex-shrink-0">
                            <button type="button" wire:click="askDeleteConfirm({{ $r->id }})"
                                    class="bg-red-500 hover:bg-red-600 text-white text-xs font-semibold px-2 py-1 rounded-lg whitespace-nowrap cursor-pointer">
                                {{ __('admin.actions.delete') }}
                            </button>
                            <button type="button" wire:click="cancelDelete"
                                    class="p-1 text-gray-400 hover:text-gray-600 rounded cursor-pointer" title="{{ __('admin.actions.cancel') }}">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                @else
                    <button wire:click="switchTo({{ $r->id }})" type="button"
                            class="flex-1 flex items-center gap-2 px-3 py-2 rounded-xl text-sm font-medium transition-colors text-left
                                   {{ $currentId == $r->id
                                       ? 'admin-nav-active ring-1 ring-gray-300'
                                       : 'text-gray-700 hover:bg-gray-50 hover:text-gray-900' }}">
                        <span class="w-2 h-2 rounded-full flex-shrink-0 {{ $currentId == $r->id ? 'bg-gray-700' : 'bg-gray-300' }}"></span>
                        <span class="truncate">{{ $r->name }}</span>
                        @if($currentId == $r->id)
                            <svg class="w-3.5 h-3.5 text-gray-500 ml-auto flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                            </svg>
                        @endif
                    </button>
                    @if($currentId != $r->id)
                        <button type="button"
                                wire:click="askDelete({{ $r->id }})"
                                title="{{ __('admin.restaurant_switcher.delete_title') }}"
                                class="p-1.5 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 transition-colors cursor-pointer flex-shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    @else
                        <span style="width:28px;flex-shrink:0;"></span>
                    @endif
                @endif
            </div>
            @endforeach
        </div>

        {{-- Formulario nuevo restaurante --}}
        @if($showForm)
        <div class="p-3 border-t border-gray-100 bg-gray-50/80 space-y-2">
            <p class="text-xs font-semibold text-gray-800">{{ __('admin.restaurant_switcher.new_title') }}</p>
            <div>
                <input type="text" wire:model.defer="newName"
                       placeholder="{{ __('admin.restaurant_switcher.placeholder_name') }}"
                       class="w-full text-xs rounded-lg border border-gray-200 px-2 py-1.5 focus:ring-1 focus:ring-gray-400 focus:border-gray-400 bg-white">
                @error('newName') <p class="text-xs text-red-500 mt-0.5">{{ $message }}</p> @enderror
            </div>
            <div>
                <input type="text" wire:model.defer="newSubdomain"
                       placeholder="{{ __('admin.restaurant_switcher.placeholder_subdomain') }}"
                       class="w-full text-xs rounded-lg border border-gray-200 px-2 py-1.5 focus:ring-1 focus:ring-gray-400 focus:border-gray-400 bg-white">
                @error('newSubdomain') <p class="text-xs text-red-500 mt-0.5">{{ $message }}</p> @enderror
            </div>
            <button type="button" wire:click="createRestaurant"
                    class="w-full py-1.5 rounded-lg text-xs font-semibold bg-gray-800 hover:bg-gray-900 text-white transition-colors">
                {{ __('admin.restaurant_switcher.create') }}
            </button>
        </div>
        @endif

    </div>
</details>

@else

{{-- ── Modo SIDEBAR (fallback, sin uso activo) ──────────────────────────── --}}
<div class="px-3 pb-3">
    <div class="flex items-center justify-between px-1 mb-1.5">
        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">{{ __('admin.restaurant_switcher.section_label') }}</p>
        <button type="button" wire:click="$toggle('showForm')"
                class="w-5 h-5 rounded-full flex items-center justify-center text-gray-400 hover:bg-gray-200 hover:text-gray-800 transition-colors text-sm font-bold leading-none"
                title="{{ __('admin.restaurant_switcher.add_tooltip') }}">
            {{ $showForm ? '✕' : '+' }}
        </button>
    </div>
    <div class="flex flex-col gap-1">
        @foreach($restaurants as $r)
        <div class="flex items-center gap-1">
            @if($pendingDelete === $r->id)
                <div class="flex items-center justify-between gap-1 flex-1 px-2 py-1">
                    <span class="text-xs text-gray-500 truncate">{{ $r->name }}</span>
                    <div class="flex items-center gap-1 flex-shrink-0">
                        <button type="button" wire:click="askDeleteConfirm({{ $r->id }})"
                                class="bg-red-500 hover:bg-red-600 text-white text-xs font-semibold px-2 py-1 rounded-lg whitespace-nowrap cursor-pointer">
                            {{ __('admin.actions.delete') }}
                        </button>
                        <button type="button" wire:click="cancelDelete"
                                class="p-1 text-gray-400 hover:text-gray-600 rounded cursor-pointer">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>
            @else
                <button wire:click="switchTo({{ $r->id }})" type="button"
                        class="flex-1 flex items-center gap-2 px-3 py-2 rounded-xl text-sm font-medium transition-colors text-left
                               {{ $currentId == $r->id ? 'admin-nav-active ring-1 ring-gray-300' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' }}">
                    <span class="w-2 h-2 rounded-full flex-shrink-0 {{ $currentId == $r->id ? 'bg-gray-700' : 'bg-gray-300' }}"></span>
                    <span class="truncate">{{ $r->name }}</span>
                </button>
                @if($currentId != $r->id)
                    <button type="button" wire:click="askDelete({{ $r->id }})"
                            title="{{ __('admin.restaurant_switcher.delete_title') }}"
                            class="p-1.5 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 transition-colors cursor-pointer flex-shrink-0">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </button>
                @else
                    <span style="width:28px;flex-shrink:0;"></span>
                @endif
            @endif
        </div>
        @endforeach
    </div>
    @if($showForm)
    <div class="mt-2 p-3 rounded-xl border border-gray-200 bg-gray-50 space-y-2">
        <p class="text-xs font-semibold text-gray-800">{{ __('admin.restaurant_switcher.new_title') }}</p>
        <div>
            <input type="text" wire:model.defer="newName" placeholder="{{ __('admin.restaurant_switcher.placeholder_name') }}"
                   class="w-full text-xs rounded-lg border border-gray-200 px-2 py-1.5 focus:ring-1 focus:ring-gray-400 focus:border-gray-400 bg-white">
            @error('newName') <p class="text-xs text-red-500 mt-0.5">{{ $message }}</p> @enderror
        </div>
        <div>
            <input type="text" wire:model.defer="newSubdomain" placeholder="{{ __('admin.restaurant_switcher.placeholder_subdomain') }}"
                   class="w-full text-xs rounded-lg border border-gray-200 px-2 py-1.5 focus:ring-1 focus:ring-gray-400 focus:border-gray-400 bg-white">
            @error('newSubdomain') <p class="text-xs text-red-500 mt-0.5">{{ $message }}</p> @enderror
        </div>
        <button type="button" wire:click="createRestaurant"
                class="w-full py-1.5 rounded-lg text-xs font-semibold bg-gray-800 hover:bg-gray-900 text-white transition-colors">
            {{ __('admin.restaurant_switcher.create') }}
        </button>
    </div>
    @endif
</div>

@endif
