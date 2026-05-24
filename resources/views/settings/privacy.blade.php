<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Ajustes · Privacidad y datos
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Flash --}}
            @if(session('status'))
                <div class="bg-green-50 border border-green-200 text-green-800 rounded-lg px-4 py-3 text-sm">
                    {{ session('status') }}
                </div>
            @endif

            {{-- Exportar datos --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h3 class="text-base font-semibold text-gray-900 mb-1">Exportar mis datos</h3>
                <p class="text-sm text-gray-500 mb-4">
                    Descarga un archivo JSON con toda la información asociada a tu cuenta: perfil, restaurantes, productos, categorías y suscripciones.
                    Este es tu derecho de portabilidad según el RGPD (Art. 20).
                </p>
                <a href="{{ route('settings.privacy.export') }}"
                   class="inline-flex items-center gap-2 px-4 py-2 bg-gray-800 text-white text-sm font-medium rounded-lg hover:bg-gray-700 transition">
                    Descargar mis datos (JSON)
                </a>
            </div>

            {{-- Eliminar cuenta --}}
            <div class="bg-white rounded-xl shadow-sm border border-red-100 p-6">
                <h3 class="text-base font-semibold text-red-700 mb-1">Eliminar mi cuenta</h3>
                <p class="text-sm text-gray-500 mb-4">
                    Elimina permanentemente tu cuenta y todos los datos asociados: restaurantes, productos, categorías y suscripción.
                    Esta acción <strong>no se puede deshacer</strong>. Tus datos serán borrados de forma definitiva.
                    Este es tu derecho al olvido según el RGPD (Art. 17).
                </p>

                <button onclick="document.getElementById('delete-modal').classList.remove('hidden')"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition">
                    Eliminar mi cuenta
                </button>
            </div>

        </div>
    </div>

    {{-- Modal confirmación eliminación --}}
    <div id="delete-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm px-4">
        <div class="bg-white rounded-2xl shadow-xl max-w-md w-full p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-2">¿Eliminar tu cuenta?</h3>
            <p class="text-sm text-gray-500 mb-5">
                Se borrarán de forma permanente tu cuenta, todos tus restaurantes, productos, categorías y suscripción.
                Introduce tu contraseña actual para confirmar.
            </p>

            <form method="POST" action="{{ route('settings.privacy.destroy') }}">
                @csrf
                @method('DELETE')

                <div class="mb-4">
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Contraseña actual</label>
                    <input type="password" id="password" name="password" required
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-400"
                           placeholder="Tu contraseña">
                    @error('password')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex gap-3 justify-end">
                    <button type="button"
                            onclick="document.getElementById('delete-modal').classList.add('hidden')"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition">
                        Cancelar
                    </button>
                    <button type="submit"
                            class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition">
                        Eliminar cuenta definitivamente
                    </button>
                </div>
            </form>
        </div>
    </div>

    @if($errors->has('password'))
    <script>
        document.getElementById('delete-modal').classList.remove('hidden');
    </script>
    @endif

</x-app-layout>
