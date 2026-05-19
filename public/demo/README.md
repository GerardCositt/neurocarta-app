# Fotos de la plantilla demo (opcional)

**No hay imágenes empaquetadas en el repositorio.** La plantilla «Cargar datos de prueba» crea categorías y platos con los nombres de `DemoContent`; la foto solo se asigna si existe un archivo o un CDN configurado.

## Opción recomendada — tus fotos reales

Coloca JPEG/PNG/WebP en **`local/demo-product-images/`** con el nombre del plato (`croquetas-jamon.jpg`, `entrecot.jpg`, …). Lista completa en `local/demo-product-images/README.md`.

Luego: **Cargar datos de prueba** o `php artisan demo:resync-template-photos --by-name`.

## Opción CDN (producción, sin peso en el VPS)

En `.env`:

```env
NEUROCARTA_TEMPLATE_PRODUCT_IMAGE_BASE_URL=https://tu-cdn.ejemplo/menu-template
# NEUROCARTA_TEMPLATE_PRODUCT_IMAGE_FLAT=false
```

Archivos en `{BASE}/demo/croquetas-jamon.jpg`, etc.

## Sin fotos

Si no hay archivo ni CDN, el producto se crea **sin foto** y en el panel/carta se muestra el placeholder (`noimg.png`). Puedes subir fotos después o usar IA (plan Pro).

## Solo desarrollo — stock Pexels (no se sube a Git)

```bash
bash scripts/download-demo-food-images.sh   # escribe en public/demo/ (ignorado por git)
```
