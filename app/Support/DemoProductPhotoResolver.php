<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

/**
 * Fotos del menú demo: busca primero en {@see base_path('local/demo-product-images')},
 * luego en {@see base_path('img/demo-product-images')} y {@see base_path('img')}
 * (solo nombres esperados, p. ej. entrecot.jpg), y por último en {@see public_path('demo')} si existe el archivo (p. ej. tras el script local de Pexels).
 *
 * No hay fotos por defecto en el repo: si no hay origen, devuelve null y el producto usa el placeholder.
 * CDN: {@see config('neurocarta.template_product_image_base_url')} guarda la clave demo/archivo.jpg; {@see ProductPhotoUrl} resuelve la URL al mostrar.
 */
final class DemoProductPhotoResolver
{
    /**
     * Ruta relativa al disco public o URL absoluta (https) para un producto de plantilla.
     */
    public static function resolveForTemplateProduct(?string $relativePath): ?string
    {
        $relativePath = trim((string) $relativePath);
        if ($relativePath === '' || str_contains($relativePath, '..')) {
            return null;
        }

        $normalized = ltrim(str_replace('\\', '/', $relativePath), '/');
        if (! preg_match('#^demo/[a-z0-9][a-z0-9._-]*$#i', $normalized)) {
            return null;
        }

        $base = trim((string) config('neurocarta.template_product_image_base_url'));
        if ($base !== '') {
            return $normalized;
        }

        return self::ensureOnPublicDisk($relativePath);
    }

    /**
     * Copia la foto al disco public (`storage/app/public/demo/...`) si hay origen y devuelve la ruta relativa al disco.
     */
    public static function ensureOnPublicDisk(?string $relativePath): ?string
    {
        $relativePath = trim((string) $relativePath);
        if ($relativePath === '' || str_contains($relativePath, '..')) {
            return null;
        }

        $normalized = ltrim(str_replace('\\', '/', $relativePath), '/');

        if (! preg_match('#^demo/[a-z0-9][a-z0-9._-]*$#i', $normalized)) {
            return null;
        }

        $basename = basename($normalized);
        $localHero = self::resolveLocalHeroPath($basename);

        if ($localHero !== null) {
            [$absolutePath, $storageRelative] = $localHero;
            $bytes = file_get_contents($absolutePath);
            if ($bytes !== false && $bytes !== '') {
                Storage::disk('public')->put($storageRelative, $bytes);

                return $storageRelative;
            }
        }

        if (Storage::disk('public')->exists($normalized)) {
            return $normalized;
        }

        $bundledPath = public_path($normalized);
        if (is_file($bundledPath)) {
            $bytes = file_get_contents($bundledPath);
            if ($bytes !== false && $bytes !== '') {
                Storage::disk('public')->put($normalized, $bytes);
            }

            return Storage::disk('public')->exists($normalized) ? $normalized : null;
        }

        return null;
    }

    /**
     * @return array{0: string, 1: string}|null [ruta absoluta, ruta relativa al disco public]
     */
    private static function resolveLocalHeroPath(string $basename): ?array
    {
        $directories = [
            base_path('local/demo-product-images'),
            base_path('img/demo-product-images'),
            base_path('img'),
        ];

        foreach ($directories as $dir) {
            $found = self::resolveHeroInDirectory($dir, $basename);
            if ($found !== null) {
                return $found;
            }
        }

        return null;
    }

    /**
     * Solo coincide por nombre de archivo esperado (no recorre todo el directorio).
     *
     * @return array{0: string, 1: string}|null
     */
    private static function resolveHeroInDirectory(string $dir, string $basename): ?array
    {
        if ($dir === '' || ! is_dir($dir)) {
            return null;
        }

        $stem = pathinfo($basename, PATHINFO_FILENAME);
        $preferredExt = strtolower((string) pathinfo($basename, PATHINFO_EXTENSION));

        $extensions = array_values(array_unique(array_filter([
            $preferredExt,
            'jpg',
            'jpeg',
            'png',
            'webp',
        ])));

        foreach ($extensions as $ext) {
            if ($ext === '') {
                continue;
            }
            $absolutePath = $dir.'/'.$stem.'.'.$ext;
            if (is_file($absolutePath)) {
                return [$absolutePath, 'demo/'.$stem.'.'.$ext];
            }
        }

        return null;
    }
}
