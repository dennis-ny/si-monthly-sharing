# Setup Workshop PHP + Database (Day 1) & Laravel (Day 2) dalam Docker

Setup siap pakai untuk workshop 2 hari mahasiswa Sistem Informasi (level pemula/nol). Peserta **tidak perlu menginstal XAMPP, PHP, Composer, maupun VS Code** — cukup membuka link di browser laptop masing-masing melalui jaringan lokal (LAN/Wi-Fi) atau server panitia.

---

## 1. Prasyarat (Server / PC Penyelenggara)

- **Hanya Docker atau Podman**: Docker Desktop (Windows + WSL2 / macOS), Docker Engine + Compose plugin (Linux), atau Podman + `podman-compose`.
- Tidak perlu menginstal PHP, Composer, MySQL, atau Node.js di host.

---

## 2. Mulai Cepat (Satu Perintah)

### Menyalakan semua layanan
```bash
cp .env.example .env   # opsional, ubah password admin / root DB / port jika perlu
docker compose up -d --build
```
*(Semua dependensi termasuk Laravel 12 + `vendor/` dan CodeMirror 5 sudah terpasang saat proses build. Setelah build selesai, tidak butuh internet lagi.)*

### Akun Peserta
- **Daftar mandiri lewat web (utama):** peserta langsung membuka `/` dan mendaftar memakai **NIM** + password masing-masing. Sistem otomatis membuatkan workspace pribadi, user MySQL `<nim>`, dan izin database `db_<nim>`.
- **Provision massal lewat CLI (opsional, misal 60 peserta):**
  ```bash
  ./scripts/provision.sh 60
  ```
- **Mode demo (1 peserta):**
  ```bash
  ./scripts/provision.sh 1
  ```
  *(Pada mode `N=1`, halaman beranda `/` menampilkan tombol langsung **Masuk Mode Demo (Peserta 01)**.)*

### Menemukan URL & IP LAN
```bash
./scripts/info.sh
```
Di **Windows**, buka PowerShell lalu jalankan `ipconfig` dan cari **IPv4 Address** pada adapter Wi-Fi/LAN (contoh: `192.168.1.25`). URL utama peserta adalah `http://192.168.1.25:8000/`.

### Mematikan & Reset
- **Mematikan sementara** (data peserta & DB tetap tersimpan):
  ```bash
  docker compose down
  ```
- **Reset seluruh workspace peserta ke kondisi awal** (file Day 1 & Day 2 kembali ke template awal, database peserta di-drop agar mulai lagi dari `1. buat_db.php`; akun & token tetap ada):
  ```bash
  ./scripts/reset.sh
  ```
  *(Untuk mereset 1 peserta saja, gunakan tombol **Reset** di halaman `/admin`.)*
- **Menghapus semua akun, DB, dan folder peserta setelah acara selesai:**
  ```bash
  ./scripts/teardown.sh
  ```
- **Reset total termasuk volume Docker:**
  ```bash
  docker compose down -v
  ```

---

## 3. Alur Belajar Peserta

### Day 1 — PHP Native + Database MySQL (6 Tahap)
Peserta mengerjakan 6 file PHP singkat (tanpa HTML/CSS kotor) di `/p/<token>/editor`:
1. `1. buat_db.php` — `CREATE DATABASE` & `CREATE TABLE pengeluaran`
2. `2. koneksi.php` — `mysqli_connect()` ke database pribadi (`db_<nim>`)
3. `3. tampil.php` — `SELECT` (READ)
4. `4. tambah.php` — `INSERT` (CREATE)
5. `5. ubah.php` — `UPDATE` (UPDATE)
6. `6. hapus.php` — `DELETE` (DELETE)

Peserta juga dapat memantau tabel & struktur database lewat `/p/<token>/db` serta mengintip file tampilan (`index.php`, `style.css`) secara read-only di File Explorer.

### Day 2 — Laravel 12 MVC + Migration (5 Tahap)
Di `/p/<token>/editor-laravel`, peserta membangun ulang aplikasi yang sama (membaca database `db_<nim>` yang sama dengan Day 1) dalam 5 tahap berurutan dengan indikator progres otomatis:
1. `create_pengeluaran_table.php` — Migration
2. `Pengeluaran.php` — Model (Eloquent)
3. `PengeluaranController.php` — Controller (Full CRUD tanpa SQL manual)
4. `web.php` — Route
5. `index.blade.php` — View (Blade, menggunakan `@extends('layouts.app')` dan `@include('partials.form')`)

---

## 4. Dashboard Admin (`/admin`)

1. Buka `http://<HOST>:8000/admin` (otomatis diarahkan ke form login `/admin/login`; mendukung juga HTTP Basic Auth untuk skrip otomasi).
2. Login:
   - Username: `admin`
   - Password: nilai `ADMIN_PASSWORD` di `.env` (default: `instruktur2026`)
3. Fitur di Dashboard Admin:
   - **Toggle Aktif/Nonaktifkan Halaman Laravel (Day 2):** kunci akses Day 2 selama sesi Day 1, lalu aktifkan otomatis saat masuk Day 2.
   - **Monitoring Progres Peserta:** lihat waktu simpan terakhir, jumlah baris DB, status `Buat DB`, `Koneksi`, `CRUD (0/4–4/4)`, dan progres `Day 2 (0/5–5/5)`.
   - **Manajemen per Peserta:** tombol **Reset** (kembalikan file & hapus DB tanpa menghapus akun), **Reset PW** (kembalikan password login ke NIM), dan **Hapus** (hapus permanen akun + DB + folder).
   - **Unduh CSV** (`/admin/export.csv`) dan tautan ke **phpMyAdmin** (`http://<HOST>:8080/`).

