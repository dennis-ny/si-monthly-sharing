#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(dirname "$SCRIPT_DIR")"
cd "$ROOT_DIR"

source "$SCRIPT_DIR/_compose.sh"

BASE_URL="${BASE_URL:-http://127.0.0.1:${WEB_PORT:-8000}}"
ADMIN_PASS="${ADMIN_PASSWORD:-instruktur2026}"
PASS_COUNT=0
FAIL_COUNT=0

pass() {
    echo "  [PASS] $1"
    PASS_COUNT=$((PASS_COUNT + 1))
}

fail() {
    echo "  [FAIL] $1" >&2
    FAIL_COUNT=$((FAIL_COUNT + 1))
}

assert_contains() {
    local haystack="$1"
    local needle="$2"
    local label="$3"
    if grep -Fq -- "$needle" <<< "$haystack"; then
        pass "$label"
    else
        fail "$label (tidak menemukan: $needle)"
    fi
}

assert_not_contains() {
    local haystack="$1"
    local needle="$2"
    local label="$3"
    if ! grep -Fq -- "$needle" <<< "$haystack"; then
        pass "$label"
    else
        fail "$label (seharusnya tidak mengandung: $needle)"
    fi
}

echo "============================================================"
echo "  ACCEPTANCE TESTS — WORKSHOP PHP CRUD (DAY 1) & LARAVEL (DAY 2)"
echo "============================================================"

# ------------------------------------------------------------------
# TES 1: Service hidup & sehat
# ------------------------------------------------------------------
echo "[Tes 1] Mengecek kesehatan service web, db, dan phpmyadmin..."
HEALTH=$(curl -fsS "${BASE_URL}/health")
assert_contains "$HEALTH" "ok" "Endpoint /health mengembalikan ok"

HOME_HTML=$(curl -fsS "${BASE_URL}/")
assert_contains "$HOME_HTML" "Selamat Datang di Server Workshop" "Halaman beranda termuat"

PMA_CODE=$(curl -s -o /dev/null -w "%{http_code}" "http://127.0.0.1:${PHPMYADMIN_PORT:-8080}/" || true)
if [ "$PMA_CODE" = "200" ] || [ "$PMA_CODE" = "302" ]; then
    pass "phpMyAdmin merespons (HTTP $PMA_CODE)"
else
    fail "phpMyAdmin tidak merespons (HTTP $PMA_CODE)"
fi

ADMIN_UNAUTH=$(curl -s -o /dev/null -w "%{http_code}" "${BASE_URL}/admin")
if [ "$ADMIN_UNAUTH" = "401" ]; then pass "Panel /admin menolak akses tanpa password (HTTP 401)"; else fail "Panel /admin seharusnya 401, dapat HTTP $ADMIN_UNAUTH"; fi

ADMIN_AUTH=$(curl -s -u "admin:${ADMIN_PASS}" -o /dev/null -w "%{http_code}" "${BASE_URL}/admin")
if [ "$ADMIN_AUTH" = "200" ]; then pass "Panel /admin menerima password yang benar (HTTP 200)"; else fail "Panel /admin gagal login (HTTP $ADMIN_AUTH)"; fi

# ------------------------------------------------------------------
# TES 2: Provision via daftar NIM & N=3 peserta
# ------------------------------------------------------------------
echo "[Tes 2] Provision via daftar NIM..."
TMP_NIM=$(mktemp)
printf "2501001,Budi Santoso\n2501002,Siti Rahmawati\n2501003,Andi Pratama\n" > "$TMP_NIM"
PROV_NIM=$("$SCRIPT_DIR/provision.sh" "$TMP_NIM")
rm -f "$TMP_NIM"
assert_contains "$PROV_NIM" "Berhasil membuat 3 peserta." "Provision via daftar NIM berhasil (3 peserta)"
assert_contains "$PROV_NIM" "db_2501001" "DB peserta memakai nama NIM (db_2501001)"

CSV_DATA=$(curl -fsS -u "admin:${ADMIN_PASS}" "${BASE_URL}/admin/export.csv")
TOK1=$(echo "$CSV_DATA" | awk -F',' 'NR==2 {gsub(/"/,"",$4); print $4}')
TOK2=$(echo "$CSV_DATA" | awk -F',' 'NR==3 {gsub(/"/,"",$4); print $4}')
TOK3=$(echo "$CSV_DATA" | awk -F',' 'NR==4 {gsub(/"/,"",$4); print $4}')

if [ "${#TOK1}" -ge 8 ] && [ "${#TOK2}" -ge 8 ] && [ "${#TOK3}" -ge 8 ] && [ "$TOK1" != "$TOK2" ]; then
    pass "3 peserta punya token unik acak (>= 8 karakter): $TOK1, $TOK2, $TOK3"
