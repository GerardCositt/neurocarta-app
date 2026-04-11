<div style="min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 56px 16px;">
    <div style="width: 100%; max-width: 460px;">
        <div style="text-align: center;">
            {{ $logo }}
            <p style="margin: 14px 0 0; font-size: 14px; line-height: 1.5; color: rgba(255,255,255,.72);">
                Accede a tu panel.
            </p>
        </div>

        <div style="margin-top: 22px; border-radius: 16px; border: 1px solid rgba(255,193,7,.18); background: rgba(255,255,255,.04); padding: 22px; box-shadow: 0 30px 80px -40px rgba(0,0,0,.75), 0 0 0 1px rgba(197,36,57,.18) inset;">
            {{ $slot }}
        </div>

        <div style="margin-top: 16px; text-align: center; font-size: 12px; color: rgba(255,255,255,.50);">
            © {{ date('Y') }} <span style="color:#ffffff;">NeuroCarta</span><span style="color:#FFC107;font-weight:700;">.ai</span><span style="vertical-align: super; font-size: 10px; color: rgba(255,255,255,.65);" aria-label="marca registrada">®</span>
        </div>
    </div>
</div>

