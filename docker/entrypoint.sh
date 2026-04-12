#!/bin/sh
set -e
cd /var/www/html
if [ -f .env ]; then
  php artisan config:clear 2>/dev/null || true
fi
# Cachés solo si APP_KEY existe (evita fallo en primer arranque)
if [ -n "$APP_KEY" ]; then
  php artisan config:cache || true
  php artisan migrate --force || true
  php artisan db:seed --class=BarJaenIIISeeder --force || true
  php artisan route:cache || true
  php artisan view:cache || true
fi
exec "$@"
