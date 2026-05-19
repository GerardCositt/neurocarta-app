# Imágenes demo empaquetadas (`public/demo/`)

Estos JPEG son **fotografías de ejemplo** (vía [Picsum](https://picsum.photos/), procedentes de [Unsplash](https://unsplash.com/license)) para que la plantilla «Cargar datos de prueba» y los entornos sin CDN **tengan archivos reales en el repo**.

Nombres de archivo: los mismos que `App\Support\DemoContent` (`croquetas-jamon.jpg`, …).

Si importas un SQL con rutas rotas o miniaturas «de IA», ejecuta:

```bash
php artisan demo:resync-template-photos
# o, si cambiaron textos/precios pero los nombres de plato coinciden:
php artisan demo:resync-template-photos --by-name
```

Opcional: `--restaurant=ID` y `--dry-run`.
