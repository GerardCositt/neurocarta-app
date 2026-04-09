@props([
    'variant' => 'info',
    'icon' => null,
    'showIcon' => true,
    'autoDismiss' => null,
])

@php
    $allowed = ['info', 'success', 'warning', 'danger'];
    $variant = in_array((string) $variant, $allowed, true) ? $variant : 'info';
    $defaultIcons = [
        'info' => '💡',
        'success' => '✅',
        'warning' => '⚠️',
        'danger' => '❌',
    ];
    $emoji = $showIcon ? ($icon ?? $defaultIcons[$variant]) : null;
    $dismissMs = ($autoDismiss !== null && $autoDismiss !== '' && is_numeric($autoDismiss))
        ? max(0, (int) $autoDismiss)
        : null;
@endphp

<div {{ $attributes->merge(['class' => 'admin-banner admin-banner--' . $variant]) }}
     role="{{ $variant === 'danger' ? 'alert' : 'status' }}"
     @if ($dismissMs !== null) data-auto-dismiss="{{ $dismissMs }}" @endif>
    @if ($emoji !== null && $emoji !== '')
        <span class="admin-banner__icon" aria-hidden="true">{{ $emoji }}</span>
    @endif
    <div class="admin-banner__content">{{ $slot }}</div>
</div>
