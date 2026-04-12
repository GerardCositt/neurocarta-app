<x-guest-layout>
    <x-jet-authentication-card>
        <x-slot name="logo">
            <x-jet-authentication-card-logo />
        </x-slot>

        {{-- Plan seleccionado --}}
        @php
            $planLabels = [
                'trial'   => ['label' => 'Trial gratuito', 'color' => '#FFC107', 'bg' => 'rgba(255,193,7,.12)'],
                'basico'  => ['label' => 'Plan Básico · 59€/mes', 'color' => 'rgba(255,255,255,.8)', 'bg' => 'rgba(255,255,255,.06)'],
                'pro'     => ['label' => 'Plan Pro · 129€/mes', 'color' => '#fff', 'bg' => 'rgba(197,36,57,.15)'],
                'premium' => ['label' => 'Plan Premium · 249€/mes', 'color' => 'rgba(255,255,255,.8)', 'bg' => 'rgba(255,255,255,.06)'],
            ];
            $planInfo = $planLabels[$plan] ?? $planLabels['trial'];
        @endphp

        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;">
            <span style="font-size:13px;font-weight:700;color:{{ $planInfo['color'] }};background:{{ $planInfo['bg'] }};padding:5px 12px;border-radius:20px;">
                {{ $planInfo['label'] }}
            </span>
            <a href="{{ route('register') }}" style="font-size:12px;color:rgba(255,255,255,.45);text-decoration:underline;">Cambiar</a>
        </div>

        <x-jet-validation-errors class="mb-4" />

        <form method="POST" action="{{ route('register') }}">
            @csrf
            <input type="hidden" name="plan" value="{{ $plan }}">

            {{-- Email --}}
            <div>
                <x-jet-label for="email" value="Email" />
                <x-jet-input id="email" class="block mt-1 w-full" type="email" name="email"
                    :value="old('email')" required autofocus autocomplete="email"
                    placeholder="tu@restaurante.com" />
            </div>

            {{-- Nombre del restaurante --}}
            <div class="mt-4">
                <x-jet-label for="restaurant_name" value="Nombre del restaurante" />
                <x-jet-input id="restaurant_name" class="block mt-1 w-full" type="text" name="restaurant_name"
                    :value="old('restaurant_name')" required autocomplete="organization"
                    placeholder="Ej: Bar Pepe, La Taberna..." />
            </div>

            {{-- Teléfono --}}
            <div class="mt-4">
                <x-jet-label for="phone" value="Teléfono" />
                <x-jet-input id="phone" class="block mt-1 w-full" type="tel" name="phone"
                    :value="old('phone')" required autocomplete="tel"
                    placeholder="+34 600 000 000" />
                <p style="margin:6px 0 0;font-size:12px;color:rgba(255,255,255,.40);">
                    Solo para verificar que eres real. No llamamos.
                </p>
            </div>

            {{-- Aviso legal --}}
            <p style="margin:18px 0 0;font-size:12px;color:rgba(255,255,255,.40);line-height:1.5;">
                Al registrarte aceptas los
                <a href="{{ route('terms.show') }}" target="_blank" style="color:rgba(255,255,255,.65);text-decoration:underline;">Términos de servicio</a>
                y la
                <a href="{{ route('policy.show') }}" target="_blank" style="color:rgba(255,255,255,.65);text-decoration:underline;">Política de privacidad</a>.
            </p>

            <div style="margin-top:20px;">
                <button type="submit" style="width:100%;padding:13px;border-radius:12px;border:none;cursor:pointer;font-size:15px;font-weight:800;letter-spacing:-0.01em;
                    {{ $plan === 'trial' ? 'background:#FFC107;color:#0F0F0F;' : 'background:#c52439;color:#fff;' }}">
                    @if($plan === 'trial')
                        Crear cuenta gratis
                    @else
                        Continuar al pago
                    @endif
                </button>
            </div>

            <div style="margin-top:16px;text-align:center;font-size:13px;color:rgba(255,255,255,.45);">
                ¿Ya tienes cuenta?
                <a href="{{ route('login') }}" style="color:rgba(255,255,255,.70);text-decoration:underline;">Inicia sesión</a>
            </div>
        </form>
    </x-jet-authentication-card>
</x-guest-layout>
