# Integración NeuroCarta ↔ Odoo

> Análisis técnico y decisiones de arquitectura — Abril 2026

---

## Contexto

Este documento recoge el análisis de viabilidad y la estrategia para integrar NeuroCarta con Odoo.
El objetivo es que los restaurantes que ya usan Odoo como ERP/TPV puedan sincronizar automáticamente
su carta y recibir los pedidos QR directamente en su sistema.

---

## Estado actual de NeuroCarta (puntos de partida)

| Área | Estado |
|---|---|
| API REST propia | Casi inexistente — solo endpoint `auth:sanctum` en `api.php` |
| Webhooks | No implementados |
| Sistema de colas (Queue/Jobs) | No activo |
| Campo de referencia externa en productos | No existe (no hay `odoo_product_id`) |
| Precedente de importación externa | `ImportRemoteMenu` (Artisan command) — importa carta desde HTML |
| Pedidos | `StoreOrderController` — acepta pedidos vía POST pero es ruta web, no API real |

---

## Lo que ofrece Odoo de serie

- **API JSON-RPC nativa** — permite leer y crear cualquier modelo sin módulo custom
- **Módulo POS** — productos, categorías, precios, stock
- **Módulo Restaurant** — mesas, comandas
- **Webhooks** (desde Odoo 16+) — notifica cambios en tiempo real

---

## Puntos de integración viables

### 1. Sincronización de carta: Odoo → NeuroCarta ✅ Alta viabilidad

**Qué hace**: cuando el restaurante actualiza precios o productos en Odoo, se refleja
automáticamente en la carta digital de NeuroCarta.

**Flujo**:
```
Odoo (maestro de productos)
    ↓  JSON-RPC nativo de Odoo
NeuroCarta Artisan command `odoo:sync {restaurant}`
    ↓  escribe en products / categories de NeuroCarta
Carta pública (lo que ve el cliente)
```

**Mapeado de campos**:

| Odoo | NeuroCarta |
|---|---|
| `product.name` | `products.name` |
| `product.list_price` | `products.price` |
| `product.description` | `products.description` |
| `product.image_1920` | `products.photo` |
| `pos.category` | `categories.name` |

**Brechas a resolver**:
- Añadir columna `odoo_product_id` en `products` para evitar duplicados en cada sync
- Añadir columna `odoo_category_id` en `categories`
- Nueva tabla `odoo_connections` para almacenar credenciales por restaurante (url, db, user, api_key)

---

### 2. Envío de pedidos: NeuroCarta → Odoo ✅ Viabilidad media

**Qué hace**: cada pedido QR generado en NeuroCarta crea automáticamente una comanda en Odoo POS.

**Flujo**:
```
Cliente hace pedido en carta QR
    ↓
StoreOrderController crea Order en NeuroCarta
    ↓  dispara Laravel Job (asíncrono, con reintentos)
Job llama a Odoo JSON-RPC → crea pos.order
```

**Condición necesaria**: los `product_id` de NeuroCarta deben estar vinculados a los de Odoo.
Por eso el punto 1 (sync de productos) debe ir primero.

**Brechas a resolver**:
- Activar Laravel Queue (Redis o database driver)
- Crear `SendOrderToOdooJob` con política de reintentos
- Si Odoo no responde, el pedido en NeuroCarta no debe fallar (cola desacoplada)

---

### 3. Sincronización de stock: Odoo → NeuroCarta ⚠️ Viabilidad media-baja

**Qué hace**: si un producto se agota en Odoo, se oculta automáticamente en la carta.

**Flujo**:
```
Stock cambia en Odoo
    ↓  webhook de Odoo (requiere configurarlo en Odoo)
NeuroCarta endpoint receptor `/api/webhooks/odoo`
    ↓
products.active = false (producto oculto en carta)
```

**Brechas a resolver**:
- NeuroCarta no tiene ningún endpoint autenticado para recibir webhooks
- Hay que construir autenticación de webhook (firma HMAC o API key por restaurante)

---

## Arquitectura recomendada (MVP)

```
┌─────────────────────────────────────────────┐
│                   ODOO                      │
│  Productos / Precios / Stock / POS          │
│  API JSON-RPC nativa (sin módulo custom)    │
└──────────────┬──────────────────────────────┘
               │ JSON-RPC
               ▼
┌─────────────────────────────────────────────┐
│              NEUROCARTA                     │
│                                             │
│  OdooSyncService                            │
│  └── Artisan: odoo:sync {restaurant}        │
│      └── Lee productos de Odoo              │
│      └── Escribe en products/categories     │
│                                             │
│  StoreOrderController                       │
│  └── Crea Order en NeuroCarta               │
│  └── Dispara SendOrderToOdooJob             │
│      └── Llama a Odoo: crea pos.order       │
│                                             │
│  API REST (api.php)                         │
│  └── Endpoints autenticados (Sanctum)       │
│  └── Para futuros consumidores              │
└──────────────┬──────────────────────────────┘
               │
               ▼
        Carta pública QR
        (lo que ve el cliente)
```

