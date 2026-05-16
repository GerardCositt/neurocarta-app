#!/usr/bin/env bash
# Equivale a: php artisan setup
# (importa CARTA_IMPORT_SQL si existe y la BD está vacía; si no, migraciones + seeds).
set -euo pipefail
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"
exec php artisan setup