else
    fail "Token peserta tidak valid"
fi

PORTAL1=$(curl -fsS "${BASE_URL}/p/${TOK1}/")
assert_contains "$PORTAL1" "2501001" "Portal Peserta 1 menampilkan NIM peserta"

# ------------------------------------------------------------------
# TES 3: Day 1 6-Step (buat_db -> koneksi -> tampil -> tambah -> ubah -> hapus)
# ------------------------------------------------------------------
echo "[Tes 3] Day 1 alur peserta (6 Step: Buat DB + Koneksi + Full CRUD murni PHP)..."
D1_AWAL=$(curl -s "${BASE_URL}/p/${TOK1}/app/index.php")
assert_contains "$D1_AWAL" "Database / Tabel Belum Dibuat" "Day 1 awal (buat_db.php berlubang) menampilkan pesan ramah Tahap 1"
assert_not_contains "$D1_AWAL" "Fatal error" "Day 1 awal tidak menampilkan Fatal error PHP"

# Cek /db sebelum buat_db selesai -> pesan ramah belum dibuat
DB_PRE=$(curl -fsS "${BASE_URL}/p/${TOK1}/db")
assert_contains "$DB_PRE" "belum dibuat" "Halaman /db menampilkan pesan ramah jika DB belum dibuat"

# Pastikan fitur kunci jawaban sudah dinonaktifkan di API (HTTP 400)
KEY_DISABLED=$(curl -s -o /dev/null -w "%{http_code}" "${BASE_URL}/p/${TOK1}/api.php?mode=day1&aksi=kunci&file=tampil.php")
if [ "$KEY_DISABLED" = "400" ]; then pass "Endpoint api.php?aksi=kunci sudah dinonaktifkan (HTTP 400)"; else fail "Endpoint aksi=kunci seharusnya 400, dapat HTTP $KEY_DISABLED"; fi

# Tahap 1: isi buat_db.php -> DB & tabel pengeluaran tercipta, lanjut pesan Tahap 2 (koneksi.php)
KUNCI_BUATDB='<?php
$sqlDb = "CREATE DATABASE IF NOT EXISTS db_2501001";
$sqlTabel = "CREATE TABLE IF NOT EXISTS pengeluaran (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    keterangan VARCHAR(100) NOT NULL,
    nominal    INT NOT NULL,
    tanggal    DATE NOT NULL
)";'
curl -fsS -X POST "${BASE_URL}/p/${TOK1}/api.php?mode=day1&aksi=simpan&file=buat_db.php" --data-urlencode "isi=${KUNCI_BUATDB}" >/dev/null
D1_AFTER_DB=$(curl -fsS "${BASE_URL}/p/${TOK1}/app/index.php")
assert_contains "$D1_AFTER_DB" "Database &amp; Tabel Sudah Dibuat (db_2501001)" "Tahap 1: buat_db.php berhasil membuat DB & tabel pengeluaran"
assert_contains "$D1_AFTER_DB" "Koneksi PHP Belum Tersambung" "Setelah Tahap 1 lulus, tampil pesan ramah Tahap 2 (koneksi.php)"

# Tahap 2: isi koneksi.php -> database tersambung, tampil.php masih berlubang -> pesan ramah READ
KUNCI_KON='<?php
$host = "db";
$user = "2501001";
$password = "2501001";
$database = "db_2501001";
$koneksi = mysqli_connect($host, $user, $password, $database);'
curl -fsS -X POST "${BASE_URL}/p/${TOK1}/api.php?mode=day1&aksi=simpan&file=koneksi.php" --data-urlencode "isi=${KUNCI_KON}" >/dev/null
D1_KON=$(curl -fsS "${BASE_URL}/p/${TOK1}/app/index.php")
assert_contains "$D1_KON" "Database Tersambung (db_2501001)" "Tahap 2: koneksi.php berhasil menyambungkan database"
assert_contains "$D1_KON" "Daftar pengeluaran belum bisa ditampilkan (READ)" "tampil.php berlubang menampilkan pesan ramah READ"

# Tahap 0 & 2: isi tampil.php + ubah judul -> tabel kosong (0 baris) & judul baru tampil
KUNCI_TAMPIL=$(cat web/templates/day1/kunci/tampil.php)
TAMPIL_BUDI=$(python3 -c "import sys; print(sys.stdin.read().replace('Catatan Pengeluaran', 'Catatan Budi'))" <<< "$KUNCI_TAMPIL")
curl -fsS -X POST "${BASE_URL}/p/${TOK1}/api.php?mode=day1&aksi=simpan&file=tampil.php" --data-urlencode "isi=${TAMPIL_BUDI}" >/dev/null
D1_READ=$(curl -fsS "${BASE_URL}/p/${TOK1}/app/index.php")
assert_contains "$D1_READ" "Catatan Budi" "Tahap 0 (ubah \$judul) langsung terlihat"
assert_contains "$D1_READ" "Belum ada data pengeluaran" "Tahap 2 (READ/SELECT) menampilkan tabel kosong (0 baris, tanpa seeder)"

