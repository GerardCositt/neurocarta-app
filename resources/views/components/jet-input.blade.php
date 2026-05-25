@props(['disabled' => false])

<input {{ $disabled ? 'disabled' : '' }} {!! $attributes->merge(['class' => 'rounded-md shadow-sm focus:ring-2 focus:outline-none']) !!} style="background:#2a2a2a;border:1px solid rgba(255,255,255,0.12);color:#fff;caret-color:#FF7A00;padding:10px 14px;width:100%;box-sizing:border-box;" onfocus="this.style.borderColor='#FF7A00'" onblur="this.style.borderColor='rgba(255,255,255,0.12)'">
