#!/usr/bin/env bash
set -euo pipefail

WEB_PORT="${WEB_PORT:-8000}"
PHPMYADMIN_PORT="${PHPMYADMIN_PORT:-8080}"

if [ -f .env ]; then
    # Baca WEB_PORT dan PHPMYADMIN_PORT jika ada di .env
    val_web=$(grep -E '^WEB_PORT=' .env | tail -n1 | cut -d= -f2 || true)
    val_pma=$(grep -E '^PHPMYADMIN_PORT=' .env | tail -n1 | cut -d= -f2 || true)
    [ -n "$val_web" ] && WEB_PORT="$val_web"
    [ -n "$val_pma" ] && PHPMYADMIN_PORT="$val_pma"
fi

# Deteksi IP LAN utama
LAN_IP=""
if command -v ip >/dev/null 2>&1; then
    LAN_IP=$(ip route get 1.1.1.1 2>/dev/null | awk '{for(i=1;i<=NF;i++) if($i=="src") {print $(i+1); exit}}' || true)
fi
if [ -z "$LAN_IP" ] && command -v hostname >/dev/null 2>&1; then
    LAN_IP=$(hostname -I 2>/dev/null | awk '{print $1}' || true)
fi
if [ -z "$LAN_IP" ]; then
    LAN_IP="localhost"
fi

echo "============================================================"
echo "  SERVER WORKSHOP SIAP DIAKSES DI JARINGAN LOKAL (LAN)"
echo "============================================================"
echo "  Beranda / QR Code  : http://${LAN_IP}:${WEB_PORT}/"
echo "  Panel Instruktur   : http://${LAN_IP}:${WEB_PORT}/admin"
echo "  Cetak Kartu QR     : http://${LAN_IP}:${WEB_PORT}/admin/kartu"
echo "  Konsep Day 1       : http://${LAN_IP}:${WEB_PORT}/konsep"
echo "  Contekan PHP       : http://${LAN_IP}:${WEB_PORT}/contekan"
echo "  Konsep Laravel     : http://${LAN_IP}:${WEB_PORT}/konsep-laravel"
echo "  PHP vs Laravel     : http://${LAN_IP}:${WEB_PORT}/banding"
echo "  phpMyAdmin         : http://${LAN_IP}:${PHPMYADMIN_PORT}/"
echo "============================================================"
echo "Catatan: Jika memakai Windows + WSL2, cek IP Wi-Fi Windows"
echo "lewat perintah 'ipconfig' di PowerShell."
