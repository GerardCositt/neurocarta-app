# NeuroCarta — Contexto del proyecto

> **Instrucción para Claude Code**: Al final de cada conversación, antes de despedirte, pregunta siempre al usuario si quiere actualizar este archivo `CLAUDE.md` con los cambios o decisiones relevantes de la sesión. Si dice que sí, actualízalo y haz commit + push.

## Proyectos relacionados

| Proyecto | Ruta local | Repo GitHub |
|---|---|---|
| App Laravel (panel admin + carta pública) | `../neurocarta-app` | `GerardCositt/neurocarta-app` |
| Landing page (React/Vite) | `../neurocarta-ai-landings/neurocarta-conversion` | `GerardCositt/neurocarta-ai-landings` |

---

## Arquitectura de despliegue

### Staging (activo)
- **App**: https://neurocarta-staging.onrender.com
- **Plataforma**: Render (Docker, PHP 8.2 + Apache)
- **Base de datos**: PostgreSQL en Render
- **Subdominio detectado**: `neurocarta-staging` (guardado en tabla `restaurants`)
- **Usuario de prueba**: test@test.com / test1234
- **Health check**: `/up` → devuelve 200 OK
- **Keep-alive**: UptimeRobot hace ping a `/up` cada 5 min

### Producción / Plesk (app.neurocarta.ai)
- Redirige a staging con `.htaccess` (301 → neurocarta-staging.onrender.com)
- Ruta en servidor: `/var/www/vhosts/neurocarta.ai/app.neurocarta.ai/laravel/public/.htaccess`

### Landing (neurocarta.ai)
- **Plataforma**: Plesk (servidor COSITT, IP 217.154.188.235)
- **Usuario SSH**: `neurocarta.ai_d8ugncl8ukj`
- **Ruta httpdocs**: `/var/www/vhosts/neurocarta.ai/httpdocs/`
- Los archivos del `dist` se suben por SCP directamente a `httpdocs/`
- La landing NO usa deploy automático — hay que subir el `dist` manualmente por SCP tras cada build

---

## Flujo de deploy

### App (staging en Render)
1. `git push` a `main` → Render detecta el push y despliega automáticamente
2. El `Dockerfile` hace build de assets Node + PHP
3. `docker/entrypoint.sh` corre `php artisan migrate --force` al arrancar

### Landing (neurocarta.ai)
1. Editar `src/App.jsx` (u otros archivos fuente)
2. `npm run build` en `neurocarta-ai-landings/neurocarta-conversion`
3. Subir por SCP:
   ```bash
   scp -r "/ruta/local/neurocarta-conversion/dist/." neurocarta.ai_d8ugncl8ukj@217.154.188.235:/var/www/vhosts/neurocarta.ai/httpdocs/
   ```

---

## Variables de entorno en Render (staging)

| Variable | Valor |
|---|---|
| `APP_ENV` | `production` |
| `APP_DEBUG` | `true` (cambiar a false cuando sea estable) |
| `DB_CONNECTION` | `pgsql` |
| `SESSION_DRIVER` | `cookie` |
| `APP_URL` | `https://neurocarta-staging.onrender.com` |

---

## Decisiones técnicas relevantes

- **PostgreSQL en lugar de SQLite**: La app original usaba SQLite. Se migró a PostgreSQL para Render. Los booleanos usan `true`/`false` en lugar de `1`/`0`.
- **Detección de restaurante por subdominio**: El middleware `DetectRestaurant` lee el subdominio para cargar el restaurante. En staging el subdominio es `neurocarta-staging`.
- **SESSION_DRIVER=cookie**: Necesario porque Render no persiste el filesystem entre deploys.
- **Logout redirige a /login**: Configurado en `AppServiceProvider` via `LogoutResponse` de Fortify.
- **Botón "Solicita acceso" eliminado** del login (`vendor/jetstream/components/authentication-card.blade.php`).

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

- [ ] Cambiar `APP_DEBUG=false` en Render cuando el staging sea estable
- [ ] Implementar flujo de registro con trial (ver sección arriba)
- [ ] Crear planes en Stripe y conectar webhooks
- [ ] Pantalla de bloqueo día 8 (panel + carta pública + QR)
- [ ] Emails de aviso día 5 y día 7
- [ ] Validación de teléfono por WhatsApp/SMS (anti-abuso trial)
- [ ] Evaluar si subir landing a deploy automático o mantener SCP manual
