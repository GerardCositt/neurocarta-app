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
- **IP pública**: 149.71.98.240
- **Servidor**: SRV-COSI03-029 (nombre interno Jotelulu)
- **Ruta app**: `/opt/neurocarta/`
- **Docker**: 3 contenedores — `neurocarta-app-1` (PHP/Laravel), `neurocarta-db-1` (PostgreSQL 16), `neurocarta-nginx-1` (Nginx + SSL)
- **SSL**: Let's Encrypt (certbot), renovación automática. Certs en `/etc/letsencrypt/live/app.neurocarta.ai/`
- **Base de datos**: PostgreSQL 16 en contenedor, datos en volumen `neurocarta_db_data`
- **Usuario de prueba**: test@test.com / test1234
- **Deploy**: script `/opt/neurocarta/deploy.sh` → git pull + rebuild + migrate
- **Clave SSH**: `~/.ssh/neurocarta_jotelulu` (Mac mini) → clave `neurocarta-mac` en Jotelulu

### Staging — Render (legacy, mantener por ahora)
- **App**: https://neurocarta-staging.onrender.com
- **Plataforma**: Render (Docker, PHP 8.2 + Apache)
- **Base de datos**: PostgreSQL en Render
- **Usuario de prueba**: test@test.com / test1234
- **Health check**: `/up` → devuelve 200 OK
- **Keep-alive**: UptimeRobot hace ping a `/up` cada 5 min

### Landing (neurocarta.ai)
- **Plataforma**: Plesk (servidor COSITT, IP 217.154.188.235)
- **Usuario SSH**: `neurocarta.ai_d8ugncl8ukj`
- **Ruta httpdocs**: `/var/www/vhosts/neurocarta.ai/httpdocs/`
- Los archivos del `dist` se suben por SCP directamente a `httpdocs/`
- La landing NO usa deploy automático — hay que subir el `dist` manualmente por SCP tras cada build

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
1. Editar `src/App.jsx` (u otros archivos fuente)
2. `npm run build` en `neurocarta-ai-landings/neurocarta-conversion`
3. Subir por SCP:
   ```bash
   scp -r "/ruta/local/neurocarta-conversion/dist/." neurocarta.ai_d8ugncl8ukj@217.154.188.235:/var/www/vhosts/neurocarta.ai/httpdocs/
   ```

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

---

## Decisiones técnicas relevantes

- **PostgreSQL en lugar de SQLite**: La app original usaba SQLite. Se migró a PostgreSQL.
- **Detección de restaurante por subdominio**: El middleware `DetectRestaurant` lee el subdominio.
- **SESSION_DRIVER=cookie**: Necesario porque el filesystem de Docker no persiste entre recreaciones.
- **Logout redirige a /login**: Configurado en `AppServiceProvider` via `LogoutResponse` de Fortify.
- **BarJaenIIISeeder eliminado del entrypoint**: Se quitó de `docker/entrypoint.sh` porque pisaba datos reales de usuarios al desplegarse. Solo se lanza manualmente para demo.
- **Turnstile desactivado en login**: El widget estaba fuera del `<form>` (token nunca se enviaba) y bloqueaba el login. Eliminado del blade y del pipeline de Fortify. Pendiente reactivar correctamente con claves reales de Cloudflare.
- **DNS en Cloudflare**: `app.neurocarta.ai` → `149.71.98.240`, proxy desactivado (nube gris).

---

## Planes y precios (cerrado 2026-04-12)

| Plan | Precio | Límites |
|---|---|---|
| **Gratis (trial)** | 0€ / 7 días | Sin límites — acceso total |
| **Básico** | €59/mes (fact. anual) | 100 productos, 20 cats, sin IA ni traducciones |
| **Pro** | €129/mes (fact. anual) | 500 productos, 60 cats, IA + traducciones + CSV |
| **Premium** | €249/mes (fact. anual) | 2.000 productos, 200 cats, IA ilimitada |

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

## Pendiente / próximos pasos

- [ ] Implementar flujo de registro con trial (ver sección arriba)
- [ ] Reactivar Turnstile correctamente en login con claves reales de Cloudflare
- [ ] Crear planes en Stripe y conectar webhooks
- [ ] Pantalla de bloqueo día 8 (panel + carta pública + QR)
- [ ] Emails de aviso día 5 y día 7
- [ ] Validación de teléfono por WhatsApp/SMS (anti-abuso trial)
- [ ] Migrar base de datos de Render a Jotelulu o descartar staging
- [ ] Evaluar si subir landing a deploy automático o mantener SCP manual
