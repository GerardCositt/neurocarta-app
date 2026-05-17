<?php

return [

    /** Subdominio de la carta pública de ventas (demo.neurocarta.ai). */
    'demo_subdomain' => env('NEUROCARTA_DEMO_SUBDOMAIN', 'demo'),

    /**
     * Emails con acceso admin al panel (Premium en todos los locales + selector de restaurantes).
     * FILAMENT_ADMIN_EMAIL tiene prioridad; esto cubre test@test.com si no está en .env.
     */
    'demo_admin_emails' => array_values(array_filter(array_map(
        'trim',
        explode(',', env('NEUROCARTA_DEMO_ADMIN_EMAILS', 'test@test.com'))
    ))),

];
