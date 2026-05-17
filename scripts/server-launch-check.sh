#!/usr/bin/env bash
# Verificación operativa en el servidor Jotelulu (ejecutar en /opt/neurocarta como root).
# Uso:
#   cd /opt/neurocarta && bash scripts/server-launch-check.sh
# Desde Mac:
#   ssh -i ~/.ssh/neurocarta_jotelulu2 root@149.71.98.35 'cd /opt/neurocarta && bash scripts/server-launch-check.sh'

set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

CONTAINER="${NEUROCARTA_CONTAINER:-neurocarta-app-1}"
APP_URL="${NEUROCARTA_APP_URL:-https://app.neurocarta.ai}"

echo "══════════════════════════════════════════════════════════"
echo " NeuroCarta — guion servidor (pre-lanzamiento)"
echo " Contenedor: $CONTAINER"
echo "══════════════════════════════════════════════════════════"
echo ""

if ! docker ps --format '{{.Names}}' | grep -qx "$CONTAINER"; then
  echo "ERROR: contenedor «$CONTAINER» no está en ejecución."
  docker ps --format 'table {{.Names}}\t{{.Status}}'
  exit 1
fi

echo "▶ 1/5 Health HTTP"
HTTP_UP=$(curl -sS -o /dev/null -w "%{http_code}" "${APP_URL}/up" || echo "000")
echo "   ${APP_URL}/up → HTTP $HTTP_UP"
if [[ "$HTTP_UP" != "200" ]]; then
  echo "   ! Revisa nginx / contenedor app"
fi
echo ""

echo "▶ 2/5 Tests Launch (PHPUnit)"
docker exec "$CONTAINER" bash /opt/neurocarta/scripts/launch-test.sh
echo ""

echo "▶ 3/5 launch:check (env + scheduler + demo)"
docker exec "$CONTAINER" php artisan launch:check
echo ""

echo "▶ 4/5 Restaurante demo"
bash "$ROOT/scripts/ensure-demo-docker.sh"
echo ""

echo "▶ 5/5 Cron del servidor"
if [[ -f /etc/cron.d/neurocarta ]]; then
  echo "   ✓ /etc/cron.d/neurocarta existe:"
  sed 's/^/   /' /etc/cron.d/neurocarta
else
  echo "   ! Falta /etc/cron.d/neurocarta — ver docs/SERVIDOR-LANZAMIENTO.md §4"
fi
echo ""

echo "══════════════════════════════════════════════════════════"
echo " Siguiente paso: QA manual → docs/LAUNCH-QA.md (bloques 1-11)"
echo " Guion completo: docs/SERVIDOR-LANZAMIENTO.md"
echo "══════════════════════════════════════════════════════════"
