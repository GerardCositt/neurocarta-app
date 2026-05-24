# NeuroCarta — Contexto del proyecto

> **Instrucción para Claude Code**: Al final de cada conversación, antes de despedirte, pregunta siempre al usuario si quiere actualizar este archivo `CLAUDE.md` con los cambios o decisiones relevantes de la sesión. Si dice que sí, actualízalo y haz commit + push.

## Proyectos relacionados

| Proyecto | Ruta local | Repo GitHub |
|---|---|---|
| App Laravel (panel admin + carta pública) | `../neurocarta-app` | `GerardCositt/neurocarta-app` |
| Landing page (React/Vite) | `../neurocarta-ai-landings/neurocarta-conversion` | `GerardCositt/neurocarta-ai-landings` |

---

## Arquitectura de despliegue

### Producción — Jotelulu (activo desde 2026-04-13)
- **App**: https://app.neurocarta.ai
- **Plataforma**: Jotelulu VPS (Ubuntu 24.04, 2 vCPU, 2 GB RAM, 25 GB NVMe)
- **IP pública**: 149.71.98.35
- **Servidor**: SRV-COSI03-034 (nombre interno Jotelulu), nombre en panel: `server-neurocarta`
- **Ruta app**: `/opt/neurocarta/`
- **Docker**: 3 contenedores — `neurocarta-app-1` (PHP/Laravel), `neurocarta-db-1` (PostgreSQL 16), `neurocarta-nginx-1` (Nginx + SSL)
- **SSL**: Let's Encrypt (certbot), renovación automática. Certs en `/etc/letsencrypt/live/app.neurocarta.ai/`
- **Base de datos**: PostgreSQL 16 en contenedor, datos en volumen `neurocarta_db_data`
- **Usuario de prueba**: test@test.com / test1234 (id=3, name=Admin)
- **Deploy**: script `/opt/neurocarta/deploy.sh` → git pull + rebuild + migrate + cache:clear
- **Deploy automático**: GitHub Action en `.github/workflows/deploy.yml` → push a main dispara deploy
- **Clave SSH**: `~/.ssh/neurocarta_jotelulu2` (Mac mini) → clave `neurocarta-mac2` en Jotelulu
- **Panel admin**: https://app.neurocarta.ai/admin (Filament), acceso con FILAMENT_ADMIN_EMAIL=test@test.com

#### Backups producción — configurado 2026-05-16
- **Script**: `/opt/neurocarta/scripts/backup.sh`
- **Cron**: `/etc/cron.d/neurocarta`
  - Scheduler Laravel cada minuto: `docker exec neurocarta-app-1 php artisan schedule:run`
  - Backup diario 03:00: `/opt/neurocarta/scripts/backup.sh`
- **Destino backups**: `/opt/neurocarta/backups/`
  - DB diaria: `/opt/neurocarta/backups/db/daily/`
  - DB semanal: `/opt/neurocarta/backups/db/weekly/`
  - Storage diario: `/opt/neurocarta/backups/storage/daily/`
  - Storage semanal: `/opt/neurocarta/backups/storage/weekly/`
- **Retención**: 7 diarios + 4 semanales (28 días).
- **Log script**: `/opt/neurocarta/backups/backup.log`
- **Log cron**: `/var/log/neurocarta-backup.log`
- **Primer backup verificado**: `neurocarta_20260516_194028.sql.gz` (DB) y `neurocarta_storage_20260516_194028.tar.gz` (storage).
- **Restauración probada**: dump restaurado en DB temporal `neurocarta_restore`; recuentos verificados (`users=7`, `restaurants=6`, `subscriptions=6`); DB temporal eliminada después.
- **Pendiente recomendado**: copia externa/off-server (S3, rsync externo o backup gestionado) antes de escalar clientes.

### Staging — Render (legacy, mantener por ahora)
- **App**: https://neurocarta-staging.onrender.com
- **Plataforma**: Render (Docker, PHP 8.2 + Apache)
- **Base de datos**: PostgreSQL en Render
- **Usuario de prueba**: test@test.com / test1234
- **Health check**: `/up` → devuelve 200 OK
- **Keep-alive**: UptimeRobot hace ping a `/up` cada 5 min

### Landing (neurocarta.ai)
- **Plataforma**: Plesk (servidor COSITT, IP 217.154.188.235)
- **Repo**: `GerardCositt/neurocarta-ai-landings` → carpeta `neurocarta-conversion/`
- **Deploy**: push a `main` → GitHub Action `deploy-neurocarta-ai.yml` (build Vite + rsync a `httpdocs/`)
- **SCP manual**: solo emergencia si falla Actions

---

## Flujo de deploy

### App (Jotelulu — producción)
1. Editar código en el Mac y hacer `git push` a `main`
2. En el servidor: `/opt/neurocarta/deploy.sh`
   - Hace `git pull origin main`
   - Reconstruye el contenedor app
   - Copia `.env` al contenedor
   - Limpia config cache
   - Corre migraciones
3. Tras deploy con plantilla demo (si aplica): `docker exec neurocarta-app-1 php artisan demo:resync-template-photos --by-name` y comprobar que existen `public/demo/*.jpg` en la imagen

### App (Render — staging legacy)
1. `git push` a `main` → Render detecta el push y despliega automáticamente

### Landing (neurocarta.ai)
1. Editar en `neurocarta-ai-landings/neurocarta-conversion/`
2. `git push origin main` → deploy automático a Plesk (~30 s)
3. Verificar: https://github.com/GerardCositt/neurocarta-ai-landings/actions

---

## Variables de entorno en Jotelulu (producción)

El `.env` está en `/opt/neurocarta/.env` en el servidor (NO en el repo).
Variables clave:

