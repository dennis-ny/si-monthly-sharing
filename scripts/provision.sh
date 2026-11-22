#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(dirname "$SCRIPT_DIR")"
cd "$ROOT_DIR"

source "$SCRIPT_DIR/_compose.sh"

ARG="${1:-60}"

if [ -f "$ARG" ]; then
    echo "Membuat akun peserta dari daftar NIM: ${ARG} ..."
    "${COMPOSE_CMD[@]}" exec -T web php /var/www/cli/provision.php --stdin < "$ARG"
else
    echo "Membuat akun untuk ${ARG} peserta..."
    "${COMPOSE_CMD[@]}" exec -T web php /var/www/cli/provision.php "$ARG"
fi
