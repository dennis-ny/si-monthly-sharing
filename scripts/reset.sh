#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(dirname "$SCRIPT_DIR")"
cd "$ROOT_DIR"

source "$SCRIPT_DIR/_compose.sh"

echo "Mereset semua file peserta ke versi awal dan database ke 3 data contoh..."
"${COMPOSE_CMD[@]}" exec -T web php /var/www/cli/reset.php
