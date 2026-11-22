#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(dirname "$SCRIPT_DIR")"
cd "$ROOT_DIR"

source "$SCRIPT_DIR/_compose.sh"

TAR_FILE="${1:-offline-images/workshop-images.tar}"
if [ ! -f "$TAR_FILE" ]; then
    echo "File $TAR_FILE tidak ditemukan." >&2
    exit 1
fi

echo "Memuat Docker images dari $TAR_FILE (tanpa internet)..."
"${OCI_CMD[@]}" load -i "$TAR_FILE"
echo "Selesai! Jalankan: docker compose up -d --no-build"