# Tahap 3: isi tambah.php -> submit 2 data CREATE -> data masuk DB
KUNCI_TAMBAH=$(cat web/templates/day1/kunci/tambah.php)
curl -fsS -X POST "${BASE_URL}/p/${TOK1}/api.php?mode=day1&aksi=simpan&file=tambah.php" --data-urlencode "isi=${KUNCI_TAMBAH}" >/dev/null
ADD_CODE=$(curl -s -o /dev/null -w "%{http_code}" -X POST "${BASE_URL}/p/${TOK1}/app/index.php" \
    --data-urlencode "aksi=tambah" \
    --data-urlencode "keterangan=Beli Es Teh Manis" \
    --data-urlencode "nominal=12000" \
    --data-urlencode "tanggal=2026-11-23")
if [ "$ADD_CODE" = "302" ]; then pass "Tahap 3 (CREATE/INSERT) berhasil & redirect (HTTP 302)"; else fail "CREATE gagal (HTTP $ADD_CODE)"; fi

curl -s -o /dev/null -X POST "${BASE_URL}/p/${TOK1}/app/index.php" \
    --data-urlencode "aksi=tambah" \
    --data-urlencode "keterangan=Fotokopi tugas" \
    --data-urlencode "nominal=5000" \
    --data-urlencode "tanggal=2026-11-23"

D1_AFTER_ADD=$(curl -fsS "${BASE_URL}/p/${TOK1}/app/index.php")
assert_contains "$D1_AFTER_ADD" "Beli Es Teh Manis" "Data baru tampil di halaman Day 1"
assert_contains "$D1_AFTER_ADD" "17.000" "Total pengeluaran terbarui (Rp 17.000)"

# Tahap 4: isi ubah.php -> UPDATE baris ID 1 menjadi Rp 20000
KUNCI_UBAH=$(cat web/templates/day1/kunci/ubah.php)
curl -fsS -X POST "${BASE_URL}/p/${TOK1}/api.php?mode=day1&aksi=simpan&file=ubah.php" --data-urlencode "isi=${KUNCI_UBAH}" >/dev/null
UPD_CODE=$(curl -s -o /dev/null -w "%{http_code}" -X POST "${BASE_URL}/p/${TOK1}/app/index.php" \
    --data-urlencode "aksi=ubah" \
    --data-urlencode "id=1" \
    --data-urlencode "keterangan=Beli Es Teh Jumbo" \
    --data-urlencode "nominal=20000" \
    --data-urlencode "tanggal=2026-11-23")
if [ "$UPD_CODE" = "302" ]; then pass "Tahap 4 (UPDATE) berhasil & redirect (HTTP 302)"; else fail "UPDATE gagal (HTTP $UPD_CODE)"; fi
D1_AFTER_UPD=$(curl -fsS "${BASE_URL}/p/${TOK1}/app/index.php")
assert_contains "$D1_AFTER_UPD" "Beli Es Teh Jumbo" "Data hasil UPDATE tampil di tabel"
assert_contains "$D1_AFTER_UPD" "25.000" "Total terbarui setelah UPDATE (Rp 25.000)"

# Tahap 5: isi hapus.php -> DELETE baris ID 2 (Fotokopi tugas 5000)
KUNCI_HAPUS=$(cat web/templates/day1/kunci/hapus.php)
curl -fsS -X POST "${BASE_URL}/p/${TOK1}/api.php?mode=day1&aksi=simpan&file=hapus.php" --data-urlencode "isi=${KUNCI_HAPUS}" >/dev/null
DEL_CODE=$(curl -s -o /dev/null -w "%{http_code}" -X POST "${BASE_URL}/p/${TOK1}/app/index.php" \
    --data-urlencode "aksi=hapus" \
    --data-urlencode "id=2")
if [ "$DEL_CODE" = "302" ]; then pass "Tahap 5 (DELETE) berhasil & redirect (HTTP 302)"; else fail "DELETE gagal (HTTP $DEL_CODE)"; fi
D1_AFTER_DEL=$(curl -fsS "${BASE_URL}/p/${TOK1}/app/index.php")
assert_not_contains "$D1_AFTER_DEL" "Fotokopi tugas" "Data yang dihapus hilang dari tabel"
assert_contains "$D1_AFTER_DEL" "20.000" "Total terbarui setelah DELETE (Rp 20.000)"