---

## 5. Membawa Image Secara Offline (Tanpa Internet di Venue)

**Saat ada internet:**
```bash
./scripts/offline-save.sh
```
Menghasilkan `offline-images/workshop-images.tar` berisi image `web` (sudah termasuk Laravel 12 + `vendor/` + CodeMirror 5), `mariadb:10.11`, dan `phpmyadmin:5`.

**Di PC/server venue saat offline:**
```bash
./scripts/offline-load.sh
docker compose up -d --no-build
```

---

## 6. Membuka Port Firewall di Windows

Jika dijalankan di PC Windows, buka **PowerShell (Run as Administrator)** lalu jalankan:
```powershell
New-NetFirewallRule -DisplayName "Workshop Web 8000" -Direction Inbound -Protocol TCP -LocalPort 8000 -Action Allow
New-NetFirewallRule -DisplayName "Workshop phpMyAdmin 8080" -Direction Inbound -Protocol TCP -LocalPort 8080 -Action Allow
```
Pastikan profil jaringan Wi-Fi di Windows diset ke **Private** (bukan Public).

---

## 7. Troubleshooting

| Masalah | Penyebab Umum | Solusi |
|---|---|---|
| **Port 8000 atau 8080 bentrok** (`address already in use`) | Sudah ada aplikasi lain di port tersebut. | Ubah `WEB_PORT=8081` atau `PHPMYADMIN_PORT=8082` di `.env`, lalu jalankan `docker compose up -d`. |
| **Peserta tidak bisa membuka IP server** padahal satu Wi-Fi | **AP Isolation / Client Isolation** aktif di Wi-Fi kampus/venue. | Pindahkan server & peserta ke router/Access Point sendiri atau tethering hotspot cadangan. |
| **`provision.sh` gagal koneksi ke DB** | MariaDB masih inisialisasi pertama kali. | Tunggu 10–15 detik sampai status `db` adalah `healthy`, lalu jalankan ulang. |
| **Error line ending (`\r`) di skrip `.sh` pada Windows** | Git mengonversi LF menjadi CRLF. | Repositori sudah menyertakan `.gitattributes` (`*.sh text eol=lf`). Jika telanjur CRLF, jalankan `sed -i 's/\r$//' scripts/*.sh`. |
| **Preview tidak berubah setelah Simpan** | Cache browser lama. | Klik tombol **Muat Ulang Preview** di kanan atas panel preview (server otomatis menginvalidasi OPcache & compiled Blade views). |

---

## 8. Checklist Hari-H

- [ ] **H-1:** Jalankan `docker compose up -d --build` dan `./scripts/test.sh` sampai semua tes PASS (`70 PASS, 0 FAIL`).
- [ ] **H-1:** Ubah `ADMIN_PASSWORD` dan `MYSQL_ROOT_PASSWORD` di `.env` untuk produksi.
- [ ] **H-1:** Jalankan `./scripts/offline-save.sh` jika venue tidak menjamin koneksi internet.
- [ ] **H-30 menit:** Nonaktifkan halaman Laravel (Mode Day 1) di `/admin`, tes registrasi 1 akun NIM dari HP/laptop lain, lalu hapus akun tesnya.
- [ ] **H-15 menit:** Siapkan tab `/admin` di laptop panitia untuk monitoring progres & bantuan reset peserta.

---

## 9. Keputusan Teknis & Keamanan

1. **Kompatibilitas Docker & Podman:** `docker-compose.yml` dan `Dockerfile` memakai FQDN image (`docker.io/library/...`) dan skrip `scripts/*.sh` otomatis mendeteksi `docker compose`, `docker-compose`, atau `podman-compose`.
2. **Laravel Multi-Peserta Tanpa Duplikasi `vendor/`:** Satu instalasi Laravel 12 berada di `/opt/laravel-base`. Setiap peserta hanya menyimpan 5 file editable dan folder `storage/` di volume `participants_data`; routing, view path, compiled view, dan koneksi DB diinjeksi per-request di `run_day2.php`.
3. **Isolasi & Hardening:**
   - Apache memblokir akses HTTP langsung ke `/lib/`, `/pages/`, dan skrip internal (`404`), serta mengirim header `X-Content-Type-Options: nosniff` dan `X-Frame-Options: SAMEORIGIN`.
   - Runner Day 1 & Day 2 menerapkan `open_basedir` per peserta, `max_execution_time=5`, batas memori, dan `disable_functions` (memblokir `exec`, `shell_exec`, `system`, `proc_open`, `popen`, `passthru`, `pcntl_exec`).
   - User MySQL terpisah per NIM (`GRANT ... ON db_<nim>.*`) dengan `MAX_USER_CONNECTIONS 15`.
   - Session cookie memakai `HttpOnly` + `SameSite=Lax` dengan `session_regenerate_id(true)` saat login/daftar.
   - `api.php` menerapkan whitelist file ketat, anti path-traversal, dan memblokir `mode=day2` saat Laravel dinonaktifkan.
4. **Anti-Basi OPcache & Blade:** `opcache.revalidate_freq=0` + `opcache_invalidate()` setiap simpan file + pembersihan compiled Blade views otomatis.
