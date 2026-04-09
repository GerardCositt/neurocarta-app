{{-- Pastillas Destacado / Recomendado — carta pública (neuromarketing: icono + jerarquía + microcopy) --}}
@if (!empty($featured) || !empty($recommended))
    <div class="commercial-pill-row">
        @if (!empty($featured))
            <span class="commercial-pill commercial-pill--featured @if(!empty($modal)) commercial-pill--modal @endif">
                <span class="commercial-pill__visual" aria-hidden="true">
                    <svg class="commercial-pill__svg" viewBox="0 0 24 24" fill="currentColor" focusable="false">
                        <path d="M5 16L3 5l5.5 5L12 4l3.5 6L21 5l-2 11H5zm2.7-2h8.6l.5-5.1-2.4 2.2-2.5-4.4-2.5 4.4-2.4-2.2L7.2 14z"/>
                    </svg>
                </span>
                <span class="commercial-pill__text">
                    <span class="commercial-pill__title">{{ __('public_menu.pill_featured') }}</span>
                    <span class="commercial-pill__hook">{{ __('public_menu.pill_hook_featured') }}</span>
                </span>
            </span>
        @endif
        @if (!empty($recommended))
            <span class="commercial-pill commercial-pill--recommended @if(!empty($modal)) commercial-pill--modal @endif">
                <span class="commercial-pill__visual" aria-hidden="true">
                    <svg class="commercial-pill__svg" viewBox="0 0 24 24" fill="currentColor" focusable="false">
                        <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                    </svg>
                </span>
                <span class="commercial-pill__text">
                    <span class="commercial-pill__title">{{ __('public_menu.pill_recommended') }}</span>
                    <span class="commercial-pill__hook">{{ __('public_menu.pill_hook_recommended') }}</span>
                </span>
            </span>
        @endif
    </div>
@endif