# ------------------------------------------------------------------
# TES 4: Day 2 alur peserta (Laravel MVC + Migration membaca DB yang sama)
# ------------------------------------------------------------------
echo "[Tes 4] Day 2 alur peserta (Laravel: Migration + Model + Route + Controller + View)..."
# Pastikan halaman Laravel aktif sebelum menguji Day 2
curl -fsS -u "admin:${ADMIN_PASS}" -X POST "${BASE_URL}/admin/action" --data "aksi=toggle_laravel&enable=1" >/dev/null
D2_AWAL=$(curl -s "${BASE_URL}/p/${TOK1}/laravel/")
assert_contains "$D2_AWAL" "Lengkapi file Migration" "Day 2 awal menampilkan pesan ramah Tahap 1 (Migration)"
assert_not_contains "$D2_AWAL" "Whoops" "Day 2 awal tidak menampilkan layar stack trace Whoops/Ignition"

declare -A D2_FILES=(
    ["database/migrations/create_pengeluaran_table.php"]="web/templates/day2/kunci/create_pengeluaran_table.php"
    ["app/Models/Pengeluaran.php"]="web/templates/day2/kunci/Pengeluaran.php"
    ["routes/web.php"]="web/templates/day2/kunci/web.php"
    ["app/Http/Controllers/PengeluaranController.php"]="web/templates/day2/kunci/PengeluaranController.php"
    ["resources/views/pengeluaran/index.blade.php"]="web/templates/day2/kunci/index.blade.php"
)

for f in "${!D2_FILES[@]}"; do
    ENC_F=$(python3 -c "import urllib.parse,sys; print(urllib.parse.quote(sys.argv[1], safe=''))" "$f")
    KUNCI_D2=$(cat "${D2_FILES[$f]}")
    curl -fsS -X POST "${BASE_URL}/p/${TOK1}/api.php?mode=day2&aksi=simpan&file=${ENC_F}" --data-urlencode "isi=${KUNCI_D2}" >/dev/null
done

D2_DONE=$(curl -fsS "${BASE_URL}/p/${TOK1}/laravel/")
assert_contains "$D2_DONE" "Beli Es Teh Jumbo" "Day 2 Laravel menampilkan data CRUD yang SAMA dengan Day 1"
assert_contains "$D2_DONE" "20.000" "Day 2 Laravel menampilkan total yang konsisten (Rp 20.000)"

# Tes Peserta 2 (yang BELUM ikut Day 1): langsung isi 5 file Day 2 -> Migration otomatis membuat DB & tabel kosong!
for f in "${!D2_FILES[@]}"; do
    ENC_F=$(python3 -c "import urllib.parse,sys; print(urllib.parse.quote(sys.argv[1], safe=''))" "$f")
    KUNCI_P2=$(cat "${D2_FILES[$f]}")
    curl -fsS -X POST "${BASE_URL}/p/${TOK2}/api.php?mode=day2&aksi=simpan&file=${ENC_F}" --data-urlencode "isi=${KUNCI_P2}" >/dev/null
done
D2_P2=$(curl -fsS "${BASE_URL}/p/${TOK2}/laravel/")
assert_contains "$D2_P2" "Catatan Pengeluaran (Laravel)" "Peserta 2 (absen Day 1) berhasil membuat tabel kosong lewat Migration Day 2"
assert_not_contains "$D2_P2" "Beli Es Teh Jumbo" "Peserta 2 tidak melihat data Peserta 1"

# Tes Full CRUD di Day 2 Laravel (CREATE -> UPDATE -> DELETE dengan CSRF token)
CJ=$(mktemp)
D2_FORM=$(curl -fsS -c "$CJ" "${BASE_URL}/p/${TOK1}/laravel/")
CSRF=$(python3 -c "import re,sys; m=re.search(r'name=\"_token\" value=\"([^\"]+)\"', sys.stdin.read()); print(m.group(1) if m else '')" <<< "$D2_FORM")
L_ADD=$(curl -s -b "$CJ" -c "$CJ" -o /dev/null -w "%{http_code}" -X POST "${BASE_URL}/p/${TOK1}/laravel/simpan" \
    --data-urlencode "_token=${CSRF}" \
    --data-urlencode "keterangan=Jajan Laravel" \
    --data-urlencode "nominal=15000" \
    --data-urlencode "tanggal=2026-11-24")
if [ "$L_ADD" = "302" ]; then pass "Day 2 Laravel CREATE (/simpan) berhasil (HTTP 302)"; else fail "Day 2 Laravel CREATE gagal (HTTP $L_ADD)"; fi

D2_AFTER_ADD=$(curl -fsS -b "$CJ" "${BASE_URL}/p/${TOK1}/laravel/")
assert_contains "$D2_AFTER_ADD" "Jajan Laravel" "Data dari Laravel tampil di halaman Laravel"
assert_contains "$D2_AFTER_ADD" "35.000" "Total Laravel terbarui (Rp 35.000)"