| Variable | Valor |
|---|---|
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_URL` | `https://app.neurocarta.ai` |
| `DB_CONNECTION` | `pgsql` |
| `DB_HOST` | `db` (contenedor) |
| `DB_DATABASE` | `neurocarta` |
| `DB_USERNAME` | `neurocarta` |
| `SESSION_DRIVER` | `cookie` |
| `MAIL_HOST` | `smtp.resend.com` |
| `MAIL_PORT` | `465` |
| `MAIL_USERNAME` | `resend` |
| `MAIL_PASSWORD` | API key de Resend |
| `MAIL_ENCRYPTION` | `ssl` |
| `MAIL_FROM_ADDRESS` | `noreply@neurocarta.ai` |
| `TURNSTILE_SECRET_KEY` | vacía (desactivado en login) |
| `TURNSTILE_SITE_KEY` | vacía (desactivado en login) |
| `FILAMENT_ADMIN_EMAIL` | test@test.com |
| `NEUROCARTA_TEMPLATE_PRODUCT_IMAGE_BASE_URL` | opcional — CDN sin barra final; vacío = `public/demo/` → storage |
| `NEUROCARTA_TEMPLATE_PRODUCT_IMAGE_FLAT` | `false` por defecto — `true` si en el CDN los JPG están en la raíz del bucket (sin carpeta `demo/`) |
| `OPENAI_API_KEY` | clave de plataforma para modo IA billing `platform` (activa desde 2026-05-23) |
| `STRIPE_KEY` | `pk_live_...` clave pública (solo usada por `launch:check`) |
| `STRIPE_SECRET` | `sk_live_...` clave secreta — configurada en producción 2026-05-23 |
| `STRIPE_WEBHOOK_SECRET` | `whsec_...` — endpoint `https://app.neurocarta.ai/stripe/webhook` |
| `STRIPE_PRICE_BASICO_MONTHLY` | `price_1TYpwNLTcxPSuIkc88ukk5Z4` (live) |
| `STRIPE_PRICE_BASICO_ANNUAL` | `price_1TYpwNLTcxPSuIkcEO0z4uQu` (live) |
| `STRIPE_PRICE_PRO_MONTHLY` | `price_1TYq1nLTcxPSuIkckXqcwt0p` (live) |
| `STRIPE_PRICE_PRO_ANNUAL` | `price_1TYpyVLTcxPSuIkcurPi73Y8` (live) |
| `STRIPE_PRICE_PREMIUM_MONTHLY` | `price_1TYq1QLTcxPSuIkchK0vb4J6` (live) |
| `STRIPE_PRICE_PREMIUM_ANNUAL` | `price_1TYq00LTcxPSuIkcJ845SmB6` (live) |

---

## Decisiones técnicas relevantes

