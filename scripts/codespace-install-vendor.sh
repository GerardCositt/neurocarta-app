#!/usr/bin/env bash
# Instala vendor cuando el Codespace usa PHP en /usr/local/php SIN ext-gd.
# Opción definitiva: Rebuild Container (ver README) para tener GD y códigos QR.
set -euo pipefail
cd "$(dirname "$0")/.."

if php -m 2>/dev/null | grep -qi '^gd$'; then
  echo "ext-gd ya está cargado; instalando con composer normal…"
  composer install --no-interaction "$@"
  exit 0
fi

echo "AVISO: ext-gd no está disponible (típico del Codespace por defecto)."
echo "  → Recomendado: paleta de comandos → «Codespaces: Rebuild Container»"
echo "  → Temporal: se instala ignorando ext-gd (generación QR puede fallar)."
echo ""

composer install --no-interaction --ignore-platform-req=ext-gd "$@"
