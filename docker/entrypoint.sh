#!/bin/sh
set -e
cd /var/www/html
if [ -f .env ]; then
  php artisan config:clear 2>/dev/null || true
fi
# Cachés solo si APP_KEY existe (evita fallo en primer arranque)
if [ -n "$APP_KEY" ]; then
  php artisan config:cache 2>/dev/null || true
  php artisan migrate --force 2>/dev/null || true
  php artisan route:cache 2>/dev/null || true
  php artisan view:cache 2>/dev/null || true
fi
exec "$@"
