# Guion del servidor — NeuroCarta (Jotelulu / producción)

> Checklist operativo para cerrar **Fase 6**, **Fase 9 (demo en prod)** y el **pre-QA** de `docs/LAUNCH-QA.md`.  
> No sustituye el QA manual en navegador (`docs/LAUNCH-QA.md` bloques 1–11).

## Datos del servidor

| Concepto | Valor |
|----------|--------|
| App | https://app.neurocarta.ai |
| IP | `149.71.98.35` |
| SSH | `ssh -i ~/.ssh/neurocarta_jotelulu2 root@149.71.98.35` |
| Ruta app | `/opt/neurocarta/` |
| Contenedor Laravel | `neurocarta-app-1` |
| Contenedor DB | `neurocarta-db-1` |
| Deploy manual | `/opt/neurocarta/deploy.sh` |
| Deploy automático | push a `main` → GitHub Action `.github/workflows/deploy.yml` |
| `.env` producción | `/opt/neurocarta/.env` (no está en Git) |

---

## 1. Conectar y comprobar contenedores

```bash
ssh -i ~/.ssh/neurocarta_jotelulu2 root@149.71.98.35

cd /opt/neurocarta
docker ps --format 'table {{.Names}}\t{{.Status}}'
```

Debes ver al menos: `neurocarta-app-1`, `neurocarta-db-1`, `neurocarta-nginx-1` en estado **Up**.

Health rápido desde tu Mac (sin SSH):

```bash
curl -sS -o /dev/null -w "%{http_code}\n" https://app.neurocarta.ai/up
# Esperado: 200
```

---

## 2. Tras cada deploy (o si el Action falló)

```bash
cd /opt/neurocarta
git log -1 --oneline          # commit desplegado
bash deploy.sh                # solo si hace falta forzar (el Action ya lo ejecuta)
```

Comprobar que el contenedor app tiene el código nuevo:

```bash
docker exec neurocarta-app-1 php artisan --version
docker exec neurocarta-app-1 git -C /opt/neurocarta rev-parse --short HEAD 2>/dev/null || true
```

---

## 3. Guion único — verificación pre-lanzamiento (≈10 min)

En el servidor, desde `/opt/neurocarta`:

```bash
bash scripts/server-launch-check.sh
```

O paso a paso:

```bash
# A) Tests automáticos: en local/CI → ./scripts/launch-test.sh (en prod no hay PHPUnit en la imagen)
# B) Env, scheduler, demo
docker exec neurocarta-app-1 php artisan launch:check --skip-tests

# C) Restaurante demo + menú de ventas
bash scripts/ensure-demo-docker.sh

# D) Health y carta demo
curl -sS -o /dev/null -w "up=%{http_code}\n" https://app.neurocarta.ai/up
# Sustituye ID si launch:check lo imprime:
curl -sS -o /dev/null -w "menu=%{http_code}\n" "https://app.neurocarta.ai/?restaurant=ID_DEMO"
```

Marca ✅ cada bloque si termina sin error.

---

## 4. Cron y scheduler (Fase 6 — crítico para trials)

Debe existir `/etc/cron.d/neurocarta` con algo equivalente a:

```cron
* * * * * root docker exec neurocarta-app-1 php artisan schedule:run >> /var/log/neurocarta-scheduler.log 2>&1
0 3 * * * root /opt/neurocarta/scripts/backup.sh >> /var/log/neurocarta-backup.log 2>&1
```

Comprobar:

```bash
cat /etc/cron.d/neurocarta
tail -20 /var/log/neurocarta-scheduler.log
```

Tareas Laravel programadas (`app/Console/Kernel.php`):

| Comando | Cuándo |
|---------|--------|
| `offers:expire` | Diario 00:05 |
| `trial:send-warnings` | Diario 09:00 |

Prueba manual (opcional, no envía si no hay trials en día 5/7):

```bash
docker exec neurocarta-app-1 php artisan trial:send-warnings
docker exec neurocarta-app-1 php artisan offers:expire
```

---

## 5. Variables `.env` (Fase 6)

Revisar en el servidor (no pegar secretos en chats):

```bash
grep -E '^(APP_ENV|APP_DEBUG|APP_URL|SESSION_DRIVER|SESSION_SECURE_COOKIE|MAIL_HOST|MAIL_FROM|QUEUE_CONNECTION|DB_CONNECTION)=' /opt/neurocarta/.env
```

| Variable | Esperado |
|----------|----------|
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_URL` | `https://app.neurocarta.ai` |
| `SESSION_DRIVER` | `cookie` |
| `SESSION_SECURE_COOKIE` | `true` |
| `MAIL_HOST` | configurado (SMTP) |
| `MAIL_FROM_ADDRESS` | `noreply@neurocarta.ai` |

