<x-jet-form-section submit="createCategory">
    <x-slot name="title">
        Crear nueva categoría
    </x-slot>

    <x-slot name="description">
        Crea una nueva categoría para la agrupación de productos
    </x-slot>

    <x-slot name="form">
        <div class="col-span-6 sm:col-span-4">
            <x-jet-label for="category.name" value="{{ __('nombre de la categoría') }}" />
            <x-jet-input id="category.name" type="text" class="mt-1 block w-full" wire:model.defer="category.name"  />
            <x-jet-input-error for="category.name" class="mt-2" />
        </div>
    </x-slot>

    <x-slot name="actions">
        <x-jet-action-message class="mr-3" on="categoryCreated">
            {{ __('Guardado') }}
        </x-jet-action-message>

        <x-jet-button>
            {{ __('Guardar') }}
        </x-jet-button>
    </x-slot>
</x-jet-form-section>
