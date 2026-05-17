#!/usr/bin/env bash
# Crea NeuroCarta Demo dentro del contenedor app en producción/staging Docker.
# Uso en servidor Jotelulu:
#   bash /opt/neurocarta/scripts/ensure-demo-docker.sh
# O desde tu Mac (con SSH):
#   ssh user@149.71.98.35 'bash /opt/neurocarta/scripts/ensure-demo-docker.sh'

set -euo pipefail

CONTAINER="${NEUROCARTA_CONTAINER:-neurocarta-app-1}"

if ! docker ps --format '{{.Names}}' | grep -qx "$CONTAINER"; then
  echo "ERROR: contenedor «$CONTAINER» no está en ejecución."
  echo "Contenedores:"
  docker ps --format '  {{.Names}}'
  exit 1
fi

echo "=== NeuroCarta: crear restaurante demo en $CONTAINER ==="
docker exec "$CONTAINER" php artisan demo:ensure --menu --force
docker exec "$CONTAINER" php artisan demo:unlock-public --force 2>/dev/null \
  || echo "(demo:unlock-public tras deploy del comando en main)"
echo ""
docker exec "$CONTAINER" php artisan tinker --execute="echo App\Models\Restaurant::where('subdomain','demo')->exists() ? 'OK demo id='.App\Models\Restaurant::where('subdomain','demo')->value('id') : 'FAIL';" 2>/dev/null || true
echo "=== Hecho ==="