NEW_ID=$(python3 -c "import re,sys; m=re.search(r'/hapus/(\d+)', sys.stdin.read()); print(m.group(1) if m else '')" <<< "$D2_AFTER_ADD")
L_UPD=$(curl -s -b "$CJ" -c "$CJ" -o /dev/null -w "%{http_code}" -X POST "${BASE_URL}/p/${TOK1}/laravel/ubah/${NEW_ID}" \
    --data-urlencode "_token=${CSRF}" \
    --data-urlencode "keterangan=Jajan Laravel Edited" \
    --data-urlencode "nominal=25000" \
    --data-urlencode "tanggal=2026-11-24")
if [ "$L_UPD" = "302" ]; then pass "Day 2 Laravel UPDATE (/ubah/{id}) berhasil (HTTP 302)"; else fail "Day 2 Laravel UPDATE gagal (HTTP $L_UPD)"; fi

D1_SYNC=$(curl -fsS "${BASE_URL}/p/${TOK1}/app/index.php")
assert_contains "$D1_SYNC" "Jajan Laravel Edited" "Data yang diubah di Laravel otomatis tersinkron ke Day 1"

L_DEL=$(curl -s -b "$CJ" -c "$CJ" -o /dev/null -w "%{http_code}" -X POST "${BASE_URL}/p/${TOK1}/laravel/hapus/${NEW_ID}" \
    --data-urlencode "_token=${CSRF}")
if [ "$L_DEL" = "302" ]; then pass "Day 2 Laravel DELETE (/hapus/{id}) berhasil (HTTP 302)"; else fail "Day 2 Laravel DELETE gagal (HTTP $L_DEL)"; fi
rm -f "$CJ"

DB_VIEW2=$(curl -fsS "${BASE_URL}/p/${TOK2}/db")
assert_not_contains "$DB_VIEW2" "Beli Es Teh Jumbo" "Peserta 2 tidak melihat data DB Peserta 1"

# ------------------------------------------------------------------
# TES 5: Tes basi (stale) OPcache & Blade (5 kali, jeda >2 detik)
# ------------------------------------------------------------------
echo "[Tes 5] Tes anti-basi OPcache (Day 1) & Blade (Day 2) - 5 percobaan dengan jeda 2.1 detik..."
STALE_D1=0
for i in 1 2 3 4 5; do
    MARKER="MARKER_D1_${i}_$(date +%s)"
    MOD_D1=$(python3 -c "import sys; s=sys.stdin.read(); print(s.replace('Catatan Pengeluaran', '${MARKER}'))" <<< "$KUNCI_TAMPIL")
    curl -fsS -X POST "${BASE_URL}/p/${TOK1}/api.php?mode=day1&aksi=simpan&file=tampil.php" --data-urlencode "isi=${MOD_D1}" >/dev/null
    OUT_D1=$(curl -fsS "${BASE_URL}/p/${TOK1}/app/index.php")
    if ! grep -Fq "$MARKER" <<< "$OUT_D1"; then STALE_D1=$((STALE_D1 + 1)); fi
    sleep 2.1
done
if [ "$STALE_D1" -eq 0 ]; then pass "Day 1 OPcache: 0/5 basi"; else fail "Day 1 OPcache: ${STALE_D1}/5 basi"; fi

BLADE_KUNCI=$(cat web/templates/day2/kunci/index.blade.php)
STALE_D2=0
for i in 1 2 3 4 5; do
    MARKER="MARKER_D2_${i}_$(date +%s)"
    MOD_D2=$(python3 -c "import sys; s=sys.stdin.read(); print(s.replace('<h1>Catatan Pengeluaran (Laravel)</h1>', '<h1>${MARKER}</h1>'))" <<< "$BLADE_KUNCI")
    curl -fsS -X POST "${BASE_URL}/p/${TOK1}/api.php?mode=day2&aksi=simpan&file=resources%2Fviews%2Fpengeluaran%2Findex.blade.php" --data-urlencode "isi=${MOD_D2}" >/dev/null
    OUT_D2=$(curl -fsS "${BASE_URL}/p/${TOK1}/laravel/")
    if ! grep -Fq "$MARKER" <<< "$OUT_D2"; then STALE_D2=$((STALE_D2 + 1)); fi
    sleep 2.1
done
if [ "$STALE_D2" -eq 0 ]; then pass "Day 2 Laravel/Blade: 0/5 basi"; else fail "Day 2 Laravel/Blade: ${STALE_D2}/5 basi"; fi

