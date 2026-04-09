# Publicar la aplicación (hosting)

Tienes dos caminos habituales: **Docker** (recomendado para demo o VPS) o **servidor clásico** (Nginx/Apache + PHP + MySQL).

## Opción A — Docker (un solo comando en casi cualquier hosting)

En la raíz del repo:

1. Copia variables de entorno y ajusta producción:
   ```bash
   cp .env.example .env
   ```
   En `.env` define como mínimo:
   - `APP_ENV=production`
   - `APP_DEBUG=false`
   - `APP_URL=https://tu-dominio.com` (sin barra final)
   - `APP_KEY=` (luego la generas dentro del contenedor)
   - `DB_CONNECTION=mysql`
   - `DB_HOST=mysql` (solo para `docker-compose.prod.yml`; el servicio ya fuerza este host)
   - `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` (deben coincidir con las variables del servicio `mysql` en el compose, o edita el compose)

2. Arranca:
   ```bash
   docker compose -f docker-compose.prod.yml up -d --build
   ```

3. Primera configuración (dentro del contenedor `app`):
   ```bash
   docker compose -f docker-compose.prod.yml exec app php artisan key:generate --force
   docker compose -f docker-compose.prod.yml exec app php artisan migrate --force
   docker compose -f docker-compose.prod.yml exec app php artisan db:seed --force
   docker compose -f docker-compose.prod.yml exec app php artisan demo:prepare
   docker compose -f docker-compose.prod.yml exec app php artisan storage:link
   ```

4. Abre el puerto publicado (por defecto **8080** → mapea en el panel del proveedor o pon delante un proxy con TLS). La carta pública con restaurante por query:
   `https://tu-dominio.com/?restaurant=1`  
   (el id lo muestra `php artisan demo:prepare`.)

5. **HTTPS**: delante del contenedor usa Caddy, Traefik, Nginx o el balanceador de tu proveedor (Let's Encrypt).

6. IA ilimitada solo en servidor de demo (opcional):
   ```bash
   docker compose -f docker-compose.prod.yml exec app php artisan demo:prepare --unlimited-ai --force
   ```

Proveedores habituales donde esto encaja: **VPS** (Hetzner, DigitalOcean, OVH), **Railway**, **Fly.io**, **Render** (con Dockerfile), etc. (cada uno tiene su forma de exponer puerto y TLS; el artefacto es la imagen que construye este `Dockerfile`).

## Opción B — Sin Docker (VPS o hosting PHP)

1. PHP **8.2+** recomendado (en Plesk, ojo con la versión de **CLI** vs **FPM**), extensiones: `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `fileinfo`, `gd`, `zip`.
2. **MySQL 8** o **MariaDB 10.6+**.
3. Nginx o Apache con **document root** en `public/`.
4. En el servidor:
   ```bash
   git clone … && cd carta-barjaen-v2
   composer install --no-dev --optimize-autoloader
   cp .env.example .env
   # edita .env (APP_URL, DB_*, APP_DEBUG=false)
   php artisan key:generate
   npm ci && npm run production
   php artisan migrate --force
   php artisan db:seed --force
   php artisan demo:prepare
   ```
5. Permisos: `storage` y `bootstrap/cache` escribibles por el usuario del servidor web.
6. Cola/worker: si más adelante usas colas Redis, añade `supervisor`; con `QUEUE_CONNECTION=sync` no hace falta al inicio.

## Después del despliegue

- Crea un usuario desde `/register` si el registro está abierto, o usa el flujo que tengáis en producción.
- Revisa la guía de demo: `docs/DEMO.md`.

## Plesk (nota rápida)

En Plesk es habitual que:
- El sitio web use una versión de PHP (FPM) distinta a la del **CLI** en SSH.
- `composer install` o `php artisan ...` fallen si ejecutas con el PHP CLI antiguo.

Ejemplo de ejecución explícita con PHP 8.4 en Plesk:

```bash
/opt/plesk/php/8.4/bin/php -v
/opt/plesk/php/8.4/bin/php artisan migrate --force
/opt/plesk/php/8.4/bin/php artisan view:clear
```
