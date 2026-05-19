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

    /**
     * Base URL (HTTPS) donde están las fotos de la plantilla de ejemplo, sin barra final.
     * Ej.: https://cdn.neurocarta.ai/menu-template
     *
     * Por defecto se concatena la ruta relativa de DemoContent (p. ej. demo/croquetas-jamon.jpg),
     * es decir la URL final es {base}/demo/croquetas-jamon.jpg.
     *
     * Si tus archivos están en la raíz del bucket/CDN (sin carpeta demo/), pon
     * NEUROCARTA_TEMPLATE_PRODUCT_IMAGE_FLAT=true para usar solo el nombre de archivo.
     *
     * Vacío = copiar desde local/demo-product-images, public/demo o img/ al storage.
     */
    'template_product_image_base_url' => rtrim((string) env('NEUROCARTA_TEMPLATE_PRODUCT_IMAGE_BASE_URL', ''), '/'),

    /** true = URL {base}/croquetas-jamon.jpg | false = {base}/demo/croquetas-jamon.jpg */
    'template_product_image_flat' => filter_var(env('NEUROCARTA_TEMPLATE_PRODUCT_IMAGE_FLAT', false), FILTER_VALIDATE_BOOL),

];
