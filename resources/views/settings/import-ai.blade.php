<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Ajustes · Importar carta con IA
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-4 text-xs text-gray-400 bg-gray-50 border border-gray-100 rounded-lg px-4 py-3">
                El contenido generado por IA (descripciones e imágenes) es orientativo. Revísalo antes de publicarlo.
                Al usar esta función aceptas los
                <a href="https://neurocarta.ai/terminos" target="_blank" rel="noopener" class="underline hover:text-gray-600">Términos de servicio</a>,
                que incluyen las condiciones de uso de inteligencia artificial.
            </div>
            @livewire('admin.import-ai')
        </div>
    </div>
</x-app-layout>