- **PostgreSQL en lugar de SQLite**: La app original usaba SQLite. Se migró a PostgreSQL.
- **Detección de restaurante por subdominio**: El middleware `DetectRestaurant` lee el subdominio.
- **SESSION_DRIVER=cookie**: Necesario porque el filesystem de Docker no persiste entre recreaciones.
- **Logout redirige a /login**: Configurado en `AppServiceProvider` via `LogoutResponse` de Fortify.
- **BarJaenIIISeeder eliminado del entrypoint**: Se quitó de `docker/entrypoint.sh` porque pisaba datos reales de usuarios al desplegarse. Solo se lanza manualmente para demo.
- **Turnstile desactivado en login**: El widget estaba fuera del `<form>` (token nunca se enviaba) y bloqueaba el login. Eliminado del blade y del pipeline de Fortify. Pendiente reactivar correctamente con claves reales de Cloudflare.
- **DNS en Cloudflare**: `app.neurocarta.ai` → `149.71.98.240`, proxy desactivado (nube gris).
- **Seguridad de uploads públicos (2026-05-16)**: Las subidas de cliente para logos, fotos de productos y alérgenos no aceptan SVG ni GIF. Solo se permiten imágenes raster conservadoras (`jpg/jpeg`, `png`, `webp`) con límites explícitos de tamaño. `ImageAssetService` revalida el MIME real (`image/jpeg`, `image/png`, `image/webp`) y re-encodea las imágenes antes de guardarlas, de modo que no se almacenen SVG subidos por usuarios aunque un formulario omitiera la validación.
- **Gates por plan (2026-05-17)**: `PlanEntitlementService` define cuotas (productos/cats/restaurantes) y features booleanas (`ai`, `csv_import`, `translations`). Básico sin IA/CSV/traducciones; Pro/Premium con acceso; trial activo equivale a Premium. Middleware `EnsurePlanFeature` en rutas; helper `App\Support\PlanFeatureGate` para blades/Livewire; guards en `ProductImport`, `ImportAi`, `TranslationManager`, `Products`, `Pairings`. Rutas protegidas: import CSV (+ plantilla), import IA, traducciones, facturación IA (`/settings/ai-billing`). Banner `plan_error` en layout admin. Tests: `TenantIsolationTest`, `SubscriptionExpiryTest`, `PlanFeatureGateTest`. Commit: `d38cc17`.
- **Listados admin y controles visuales (2026-05-18)**: En `/product`, `/category`, `/advice` y `/pairing`, los toggles de selección/ocultar/destacar/recomendar/oferta pasan a botones visuales con `role="checkbox"` y `wire:key` dependiente del estado, para evitar que Livewire v2/morphdom deje checkboxes nativos visualmente desincronizados. Productos adopta el ancho/formato contenido con recuadro usado por categorías/avisos/maridajes. El panel de selección masiva queda por encima de la tabla vía `admin-bulk-panel` con z-index propio. Commit: `6293f49`.
- **Subidas de imágenes de producto (2026-05-18)**: `Products::persistProduct()` captura errores de `ImageAssetService` y los muestra en el campo `filename` en vez de perder silenciosamente la imagen. `docker/entrypoint.sh` ejecuta `php artisan storage:link || true` en deploy para asegurar el enlace público `public/storage`. Pendiente: prueba manual completa de crear/editar producto con JPG/PNG/WebP en producción.
- **Búsqueda admin y landing tras login (2026-05-18)**: Los filtros de búsqueda en Livewire (`Products`, `Category\Show`, `Allergen\Show`, `Advices\Show`, `OrderList`) usan `LOWER(campo) LIKE ?` con el término en minúsculas y `%`/`_` escapados, para que PostgreSQL/MySQL no distingan mayúsculas. `RouteServiceProvider::HOME`, la ruta `dashboard` y el alta tras reset de contraseña (`SetPasswordController`) redirigen a `/product` en servidor; se retira el shim `window.location` de `dashboard.blade.php`. Tests `RegistrationTrialFlowTest` y `SubscriptionExpiryTest` actualizados. Commit: `79b3bde`.
- **Alérgenos oficiales cargables desde UI (2026-05-18)**: Los 14 alérgenos obligatorios UE (Reg. 1169/2011) están centralizados en `App\Support\OfficialAllergens` (fuente única usada por `MandatoryAllergensSeeder` y por la UI). En `/allergen` aparece un botón "Cargar alérgenos oficiales" (índigo) solo cuando falta alguno de los 14; al pulsarlo inserta únicamente los ausentes (idempotente) y muestra un flash con el recuento. Los alérgenos son globales (sin `account_id`/`restaurant_id`); el scoping por restaurante está en el pivot `allergen_product`. Commit: `66fa6e0`.
- **Raíz `/` en producción redirige siempre al panel (2026-05-18)**: `routes/web.php` — el host del panel (`app.neurocarta.ai`) en entorno `production` redirige siempre a login o dashboard, nunca muestra la carta pública. El bug anterior era que `session('admin_restaurant_id')` estaba en la condición de preview, que siempre estaba set para admins logueados. Commit: `f9a76e4`.
- **Límites y gates de plan alineados con landing (2026-05-18)**: `PlanEntitlementService` actualizado con los límites definitivos de la landing: Básico 70/6/1, Pro 250/15/2, Premium 1.000/100/3. Nueva feature gate `offers` (false en Básico): los toggles de oferta y destacado en la tabla muestran candado en Básico; la sección de oferta en el formulario de producto se oculta; guards en `offerToggleFromTable()`, `toggleFeatured()` y `bulkSetFeatured()`. Commit: `b95fdf1`.
- **"Ver carta" genera URL de subdominio en producción (2026-05-18)**: Jetstream registraba su propia clase `Laravel\Jetstream\Http\Livewire\NavigationMenu` (sin `mount()`, sin `$qrMenuUrl`) bajo el alias `navigation-menu` en su ServiceProvider, sobreescribiendo nuestra clase `App\Http\Livewire\NavigationMenu`. Resultado: la URL siempre caía al fallback `?restaurant=10`. Fix: registrar explícitamente nuestra clase en `AppServiceProvider::boot()` con `Livewire::component('navigation-menu', \App\Http\Livewire\NavigationMenu::class)`. Commit: `e94c04b`. **Regla general**: si se añade un componente Livewire con el mismo nombre que uno de Jetstream, siempre registrarlo en AppServiceProvider para que tome precedencia.
- **Badge de plan y botón "Mejora tu plan" en sidebar (2026-05-18)**: `NavigationMenu` expone `$currentPlan` (via `PlanEntitlementService::effectivePlanForAccount`). El sidebar muestra un badge de color con el plan activo. Con plan Básico aparece un enlace discreto "Mejora tu plan →" (borde discontinuo gris, sin color de fondo) y los items del submenú de Ajustes bloqueados (import CSV, import IA, IA billing, traducciones) muestran un pill "Pro" en índigo. Commits: `95510ce`, `4e07876`.
- **`@php($expr)` shorthand causa ParseError en Blade (2026-05-18)**: La forma `@php($variable = valor)` compila a `<?php($variable = valor)` sin cerrar, dejando el HTML siguiente como código PHP y fallando en el token `class`. Siempre usar `@php $variable = valor; @endphp` en su lugar. Commit fix: `6692df5`.
- **Precios anuales actualizados a "1 mes gratis" (2026-05-18)**: Cambiado de "2 meses gratis" a "1 mes gratis" en `subscription/expired.blade.php`. Precios anuales recalculados: Básico 275€, Pro 385€, Premium 759€. Límites corregidos (estaban desfasados: Pro 500/60→250/15, Básico 100/20→70/6, Premium 2000/200→1000/100, precio 65€→69€). Commit: `84e27d0`.
- **Checkout Stripe redirige al admin si suscripción está `active` (decisión de diseño)**: `CheckoutController::create()` redirige a dashboard si `$subscription->status === 'active'`. Esto es correcto: la página `/subscription/expired` es para usuarios expirados, no para cambio de plan desde panel activo. El checkout no funcionará hasta configurar `STRIPE_SECRET` y los `STRIPE_PRICE_*` en el `.env` de producción.
- **Usuario de prueba para planes**: `geycor@gmail.com` / `1234` (Bar Geycor, subdominio `bar-geycor`). Usar `php artisan dev:set-plan basico --email=geycor@gmail.com` para cambiar de plan. `test@test.com` es el admin de Filament y no tiene cuenta/suscripción asociada — no usar para pruebas de planes.
- **Plantilla «Cargar datos de prueba» y fotos demo (2026-05-19)**: Una sola plantilla fija en `App\Support\DemoContent` (18 platos + 5 categorías). El cliente pulsa **Cargar datos de prueba** solo si la carta **no tiene ningún producto**; **Borrar datos de prueba** solo si todos los productos son plantilla (`products.is_template = true`) o legado que coincide por firma nombre+descripción+precio — en cuanto exista **un producto propio** (`is_template = false`), no hay carga ni borrado masivo. Columna `products.is_template` (migración `2026_05_19_200000`). Import CSV/IA y guardar ficha marcan `is_template = false`. **Fotos (decisión)**: no se versionan JPEG de stock en el repo (evita imágenes que no coinciden con el plato). Sin archivo ni CDN → producto sin `photo` → placeholder `noimg.png`. Origen, en orden: `local/demo-product-images/`, `img/demo-product-images/`, `public/demo/` (solo local, `.gitignore`), o `NEUROCARTA_TEMPLATE_PRODUCT_IMAGE_BASE_URL` (+ `FLAT` opcional). Script dev opcional: `bash scripts/download-demo-food-images.sh` (Pexels → `public/demo/`, no commitear). Tras SQL/rutas rotas: `php artisan demo:resync-template-photos` (`--by-name`). **Cabecera tabla `/product`**: sin sticky en `<thead>`. Commits: `407fb35`, `c346f3f` (stock revertido en repo), política sin JPG en git en commit posterior.
- **Fix z-index modales admin — stacking context -mx-4 (2026-05-23)**: Los modales de edición en `/category`, `/allergen` y `/pairing` aparecían por detrás de la cabecera. Causa raíz: el div `.-mx-4` tenía `position:relative; z-index:2` vía CSS, creando un stacking context que atrapaba los `position:fixed` internos al z-index:2 del contexto padre. Fix: mover el bloque `@if($isOpen)...<div id="lw-*-edit-modal-root">@include(...)` fuera del div `-mx-4`, justo antes de los modales de confirmación de borrado. Adicionalmente, el `z-index` del header (`.admin-page-header`) se bajó de 10000 a 50 en `admin-overrides.css` para que los modales en `z-index:20000` siempre ganen. El z-index:50 del header sigue siendo superior al máximo de contenido de página (z-index:28 del bulk panel).
- **Banner "Saldo IA" eliminado del sidebar (2026-05-23)**: Se eliminó `<livewire:admin.ai-credits-banner />` de `navigation-menu.blade.php`. El padding inferior del nav cambió de `pb-44` a `pb-6`. El saldo de créditos sigue siendo visible en `/settings/ai-billing`.
- **OpenAI API key configurada en producción (2026-05-23)**: `OPENAI_API_KEY` añadida al `.env` de Jotelulu (`/opt/neurocarta/.env`) y copiada al contenedor con `docker cp`. Esto activa el modo `platform` en `AiCreditService`: la plataforma paga las llamadas y descuenta créditos del saldo del restaurante. Los planes Pro y Premium tienen IA habilitada; Básico sigue sin IA.
- **Créditos IA mensuales automáticos (2026-05-23)**: Nuevo comando `credits:monthly-refill` en `app/Console/Commands/RefillMonthlyAiCreditsCommand.php`. Establece (no acumula) 300 créditos a cada restaurante de cuentas con plan Pro o Premium. Programado en `Kernel.php` con `monthlyOn(1, '02:00')`. Se ejecuta el día 1 de cada mes. Relación correcta en Account: `subscriptions` (plural, `hasMany`). Probado manualmente en producción: "Done: 1 restaurants refilled."
- **Modal confirmación IA detrás del panel de edición de producto (2026-05-23)**: El modal `@if($confirmingAiAction)` en `products.blade.php` tenía `z-50` (z-index:50) pero `productfrm.blade.php` usa `z-index:20000` en su div raíz. Fix: cambiar a `style="z-index:30000"` en el div del modal de confirmación. Commit: `305d3a3`.
- **Rename `active` → `hidden` en 4 tablas (2026-05-23)**: La columna `active` tenía semántica invertida (`true` = oculto). Se renombró a `hidden` en `products`, `categories`, `pairings` y `allergens`. Migración `2026_05_23_100000_rename_active_to_hidden_in_menu_tables.php` usa `DB::statement("ALTER TABLE x RENAME COLUMN active TO hidden")` con SQL puro — **no usa `renameColumn()`** que requiere `doctrine/dbal` (no instalado). Tests en SQLite funcionan sin DBAL. Commits: `b7c4fe1`. Regla: usar SQL puro para renombrar columnas en este proyecto.
- **Split `Products.php` en 3 traits (2026-05-23)**: El componente Livewire `Products.php` pasó de 1515 a 705 líneas extrayendo 3 traits en `app/Http/Livewire/Concerns/`: `ManagesProductBulkActions` (selección + acciones masivas), `ManagesProductAi` (IA descripción/imagen/alérgenos), `ManagesProductDemoContent` (carga/borrado plantilla demo). Los métodos que los traits llaman en el componente padre se cambiaron de `private` a `protected`: `getRestaurantId()`, `notifyNavigationMenuRefresh()`, `buildFilteredProductQuery()`, `aiCredits()`. Los traits no pueden acceder a miembros `private` del componente — usar `protected`. Commit: `4f42589`.
- **Tests para servicios críticos (2026-05-23)**: 54 tests nuevos en total — `tests/Unit/AiCreditServiceTest.php` (11: costes, créditos, spend, billing modes), `tests/Unit/OpenAiServiceTest.php` (9: detección key, generación texto/imagen con `Http::fake`, errores), `tests/Feature/StripeWebhookTest.php` (15: guards, payment_succeeded/failed, sub_deleted/updated, checkout validaciones), `tests/Unit/ColorMathTest.php` (19: HSL↔RGB, hex, WCAG contraste). Commits: `4219d6b`, `a96bda9`.
- **Split `MenuBrandPaletteService` en 3 clases (2026-05-23)**: El servicio de 668 líneas se dividió en: `App\Support\ColorMath` (155 líneas, matemática pura estática — HSL↔RGB, hex, WCAG luminancia/contraste), `App\Support\LogoColorSampler` (270 líneas, operaciones GD — carga, reescalado, color dominante por hue-buckets, swatches distintos), y `MenuBrandPaletteService` (263 líneas, API pública + construcción de vars CSS, inyecta `LogoColorSampler`). La API pública no cambió — ningún caller actualizado. `LogoColorSampler` se inyecta por constructor, lo que lo hace testable de forma independiente. Commit: `a96bda9`.
- **Modal "Comprar créditos IA" en header (2026-05-23)**: Botón naranja (`#FF7A00`) en el header del admin, visible solo cuando hay restaurante seleccionado (`$_hR`). Abre un modal centrado (`id="creditsIaModal"`) con 3 tarjetas de packs de créditos + tabla de consumo por acción. El modal también salta automáticamente la **primera vez** que un usuario se queda sin créditos (`InsufficientAiCreditsException`), guardado con `localStorage('nc_credits_modal_shown')` — el botón del header siempre lo abre sin importar el localStorage. El modal se cierra con la X o haciendo clic en el fondo. Coste por acción: generar imagen 10cr, mejorar imagen 5cr, generar descripción 3cr, texto alérgeno 2cr, importar carta IA 15cr. Commits: `f6fba9a`, `cf67534`, `dafbeb4`, `23e7483`.
- **Créditos mensuales por plan corregidos (2026-05-23)**: Pro 200 créditos/mes, Premium 400 créditos/mes (el comando `credits:monthly-refill` usaba 300 — corregido a los valores definitivos 200/400). Actualizado en `subscription/expired.blade.php` y en la landing `App.jsx`. Commit landing: `ba7571f`.
- **Stripe `tax_behavior: exclusive` para IVA aditivo (2026-05-23)**: En `CreditCheckoutController`, `price_data` lleva `'tax_behavior' => 'exclusive'` para que el IVA se sume al precio base en lugar de estar incluido. Sin esta key, Stripe trata el precio como inclusivo de impuestos. Commit: `f6fba9a`.
- **Stripe `customer_update[name]` requerido con tax_id_collection (2026-05-23)**: Al activar `tax_id_collection` en Stripe Checkout, Stripe exige que `customer_update` incluya `'name' => 'auto'`; sin él devuelve "Tax ID collection requires updating business name on the customer". Fix en `CreditCheckoutController`. Commit: `f6fba9a`.
- **Stripe Customer Portal sin `flow_data` (2026-05-23)**: `flow_data.type = 'subscription_update'` requiere que el Customer Portal esté configurado en el Dashboard de Stripe (Settings → Billing → Customer portal → Switch plans). Si no está configurado, Stripe devuelve error → el controlador caía a `redirect('product')`. Fix: eliminar `flow_data` de `BillingPortalController::redirect()`. El portal simplemente abre y el usuario gestiona el plan desde ahí. **Pendiente**: configurar "Switch plans" en el Dashboard de Stripe con los 6 price IDs live para que el cambio de plan desde el portal funcione. Commit: `384c82b`.
- **`/subscription/manage` rediseñada con 2 botones por tarjeta (2026-05-23)**: En lugar de un toggle superior + 1 botón por tarjeta (confuso porque el toggle no afectaba la acción al hacer clic), cada tarjeta muestra los 2 botones de forma independiente: mensual y anual con sus precios. El combo plan+intervalo actual se muestra como etiqueta deshabilitada "actual". Usuarios con Stripe: botones que hacen POST al portal. Usuarios sin Stripe (trial): enlaces `<a>` a `checkout.start`. Commit: `384c82b`.
- **Checkout Stripe abre en pestaña nueva (2026-05-23)**: Los enlaces de packs de créditos en `ai-billing-settings.blade.php` tienen `target="_blank" rel="noopener noreferrer"`. Commit: `f6fba9a`.
- **`dispatchBrowserEvent` de Livewire 2 escuchaba en `document`; Livewire 3 cambia a `window` (2026-05-23/24)**: En LW2 el evento se disparaba en el nodo del componente y burbujaba hasta `document`. En LW3 `$this->dispatch()` dispara directamente en `window`. Tras la migración a LW3, cambiar todos los `document.addEventListener('evento', fn)` a `window.addEventListener('evento', fn)` para eventos del servidor. Regla anterior ahora obsoleta.
- **Overlay importación IA — solución definitiva JS puro (2026-05-23)**: El enfoque `wire:loading wire:target="process"` parpadeaba con peticiones cortas. El enfoque Alpine.js + `@entangle` en el elemento raíz tampoco funcionaba: Livewire pierde el estado Alpine al re-renderizar el componente. Solución definitiva en commit `45b58a4`: overlay con `id="import-ai-overlay"` y `style="display:none"`, botón con `onclick` puro que lo muestra, servidor lanza `dispatchBrowserEvent('import-ai-done')` en TODOS los caminos de retorno de `process()` (early returns + catch + éxito), listener JS lo oculta. Emoji 🦋 con animación CSS `butterfly-float` (ondulación + rotación, 2,4s). **Regla general**: para overlays durante peticiones Livewire largas usar JS puro + `dispatchBrowserEvent` desde el servidor. No usar `x-data` en el elemento raíz de un componente Livewire para estado que deba sobrevivir re-renders.

