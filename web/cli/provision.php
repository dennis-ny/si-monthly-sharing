<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit("Hanya bisa dijalankan lewat CLI.\n");
}

require_once '/var/www/html/lib/common.php';

$arg = $argv[1] ?? (string) (getenv('DEFAULT_PARTICIPANTS') ?: 60);
$specs = [];

if ($arg === '--stdin') {
    $raw = stream_get_contents(STDIN);
    $lines = preg_split('/\r\n|\r|\n/', (string) $raw);
    $seen = [];
    $idx = 0;
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        $parts = array_map('trim', explode(',', $line, 2));
        $nimRaw = $parts[0] ?? '';
        $nama   = $parts[1] ?? '';
        $nim    = strtolower((string) preg_replace('/[^a-zA-Z0-9]/', '', $nimRaw));
        if ($nim === '') {
            continue;
        }
        if (strlen($nim) > 20) {
            $nim = substr($nim, 0, 20);
        }
        if (isset($seen[$nim])) {
            continue;
        }
        $seen[$nim] = true;
        $idx++;
        $specs[] = [
            'number'  => $idx,
            'id'      => 'nim_' . $nim,
            'label'   => $nama !== '' ? "{$nama} ({$nim})" : "NIM {$nim}",
            'db_name' => 'db_' . $nim,
            'db_user' => $nim,
            'db_pass' => $nim,
        ];
    }
    if (empty($specs)) {
        fwrite(STDERR, "Daftar NIM kosong atau tidak valid.\n");
        exit(1);
    }
} else {
    $n = (int) $arg;
    if ($n < 1 || $n > 200) {
        fwrite(STDERR, "Jumlah peserta harus antara 1 sampai 200 (atau berikan path file daftar NIM).\n");
        exit(1);
    }
    $pad = $n >= 100 ? 3 : 2;
    for ($i = 1; $i <= $n; $i++) {
        $num = str_pad((string) $i, $pad, '0', STR_PAD_LEFT);
        $id = 'p' . $num;
        $specs[] = [
            'number'  => $i,
            'id'      => $id,
            'label'   => 'Peserta ' . $num,
            'db_name' => 'db_' . $id,
            'db_user' => $id,
            'db_pass' => $id,
        ];
    }
}

$dbHost = getenv('DB_HOST') ?: 'db';
$dbRootPass = getenv('MYSQL_ROOT_PASSWORD') ?: 'workshop_root_2026';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$mysqli = new mysqli($dbHost, 'root', $dbRootPass);
$mysqli->set_charset('utf8mb4');

// Bersihkan peserta lama jika ada
$oldReg = load_registry();
foreach ($oldReg['participants'] ?? [] as $old) {
    $oldDb   = preg_replace('/[^a-z0-9_]/', '', $old['db_name'] ?? '');
    $oldUser = preg_replace('/[^a-z0-9_]/', '', $old['db_user'] ?? '');
    $oldId   = preg_replace('/[^a-z0-9_]/', '', $old['id'] ?? '');
    if ($oldDb !== '') {
        $mysqli->query("DROP DATABASE IF EXISTS `{$oldDb}`");
    }
    if ($oldUser !== '') {
        $mysqli->query("DROP USER IF EXISTS '{$oldUser}'@'%'");
    }
    if ($oldId !== '') {
        rrmdir_safe(participant_dir($oldId));
    }
}

$participants = [];
$usedTokens = [];

