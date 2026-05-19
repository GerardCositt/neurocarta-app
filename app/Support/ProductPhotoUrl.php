<?php

namespace App\Support;

/**
 * URL pública para mostrar la foto de un producto (storage relativo o URL absoluta/CDN).
 */
final class ProductPhotoUrl
{
    public static function publicUrl(?string $photo): string
    {
        $photo = trim((string) $photo);
        if ($photo === '') {
            return asset('img/noimg.png');
        }

        if (preg_match('#^https?://#i', $photo)) {
            return $photo;
        }

        // URLs protocolo-relativo "//cdn…"
        if (str_starts_with($photo, '//')) {
            return 'https:'.$photo;
        }

        // Plantilla demo: misma clave en BD (demo/archivo.jpg); CDN opcional sin guardar URL absoluta.
        if (preg_match('#^demo/[a-z0-9][a-z0-9._-]*$#i', $photo)) {
            $base = trim((string) config('neurocarta.template_product_image_base_url'));
            if ($base !== '') {
                $flat = (bool) config('neurocarta.template_product_image_flat');
                $suffix = $flat ? basename($photo) : $photo;

                return rtrim($base, '/').'/'.$suffix;
            }
        }

        return asset('storage/'.$photo);
    }
}