- **Upgrade Laravel 8→9→10→11 + Livewire 2→3 + Filament 2→3 (2026-05-24)**: Completado en rama `upgrade/laravel-11`. 3 commits: `fa5aa9d` (L8→9), `11dc36f` (L9→10 + LW2→3 + F2→3), `44a1b90` (L10→11). Lecciones clave:
  - **Livewire 3**: `dispatchBrowserEvent()` → `dispatch()` con named args; `emit()` → `dispatch()`; `window.livewire.emit/on` → `Livewire.dispatch/on`; `livewire:load` → `livewire:initialized`; `livewire:update` → hook `Livewire.hook('commit', ({ succeed }) => { succeed(() => { queueMicrotask(fn) }) })`; `wire:model` ahora diferido por defecto → añadir `.live` para comportamiento reactivo; `wire:model.debounce.Xms` → `wire:model.live.debounce.Xms`; `$this->id` → `$this->getId()`; eventos del servidor se escuchan en `window` (no `document`).
  - **Livewire 3 config**: Publicar `config/livewire.php` y ajustar `class_namespace` a `App\Http\Livewire` y `layout` a `layouts.app` (los defaults del config publicado asumen `App\Livewire` y `components.layouts.app`, que no son los de este proyecto).
  - **Livewire 3 paginación custom**: Eliminar `$this->numberOfPaginatorsRendered` de vistas de paginación propias (interno de LW2, ya no existe). El `$page` ya no se inyecta automáticamente en la vista — extraerlo del paginador con `$products->currentPage()`.
  - **Filament 3**: Crear `AdminPanelProvider` (reemplaza `config/filament.php`); `Filament\Resources\Form` → `Filament\Forms\Form`; `Filament\Resources\Table` → `Filament\Tables\Table`; `canAccessFilament()` → `canAccessPanel(Panel $panel): bool`; heroicon `office-building` → `building-office`; color `'secondary'` → `'gray'`.
  - **Stale views**: Tras cada upgrade de Livewire, ejecutar `php artisan view:clear` para eliminar vistas compiladas de la versión anterior.

