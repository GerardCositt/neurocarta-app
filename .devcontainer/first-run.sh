#!/usr/bin/env bash
# Primera vez en el codespace (o tras cambios grandes): MariaDB + composer + npm + .env
set -euo pipefail
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "${SCRIPT_DIR}/.." && pwd)"
cd "${REPO_ROOT}"
bash "${SCRIPT_DIR}/start-db.sh"
bash "${SCRIPT_DIR}/setup.sh"
