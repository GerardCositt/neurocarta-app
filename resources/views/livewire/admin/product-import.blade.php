<div>
    @if (session()->has('message'))
        <x-admin.banner variant="success">{{ session('message') }}</x-admin.banner>
    @endif

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
            <div class="min-w-0">
                <h3 class="text-lg font-semibold text-gray-800">Importar productos (CSV)</h3>
                <p class="text-sm text-gray-500 mt-1">Sube un CSV con separador <span class="font-semibold">;</span>. Los alérgenos van por nombre separados por <span class="font-semibold">|</span>. Puedes marcar muchos platos a la vez con las columnas <span class="font-semibold">destacado</span> y <span class="font-semibold">recomendado</span> (1/sí) de la plantilla.</p>
            </div>
            <div class="flex-shrink-0">
                <a href="{{ route('settings.import-products.template') }}"
                   class="inline-flex items-center px-4 py-2 rounded-xl text-sm font-semibold border border-gray-200 bg-white hover:bg-gray-50 text-gray-700 transition-colors">
                    Descargar plantilla
                </a>
            </div>
        </div>

        <div class="mt-5">
            <input type="file" wire:model="file" accept=".csv,text/csv"
                   class="block w-full text-sm text-gray-600 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-amber-50 file:text-amber-800 hover:file:bg-amber-100">
            @error('file')
                <x-admin.banner variant="danger" :show-icon="false" class="mt-2 mb-0 py-2">{{ $message }}</x-admin.banner>
            @enderror
        </div>

        @if($hasPreview)
            <div class="mt-6">
                @if(!empty($previewErrors))
                    <x-admin.banner variant="danger">
                        <div class="font-semibold mb-2">Errores</div>
                        <ul class="list-disc pl-5 space-y-1">
                            @foreach($previewErrors as $e)
                                <li>{{ $e }}</li>
                            @endforeach
                        </ul>
                    </x-admin.banner>
                @endif

                @if(!empty($previewWarnings))
                    <x-admin.banner variant="warning" class="mt-4 mb-0">
                        <div class="font-semibold mb-2">Avisos</div>
                        <ul class="list-disc pl-5 space-y-1">
                            @foreach($previewWarnings as $w)
                                <li>{{ $w }}</li>
                            @endforeach
                        </ul>
                    </x-admin.banner>
                @endif

                @if(!empty($previewRows))
                    <div class="mt-5 overflow-x-auto border border-gray-100 rounded-xl">
                        <table class="min-w-[900px] w-full text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    @foreach(array_keys($previewRows[0]) as $col)
                                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ $col }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($previewRows as $r)
                                    <tr class="border-t border-gray-100">
                                        @foreach($r as $v)
                                            <td class="px-3 py-2 text-gray-700 whitespace-nowrap">{{ $v }}</td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                <div class="mt-5 flex items-center gap-3">
                    <button type="button" wire:click="import" wire:loading.attr="disabled"
                            class="px-4 py-2 rounded-xl text-sm font-semibold bg-green-500 hover:bg-green-600 text-white transition-colors disabled:opacity-60">
                        Importar
                    </button>
                    <div class="text-xs text-gray-400">Máximo 1000 filas por importación.</div>
                </div>
            </div>
        @endif
    </div>
</div>