---

## Estado Git (2026-05-24, último commit 1397a67 en main)

- `main` en GitHub con deploy automático a Jotelulu (push → Action → `deploy.sh`).
- **Upgrade L8→L11 + LW2→3 + F2→3 mergeado a main** — producción funcionando en Laravel 11.53.1, PHP 8.4, Filament 3. Rama `upgrade/laravel-11` se puede eliminar.
- **Guion servidor**: `docs/SERVIDOR-LANZAMIENTO.md` + `bash scripts/server-launch-check.sh` en `/opt/neurocarta`.
- **QA manual**: `docs/LAUNCH-QA.md` (bloques 1–11) — pendiente en oficina.
- **Stripe live activo**: claves y price IDs configurados en `.env` producción. Pendiente: configurar Customer Portal en Dashboard (Switch plans + 6 price IDs) y prueba de pago real completa.
- **Tests**: 125 tests (122 pasan, 3 fallan BillingPortalTest pre-existentes que requieren Stripe live). Rama upgrade: mismos resultados en L11.
- **IA activa en producción**: `OPENAI_API_KEY` configurada; Pro 200 créditos/mes, Premium 400 créditos/mes (refill automático día 1 a las 02:00).
- **Modal créditos IA**: botón naranja en header + popup automático primera vez sin créditos. Commits: `f6fba9a`, `cf67534`.
- **Overlay importación IA**: fix definitivo con JS puro + `dispatchBrowserEvent`. Commit `45b58a4`. **Regla general**: no poner `x-data` en el elemento raíz de un componente Livewire si el estado Alpine debe sobrevivir re-renders durante peticiones largas; usar JS puro + browser events del servidor.
- **Stripe en producción configurado (2026-05-23)**: Claves live (`sk_live_`, `whsec_`) y 6 price IDs live añadidos al `.env` de Jotelulu. El contenedor lee el `.env` vía `env_file` en `docker-compose.prod.yml` — para actualizar variables hay que recrear el contenedor (`docker compose -f docker-compose.prod.yml up -d --force-recreate app`), no solo copiarlo con `docker cp` + `config:clear`. Los price IDs de test (`price_1TYkS7...`) no funcionan en live mode. Webhook apunta a `https://app.neurocarta.ai/stripe/webhook`.

