#!/usr/bin/env bash
# Fotos de comida alineadas con DemoContent (Pexels, licencia Pexels).
# Uso: bash scripts/download-demo-food-images.sh
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
OUT="$ROOT/public/demo"
mkdir -p "$OUT"

pex() {
  local file="$1"
  local id="$2"
  echo "→ $file (pexels $id)"
  curl -fsSL "https://images.pexels.com/photos/${id}/pexels-photo-${id}.jpeg?auto=compress&cs=tinysrgb&w=800&h=600&fit=crop" \
    -o "$OUT/$file"
}

# Archivo DemoContent          | Pexels ID | Plato
pex croquetas-jamon.jpg    14734398   # croquetas en plato
pex pan-tomate.jpg         4491283    # pan / tostada con tomate
pex ensalada-mixta.jpg     2233348    # ensalada
pex tabla-embutidos.jpg    1267326    # tabla embutidos / charcutería
pex entrecot.jpg           769289     # entrecot / steak a la parrilla
pex pollo-ajillo.jpg       2338407    # pollo con ajo
pex secreto-iberico.jpg    8523520    # carne a la parrilla
pex carrilleras-vino.jpg   958545     # guiso / estofado
pex merluza-romana.jpg     4116434    # pescado frito / rebozado
pex gambas-ajillo.jpg      566566     # gambas / langostinos
pex pulpo-gallega.jpg      1040685    # plato cocinado (marisco/restaurante)
pex lubina-horno.jpg       696218     # pescado al horno en plato
pex crema-catalana.jpg     2067400    # crema / postre caramelizado
pex tarta-queso.jpg        291528     # tarta de queso
pex brownie-helado.jpg     4106991    # brownie / chocolate
pex agua-mineral.jpg       416528     # botella de agua
pex vino-casa.jpg          460537     # copa de vino tinto
pex cerveza.jpg            1552631    # cerveza en vaso

echo "OK: $(ls -1 "$OUT"/*.jpg 2>/dev/null | wc -l | tr -d ' ') imágenes en public/demo/"
