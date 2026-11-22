<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit("Hanya bisa dijalankan lewat CLI.\n");
}

require_once '/var/www/html/lib/common.php';

$reg = load_registry();
$participants = $reg['participants'] ?? [];
if (empty($participants)) {
    echo "Belum ada peserta yang terdaftar. Jalankan provision terlebih dahulu.\n";
    exit(0);
}

$dbHost = getenv('DB_HOST') ?: 'db';
$dbRootPass = getenv('MYSQL_ROOT_PASSWORD') ?: 'workshop_root_2026';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$mysqli = new mysqli($dbHost, 'root', $dbRootPass);
$mysqli->set_charset('utf8mb4');

$count = 0;

foreach ($participants as $p) {
    $id = $p['id'];
    $dbName = preg_replace('/[^a-z0-9_]/', '', $p['db_name']);
    $pDir = participant_dir($id);

    // 1. Reset 6 file Day 1 & 3 file Day 2 ke versi awal
    if (is_dir("{$pDir}/day1")) {
        file_put_contents("{$pDir}/day1/buat_db.php", generate_buat_db_awal_php($p['db_name']));
        file_put_contents("{$pDir}/day1/koneksi.php", generate_koneksi_awal_php($p['db_user'], $p['db_pass'], $p['db_name']));
        copy(TEMPLATES_DIR . '/day1/awal/tampil.php', "{$pDir}/day1/tampil.php");
        copy(TEMPLATES_DIR . '/day1/awal/tambah.php', "{$pDir}/day1/tambah.php");
        copy(TEMPLATES_DIR . '/day1/awal/ubah.php', "{$pDir}/day1/ubah.php");
        copy(TEMPLATES_DIR . '/day1/awal/hapus.php', "{$pDir}/day1/hapus.php");
        copy(TEMPLATES_DIR . '/day1/common/style.css', "{$pDir}/day1/style.css");
        @unlink("{$pDir}/day1/.seeded");
    }
    if (is_dir("{$pDir}/day2")) {
        if (!is_dir("{$pDir}/day2/views/pengeluaran")) {
            mkdir("{$pDir}/day2/views/pengeluaran", 0775, true);
        }
        copy(TEMPLATES_DIR . '/day2/awal/create_pengeluaran_table.php', "{$pDir}/day2/create_pengeluaran_table.php");
        copy(TEMPLATES_DIR . '/day2/awal/Pengeluaran.php', "{$pDir}/day2/Pengeluaran.php");
        copy(TEMPLATES_DIR . '/day2/awal/web.php', "{$pDir}/day2/web.php");
        copy(TEMPLATES_DIR . '/day2/awal/PengeluaranController.php', "{$pDir}/day2/PengeluaranController.php");
        copy(TEMPLATES_DIR . '/day2/awal/index.blade.php', "{$pDir}/day2/views/pengeluaran/index.blade.php");
    }

    // 2. Bersihkan compiled views Blade & meta.json
    clear_blade_views_cache($id);
    @unlink("{$pDir}/meta.json");

    // 3. Hapus database peserta agar kembali mulai dari Tahap 1 (buat_db.php)
    $mysqli->query("DROP DATABASE IF EXISTS `{$dbName}`");

    // Pastikan kepemilikan www-data
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($pDir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($it as $item) {
        @chown($item->getPathname(), 'www-data');
        @chgrp($item->getPathname(), 'www-data');
    }

    $count++;
}

$mysqli->close();
echo "Berhasil mereset {$count} peserta (semua file kembali ke versi awal & database dikosongkan).\n";
