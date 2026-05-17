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
| `MAIL_HOST` | `neurocarta.ai` |
| `MAIL_FROM_ADDRESS` | `noreply@neurocarta.ai` |
| `TURNSTILE_SECRET_KEY` | vacía (desactivado en login) |
| `TURNSTILE_SITE_KEY` | vacía (desactivado en login) |
| `FILAMENT_ADMIN_EMAIL` | test@test.com |

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

---

## Estado Git (2026-05-17)

- `main` en GitHub con deploy automático a Jotelulu (push → Action → `deploy.sh`).
- **Guion servidor**: `docs/SERVIDOR-LANZAMIENTO.md` + `bash scripts/server-launch-check.sh` en `/opt/neurocarta`.
- **QA manual**: `docs/LAUNCH-QA.md` (bloques 1–11) — pendiente en oficina.
- **Stripe / Legal**: cerrados por el equipo (código + documentos); verificar live en Fase 10.
- **Tests Launch**: 16 tests (`./scripts/launch-test.sh`) + `PublicMenuPerformanceTest`.

---

## Planes y precios (actualizado 2026-05-16)

| Plan | Precio mensual | Precio anual | Límites |
|---|---|---|---|
| **Gratis (trial)** | 0€ / 7 días | — | Sin límites — acceso total |
| **Básico** | 25€/mes | 250€/año (2 meses gratis) | 100 productos, 20 cats, sin IA ni traducciones |
| **Pro** | 35€/mes | 350€/año (2 meses gratis) | 500 productos, 60 cats, IA + traducciones + CSV |
| **Premium** | 65€/mes | 650€/año (2 meses gratis) | 2.000 productos, 200 cats, IA ilimitada |

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
- [ ] Limpiar estado de Git: separar cambios reales de archivos generados (`storage`, cachés, sesiones, logs).
- [ ] Crear/usar rama de trabajo de lanzamiento si procede.
- [ ] Convertir este checklist en tareas cerradas por área: Producto, Stripe, Tenants, UX, Legal, Producción, Seguridad, Calidad y Comercial.
- [ ] Definir MVP de lanzamiento frente a mejoras post-lanzamiento.
- [ ] Alinear precios y límites comerciales con los límites reales del código antes de cerrar Stripe.

### Fase 1 — Producto crítico
- [ ] Registro completo de restaurante sin intervención manual.
- [ ] Login, recuperación de contraseña y cierre de sesión sin errores 419 en pruebas reales.
- [ ] Trial gratis con fechas correctas y avisos coherentes.
- [ ] Pantalla de trial terminado clara y con CTA real para contratar.
- [ ] Bloqueo correcto de panel, QR y carta pública cuando trial/suscripción caduca.
- [x] Planes Básico / Pro / Premium conectados a límites reales: cuotas + features IA/CSV/traducciones en código (`d38cc17`). Pendiente: validación manual en `docs/LAUNCH-QA.md` bloques 4-6 y 8.
- [x] Panel de gestión usable en móvil y escritorio (productos + categorías: tarjetas < md, tabla ≥ md; commit UX móvil).
- [ ] Crear, editar, ocultar y ordenar categorías.
- [ ] Crear, editar, ocultar y ordenar productos.
- [ ] Subida de imágenes de platos estable.
- [ ] Imagen placeholder correcta cuando no hay foto.
- [ ] Alérgenos visibles y editables.
- [x] Vista pública de carta optimizada en consultas (locales en 1 query, ofertas sin duplicar eager load; test `PublicMenuPerformanceTest` 120 platos). Pendiente: validación manual < 3 s en prod (bloque 3.4).
- [ ] Selector de idioma revisado.
- [ ] Importación CSV probada con plantilla real.
- [ ] IA de importación, descripción e imágenes con control de créditos.

### Fase 2 — Pagos y suscripciones
- [x] Stripe en código (Checkout + webhooks). Pendiente: **live** probado (Fase 10).
- [ ] Stripe conectado en producción (claves `.env`).
- [ ] Checkout real para cada plan.
- [ ] Webhooks de Stripe configurados.
- [ ] Activación automática de suscripción tras pago.
- [ ] Cancelación, impago y renovación gestionados.
- [ ] Facturación anual/mensual clara.
- [ ] Emails de trial, alta, pago fallido y renovación.
- [x] Límite por plan aplicado de forma centralizada (`PlanEntitlementService` + `EnsurePlanFeature`). Pendiente: Stripe live y prueba de cobro.

### Fase 3 — Multi-restaurante / tenants
- [ ] Cada restaurante aislado correctamente.
- [ ] Subdominio o URL pública por restaurante funcionando.
- [ ] Usuario asignado a su cuenta/restaurante.
- [x] Evitar que un usuario vea datos de otro restaurante (scopes + test `TenantIsolationTest`; validar manual bloque 9 de `docs/LAUNCH-QA.md`).
- [ ] Selector de restaurante probado si una cuenta tiene varios.
- [ ] Seeds/demo separados de datos reales.
- [x] Eliminar o justificar `Restaurant::first()` fuera de seeds: solo en `DemoMenuSeeder` (fallback); `DetectRestaurant` usa subdominio/sesión/cookie.

