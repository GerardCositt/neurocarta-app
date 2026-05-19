# Lanzamiento completo — todos los pasos

> Orden recomendado. Marca cada ítem cuando esté hecho.  
> Demo pública: **https://demo.neurocarta.ai/** · Panel: **https://app.neurocarta.ai**

---

## Fase 0 — Subir código al servidor (obligatorio primero)

Cambios pendientes en repo local: demo SSL/nginx, `demo:unlock-public`, bypass carta demo, `launch:check` con `config()`.

```bash
# En tu Mac (repo neurocarta-app)
git add app/Console/Commands/DemoUnlockPublicCommand.php \
        app/Services/DemoPublicMenuService.php \
        app/Console/Commands/EnsureDemoCommand.php \
        app/Console/Commands/LaunchCheckCommand.php \
        app/Http/Middleware/CheckPublicMenuSubscription.php \
        docker/nginx.conf \
        docs/SERVIDOR-LANZAMIENTO.md \
        docs/LANZAMIENTO-TODOS-LOS-PASOS.md \
        scripts/ensure-demo-docker.sh \
        scripts/server-launch-check.sh \
        tests/Feature/PublicMenuSubscriptionTest.php
git commit -m "Demo público: SSL/nginx, unlock suscripción y launch:check en prod"
git push origin main
```

Espera que el GitHub Action termine (o en servidor: `cd /opt/neurocarta && git pull && bash deploy.sh`).

---

## Fase 1 — Servidor (≈20 min, SSH)

```bash
ssh -i ~/.ssh/neurocarta_jotelulu2 root@149.71.98.35
cd /opt/neurocarta
git pull
bash deploy.sh

# .env aplicado al contenedor + caché
docker compose -f docker-compose.prod.yml up -d --force-recreate app
docker exec neurocarta-app-1 php artisan config:clear
docker exec neurocarta-app-1 php artisan config:cache

# Demo + desbloqueo carta
docker exec neurocarta-app-1 php artisan demo:ensure --force
docker exec neurocarta-app-1 php artisan demo:unlock-public --force

# Nginx (server_name demo + HSTS si no está tras pull)
grep server_name docker/nginx.conf
docker compose -f docker-compose.prod.yml restart nginx

# Verificación automática
bash scripts/server-launch-check.sh

# Valores efectivos (deben ser debug=false, secure=true)
docker exec neurocarta-app-1 php artisan tinker --execute="
echo 'debug='.(config('app.debug')?'true':'false').' secure='.(config('session.secure')?'true':'false').PHP_EOL;
"

# HTTPS demo
curl -sI http://demo.neurocarta.ai/ | grep -i location
curl -sS -o /dev/null -w "demo https=%{http_code}\n" https://demo.neurocarta.ai/
```

| Check | Comando / URL | OK |
|-------|----------------|-----|
| Health | `curl -sS https://app.neurocarta.ai/up` → 200 | ☐ |
| launch:check | `docker exec neurocarta-app-1 php artisan launch:check --skip-tests` → todo ✓ | ☐ |
| Demo carta | https://demo.neurocarta.ai/ (candado HTTPS) | ☐ |
| Cron | `cat /etc/cron.d/neurocarta` | ☐ |
| Backups | `ls -lh backups/db/daily/ \| tail -3` | ☐ |

---

## Fase 2 — QA manual (`docs/LAUNCH-QA.md`)

Haz **todos** los bloques en producción. Usa cuenta nueva por bloque cuando indique el doc.

| Bloque | Tema | ☐ |
|--------|------|---|
| 1 | Registro y trial | ☐ |
| 2 | CRUD carta (categorías, productos, fotos) | ☐ |
| 3 | Carta pública + móvil | ☐ |
| 4 | CSV (plan Pro) | ☐ |
| 5 | IA import (plan Pro) | ☐ |
| 6 | Traducciones (plan Pro) | ☐ |
| 7 | Trial expirado + bloqueo | ☐ |
| 8 | Límites plan Básico | ☐ |
| 9 | Aislamiento multi-tenant | ☐ |
| 10 | Emails transaccionales | ☐ |
| 11 | Chrome / Safari / móvil | ☐ |

