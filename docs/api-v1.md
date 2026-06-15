# NeuroCarta.ai — API REST v1

Documentación de la API pública para integración con TPV y sistemas externos.

**Disponible en:** Plan Pro y Plan Premium  
**Base URL:** `https://app.neurocarta.ai/api/v1`  
**Formato:** JSON (`Content-Type: application/json`)  
**Rate limit:** 120 peticiones / minuto por API key

---

## Autenticación

Todas las peticiones requieren un header de autorización con la API key del restaurante.

```
Authorization: Bearer nc_live_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

Las API keys se generan desde **Ajustes → API** en el panel de administración. Cada restaurante puede tener una sola key activa. Si se genera una nueva, la anterior queda revocada automáticamente.

### Errores de autenticación

| HTTP | Mensaje | Causa |
|------|---------|-------|
| 401 | `API key requerida` | No se envió el header `Authorization` |
| 401 | `API key inválida o revocada` | La key no existe o fue revocada |
| 403 | `El acceso a la API requiere plan Pro o Premium activo` | La suscripción no está activa o es plan Básico |

---

## Endpoints

### GET `/menu`

Devuelve la carta completa del restaurante: categorías y productos.

**Request**
```http
GET https://app.neurocarta.ai/api/v1/menu
Authorization: Bearer nc_live_...
```

**Response 200**
```json
{
  "restaurant": "El Resstauante",
  "categories": [
    {
      "id": 12,
      "external_sku": "ENT",
      "name": "Entrantes",
      "hidden": false,
      "order": 1,
      "products": [
        {
          "id": 101,
          "external_sku": "ENS-001",
          "name": "Ensalada de la casa",
          "description": "Tomate de temporada con ventresca.",
          "price": "14.00",
          "offer_price": null,
          "offer": false,
          "hidden": false,
          "featured": true,
          "recommended": false,
          "photo": "img/abc123.webp",
          "category_id": 12,
          "order": 1
        }
      ]
    }
  ]
}
```

---

### POST `/categories/sync`

Crea o actualiza categorías por `sku`. Si la categoría no existe, se crea. Si ya existe, se actualiza.

**Request**
```http
POST https://app.neurocarta.ai/api/v1/categories/sync
Authorization: Bearer nc_live_...
Content-Type: application/json
```

```json
[
  { "sku": "ENT",  "name": "Entrantes",  "hidden": false },
  { "sku": "PRIN", "name": "Principales","hidden": false },
  { "sku": "POST", "name": "Postres",    "hidden": false }
]
```

**Campos**

| Campo | Tipo | Req. | Descripción |
|-------|------|------|-------------|
| `sku` | string (max 100) | ✓ | Identificador único en tu TPV |
| `name` | string (max 255) | ✓ | Nombre de la categoría |
| `hidden` | boolean | — | `true` para ocultar de la carta pública. Default: `false` |

**Response 200**
```json
{
  "synced": 3,
  "results": [
    { "sku": "ENT",  "id": 12, "status": "ok" },
    { "sku": "PRIN", "id": 13, "status": "ok" },
    { "sku": "POST", "id": 14, "status": "ok" }
  ]
}
```

Si el plan no permite más categorías, el resultado de esa categoría es `"status": "skipped"` con el campo `"reason"`.

---

### POST `/products/sync`

Crea o actualiza productos por `sku`. Soporta imágenes remotas (se descargan y optimizan automáticamente).

**Request**
```http
POST https://app.neurocarta.ai/api/v1/products/sync
Authorization: Bearer nc_live_...
Content-Type: application/json
```

```json
[
  {
    "sku": "ENS-001",
    "name": "Ensalada de la casa",
    "price": 14.00,
    "category_sku": "ENT",
    "description": "Tomate de temporada con ventresca, regado con vinagreta especial.",
    "image_url": "https://tu-tpv.com/fotos/ensalada.jpg",
    "active": true
  },
  {
    "sku": "ARR-002",
    "name": "Arroz meloso de mar",
    "price": 21.00,
    "category_sku": "PRIN",
    "active": true
  }
]
```

**Campos**

| Campo | Tipo | Req. | Descripción |
|-------|------|------|-------------|
| `sku` | string (max 100) | ✓ | Identificador único en tu TPV |
| `name` | string (max 255) | ✓ | Nombre del producto |
| `price` | numeric ≥ 0 | ✓ | Precio (sin símbolo de moneda) |
| `category_sku` | string (max 100) | — | SKU de la categoría (debe existir previamente) |
| `description` | string (max 2000) | — | Descripción del plato |
| `image_url` | URL (max 500) | — | URL pública de la imagen. Se descarga, redimensiona y guarda |
| `active` | boolean | — | `false` para ocultar de la carta. Default: `true` |

**Response 200**
```json
{
  "synced": 2,
  "results": [
    { "sku": "ENS-001", "id": 101, "status": "ok" },
    { "sku": "ARR-002", "id": 102, "status": "ok" }
  ]
}
```

Si el plan no permite más productos, el resultado es `"status": "skipped"` con `"reason"`.

---

### DELETE `/products/{sku}`

Elimina un producto por su SKU.

**Request**
```http
DELETE https://app.neurocarta.ai/api/v1/products/ENS-001
Authorization: Bearer nc_live_...
```

**Response 200**
```json
{
  "deleted": true,
  "sku": "ENS-001"
}
```

**Response 404**
```json
{
  "error": "Producto con SKU 'ENS-001' no encontrado"
}
```

---

### PATCH `/products/{sku}/status`

Activa o desactiva un producto (visible / oculto en carta) sin eliminar ni modificar el resto de datos.

**Request**
```http
PATCH https://app.neurocarta.ai/api/v1/products/ENS-001/status
Authorization: Bearer nc_live_...
Content-Type: application/json
```

```json
{ "active": false }
```

**Campos**

| Campo | Tipo | Req. | Descripción |
|-------|------|------|-------------|
| `active` | boolean | ✓ | `true` = visible, `false` = oculto |

**Response 200**
```json
{
  "sku": "ENS-001",
  "active": false
}
```

**Response 404**
```json
{
  "error": "Producto con SKU 'ENS-001' no encontrado"
}
```

---

## Flujo recomendado de integración

Para una sincronización completa desde tu TPV:

```
1. POST /categories/sync   → sincroniza todas las categorías
2. POST /products/sync     → sincroniza todos los productos (batch de max. 100)
3. PATCH /products/{sku}/status  → cuando un plato se agota o vuelve a estar disponible
4. DELETE /products/{sku}        → cuando un plato se elimina del TPV
```

**Sincronización periódica recomendada:** cada 15-30 minutos para precios y disponibilidad. Al abrir y cerrar el local para status.

---

## Códigos de error generales

| HTTP | Descripción |
|------|-------------|
| 400 | Parámetros inválidos o faltantes (ver `errors` en el body) |
| 401 | API key ausente o inválida |
| 403 | Plan sin acceso a la API |
| 404 | Recurso no encontrado |
| 422 | Error de validación de campos |
| 429 | Rate limit superado (120 req/min) |
| 500 | Error interno del servidor |

---

## Límites por plan

| Límite | Pro | Premium |
|--------|-----|---------|
| Productos | 250 | 1.000 |
| Categorías | 15 | 100 |
| Restaurantes | 2 | 3 |
| Rate limit | 120 req/min | 120 req/min |

Los productos y categorías que superen el límite del plan se devuelven con `"status": "skipped"`.

---

## Ejemplo completo con cURL

```bash
# 1. Sincronizar categorías
curl -X POST https://app.neurocarta.ai/api/v1/categories/sync \
  -H "Authorization: Bearer nc_live_TU_API_KEY" \
  -H "Content-Type: application/json" \
  -d '[{"sku":"ENT","name":"Entrantes"},{"sku":"PRIN","name":"Principales"}]'

# 2. Sincronizar productos
curl -X POST https://app.neurocarta.ai/api/v1/products/sync \
  -H "Authorization: Bearer nc_live_TU_API_KEY" \
  -H "Content-Type: application/json" \
  -d '[{"sku":"ENS-001","name":"Ensalada","price":14.00,"category_sku":"ENT","active":true}]'

# 3. Ocultar un plato agotado
curl -X PATCH https://app.neurocarta.ai/api/v1/products/ENS-001/status \
  -H "Authorization: Bearer nc_live_TU_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{"active":false}'

# 4. Consultar la carta completa
curl https://app.neurocarta.ai/api/v1/menu \
  -H "Authorization: Bearer nc_live_TU_API_KEY"
```

---

*NeuroCarta.ai® · API v1 · Junio 2026*
