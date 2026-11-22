#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(dirname "$SCRIPT_DIR")"
cd "$ROOT_DIR"

source "$SCRIPT_DIR/_compose.sh"

echo "Menghapus semua akun, database, dan folder peserta..."
"${COMPOSE_CMD[@]}" exec -T web php /var/www/cli/teardown.php
