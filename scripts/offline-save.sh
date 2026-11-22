#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(dirname "$SCRIPT_DIR")"
cd "$ROOT_DIR"

source "$SCRIPT_DIR/_compose.sh"

mkdir -p offline-images
echo "1/3 Memastikan semua image sudah ter-build & ter-pull..."
"${COMPOSE_CMD[@]}" build
"${OCI_CMD[@]}" pull docker.io/library/mariadb:10.11
"${OCI_CMD[@]}" pull docker.io/library/phpmyadmin:5

WEB_IMAGE=$("${COMPOSE_CMD[@]}" images -q web 2>/dev/null | head -n1 || true)
if [ -z "$WEB_IMAGE" ]; then
    echo "Menjalankan build ulang untuk mendapatkan nama image web..."
    "${COMPOSE_CMD[@]}" build web
fi

# Ambil nama image web dari compose config atau default folder
PROJECT_NAME="$(basename "$ROOT_DIR" | tr '[:upper:]' '[:lower:]' | sed 's/[^a-z0-9_-]//g')"
WEB_IMG_NAME="${PROJECT_NAME}-web:latest"
if ! "${OCI_CMD[@]}" image inspect "$WEB_IMG_NAME" >/dev/null 2>&1; then
    WEB_IMG_NAME="${PROJECT_NAME}_web:latest"
fi

echo "2/3 Menyimpan image ke offline-images/workshop-images.tar ..."
"${OCI_CMD[@]}" save -o offline-images/workshop-images.tar docker.io/library/mariadb:10.11 docker.io/library/phpmyadmin:5 "$WEB_IMG_NAME"
echo "3/3 Selesai! Ukuran arsip:"
ls -lh offline-images/workshop-images.tar
echo "Salin folder proyek beserta offline-images/workshop-images.tar ke PC penyelenggara."
