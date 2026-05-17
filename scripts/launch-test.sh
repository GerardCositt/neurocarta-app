#!/usr/bin/env bash
# Tests de lanzamiento: solo la suite Launch (aislamiento, suscripción, planes, login, registro).
# Uso: ./scripts/launch-test.sh
# Producción: docker exec neurocarta-app-1 bash /opt/neurocarta/scripts/launch-test.sh

set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

if [[ ! -f vendor/bin/phpunit ]]; then
  echo "ERROR: vendor/bin/phpunit no existe. Ejecuta: composer install"
  exit 1
fi

echo "══════════════════════════════════════════════════════════"
echo " NeuroCarta — suite Launch (tests críticos pre-lanzamiento)"
echo "══════════════════════════════════════════════════════════"
echo ""

vendor/bin/phpunit --testsuite=Launch --testdox --colors=always
EXIT=$?

echo ""
if [[ $EXIT -eq 0 ]]; then
  echo "✓ Todos los tests Launch pasaron."
else
  echo "✗ Falló la suite Launch (código $EXIT)."
fi

exit $EXIT
