# Roadmap: planes, Stripe y creación de tenants

Objetivo: dividir el producto en **3 modalidades de tarifa**, incorporar **Stripe** y automatizar la **creación de tenants/restaurantes tras el pago**, aplicando **limitaciones por plan**.

## Alcance (MVP)

- **Planes**: 3 tarifas (ej. Basic / Pro / Premium) con límites claros.
- **Cobro**: Stripe Checkout (suscripción mensual/anual o pago único; definir).
- **Provisioning**: tras pago exitoso, crear tenant/restaurante y usuario admin.
- **Entitlements**: aplicar límites por plan (features y cuotas) en API/UI.
- **Operaciones**: upgrades/downgrades, cancelación, impagos, facturas.

## Decisiones a cerrar (antes de picar código)

- **Tipo de cobro**:
  - Suscripción (recomendado para planes) vs pago único.
  - Mensual/anual y si hay prueba gratuita.
- **Qué es un “tenant”** en v2:
  - ¿Un `Restaurant` ya es el tenant?
  - ¿Se necesita un modelo `Tenant/Account` separado (una cuenta puede tener varios restaurantes)?
- **Dominio / subdominio**:
  - ¿Se asigna `subdomain` automáticamente?
  - ¿Se permite dominio propio?
- **Propietario**:
  - ¿Un `User` puede administrar varios restaurantes? (probable)
- **Migración de clientes existentes**:
  - ¿Cómo se pasan restaurantes ya creados a un plan?

## Modelo de planes (propuesta inicial)

Define límites por plan en dos bloques:

- **Features (boolean)**: activar/desactivar módulos.
  - Importar CSV
  - Importar con IA
  - Traducciones
  - Pedidos
  - Multi-idioma público
  - Personalización avanzada (logos/paleta/branding)
- **Cuotas (numéricas)**:
  - Máx. productos
  - Máx. categorías
  - Máx. idiomas/traducciones
  - Límite IA (créditos o “ilimitado”)
  - Máx. pedidos/mes (si aplica)

## Plan de ejecución (tareas)

### 1) Inventario de features y puntos de control

- **Mapa de funcionalidades**:
  - Listar acciones del panel que deben limitarse (crear/editar/borrar/importar).
  - Listar endpoints/API afectados (si existen).
- **Puntos de enforcement**:
  - Middleware / Policies / Gates.
  - Servicios de dominio (recomendado: un `PlanEntitlementService`).
- **UI/UX**:
  - Estado “bloqueado por plan” con CTA a upgrade.

Entregable: tabla “feature → dónde se controla → mensaje al usuario”.

### 2) Datos y arquitectura de “suscripción”

- Crear modelos/tablas necesarias para:
  - **Plan** (o config estática + enum).
  - **Subscription** / **BillingAccount** vinculada a usuario/tenant.
  - Registro de **Stripe Customer ID**.
  - Estado: active / past_due / canceled / trialing.
- Definir relación:
  - Opción A (simple): `Restaurant` tiene `plan` + `stripe_*`.
  - Opción B (más escalable): `Account` (tenant) y dentro varios `Restaurant`.

Entregable: diagrama simple de entidades y relaciones.

### 3) Stripe: integración base

- Crear cuenta Stripe + modo test.
- Variables `.env`:
  - `STRIPE_KEY`, `STRIPE_SECRET`, `STRIPE_WEBHOOK_SECRET`
- Checkout:
  - Endpoint “crear sesión de checkout” por plan.
  - Página “elige plan” (3 tarjetas).
  - Return URLs: success/cancel.
- Webhooks (imprescindible):
  - `checkout.session.completed`
  - `invoice.paid`
  - `invoice.payment_failed`
  - `customer.subscription.updated`
  - `customer.subscription.deleted`

Entregable: flujo completo de pago en test + logs de webhook OK.

### 4) Provisioning: crear tenant/restaurante tras el pago

- Definir payload mínimo de alta:
  - email propietario
  - nombre restaurante
  - subdominio deseado (si aplica)
  - plan elegido
- Flujo recomendado:
  1. Usuario elige plan y rellena datos básicos.
  2. Checkout de Stripe.
  3. Webhook confirma pago.
  4. Backend crea:
     - `User` (si no existe) y lo marca como owner/admin.
     - `Restaurant` (tenant) con settings iniciales.
     - `Subscription/BillingAccount` con estado active.
  5. Enviar email “tu panel está listo” con enlace `/login` y URL pública.

Puntos críticos:
- Idempotencia del webhook (evitar dobles altas).
- Manejar casos donde el usuario ya exista.
- Manejar subdominio duplicado (estrategia de fallback).

Entregable: tenant creado automáticamente tras pago test.

### 5) Entitlements (limitaciones por plan)

- Implementar servicio central:
  - `canUse(feature)`
  - `assertCanUse(feature)` (lanza excepción con mensaje)
  - `remainingQuota(quota)`
- Aplicar enforcement:
  - Crear producto/categoría (máximos).
  - Importaciones (CSV/IA).
  - Traducciones.
  - Pedidos (si aplica).
  - IA: integrar con el servicio actual de créditos.
- UI:
  - Deshabilitar botones + mensaje claro + link “Mejorar plan”.

Entregable: límites reales aplicados y probados.

### 6) Gestión del plan (upgrade/downgrade/cancel)

- Pantalla “Plan y facturación”:
  - Plan actual + estado.
  - Botón upgrade/downgrade.
  - Cancelar suscripción.
  - Enlaces a facturas/portal.
- Stripe Billing Portal (recomendado):
  - Acceso desde panel.
  - Mantener sincronizado por webhooks.

Entregable: cambios de plan sin romper acceso.

### 7) Migración / admin interno (opcional)

- Comando Artisan:
  - asignar plan a restaurantes existentes
  - simular estados (demo/staging)
- Admin interno:
  - ver suscripciones, forzar plan, ver eventos webhook.

## Test plan (checklist)

- [ ] Compra plan Basic en modo test → crea restaurante + user owner.
- [ ] QR/URL pública abre ese restaurante.
- [ ] Límite de productos en Basic bloquea creación al superar.
- [ ] Upgrade a Pro → desbloquea feature y aumenta cuotas.
- [ ] Payment failed → `past_due` y restricciones (definir cuáles).
- [ ] Cancelación → mantiene acceso hasta fin de periodo (si aplica).
- [ ] Webhook reintentado → no crea duplicados (idempotencia).

## Notas para demo/staging

- En un dominio compartido, asegurar siempre URL pública con `?restaurant=ID` para previsualización/admin.
- Mantener un modo “demo” donde la IA sea ilimitada si `APP_ENV=demo`.

