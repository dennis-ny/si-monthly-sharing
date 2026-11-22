<?php

declare(strict_types=1);

require_once __DIR__ . '/lib/common.php';

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

function json_out(int $code, array $payload): never
{
    http_response_code($code);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

$token = (string) ($_GET['token'] ?? '');
$participant = find_participant_by_token($token);
if (!$participant) {
    json_out(403, ['ok' => false, 'error' => 'Token peserta tidak valid.']);
}

$mode = (string) ($_REQUEST['mode'] ?? 'day1');
$whitelist = get_whitelist($mode);
if ($whitelist === null) {
    json_out(400, ['ok' => false, 'error' => 'Mode tidak valid.']);
}
if ($mode === 'day2' && !is_laravel_enabled()) {
    json_out(403, ['ok' => false, 'error' => 'Halaman Laravel (Day 2) masih nonaktif.']);
}

$aksi = (string) ($_REQUEST['aksi'] ?? 'baca');
$file = (string) ($_REQUEST['file'] ?? '');

// Tolak path traversal & karakter ilegal secara eksplisit sebelum cek whitelist
if ($file === '' || str_contains($file, '..') || str_contains($file, "\0") || str_contains($file, '\\')) {
    json_out(400, ['ok' => false, 'error' => 'Nama file tidak diizinkan.']);
}

// Aksi khusus: intip file sistem / read-only (whitelist ketat)
if ($aksi === 'intip') {
    if ($mode === 'day1') {
        $day1RoMap = [
            'index.php' => TEMPLATES_DIR . '/day1/common/index_view.php',
            'style.css' => participant_dir($participant['id']) . '/day1/style.css',
        ];
        if (!array_key_exists($file, $day1RoMap)) {
            json_out(400, ['ok' => false, 'error' => 'File tidak tersedia untuk dilihat.']);
        }
        $roPath = $day1RoMap[$file];
        if (!is_file($roPath)) {
            json_out(404, ['ok' => false, 'error' => 'File tidak ditemukan.']);
        }
        json_out(200, [
            'ok'              => true,
            'file'            => $file,
            'editable'        => false,
            'readonly_system' => true,
            'isi'             => (string) file_get_contents($roPath),
        ]);
    }
    if ($mode !== 'day2') {
        json_out(400, ['ok' => false, 'error' => 'Mode tidak valid.']);
    }
    $roFiles = get_laravel_readonly_files();
    if (!array_key_exists($file, $roFiles)) {
        json_out(400, ['ok' => false, 'error' => 'File tidak tersedia untuk dilihat.']);
    }
    $entry = $roFiles[$file];
    if (($entry['virtual'] ?? null) === 'env') {
        $isi = "APP_NAME=\"Catatan Pengeluaran\"\n"
             . "APP_ENV=local\n"
             . "APP_DEBUG=false\n"
             . "APP_URL=/p/{$participant['token']}/laravel\n\n"
             . "DB_CONNECTION=mysql\n"
             . "DB_HOST=db\n"
             . "DB_PORT=3306\n"
             . "DB_DATABASE={$participant['db_name']}\n"
             . "DB_USERNAME={$participant['db_user']}\n"
             . "DB_PASSWORD={$participant['db_pass']}\n\n"
             . "SESSION_DRIVER=file\n"
             . "CACHE_STORE=file\n";
    } else {
        $realPath = $entry['path'];
        if (!is_file($realPath)) {
            json_out(404, ['ok' => false, 'error' => 'File sistem tidak ditemukan.']);
        }
        $isi = (string) file_get_contents($realPath);
    }
    json_out(200, [
        'ok'              => true,
        'file'            => $file,
        'editable'        => false,
        'readonly_system' => true,
        'isi'             => $isi,
    ]);
}

if (!array_key_exists($file, $whitelist)) {
    json_out(400, ['ok' => false, 'error' => 'File tidak ada dalam daftar yang diizinkan.']);
}

$spec = $whitelist[$file];
$pDir = participant_dir($participant['id']);
$targetPath = $pDir . '/' . $spec['rel_path'];

if ($aksi === 'baca') {
    if (!is_file($targetPath)) {
        json_out(404, ['ok' => false, 'error' => 'File tidak ditemukan.']);
    }
    $isi = file_get_contents($targetPath);
    json_out(200, [
        'ok'       => true,
        'file'     => $file,
        'editable' => $spec['editable'],
        'isi'      => $isi,
    ]);
}

if ($aksi === 'awal') {
    if (!empty($spec['dynamic']) && $file === 'buat_db.php') {
        json_out(200, [
            'ok'   => true,
            'file' => $file,
            'isi'  => generate_buat_db_awal_php($participant['db_name']),
        ]);
    }
    if (!empty($spec['dynamic']) && $file === 'koneksi.php') {
        json_out(200, [
            'ok'   => true,
            'file' => $file,
            'isi'  => generate_koneksi_awal_php($participant['db_user'], $participant['db_pass'], $participant['db_name']),
        ]);
    }
    if ($spec['awal'] === null || !is_file($spec['awal'])) {
        json_out(400, ['ok' => false, 'error' => 'File ini tidak memiliki versi awal terpisah.']);
    }
    json_out(200, [
        'ok'   => true,
        'file' => $file,
        'isi'  => file_get_contents($spec['awal']),
    ]);
}

if ($aksi === 'simpan') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        json_out(405, ['ok' => false, 'error' => 'Metode harus POST.']);
    }
    if (!$spec['editable']) {
        json_out(403, ['ok' => false, 'error' => 'File ini hanya untuk dibaca dan tidak boleh diubah.']);
    }

    // Dukung baik form-urlencoded (isi=...) maupun JSON body ({isi: "..."})
    $isi = null;
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    if (str_contains($contentType, 'application/json')) {
        $raw = file_get_contents('php://input');
        $decoded = json_decode((string) $raw, true);
        if (is_array($decoded) && array_key_exists('isi', $decoded) && is_string($decoded['isi'])) {
            $isi = $decoded['isi'];
        }
    } elseif (isset($_POST['isi']) && is_string($_POST['isi'])) {
        $isi = $_POST['isi'];
    }

    if ($isi === null) {
        json_out(400, ['ok' => false, 'error' => 'Isi file tidak ditemukan dalam request.']);
    }

    if (strlen($isi) > MAX_FILE_SIZE) {
        json_out(413, ['ok' => false, 'error' => 'Ukuran file melebihi batas maksimum (100 KB).']);
    }

    // Normalisasi line ending ke LF
    $isi = str_replace(["\r\n", "\r"], "\n", $isi);

    $dir = dirname($targetPath);
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }

    $written = file_put_contents($targetPath, $isi, LOCK_EX);
    if ($written === false) {
        json_out(500, ['ok' => false, 'error' => 'Gagal menyimpan file ke server.']);
    }

    // WAJIB: Invalidasi OPcache & stat cache agar preview langsung menampilkan kode terbaru
    invalidate_file_cache($targetPath);

    // Jika Day 2 (Laravel), bersihkan juga compiled Blade views milik peserta ini
    if ($mode === 'day2') {
        clear_blade_views_cache($participant['id']);
    }

    update_participant_meta($participant['id'], ($mode === 'day2' ? '[Laravel] ' : '') . $file);

    json_out(200, [
        'ok'       => true,
        'file'     => $file,
        'saved_at' => date('H:i:s'),
        'bytes'    => $written,
    ]);
}

json_out(400, ['ok' => false, 'error' => 'Aksi tidak dikenal.']);