### Fase 4 — Diseño y UX
- [ ] Revisar panel de productos con muchos platos (manual).
- [x] Tabla de productos en pantallas pequeñas: tarjetas móvil en `/product` y `/category` (< md).
- [ ] Revisar carta pública en móvil real (bloque 3 / 11.4 de `LAUNCH-QA.md`).
- [ ] Revisar estados vacíos: sin productos, sin categorías, sin imagen, sin suscripción.
- [ ] Revisar textos de ayuda, botones y errores.
- [ ] Revisar tema claro/oscuro si ambos existen.
- [ ] Branding consistente: logo, favicon, colores y emails.
- [ ] Página de precios pública lista.
- [ ] Landing pública con propuesta clara.

### Fase 5 — Legal
- [x] Términos, privacidad, cookies, aviso legal — **cerrado por abogado** (publicado en `neurocarta.ai`; validar enlaces en registro).
- [ ] Consentimiento para emails.
- [ ] RGPD: exportar/eliminar datos de cliente si aplica.
- [ ] Información de empresa, CIF/NIF y dirección legal.
- [ ] Condiciones de uso de IA si se generan textos/imágenes.

### Fase 6 — Producción técnica
- [ ] `.env` de producción revisado → **verificar en servidor** (`docs/SERVIDOR-LANZAMIENTO.md` §5).
- [ ] `APP_ENV=production` / `APP_DEBUG=false` / `APP_URL` → `launch:check` en prod.
- [x] Base de datos PostgreSQL en Docker; migraciones en `deploy.sh`.
- [x] Backups automáticos DB + storage (`scripts/backup.sh`, cron 03:00) — restauración probada 2026-05-16.
- [ ] Copia off-server de backups (recomendado antes de escalar).
- [x] Storage público en volumen Docker.
- [x] `QUEUE_CONNECTION=sync` aceptable al inicio (sin worker).
- [ ] Scheduler/cron activo → **verificar** `/etc/cron.d/neurocarta` (`SERVIDOR-LANZAMIENTO.md` §4).
- [ ] Logs / monitorización (Sentry, etc.) — opcional pre-lanzamiento.
- [x] HTTPS Let's Encrypt en nginx.
- [ ] Dominio `demo.neurocarta.ai` (opcional ventas).
- [ ] Emails SMTP en prod → prueba registro real (QA bloque 10).

### Fase 7 — Seguridad
- [ ] Revisar permisos de admin (`FILAMENT_ADMIN_EMAIL`).
- [ ] Proteger rutas internas.
- [x] Rate limit IA (30/min); login/registro con throttle Fortify.
- [x] Validación fuerte de subida de archivos públicos: logos, productos y alérgenos limitados a `jpg/jpeg`, `png`, `webp`; CSV/importaciones limitadas por tipo.
- [x] Evitar SVG peligroso en uploads públicos: SVG/GIF eliminados de validaciones y `accept`; `ImageAssetService` rechaza MIME no raster y re-encodea antes de guardar.
- [ ] CSRF funcionando.
- [ ] Cookies seguras en producción.
- [ ] Contraseñas y tokens nunca en repo.
- [ ] Revisar `.gitignore` para `storage`, `.env`, backups y dumps.
- [ ] Auditoría básica de dependencias.

### Fase 8 — Calidad
- [ ] Prueba manual completa: registro -> trial -> crear carta -> verla pública → **script**: `docs/LAUNCH-QA.md` bloques 1-3.
- [ ] Prueba manual: importar CSV → bloque 4.
- [ ] Prueba manual: subir imágenes → bloque 2.
- [ ] Prueba manual: cambiar plan/caducar trial → bloques 4 y 7.
- [ ] Prueba manual: usuario sin suscripción → bloque 7.
- [x] Tests mínimos de aislamiento por restaurante y suscripción (`TenantIsolationTest`, `SubscriptionExpiryTest`, `PlanFeatureGateTest`, `PublicMenuSubscriptionTest`). Registro crea restaurante + trial en BD. Login/registro: tests existentes; 419 en prod sin verificar.
- [ ] Revisar responsive en Chrome, Safari y móvil.
- [ ] Revisar rendimiento de carta con 100-300 productos.

### Fase 9 — Comercial
- [ ] Definir precios finales.
- [ ] Definir qué incluye cada plan.
- [ ] Demo preparada en **producción** → `bash scripts/ensure-demo-docker.sh` (`SERVIDOR-LANZAMIENTO.md` §7).
- [x] Restaurante demo en código (`demo:ensure`, `subdomain=demo`, `DemoMenuSeeder`).
- [ ] Preparar onboarding para primeros clientes.
- [ ] Preparar soporte: email, WhatsApp o formulario.
- [ ] Preparar FAQ.
- [ ] Preparar proceso para migrar cartas actuales de clientes.
- [ ] Preparar material de venta: capturas, vídeo corto y pitch.

### Fase 10 — Antes de cobrar a clientes
- [ ] Stripe en modo live probado con pago pequeño.
- [ ] Emails reales llegan bien.
- [ ] Backups restaurables.
- [ ] Dominio final probado.
- [ ] Política legal publicada.
- [ ] Panel sin datos demo mezclados.
- [ ] Usuario cliente no puede acceder a `/admin` salvo que corresponda.
- [ ] Flujo de alta tarda menos de 5 minutos.
- [ ] Al menos 2-3 restaurantes piloto probados de principio a fin.