---

## ¿Módulo Odoo o API en NeuroCarta?

### Decisión: API en NeuroCarta + API nativa de Odoo (sin módulo custom)

**Razones**:

1. **Odoo ya tiene API JSON-RPC robusta** — no necesitas un módulo para leer o escribir datos
2. **Toda la lógica vive en NeuroCarta** (Laravel), que es el entorno que se conoce y controla
3. **No hay dependencia de versión de Odoo** — un módulo custom debe mantenerse para Odoo 16, 17, 18...
4. **Despliegue independiente** — NeuroCarta se actualiza sin tocar nada en Odoo

### ¿Cuándo tendría sentido un módulo Odoo?

- Si el restaurante necesita ver pedidos de NeuroCarta dentro de la UI de Odoo
- Si necesita un botón "Publicar en carta" en la ficha de producto de Odoo
- Si se necesitan webhooks en tiempo real (stock) que Odoo empuje automáticamente
- Si hay suficientes clientes Odoo para justificar el mantenimiento

En ese caso, el módulo sería un **thin client** (solo llama a la API de NeuroCarta).
La lógica real sigue en el servidor de NeuroCarta.

---

## Protección del código e IP

### Preocupación planteada
Publicar un módulo Odoo en el App Store expone el código a la competencia.

### Análisis

El valor de NeuroCarta **no está en el código del módulo Odoo**, está en:

- El backend Laravel (SaaS multi-tenant, privado en servidor)
- La lógica de IA y traducciones
- El flujo QR → pedido → cocina
- La infraestructura y base de clientes

Un módulo Odoo "thin client" contendría únicamente llamadas HTTP a `api.neurocarta.ai`.
Aunque alguien lo copie, sin las credenciales y sin el backend de NeuroCarta no funciona.

### Reglas para proteger la IP

| Qué hacer | Por qué |
|---|---|
| Toda la lógica de negocio en NeuroCarta server-side | El servidor es privado, no es copiable |
| El módulo Odoo solo llama a la API de NeuroCarta | Sin tu API key no sirve de nada |
| **No publicar en Odoo App Store** | Evitar exposición pública del módulo |
| Distribuir el módulo directamente a clientes (.zip) | Controlas quién lo tiene y en qué versión |
| Autenticación por API key única por restaurante | Puedes revocar acceso si es necesario |

---

## Plan de implementación por fases

### Fase 1 — Base (prerequisito para todo lo demás)
- [ ] Columna `odoo_product_id` en tabla `products`
- [ ] Columna `odoo_category_id` en tabla `categories`
- [ ] Tabla `odoo_connections` (url, database, username, api_key, restaurant_id)
- [ ] Clase `OdooClient` en Laravel (wrapper JSON-RPC)

### Fase 2 — Sync de carta Odoo → NeuroCarta
- [ ] Artisan command `odoo:sync {restaurant_id}`
- [ ] Mapeo de productos y categorías de Odoo a NeuroCarta
- [ ] Scheduler: sync automático cada X minutos
- [ ] Panel en Filament para ver última sincronización y logs

### Fase 3 — Pedidos NeuroCarta → Odoo
- [ ] Activar Laravel Queue (driver: database o Redis)
- [ ] Job `SendOrderToOdooJob` con reintentos
- [ ] Hook en `StoreOrderController` para disparar el job
- [ ] Gestión de errores: pedido no falla aunque Odoo no responda

### Fase 4 — API REST pública NeuroCarta (opcional / futura)
- [ ] Endpoints REST en `api.php` con autenticación Sanctum
- [ ] Endpoint receptor de webhooks (`/api/webhooks/odoo`)
- [ ] Documentación de la API

### Fase 5 — Módulo Odoo thin client (solo si hay demanda)
- [ ] Módulo Python mínimo para Odoo
- [ ] Botón "Sincronizar con NeuroCarta" en producto
- [ ] Vista de pedidos NeuroCarta dentro de Odoo
- [ ] Distribución directa a clientes (no App Store)

---

## Estimación de esfuerzo

| Fase | Estimación |
|---|---|
| Fase 1 — Base | 1 día |
| Fase 2 — Sync carta | 3-4 días |
| Fase 3 — Pedidos a Odoo | 2-3 días |
| Fase 4 — API REST | 2 días |
| Fase 5 — Módulo Odoo | 5-7 días (solo si hay demanda) |

---

## Notas técnicas

- **Odoo JSON-RPC endpoint**: `https://[odoo-url]/jsonrpc` (método `call`)
- **Autenticación Odoo**: primero `common.authenticate` → devuelve `uid`, luego `object.execute_kw`
- **Modelos clave en Odoo POS**: `product.template`, `product.product`, `pos.category`, `pos.order`, `pos.order.line`
- **NeuroCarta usa PostgreSQL** — compatible con volúmenes de datos de Odoo sin problema
- **Odoo puede ser cloud (odoo.com) o self-hosted** — el JSON-RPC funciona igual en ambos casos
