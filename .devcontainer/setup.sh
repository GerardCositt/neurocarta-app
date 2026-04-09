#!/usr/bin/env bash
set -euo pipefail

ensure_env_kv() {
  local key="$1" val="$2"
  if [[ -f .env ]] && grep -q "^${key}=" .env; then
    sed -i "s|^${key}=.*|${key}=${val}|" .env
  else
    echo "${key}=${val}" >> .env
  fi
}

php_ext_ok() {
  php -r 'exit(extension_loaded("zip") && extension_loaded("pdo_mysql") && extension_loaded("gd") ? 0 : 1);'
}

build_php_ext_in_container() {
  if ! command -v docker-php-ext-install >/dev/null 2>&1; then
    return 1
  fi
  sudo apt-get update
  sudo DEBIAN_FRONTEND=noninteractive apt-get install -y --no-install-recommends \
    libzip-dev zip unzip libpng-dev libjpeg62-turbo-dev libfreetype6-dev
  sudo docker-php-ext-configure gd --with-freetype --with-jpeg
  sudo docker-php-ext-install -j"$(nproc)" zip pdo_mysql gd
}

if ! php_ext_ok; then
  if command -v docker-php-ext-install >/dev/null 2>&1; then
    build_php_ext_in_container
  else
    echo "ERROR: falta ext-gd (y no hay docker-php-ext-install). Rebuild Container con .devcontainer/"
    exit 1
  fi
fi

# MySQL: mismo contenedor (127.0.0.1) o antiguo compose (db)
wait_for_mysql_port() {
  local host="${1:-127.0.0.1}"
  local port="${2:-3306}"
  local max="${3:-180}"
  local i
  for ((i = 0; i < max; i++)); do
    if (echo >/dev/tcp/"$host"/"$port") &>/dev/null; then
      return 0
    fi
    sleep 1
  done
  return 1
}

if [[ "${DB_CONNECTION:-}" == "mysql" ]]; then
  MYSQL_PING_HOST="${DB_HOST:-127.0.0.1}"
  echo "Esperando puerto ${MYSQL_PING_HOST}:3306…"
  wait_for_mysql_port "${MYSQL_PING_HOST}" 3306 180 || true
  echo "Esperando credenciales carta en ${MYSQL_PING_HOST}…"
  for _ in $(seq 1 180); do
    if mysqladmin ping -h "${MYSQL_PING_HOST}" -u carta -pcarta --silent 2>/dev/null; then
      break
    fi
    sleep 1
  done
  if ! mysqladmin ping -h "${MYSQL_PING_HOST}" -u carta -pcarta --silent 2>/dev/null; then
    echo "ERROR: no hay MariaDB en ${MYSQL_PING_HOST}. Ejecuta: bash .devcontainer/start-db.sh"
    exit 1
  fi
  [[ -f .env ]] || cp .env.example .env
  ensure_env_kv DB_CONNECTION mysql
  ensure_env_kv DB_HOST "${DB_HOST:-127.0.0.1}"
  ensure_env_kv DB_PORT "${DB_PORT:-3306}"
  ensure_env_kv DB_DATABASE "${DB_DATABASE:-carta_v2}"
  ensure_env_kv DB_USERNAME "${DB_USERNAME:-carta}"
  ensure_env_kv DB_PASSWORD "${DB_PASSWORD:-carta}"
  sed -i '/^DB_DATABASE=database\/database\.sqlite$/d' .env 2>/dev/null || true
else
  if [[ ! -f .env ]]; then
    cp .env.example .env
  fi
  mkdir -p database
  if [[ ! -f database/database.sqlite ]]; then
    touch database/database.sqlite
  fi
fi

composer install --no-interaction --no-scripts
php artisan key:generate --force --no-interaction
composer install --no-interaction

npm install

if [[ "${DB_CONNECTION:-}" == "mysql" ]]; then
  echo ""
  echo "MariaDB listo. Importar volcado:"
  echo "  mysql -h ${DB_HOST:-127.0.0.1} -u carta -pcarta ${DB_DATABASE:-carta_v2} < database/backups/archivo.sql"
fi