# ------------------------------------------------------------------
# TES 6: Isolasi & keamanan API
# ------------------------------------------------------------------
echo "[Tes 6] Tes isolasi antar peserta & keamanan API..."
CODE_TRAV=$(curl -s -o /dev/null -w "%{http_code}" "${BASE_URL}/p/${TOK1}/api.php?mode=day1&aksi=baca&file=../nim_2501002/day1/koneksi.php")
if [ "$CODE_TRAV" = "400" ]; then pass "api.php menolak path traversal '../' (HTTP 400)"; else fail "api.php tidak menolak '../' (HTTP $CODE_TRAV)"; fi

CODE_WL=$(curl -s -o /dev/null -w "%{http_code}" "${BASE_URL}/p/${TOK1}/api.php?mode=day1&aksi=baca&file=style.css")
if [ "$CODE_WL" = "400" ]; then pass "api.php menolak file di luar whitelist (HTTP 400)"; else fail "api.php tidak menolak file non-whitelist (HTTP $CODE_WL)"; fi

CODE_TOK=$(curl -s -o /dev/null -w "%{http_code}" "${BASE_URL}/p/tokensalah123/api.php?mode=day1&aksi=baca&file=tampil.php")
if [ "$CODE_TOK" = "403" ]; then pass "api.php menolak token salah (HTTP 403)"; else fail "api.php tidak menolak token salah (HTTP $CODE_TOK)"; fi

CODE_DIRECT_ADMIN=$(curl -s -o /dev/null -w "%{http_code}" "${BASE_URL}/pages/admin.php")
CODE_DIRECT_LIB=$(curl -s -o /dev/null -w "%{http_code}" "${BASE_URL}/lib/common.php")
if [ "$CODE_DIRECT_ADMIN" = "404" ] && [ "$CODE_DIRECT_LIB" = "404" ]; then
    pass "Apache memblokir akses langsung ke /pages/ dan /lib/ (HTTP 404)"
else
    fail "Akses langsung ke file internal tidak diblokir (pages=$CODE_DIRECT_ADMIN, lib=$CODE_DIRECT_LIB)"
fi

ATTACK_PHP='<?php
$leak = @file_get_contents("/var/www/participants/nim_2501002/day1/koneksi.php");
$judul = ($leak ? "LEAKED_FILE" : "BLOCKED_FILE") . "|" . (function_exists("shell_exec") ? "SHELL_ON" : "SHELL_OFF");
$hasil = false;
'
curl -fsS -X POST "${BASE_URL}/p/${TOK1}/api.php?mode=day1&aksi=simpan&file=tampil.php" --data-urlencode "isi=${ATTACK_PHP}" >/dev/null
ATTACK_OUT=$(curl -s "${BASE_URL}/p/${TOK1}/app/index.php")
assert_contains "$ATTACK_OUT" "BLOCKED_FILE" "open_basedir memblokir peserta A membaca file peserta B"
assert_contains "$ATTACK_OUT" "SHELL_OFF" "disable_functions memblokir eksekusi shell dari kode peserta"

curl -fsS -X POST "${BASE_URL}/p/${TOK1}/api.php?mode=day1&aksi=simpan&file=tampil.php" --data-urlencode "isi=${KUNCI_TAMPIL}" >/dev/null

# ------------------------------------------------------------------
# TES 7: XSS & SQL Injection
# ------------------------------------------------------------------
echo "[Tes 7] Tes XSS & SQL Injection..."
PAYLOAD="<b>x</b>' OR 1=1 --"
XSS_CODE=$(curl -s -o /dev/null -w "%{http_code}" -X POST "${BASE_URL}/p/${TOK1}/app/index.php" \
    --data-urlencode "aksi=tambah" \
    --data-urlencode "keterangan=${PAYLOAD}" \
    --data-urlencode "nominal=7777" \
    --data-urlencode "tanggal=2026-11-23")
if [ "$XSS_CODE" = "302" ]; then pass "Submit payload XSS/SQLi tidak merusak query (HTTP 302)"; else fail "Submit payload XSS/SQLi gagal (HTTP $XSS_CODE)"; fi

D1_XSS=$(curl -fsS "${BASE_URL}/p/${TOK1}/app/index.php")
assert_contains "$D1_XSS" "&lt;b&gt;x&lt;/b&gt;" "Day 1 meng-escape tag HTML (anti XSS)"
assert_not_contains "$D1_XSS" "<td><b>x</b>" "Day 1 tidak merender HTML mentah"

D2_XSS=$(curl -fsS "${BASE_URL}/p/${TOK1}/laravel/")
assert_contains "$D2_XSS" "&lt;b&gt;x&lt;/b&gt;" "Day 2 Laravel Blade meng-escape tag HTML (anti XSS)"

