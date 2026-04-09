#!/usr/bin/env bash
set -euo pipefail

# Restore media files to Laravel public storage from either:
# 1) a directory that contains image files or an `img/` directory
# 2) a .zip file with image files (root or inside `img/`)

SRC_PATH="${1:-}"
DEST_DIR="storage/app/public/img"

usage() {
  echo "Uso:"
  echo "  bash scripts/restore-media.sh <ruta-a-carpeta-o-zip>"
  echo ""
  echo "Ejemplos:"
  echo "  bash scripts/restore-media.sh ~/Downloads/img"
  echo "  bash scripts/restore-media.sh ~/Downloads/img.zip"
}

if [[ -z "${SRC_PATH}" ]]; then
  usage
  exit 1
fi

if [[ ! -e "${SRC_PATH}" ]]; then
  echo "ERROR: no existe la ruta: ${SRC_PATH}"
  exit 1
fi

mkdir -p "${DEST_DIR}"

copy_from_dir() {
  local source_dir="$1"

  if [[ -d "${source_dir}/img" ]]; then
    cp -a "${source_dir}/img/." "${DEST_DIR}/"
  else
    cp -a "${source_dir}/." "${DEST_DIR}/"
  fi
}

if [[ -d "${SRC_PATH}" ]]; then
  copy_from_dir "${SRC_PATH}"
elif [[ -f "${SRC_PATH}" && "${SRC_PATH}" == *.zip ]]; then
  TMP_DIR="$(mktemp -d)"
  unzip -o "${SRC_PATH}" -d "${TMP_DIR}" >/dev/null
  copy_from_dir "${TMP_DIR}"
  rm -rf "${TMP_DIR}"
else
  echo "ERROR: la ruta debe ser una carpeta o un archivo .zip"
  exit 1
fi

php artisan storage:link >/dev/null 2>&1 || true
php artisan optimize:clear >/dev/null 2>&1 || true

echo "Media restaurada en: ${DEST_DIR}"
ls -lah "${DEST_DIR}" | head -n 20
