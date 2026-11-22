#!/usr/bin/env bash
set -euo pipefail

# Helper untuk menjalankan Docker Compose (atau Podman Compose jika Docker tidak tersedia)
if command -v docker >/dev/null 2>&1 && docker compose version >/dev/null 2>&1; then
    COMPOSE_CMD=(docker compose)
    OCI_CMD=(docker)
elif command -v docker-compose >/dev/null 2>&1; then
    COMPOSE_CMD=(docker-compose)
    OCI_CMD=(docker)
elif command -v podman-compose >/dev/null 2>&1; then
    COMPOSE_CMD=(podman-compose)
    OCI_CMD=(podman)
else
    echo "ERROR: Docker (atau Podman) Compose tidak ditemukan." >&2
    exit 1
fi
