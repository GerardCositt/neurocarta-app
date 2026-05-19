# Imágenes demo empaquetadas (`public/demo/`)

Fotos de **comida de stock** (Pexels) elegidas para que **coincidan con el tipo de plato** de `DemoContent` (croquetas, entrecot, gambas, etc.). No son fotos de tu restaurante: sirven para la demo técnica.

- Listado y enlaces: `SOURCES.md`
- Regenerar: `bash scripts/download-demo-food-images.sh`

**Fotos reales de carta:** coloca tus JPG en `local/demo-product-images/` con el mismo nombre (`entrecot.jpg`, …). Tienen prioridad al cargar la plantilla.

Tras import SQL o rutas rotas:

```bash
php artisan demo:resync-template-photos --by-name
```