Tras cambiar `.env`:

```bash
bash deploy.sh
# o al menos:
docker exec neurocarta-app-1 php artisan config:clear
docker exec neurocarta-app-1 php artisan config:cache
```

---

## 6. Backups (Fase 6 / Fase 10)

```bash
ls -lh /opt/neurocarta/backups/db/daily/ | tail -5
ls -lh /opt/neurocarta/backups/storage/daily/ | tail -5
tail -30 /opt/neurocarta/backups/backup.log
```

Restauración de prueba (solo en ventana de mantenimiento): ver notas en `CLAUDE.md` sección Backups.

Pendiente recomendado antes de escalar: copia **off-server** (S3, otro VPS).

---

## 7. Demo pública de ventas (Fase 9)

```bash
bash /opt/neurocarta/scripts/ensure-demo-docker.sh
```

URLs:

- Con subdominio (si DNS existe): https://demo.neurocarta.ai/
- Sin DNS: `https://app.neurocarta.ai/?restaurant=ID` (el script o `launch:check` muestran el id)

DNS y SSL para `demo.neurocarta.ai` (y cartas por subdominio):

| Registro | Tipo | Destino | Proxy Cloudflare |
|----------|------|---------|------------------|
| `app` | A | `149.71.98.35` | **Desactivado** (nube gris), igual que ahora |
| `demo` | A | `149.71.98.35` | **Desactivado** (nube gris) — si está naranja y el cert no incluye `demo`, error **521** |

El certificado Let's Encrypt del servidor solo cubre los nombres que incluyas al emitirlo. Tras desplegar `docker/nginx.conf` (incluye `demo` y `*.neurocarta.ai`):

```bash
# El webroot ACME está en el volumen Docker certbot_www (no en /var/www/certbot del host).
docker run --rm \
  -v neurocarta_certbot_www:/var/www/certbot \
  -v /etc/letsencrypt:/etc/letsencrypt \
  certbot/certbot certonly --webroot -w /var/www/certbot \
  --cert-name app.neurocarta.ai \
  --expand \
  -d app.neurocarta.ai \
  -d demo.neurocarta.ai \
  --non-interactive --agree-tos -m tu-email@neurocarta.ai

# Si el volumen tiene otro nombre: docker volume ls | grep certbot

cd /opt/neurocarta
docker compose -f docker-compose.prod.yml restart nginx
```

Comprobar certificado:

```bash
echo | openssl s_client -connect 149.71.98.35:443 -servername demo.neurocarta.ai 2>/dev/null \
  | openssl x509 -noout -ext subjectAltName
```

Debe listar `DNS:demo.neurocarta.ai`. Luego `https://demo.neurocarta.ai/` debe responder (no 521).

Para **muchos** subdominios de clientes (`cliente.neurocarta.ai`), conviene más adelante un certificado wildcard `*.neurocarta.ai` (desafío DNS en Cloudflare).

---

## 8. Comandos útiles para QA en producción

Caducar trial de una cuenta de prueba (bloque 7 de `LAUNCH-QA.md`):

```bash
docker exec neurocarta-app-1 php artisan trial:expire email@prueba.com --force
```

Logs Laravel:

```bash
docker exec neurocarta-app-1 tail -100 /opt/neurocarta/storage/logs/laravel.log
```

Migraciones pendientes:

```bash
docker exec neurocarta-app-1 php artisan migrate:status
```

---

## 9. Landing (otro servidor — no es Jotelulu)

Repo: `GerardCositt/neurocarta-ai-landings` → push `main` despliega a Plesk (`neurocarta.ai`).  
No requiere SSH a Jotelulu. Ver Actions en GitHub del repo landings.

---

## 10. Después del guion del servidor

1. Ejecutar **`docs/LAUNCH-QA.md`** en el navegador (oficina): bloques 1 → 3 → 7 → 9 como mínimo.  
2. Cerrar **Stripe live** y **legal** (equipo).  
3. 2–3 restaurantes piloto de principio a fin (Fase 10).

---

## Mapa checklist ↔ este guion

| Checklist (`CLAUDE.md`) | Sección de este doc |
|-------------------------|---------------------|
| Fase 6 — `.env`, APP_DEBUG, migraciones | §5, §2 |
| Fase 6 — scheduler/cron | §4 |
| Fase 6 — backups DB + storage | §6 |
| Fase 6 — SMTP / emails | §5 + prueba registro real (QA) |
| Fase 6 — dominios / HTTPS | §1, §7 (DNS demo) |
| Fase 9 — demo preparada en prod | §7 |
| Fase 8 — pre-QA automático | §3 |
| Fase 8 — QA manual | `docs/LAUNCH-QA.md` |
| Fase 10 — emails, backups, dominio | §4–§6 + QA |