---

## Planes y precios (actualizado 2026-05-18)

| Plan | Precio mensual | Precio anual | Límites | Features exclusivas |
|---|---|---|---|---|
| **Gratis (trial)** | 0€ / 7 días | — | Sin límites — acceso total | Todo incluido |
| **Básico** | 25€/mes | 275€/año (1 mes gratis) | 70 productos, 6 cats, 1 restaurante | Sin IA, CSV, traducciones ni ofertas/destacados |
| **Pro** | 35€/mes | 385€/año (1 mes gratis) | 250 productos, 15 cats, 2 restaurantes | IA, multi-idioma, ofertas/destacados, CSV |
| **Premium** | 69€/mes | 759€/año (1 mes gratis) | 1.000 productos, 100 cats, 3 restaurantes | Todo lo de Pro + soporte preferente |

> **Descuento anual**: 1 mes gratis = pagar 11 meses (25×11=275, 35×11=385, 69×11=759). Actualizar price IDs en Stripe cuando se activen claves live.

## Flujo de registro y trial (cerrado 2026-04-12)

### Entrada
- Desde la landing (botón CTA) o desde el header (Crear cuenta)
- Primero elige plan → luego registro

### Registro (solo 3 campos)
- Email
- Nombre del restaurante
- Teléfono

### Trial (7 días)
- Empieza al registrarse
- Sin tarjeta — acceso total sin límites
- Email aviso día 5 y día 7
- Puede cambiar de plan durante el trial

### Día 8 sin pago
- Panel → pantalla bloqueada con selector de plan + Stripe
- Carta pública → pantalla bloqueada
- QR → redirige a la misma pantalla bloqueada (no a la carta)
- Elige plan → pone tarjeta → todo se reactiva

### Creación del restaurante
- Automática al registrarse (no espera a Stripe)
- Un usuario = un restaurante (de momento)

### Anti-abuso de trial
- Campo teléfono es la clave para detectar trials duplicados
- Verificación por WhatsApp/SMS → **pendiente para más adelante**
- IP de registro como capa secundaria (registrar, no bloquear)

## Hoja de ruta definitiva para lanzamiento

Claude/Cursor actuará como director técnico del cierre de producto: priorizar, ordenar, auditar riesgos, proponer siguientes pasos y evitar lanzar/cobrar si faltan bloqueantes críticos.

### Regla de lanzamiento
- No cobrar a clientes hasta tener Stripe live probado, backups restaurables, legal publicado, aislamiento multi-tenant verificado y 2-3 restaurantes piloto probados de principio a fin.

### Fase 0 — Congelar base y ordenar trabajo
- [x] Limpiar estado de Git — `.gitignore` cubre storage/cachés/logs; rama `upgrade/laravel-11` mergeada; `main` limpio.
- [x] Rama de trabajo — se trabajó directamente en `main` desde producción (Jotelulu). Rama upgrade ya mergeada.
- [x] Checklist por área — este CLAUDE.md sirve de referencia activa, organizado por fases.
- [x] MVP definido — Fases 1-3 son el MVP; Fases 4-9 mejoras post-lanzamiento.
- [x] Alinear precios y límites comerciales con los límites reales del código antes de cerrar Stripe. (`b95fdf1`)

### Fase 1 — Producto crítico
- [x] Registro completo de restaurante sin intervención manual — `RegisterNewRestaurant` action crea Account + Restaurant + Subscription (trial 7 días) en una transacción. Pendiente validación manual bloque 1 LAUNCH-QA.
- [x] Login, recuperación de contraseña y cierre de sesión — CSRF activo, logout → /login, password reset con `SetPasswordController`. Pendiente validación manual bloque 1.
- [x] Trial gratis con fechas correctas y avisos coherentes — `SubscriptionExpiryTest` pasa; emails día 5 y 7 via scheduler. Pendiente validación manual bloque 6.
- [x] Pantalla de trial terminado clara y con CTA real — `/subscription/expired` con selector de plan y botones Stripe. Pendiente validación manual bloque 7.
- [x] Bloqueo correcto de panel, QR y carta pública cuando caduca — `PublicMenuSubscriptionTest` + `SubscriptionExpiryTest` pasan; `subscription.check` middleware. Pendiente validación manual bloque 7.
- [x] Planes Básico / Pro / Premium conectados a límites reales: cuotas + features IA/CSV/traducciones/ofertas en código. Límites alineados con landing (`b95fdf1`). Pendiente: validación manual en `docs/LAUNCH-QA.md` bloques 4-6 y 8.
- [x] Panel de gestión usable en móvil y escritorio (productos + categorías: tarjetas < md, tabla ≥ md; commit UX móvil).
- [x] Crear, editar, ocultar y ordenar categorías — code completo; controles visuales estabilizados (`6293f49`). Pendiente prueba manual bloque 2.
- [x] Crear, editar, ocultar y ordenar productos — code completo; controles selección/oferta/destacado/recomendado/ocultar estabilizados (`6293f49`). Pendiente prueba manual bloque 2.
- [x] Subida de imágenes de platos — errores visibles en campo `filename`; `storage:link` en deploy. Pendiente probar JPG/PNG/WebP reales en producción (bloque 2).
- [x] Imagen placeholder cuando no hay foto (`img/noimg.png` + `ProductPhotoUrl`). Plantilla demo sin fotos empaquetadas en git; fotos opcionales en `local/demo-product-images/` o CDN. Pendiente: banco de fotos reales de carta o CDN de producción.
- [x] Alérgenos visibles y editables — gestión completa en `/allergen`; botón carga 14 alérgenos oficiales UE (commit `66fa6e0`); scoping por pivot `allergen_product`.
- [x] Vista pública de carta optimizada en consultas (locales en 1 query, ofertas sin duplicar eager load; test `PublicMenuPerformanceTest` 120 platos). Pendiente: validación manual < 3 s en prod (bloque 3.4).
- [x] Selector de idioma — carta pública con selector de idioma en navbar (`nav-lang-wrap`); gestor de traducciones `/translations` (plan gate Pro+).
- [x] Importación CSV — code completo con plan gate; plantilla descargable. Pendiente prueba manual bloque 4.
- [x] IA de importación, descripción e imágenes con control de créditos — `ImportAi`, `ManagesProductAi`; modal compra créditos; `InsufficientAiCreditsException`. Pendiente prueba manual bloque 5.