# ------------------------------------------------------------------
# TES 8: Beban sederhana (40 peserta submit bersamaan)
# ------------------------------------------------------------------
echo "[Tes 8] Tes beban sederhana: provision 40 peserta & submit form bersamaan..."
"$SCRIPT_DIR/provision.sh" 40 >/dev/null
CSV40=$(curl -fsS -u "admin:${ADMIN_PASS}" "${BASE_URL}/admin/export.csv")
TOKENS40=$(echo "$CSV40" | awk -F',' 'NR>1 {gsub(/"/,"",$4); print $4}')

PIDS=()
TMP_RES=$(mktemp -d)
idx=0
for t in $TOKENS40; do
    idx=$((idx + 1))
    pid_str=$(printf "p%02d" "$idx")
    (
        KB="<?php \$sqlDb = \"CREATE DATABASE IF NOT EXISTS db_${pid_str}\"; \$sqlTabel = \"CREATE TABLE IF NOT EXISTS pengeluaran (id INT AUTO_INCREMENT PRIMARY KEY, keterangan VARCHAR(100) NOT NULL, nominal INT NOT NULL, tanggal DATE NOT NULL)\";"
        KK="<?php \$koneksi = mysqli_connect(\"db\", \"${pid_str}\", \"${pid_str}\", \"db_${pid_str}\");"
        curl -fsS -X POST "${BASE_URL}/p/${t}/api.php?mode=day1&aksi=simpan&file=buat_db.php" --data-urlencode "isi=${KB}" >/dev/null
        curl -fsS -X POST "${BASE_URL}/p/${t}/api.php?mode=day1&aksi=simpan&file=koneksi.php" --data-urlencode "isi=${KK}" >/dev/null
        curl -fsS -X POST "${BASE_URL}/p/${t}/api.php?mode=day1&aksi=simpan&file=tambah.php" --data-urlencode "isi=${KUNCI_TAMBAH}" >/dev/null
        CODE=$(curl -s -o /dev/null -w "%{http_code}" -X POST "${BASE_URL}/p/${t}/app/index.php" \
            --data-urlencode "aksi=tambah" \
            --data-urlencode "keterangan=Beban Peserta ${idx}" \
            --data-urlencode "nominal=10000" \
            --data-urlencode "tanggal=2026-11-23")
        echo "$CODE" > "${TMP_RES}/${idx}.code"
    ) &
    PIDS+=($!)
done
for pid in "${PIDS[@]}"; do
    wait "$pid" || true
done

OK40=$(grep -rl "^302$" "$TMP_RES" | wc -l)
rm -rf "$TMP_RES"
if [ "$OK40" -eq 40 ]; then
    pass "Beban 40 peserta submit bersamaan: 40/40 sukses (HTTP 302)"
else
    fail "Beban 40 peserta submit bersamaan: hanya ${OK40}/40 sukses"
fi

# ------------------------------------------------------------------
# TES 9: Reset & Teardown
# ------------------------------------------------------------------
echo "[Tes 9] Tes skrip reset & teardown..."
"$SCRIPT_DIR/reset.sh" >/dev/null
FIRST_TOK=$(echo "$TOKENS40" | head -n1)
AFTER_RESET_APP=$(curl -s "${BASE_URL}/p/${FIRST_TOK}/app/index.php")
assert_contains "$AFTER_RESET_APP" "Database / Tabel Belum Dibuat" "Reset mengembalikan file peserta (termasuk buat_db.php) ke versi awal"
AFTER_RESET_DB=$(curl -fsS "${BASE_URL}/p/${FIRST_TOK}/db")
assert_contains "$AFTER_RESET_DB" "belum dibuat" "Reset menghapus DB peserta sehingga kembali ke Tahap 1"

"$SCRIPT_DIR/teardown.sh" >/dev/null
AFTER_TD=$(curl -s -o /dev/null -w "%{http_code}" "${BASE_URL}/p/${FIRST_TOK}/")
if [ "$AFTER_TD" = "404" ]; then
    pass "Teardown menghapus semua peserta (link lama kini HTTP 404)"
else
    fail "Setelah teardown link lama masih merespons HTTP $AFTER_TD"
fi

"$SCRIPT_DIR/provision.sh" 1 >/dev/null

# ------------------------------------------------------------------
# TES 10: Registrasi Mandiri NIM, Login, Toggle Laravel Day 1, & Hapus User Admin
# ------------------------------------------------------------------
echo "[Tes 10] Tes registrasi mandiri NIM, login, toggle Laravel, dan hapus user..."
CJ_REG=$(mktemp)
REG_HDR=$(curl -s -D - -o /dev/null -c "$CJ_REG" -X POST "${BASE_URL}/" \
    --data-urlencode "action=register" \
    --data-urlencode "nim=f12202600069" \
    --data-urlencode "password=pw-f12202600069")
