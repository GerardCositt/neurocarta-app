<div style="min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 56px 16px;">
    <div style="width: 100%; max-width: 460px;">
        <div style="text-align: center;">
            {{ $logo }}
            <p style="margin: 14px 0 0; font-size: 14px; line-height: 1.5; color: rgba(255,255,255,.72);">
                Accede a tu panel. Si aún no tienes cuenta, solicita acceso.
            </p>
        </div>

        <div style="margin-top: 22px; border-radius: 16px; border: 1px solid rgba(255,255,255,.10); background: rgba(255,255,255,.04); padding: 22px; box-shadow: 0 30px 80px -40px rgba(0,0,0,.75);">
            {{ $slot }}
        </div>

        <div style="margin-top: 16px; text-align: center; font-size: 12px; color: rgba(255,255,255,.50);">
            © {{ date('Y') }} NeuroCarta<span style="color:#FFC107;">.ai</span><span style="vertical-align: super; font-size: 10px; color: rgba(255,255,255,.65);" aria-label="marca registrada">®</span>
        </div>
    </div>
</div>

