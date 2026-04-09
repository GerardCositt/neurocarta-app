@props(['disabled' => false])

<input {{ $disabled ? 'disabled' : '' }} {!! $attributes->merge([
    'class' => 'nc-input',
    'style' => 'width:100%;box-sizing:border-box;border-radius:12px;border:1px solid rgba(255,255,255,.12);background:#0F0F0F;padding:12px 14px;font-size:14px;color:#fff;box-shadow:inset 0 0 0 1px rgba(255,255,255,.03);'
]) !!}>

<style>
  .nc-input::placeholder { color: rgba(255,255,255,.35); }
  .nc-input:focus { outline: none; border-color: rgba(255,193,7,.50); box-shadow: 0 0 0 4px rgba(255,193,7,.12), inset 0 0 0 1px rgba(255,255,255,.03); }
</style>