### Fase 2 — Pagos y suscripciones
- [x] Stripe en código (Checkout + webhooks). Pendiente: **live** probado (Fase 10).
- [x] Stripe conectado en producción (claves `.env` + 6 price IDs live configurados).
- [x] Checkout para planes (suscripción) y para packs de créditos IA — abre en nueva pestaña.
- [x] Webhooks de Stripe configurados (`/stripe/webhook`, `whsec_` en `.env`).
- [x] Activación automática de suscripción tras pago — webhook `customer.subscription.updated` maneja activación; `StripeWebhookTest` pasa (15 casos). Pendiente prueba con pago live real.
- [x] Cancelación, impago y renovación gestionados — webhooks `customer.subscription.deleted`, `invoice.payment_failed`, `invoice.payment_succeeded` implementados y testeados. Pendiente prueba live real.
- [x] Facturación anual/mensual clara en `/subscription/manage` (2 botones por tarjeta) y en `/subscription/expired`.
- [x] Emails de trial, alta, pago fallido y renovación — 7 plantillas completas con footer legal Cositt · CIF B93340602. Commit `1397a67`.
- [x] Límite por plan aplicado de forma centralizada (`PlanEntitlementService` + `EnsurePlanFeature`). Límites y features alineados con landing (`b95fdf1`).
- [ ] Customer Portal de Stripe configurado en Dashboard (Settings → Billing → Customer portal): activar "Switch plans", añadir los 6 price IDs live, configurar prorrateo. **Bloqueante** para cambio de plan desde el portal.

### Fase 3 — Multi-restaurante / tenants
- [x] Cada restaurante aislado correctamente — `RestaurantScope` en todos los modelos; `TenantIsolationTest` pasa. Pendiente validación manual bloque 9 LAUNCH-QA.
- [x] Subdominio o URL pública por restaurante — `DetectRestaurant` middleware resuelve por subdominio; carta pública en `{slug}.neurocarta.ai`. Pendiente DNS wildcard en producción si se añaden clientes.
- [x] Usuario asignado a su cuenta/restaurante — registro crea `Account` + `Restaurant` + `Subscription` automáticamente.
- [x] Evitar que un usuario vea datos de otro restaurante (scopes + test `TenantIsolationTest`; validar manual bloque 9 de `docs/LAUNCH-QA.md`).
- [x] Selector de restaurante — `RestaurantSwitcher` en header (`layouts/app.blade.php:130`); permite cambiar entre restaurantes de la cuenta.
- [x] Seeds/demo separados — demo solo via `demo:ensure` + `DemoMenuSeeder`; datos de cliente usan `is_template=false`; no hay mezcla automática.
- [x] Eliminar o justificar `Restaurant::first()` fuera de seeds: solo en `DemoMenuSeeder` (fallback); `DetectRestaurant` usa subdominio/sesión/cookie.

### Fase 4 — Diseño y UX
- [ ] Revisar panel de productos con muchos platos (manual, bloque 2 LAUNCH-QA).
- [x] Tabla de productos en pantallas pequeñas: tarjetas móvil en `/product` y `/category` (< md).
- [ ] Revisar carta pública en móvil real (bloque 3 / 11.4 de `LAUNCH-QA.md`).
- [ ] Revisar estados vacíos: sin productos, sin categorías, sin imagen, sin suscripción.
- [ ] Revisar textos de ayuda, botones y errores.
- [x] Tema claro/oscuro — carta pública soporta light/dark/system via `data-theme`; admin usa tema fijo.
- [x] Branding consistente — favicon (`public/favicon.svg`, `public/favicon.ico`), logo NeuroCarta en header admin, colores `#FF7A00` en emails y UI, mascota SVG en emails.
- [x] Página de precios pública — pricing en landing `neurocarta.ai` (React/Vite, repo `neurocarta-ai-landings`). Commit landing `ba7571f`.
- [x] Landing pública con propuesta clara — `neurocarta.ai` live en Plesk con deploy automático via GitHub Actions.

### Fase 5 — Legal
- [x] Términos, privacidad, cookies, aviso legal — **cerrado por abogado** (publicado en `neurocarta.ai`; validar enlaces en registro).
- [x] Consentimiento para emails — solo emails transaccionales (bienvenida, trial, pago); consentimiento implícito al registrarse ("Al registrarte aceptas…"). No hay emails de marketing.
- [x] RGPD: exportar/eliminar datos de cliente — `PrivacyController` + `/settings/privacy` con descarga JSON (Art. 20) y eliminación de cuenta con contraseña (Art. 17). Commit `2fa18fa`.
- [x] Información de empresa, CIF/NIF y dirección legal — footer de los 7 emails transaccionales: "Cositt · CIF B93340602". Commit `1397a67`.
- [x] Condiciones de uso de IA si se generan textos/imágenes — aviso in-app en `/settings/import-ai` con enlace a Términos. Commit `0e8ab1c`.

