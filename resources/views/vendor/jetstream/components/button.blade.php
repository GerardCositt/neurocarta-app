<button {{ $attributes->merge([
        'type' => 'submit',
        'style' => 'display:inline-flex;align-items:center;justify-content:center;border-radius:12px;padding:12px 16px;font-size:13px;font-weight:900;letter-spacing:.08em;text-transform:uppercase;border:0;background:#C52439;color:#fff;cursor:pointer;box-shadow:0 12px 34px -18px rgba(197,36,57,.75);',
        'class' => 'nc-btn'
    ]) }}>
    {{ $slot }}
</button>

<style>
  .nc-btn:hover { background: #a01d2e !important; }
  .nc-btn:focus { outline: none; box-shadow: 0 0 0 4px rgba(197,36,57,.25), 0 12px 34px -18px rgba(197,36,57,.75); }
  .nc-btn:disabled { opacity: .55; cursor: not-allowed; }
</style>