REG_LOC=$(echo "$REG_HDR" | grep -i '^Location:' | awk '{print $2}' | tr -d '\r')
if [[ "$REG_LOC" == /p/*/ ]]; then
    pass "Registrasi mandiri NIM f12202600069 berhasil & auto-login redirect ke ${REG_LOC}"
else
    fail "Registrasi mandiri gagal (Location: $REG_LOC)"
fi

DASH_NIM=$(curl -fsS -b "$CJ_REG" "${BASE_URL}${REG_LOC}")
assert_contains "$DASH_NIM" "f12202600069" "Dashboard menampilkan NIM peserta"
assert_contains "$DASH_NIM" "db_f12202600069" "Dashboard menampilkan database pribadi berbasis NIM"

# Daftar ulang NIM yang sama harus ditolak
DUP_BODY=$(curl -s -X POST "${BASE_URL}/" \
    --data-urlencode "action=register" \
    --data-urlencode "nim=f12202600069" \
    --data-urlencode "password=lainnya")
assert_contains "$DUP_BODY" "sudah terdaftar" "NIM duplikat ditolak saat registrasi"

# Login pakai NIM & password
LOGIN_CODE=$(curl -s -o /dev/null -w "%{http_code}" -X POST "${BASE_URL}/" \
    --data-urlencode "action=login" \
    --data-urlencode "nim=f12202600069" \
    --data-urlencode "password=pw-f12202600069")
if [ "$LOGIN_CODE" = "302" ]; then pass "Login pakai NIM & password berhasil (HTTP 302)"; else fail "Login NIM gagal (HTTP $LOGIN_CODE)"; fi

# Admin nonaktifkan halaman Laravel (Mode Day 1)
curl -fsS -u "admin:${ADMIN_PASS}" -X POST "${BASE_URL}/admin/action" \
    --data "aksi=toggle_laravel&enable=0" >/dev/null
DIS_ED=$(curl -s -o /dev/null -w "%{http_code}" "${BASE_URL}${REG_LOC}editor-laravel")
DIS_APP=$(curl -s -o /dev/null -w "%{http_code}" "${BASE_URL}${REG_LOC}laravel/")
DIS_API=$(curl -s -o /dev/null -w "%{http_code}" "${BASE_URL}${REG_LOC}api.php?mode=day2&aksi=baca&file=routes%2Fweb.php")
if [ "$DIS_ED" = "403" ] && [ "$DIS_APP" = "403" ] && [ "$DIS_API" = "403" ]; then
    pass "Toggle Disable Laravel memblokir editor-laravel, /laravel/, & api day2 (HTTP 403)"
else
    fail "Toggle Disable Laravel gagal (editor=$DIS_ED, app=$DIS_APP, api=$DIS_API)"
fi

# Admin reset workspace per peserta (reset_workspace)
curl -fsS -u "admin:${ADMIN_PASS}" -X POST "${BASE_URL}/admin/action" \
    --data "aksi=reset_workspace&id=nim_f12202600069" >/dev/null
AFTER_RST_U=$(curl -s -o /dev/null -w "%{http_code}" "${BASE_URL}${REG_LOC}")
if [ "$AFTER_RST_U" = "200" ]; then pass "Admin reset_workspace mereset workspace tanpa menghapus akun (HTTP 200)"; else fail "reset_workspace gagal (HTTP $AFTER_RST_U)"; fi

# Admin aktifkan kembali halaman Laravel
curl -fsS -u "admin:${ADMIN_PASS}" -X POST "${BASE_URL}/admin/action" \
    --data "aksi=toggle_laravel&enable=1" >/dev/null
EN_ED=$(curl -s -o /dev/null -w "%{http_code}" "${BASE_URL}${REG_LOC}editor-laravel")
if [ "$EN_ED" = "200" ]; then pass "Toggle Enable Laravel membuka kembali editor-laravel (HTTP 200)"; else fail "Toggle Enable Laravel gagal (HTTP $EN_ED)"; fi

# Admin hapus user f12202600069
curl -fsS -u "admin:${ADMIN_PASS}" -X POST "${BASE_URL}/admin/action" \
    --data "aksi=delete_user&id=nim_f12202600069" >/dev/null
AFTER_DEL_U=$(curl -s -o /dev/null -w "%{http_code}" "${BASE_URL}${REG_LOC}")
if [ "$AFTER_DEL_U" = "404" ]; then pass "Admin berhasil menghapus akun NIM f12202600069 (HTTP 404)"; else fail "Akun terhapus masih bisa diakses (HTTP $AFTER_DEL_U)"; fi
rm -f "$CJ_REG"

echo "============================================================"
echo "  HASIL TES: ${PASS_COUNT} PASS, ${FAIL_COUNT} FAIL"
echo "============================================================"
[ "$FAIL_COUNT" -eq 0 ]
