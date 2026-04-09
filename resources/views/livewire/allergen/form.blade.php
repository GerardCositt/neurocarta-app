<x-jet-form-section submit="createAllergen">
    <x-slot name="title">
        Crear nuevo alérgeno
    </x-slot>

    <x-slot name="description">
        Si quieres crea un nuevo alérgeno para asignarlo a los productos, escribe su nombre y pulsa guardar.
    </x-slot>

    <x-slot name="form">
        <div class="col-span-6 sm:col-span-4">
            <x-jet-label for="allergen.name" value="{{ __('nombre del alérgeno') }}" />
            <x-jet-input id="allergen.name" type="text" class="mt-1 block w-full" wire:model.defer="allergen.name"  />
            <x-jet-input-error for="allergen.name" class="mt-2" />
        </div>
    </x-slot>

    <x-slot name="actions">
        <x-jet-action-message class="mr-3" on="allergenCreated">
            {{ __('Guardado') }}
        </x-jet-action-message>

        <x-jet-button>
            {{ __('Guardar') }}
        </x-jet-button>
    </x-slot>
</x-jet-form-section>