### Fase 6 — Producción técnica
- [x] `.env` de producción revisado — verificado 2026-05-24: APP_ENV=production, APP_DEBUG=false, APP_URL correcto, STRIPE_SECRET live, STRIPE_WEBHOOK_SECRET configurado.
- [x] `APP_ENV=production` / `APP_DEBUG=false` / `APP_URL` — confirmados en producción 2026-05-24.
- [x] Base de datos PostgreSQL en Docker; migraciones en `deploy.sh`.
- [x] Backups automáticos DB + storage (`scripts/backup.sh`, cron 03:00) — restauración probada 2026-05-16.
- [ ] Copia off-server de backups (recomendado antes de escalar).
- [x] Storage público en volumen Docker; deploy asegura `php artisan storage:link || true` (`6293f49`).
- [x] `QUEUE_CONNECTION=sync` aceptable al inicio (sin worker).
- [x] Scheduler/cron activo — `/etc/cron.d/neurocarta` verificado 2026-05-24: scheduler Laravel cada minuto + backup diario 03:00.
- [ ] Logs / monitorización (Sentry, etc.) — opcional pre-lanzamiento.
- [x] HTTPS Let's Encrypt en nginx.
- [ ] Dominio `demo.neurocarta.ai` (opcional ventas).
- [x] Emails SMTP en prod — configurado con Resend (smtp.resend.com:465, dominio neurocarta.ai verificado). Test manual `Mail::raw()` exitoso 2026-05-24.

### Fase 7 — Seguridad
- [x] Revisar permisos de admin (`FILAMENT_ADMIN_EMAIL`) — `hasPanelAdminAccess()` requiere `is_admin=true` en BD o email en `FILAMENT_ADMIN_EMAIL`/`demo_admin_emails`. Usuarios normales no acceden al panel.
- [x] Proteger rutas internas — todas las rutas admin/panel requieren `auth:sanctum + verified`; solo `/up`, `stripe/webhook` (con firma Stripe), carta pública y `set-password` (URL firmada) son públicas. Verificado `2026-05-24`.
- [x] Rate limit IA (30/min); login/registro con throttle Fortify. Filament login: 5 intentos/min por defecto.
- [x] Validación fuerte de subida de archivos públicos: logos, productos y alérgenos limitados a `jpg/jpeg`, `png`, `webp`; CSV/importaciones limitadas por tipo.
- [x] Evitar SVG peligroso en uploads públicos: SVG/GIF eliminados de validaciones y `accept`; `ImageAssetService` rechaza MIME no raster y re-encodea antes de guardar.
- [x] CSRF funcionando — `VerifyCsrfToken` solo excluye `stripe/webhook` (correcto). Verificado `2026-05-24`.
- [x] Cookies seguras en producción — `SESSION_SECURE_COOKIE=true` confirmado en `.env` de Jotelulu 2026-05-24.
- [x] Contraseñas y tokens nunca en repo — `git grep` limpio (solo valores de test en tests). Verificado `2026-05-24`.
- [x] Revisar `.gitignore` para `storage`, `.env`, backups y dumps — cubre `.env`, `storage/*.key`, `*.sql`, `*.dump`, `*.backup`. Verificado `2026-05-24`.
- [x] Auditoría básica de dependencias — `composer audit`: sin vulnerabilidades. Verificado `2026-05-24`.
- [x] CORS `allowed_origins` restringido — whitelist explícita (neurocarta.ai, app.neurocarta.ai, staging). Commit `28674d1`.
- [x] Idempotencia compra créditos IA — `cache()->add()` 30 días en `handleCreditPurchaseCompleted`. Commit `28674d1`.

### Fase 8 — Calidad
- [ ] Prueba manual completa: registro -> trial -> crear carta -> verla pública → **script**: `docs/LAUNCH-QA.md` bloques 1-3.
- [ ] Prueba manual: importar CSV → bloque 4.
- [ ] Prueba manual: subir imágenes → bloque 2. Pendiente validar tras fix de errores visibles y `storage:link` (`6293f49`).
- [ ] Prueba manual: cambiar plan/caducar trial → bloques 4 y 7.
- [ ] Prueba manual: usuario sin suscripción → bloque 7.
- [x] Tests mínimos de aislamiento por restaurante y suscripción (`TenantIsolationTest`, `SubscriptionExpiryTest`, `PlanFeatureGateTest`, `PublicMenuSubscriptionTest`). Registro crea restaurante + trial en BD. Login/registro: tests existentes; 419 en prod sin verificar.
- [ ] Revisar responsive en Chrome, Safari y móvil.
- [ ] Revisar rendimiento de carta con 100-300 productos.

### Fase 9 — Comercial
- [x] Definir precios finales — Básico 25/275€, Pro 35/385€, Premium 69/759€. Publicados en landing y en código.
- [x] Definir qué incluye cada plan — `PlanEntitlementService` + landing. Alineados.
- [ ] Demo preparada en **producción** → `bash scripts/ensure-demo-docker.sh` (`SERVIDOR-LANZAMIENTO.md` §7).
- [x] Restaurante demo en código (`demo:ensure`, `subdomain=demo`, `DemoMenuSeeder`). Plantilla de ejemplo en panel: `DemoContent` + `demo:resync-template-photos` (`407fb35`).
- [ ] Preparar onboarding para primeros clientes.
- [ ] Preparar soporte: email, WhatsApp o formulario.
- [ ] Preparar FAQ.
- [ ] Preparar proceso para migrar cartas actuales de clientes.
- [ ] Preparar material de venta: capturas, vídeo corto y pitch.

### Fase 10 — Antes de cobrar a clientes
- [ ] Stripe en modo live probado con pago pequeño.
- [x] Emails reales llegan bien — Resend configurado y verificado 2026-05-24 (Mail::raw test exitoso, email recibido).
- [x] Backups restaurables — restauración probada 2026-05-16 (`neurocarta_restore`, recuentos verificados).
- [x] Dominio final probado — `app.neurocarta.ai` con HTTPS Let's Encrypt activo y funcional.
- [x] Política legal publicada — publicada en `neurocarta.ai/terminos`, `neurocarta.ai/privacidad`.
- [x] Panel sin datos demo mezclados — demo data es por restaurante (subdominio `demo`); clientes nuevos empiezan con carta vacía o plantilla propia.
- [x] Usuario cliente no puede acceder a `/admin` salvo que corresponda — `canAccessPanel()` requiere `hasPanelAdminAccess()`. Verificado `2026-05-24`.
- [ ] Flujo de alta tarda menos de 5 minutos.
- [ ] Al menos 2-3 restaurantes piloto probados de principio a fin.
