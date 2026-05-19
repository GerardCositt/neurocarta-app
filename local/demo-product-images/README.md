# Fotos demo de productos (opcional, alta calidad)

Coloca aquí tus **JPEG / PNG / WebP** con **exactamente** estos nombres de archivo (o en **`img/demo-product-images/`** o en **`img/`** de la raíz del proyecto con los mismos nombres — la app busca en ese orden). Al pulsar **«Cargar datos de ejemplo»** en Productos, la app usará **primero** esta carpeta y solo si falta un archivo usará **`public/demo/`** del repositorio (fotos de ejemplo empaquetadas) y por último miniaturas vacías.

Recomendación: fotos horizontales tipo carta (aprox. **1200–1600 px** de ancho), buena luz, fondo neutro o contextual de restaurante.

| Archivo | Plato |
|---------|--------|
| `croquetas-jamon.jpg` | Croquetas de jamón |
| `pan-tomate.jpg` | Pan con tomate |
| `ensalada-mixta.jpg` | Ensalada mixta |
| `tabla-embutidos.jpg` | Tabla de embutidos |
| `entrecot.jpg` | Entrecot a la plancha |
| `pollo-ajillo.jpg` | Pollo al ajillo |
| `secreto-iberico.jpg` | Secreto ibérico |
| `carrilleras-vino.jpg` | Carrilleras al vino tinto |
| `merluza-romana.jpg` | Merluza a la romana |
| `gambas-ajillo.jpg` | Gambas al ajillo |
| `pulpo-gallega.jpg` | Pulpo a la gallega |
| `lubina-horno.jpg` | Lubina al horno |
| `crema-catalana.jpg` | Crema catalana |
| `tarta-queso.jpg` | Tarta de queso |
| `brownie-helado.jpg` | Brownie con helado |
| `agua-mineral.jpg` | Agua mineral |
| `vino-casa.jpg` | Vino de la casa |
| `cerveza.jpg` | Cerveza |

Puedes usar `.jpg`, `.jpeg`, `.png` o `.webp`: el nombre base debe coincidir con el plato (por ejemplo `entrecot.webp` sustituye a `entrecot.jpg` en `DemoContent` automáticamente al cargar la demo).

## CDN / servidor externo

Si defines `NEUROCARTA_TEMPLATE_PRODUCT_IMAGE_BASE_URL` en `.env`, las URLs guardadas en BD serán absolutas y **no** se usarán estos archivos locales.

- Por defecto la URL será `{BASE}/demo/croquetas-jamon.jpg` (misma estructura que la ruta en código).
- Si en tu bucket las imágenes están en la raíz (sin carpeta `demo/`), pon `NEUROCARTA_TEMPLATE_PRODUCT_IMAGE_FLAT=true`.

Tras cambiar la URL base o el modo flat, los restaurantes que ya cargaron la demo con URLs antiguas seguirán con las rutas viejas en BD: borra la demo y vuelve a cargarla, o edita las fotos manualmente.
