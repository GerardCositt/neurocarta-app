#!/usr/bin/env bash
# Descarga fotos de Pexels buscando por nombre de plato.
# Uso: bash scripts/download-demo-food-images.sh <PEXELS_API_KEY>
# Los JPG no van al repo (ver .gitignore). Para producción sube con: bash scripts/upload-demo-images-prod.sh
set -euo pipefail

API_KEY="${1:-${PEXELS_API_KEY:-}}"
if [[ -z "$API_KEY" ]]; then
  echo "ERROR: pasa la API key como argumento o exporta PEXELS_API_KEY" >&2
  exit 1
fi

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
OUT="$ROOT/public/demo"
mkdir -p "$OUT"

search_and_download() {
  local file="$1"
  local query="$2"
  local dest="$OUT/$file"

  echo "→ $file  (buscando: $query)"

  local encoded_query
  encoded_query=$(python3 -c "import urllib.parse,sys; print(urllib.parse.quote(sys.argv[1]))" "$query")

  local response
  response=$(curl -fsSL \
    -H "Authorization: $API_KEY" \
    "https://api.pexels.com/v1/search?query=${encoded_query}&per_page=5&orientation=landscape")

  local url
  url=$(echo "$response" | python3 -c "
import sys, json
data = json.load(sys.stdin)
photos = data.get('photos', [])
if not photos:
    sys.exit(1)
print(photos[0]['src']['large2x'])
") || {
    echo "  ⚠ sin resultados para '$query'"
    return 0
  }

  curl -fsSL "$url" -o "$dest"
  echo "  ✓ $(du -h "$dest" | cut -f1)"
}

search_and_download croquetas-jamon.jpg    "ham croquettes spanish tapas"
search_and_download pan-tomate.jpg         "pan con tomate bread tomato spanish"
search_and_download ensalada-mixta.jpg     "mixed salad plate restaurant"
search_and_download tabla-embutidos.jpg    "charcuterie board iberian cured meats"
search_and_download entrecot.jpg           "grilled entrecote beef steak plate"
search_and_download pollo-ajillo.jpg       "chicken garlic sauce spanish"
search_and_download secreto-iberico.jpg    "iberian pork grilled"
search_and_download carrilleras-vino.jpg   "braised pork cheeks red wine stew"
search_and_download merluza-romana.jpg     "battered hake fish fried"
search_and_download gambas-ajillo.jpg      "gambas al ajillo shrimp garlic"
search_and_download pulpo-gallega.jpg      "pulpo a la gallega octopus paprika"
search_and_download lubina-horno.jpg       "baked sea bass fish oven"
search_and_download crema-catalana.jpg     "crema catalana creme brulee"
search_and_download tarta-queso.jpg        "cheesecake slice plate"
search_and_download brownie-helado.jpg     "brownie ice cream chocolate"
search_and_download agua-mineral.jpg       "mineral water glass bottle"
search_and_download vino-casa.jpg          "red wine glass restaurant"
search_and_download cerveza.jpg            "beer glass cold"

echo ""
echo "✓ $(ls -1 "$OUT"/*.jpg 2>/dev/null | wc -l | tr -d ' ') imágenes en public/demo/"