foreach ($specs as $sp) {
    do {
        $token = bin2hex(random_bytes(5)); // 10 karakter hex acak
    } while (isset($usedTokens[$token]));
    $usedTokens[$token] = true;

    $id     = $sp['id'];
    $dbName = $sp['db_name'];
    $dbUser = $sp['db_user'];
    $dbPass = $sp['db_pass'];

    // 1. Siapkan user khusus peserta dengan hak CREATE khusus hanya untuk DB miliknya sendiri
    //    (Database & tabel akan dibuat sendiri oleh peserta di Tahap 1: buat_db.php!)
    $mysqli->query("DROP DATABASE IF EXISTS `{$dbName}`");
    $mysqli->query("DROP USER IF EXISTS '{$dbUser}'@'%'");
    $mysqli->query("CREATE USER '{$dbUser}'@'%' IDENTIFIED BY '{$dbPass}' WITH MAX_USER_CONNECTIONS 15");
    $mysqli->query("GRANT CREATE, ALTER, DROP, SELECT, INSERT, UPDATE, DELETE ON `{$dbName}`.* TO '{$dbUser}'@'%'");

    // 2. Buat folder kerja peserta (Day 1, Day 2, Laravel storage)
    $pDir = participant_dir($id);
    rrmdir_safe($pDir);
    $dirs = [
        "{$pDir}/day1",
        "{$pDir}/day2/views/pengeluaran",
        "{$pDir}/storage/framework/views",
        "{$pDir}/storage/framework/cache/data",
        "{$pDir}/storage/framework/sessions",
        "{$pDir}/storage/logs",
    ];
    foreach ($dirs as $d) {
        mkdir($d, 0775, true);
    }

    // Salin 6 file Day 1 versi awal (murni PHP singkat) + style.css
    file_put_contents("{$pDir}/day1/buat_db.php", generate_buat_db_awal_php($dbName));
    file_put_contents("{$pDir}/day1/koneksi.php", generate_koneksi_awal_php($dbUser, $dbPass, $dbName));
    copy(TEMPLATES_DIR . '/day1/awal/tampil.php', "{$pDir}/day1/tampil.php");
    copy(TEMPLATES_DIR . '/day1/awal/tambah.php', "{$pDir}/day1/tambah.php");
    copy(TEMPLATES_DIR . '/day1/awal/ubah.php', "{$pDir}/day1/ubah.php");
    copy(TEMPLATES_DIR . '/day1/awal/hapus.php', "{$pDir}/day1/hapus.php");
    copy(TEMPLATES_DIR . '/day1/common/style.css', "{$pDir}/day1/style.css");

    // Salin 5 file Day 2 versi awal (Migration + Model + Route + Controller + View)
    copy(TEMPLATES_DIR . '/day2/awal/create_pengeluaran_table.php', "{$pDir}/day2/create_pengeluaran_table.php");
    copy(TEMPLATES_DIR . '/day2/awal/Pengeluaran.php', "{$pDir}/day2/Pengeluaran.php");
    copy(TEMPLATES_DIR . '/day2/awal/web.php', "{$pDir}/day2/web.php");
    copy(TEMPLATES_DIR . '/day2/awal/PengeluaranController.php', "{$pDir}/day2/PengeluaranController.php");
    copy(TEMPLATES_DIR . '/day2/awal/index.blade.php', "{$pDir}/day2/views/pengeluaran/index.blade.php");

    // Set kepemilikan ke www-data agar Apache bisa baca/tulis
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($pDir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    @chown($pDir, 'www-data');
    @chgrp($pDir, 'www-data');
    foreach ($it as $item) {
        @chown($item->getPathname(), 'www-data');
        @chgrp($item->getPathname(), 'www-data');
    }

    $participants[] = [
        'number'        => $sp['number'],
        'id'            => $id,
        'nim'           => $dbUser,
        'label'         => $sp['label'],
        'token'         => $token,
        'db_name'       => $dbName,
        'db_user'       => $dbUser,
        'db_pass'       => $dbPass,
        'password_hash' => password_hash($dbPass, PASSWORD_DEFAULT),
    ];
}

$mysqli->query("FLUSH PRIVILEGES");
$mysqli->close();

$registry = [
    'created_at'   => date('Y-m-d H:i:s'),
    'count'        => count($participants),
    'participants' => $participants,
];
save_registry($registry);
@chown(REGISTRY_FILE, 'www-data');
@chgrp(REGISTRY_FILE, 'www-data');

$csvPath = PARTICIPANTS_DIR . '/participants.csv';
$fp = fopen($csvPath, 'w');
fputcsv($fp, ['nomor', 'id', 'label', 'token', 'path_portal', 'db_name', 'db_user', 'db_pass']);
foreach ($participants as $p) {
    fputcsv($fp, [
        $p['number'],
        $p['id'],
        $p['label'],
        $p['token'],
        '/p/' . $p['token'] . '/',
        $p['db_name'],
        $p['db_user'],
        $p['db_pass'],
    ]);
}
fclose($fp);
@chown($csvPath, 'www-data');
@chgrp($csvPath, 'www-data');

$total = count($participants);
echo "Berhasil membuat {$total} peserta.\n";
foreach (array_slice($participants, 0, 5) as $p) {
    echo "  - {$p['label']} ({$p['id']}): /p/{$p['token']}/  [DB: {$p['db_name']} | User/Pass: {$p['db_user']}]\n";
}
if ($total > 5) {
    echo "  ... dan " . ($total - 5) . " peserta lainnya.\n";
}
echo "Daftar lengkap tersedia di /admin dan /admin/export.csv\n";