**Comandos útiles en prod (bloque 7):**

```bash
docker exec neurocarta-app-1 php artisan trial:expire email@prueba.com --force
docker exec neurocarta-app-1 php artisan trial:send-warnings
docker exec neurocarta-app-1 php artisan schedule:run
```

---

## Fase 3 — Stripe live (bloqueante para cobrar)

En [Stripe Dashboard](https://dashboard.stripe.com) → modo **Live**.

1. Claves live en `/opt/neurocarta/.env`:
   - `STRIPE_KEY=pk_live_...`
   - `STRIPE_SECRET=sk_live_...`
   - `STRIPE_WEBHOOK_SECRET=whsec_...` (endpoint `https://app.neurocarta.ai/stripe/webhook`)
   - `STRIPE_PRICE_*`: seis Prices live, uno por plan/intervalo.
2. En Stripe Tax, los Prices deben tratarse como **sin IVA incluido**:
   - Tax behavior/default tax behavior: `exclusive`.
   - Producto con tax code adecuado para el servicio SaaS/digital.
   - Con esto, Checkout cobra `25/35/69€ + IVA` y Stripe desglosa el impuesto.
3. Recrear app y cachear: `docker compose -f docker-compose.prod.yml up -d --force-recreate app && docker exec neurocarta-app-1 php artisan config:cache`
4. Cuenta de prueba → trial expirado o sin plan → **Elegir plan** → pago real mínimo (ej. Básico).
5. Verificar en Stripe: suscripción `active`; en panel: acceso completo; carta pública activa.

| ☐ | Pago live completado |
| ☐ | Webhook 200 en Stripe (últimos eventos) |
| ☐ | Panel reactivado tras pago |

---

## Fase 4 — Email transaccional (bloque 10)

1. Registro con email **real** (Gmail/Outlook) → bienvenida + activación en < 2 min.
2. Revisar spam; SPF/DKIM en Cloudflare (registros TXT ya en DNS).
3. Avisos trial día 5/7: `docker exec neurocarta-app-1 php artisan trial:send-warnings` (o esperar cron).

| ☐ | Email registro OK |
| ☐ | Enlace activación OK |
| ☐ | Aviso trial (manual o cron) OK |

---

## Fase 5 — Restaurantes piloto (2–3)

Por cada piloto:

1. Registro real (nombre restaurante + teléfono).
2. Cargar carta (manual o CSV).
3. Probar carta en su subdominio o `?restaurant=ID`.
4. QR desde panel → abre carta en móvil.
5. Si pagan: flujo Stripe live.

| Piloto | Registro | Carta | QR/móvil | Pago (opc.) | ☐ |
|--------|----------|-------|----------|-------------|---|
| 1 | | | | | ☐ |
| 2 | | | | | ☐ |
| 3 | | | | | ☐ |

---

## Fase 6 — Comercial y legal (paralelo)

| ☐ | Términos, privacidad, cookies publicados (abogado) |
| ☐ | Precios alineados con landing |
| ☐ | Onboarding documentado (1 página Notion/PDF) |
| ☐ | Email soporte `hola@neurocarta.ai` monitorizado |
| ☐ | FAQ mínima (trial, planes, QR, subdominio) |
| ☐ | Material ventas con URL demo https://demo.neurocarta.ai/ |
| ☐ | Backup off-server (S3 / otro VPS) planificado |

---

## Fase 7 — Checklist final (antes de anunciar)

- [ ] Fases 0–1 completas en servidor
- [ ] LAUNCH-QA bloques 1–11 marcados
- [ ] Stripe live + webhook OK
- [ ] Emails OK
- [ ] 2–3 pilotos OK
- [ ] `APP_DEBUG=false` confirmado con `tinker`
- [ ] Demo no mezclada con clientes reales (cuenta propia demo)
- [ ] Anuncio / campaña solo después de todo lo anterior

---

## Referencias

- QA detallado: [`docs/LAUNCH-QA.md`](LAUNCH-QA.md)
- Servidor: [`docs/SERVIDOR-LANZAMIENTO.md`](SERVIDOR-LANZAMIENTO.md)
- Contexto proyecto: [`CLAUDE.md`](../CLAUDE.md)
