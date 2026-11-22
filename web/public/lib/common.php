<?php

declare(strict_types=1);

define('PARTICIPANTS_DIR', '/var/www/participants');
define('TEMPLATES_DIR', '/var/www/templates');
define('REGISTRY_FILE', PARTICIPANTS_DIR . '/registry.json');
define('SETTINGS_FILE', PARTICIPANTS_DIR . '/settings.json');
define('MAX_FILE_SIZE', 102400); // 100 KB
define('MAX_PARTICIPANTS', 150);

function e(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function get_day1_whitelist(): array
{
    return [
        'buat_db.php' => [
            'label'     => '1. buat_db.php',
            'rel_path'  => 'day1/buat_db.php',
            'awal'      => null,
            'kunci'     => null,
            'dynamic'   => true,
            'editable'  => true,
            'cm_mode'   => 'application/x-httpd-php',
        ],
        'koneksi.php' => [
            'label'     => '2. koneksi.php',
            'rel_path'  => 'day1/koneksi.php',
            'awal'      => null,
            'kunci'     => null,
            'dynamic'   => true,
            'editable'  => true,
            'cm_mode'   => 'application/x-httpd-php',
        ],
        'tampil.php' => [
            'label'     => '3. tampil.php',
            'rel_path'  => 'day1/tampil.php',
            'awal'      => TEMPLATES_DIR . '/day1/awal/tampil.php',
            'kunci'     => TEMPLATES_DIR . '/day1/kunci/tampil.php',
            'editable'  => true,
            'cm_mode'   => 'application/x-httpd-php',
        ],
        'tambah.php' => [
            'label'     => '4. tambah.php',
            'rel_path'  => 'day1/tambah.php',
            'awal'      => TEMPLATES_DIR . '/day1/awal/tambah.php',
            'kunci'     => TEMPLATES_DIR . '/day1/kunci/tambah.php',
            'editable'  => true,
            'cm_mode'   => 'application/x-httpd-php',
        ],
        'ubah.php' => [
            'label'     => '5. ubah.php',
            'rel_path'  => 'day1/ubah.php',
            'awal'      => TEMPLATES_DIR . '/day1/awal/ubah.php',
            'kunci'     => TEMPLATES_DIR . '/day1/kunci/ubah.php',
            'editable'  => true,
            'cm_mode'   => 'application/x-httpd-php',
        ],
        'hapus.php' => [
            'label'     => '6. hapus.php',
            'rel_path'  => 'day1/hapus.php',
            'awal'      => TEMPLATES_DIR . '/day1/awal/hapus.php',
            'kunci'     => TEMPLATES_DIR . '/day1/kunci/hapus.php',
            'editable'  => true,
            'cm_mode'   => 'application/x-httpd-php',
        ],
    ];
}

function get_day2_whitelist(): array
{
    return [
        'database/migrations/create_pengeluaran_table.php' => [
            'label'     => 'create_pengeluaran_table.php',
            'rel_path'  => 'day2/create_pengeluaran_table.php',
            'awal'      => TEMPLATES_DIR . '/day2/awal/create_pengeluaran_table.php',
            'kunci'     => TEMPLATES_DIR . '/day2/kunci/create_pengeluaran_table.php',
            'editable'  => true,
            'cm_mode'   => 'application/x-httpd-php',
        ],
        'app/Models/Pengeluaran.php' => [
            'label'     => 'Pengeluaran.php',
            'rel_path'  => 'day2/Pengeluaran.php',
            'awal'      => TEMPLATES_DIR . '/day2/awal/Pengeluaran.php',
            'kunci'     => TEMPLATES_DIR . '/day2/kunci/Pengeluaran.php',
            'editable'  => true,
            'cm_mode'   => 'application/x-httpd-php',
        ],
        'app/Http/Controllers/PengeluaranController.php' => [
            'label'     => 'PengeluaranController.php',
            'rel_path'  => 'day2/PengeluaranController.php',
            'awal'      => TEMPLATES_DIR . '/day2/awal/PengeluaranController.php',
            'kunci'     => TEMPLATES_DIR . '/day2/kunci/PengeluaranController.php',
            'editable'  => true,
            'cm_mode'   => 'application/x-httpd-php',
        ],
        'routes/web.php' => [
            'label'     => 'web.php',
            'rel_path'  => 'day2/web.php',
            'awal'      => TEMPLATES_DIR . '/day2/awal/web.php',
            'kunci'     => TEMPLATES_DIR . '/day2/kunci/web.php',
            'editable'  => true,
            'cm_mode'   => 'application/x-httpd-php',
        ],
        'resources/views/pengeluaran/index.blade.php' => [
            'label'     => 'index.blade.php',
            'rel_path'  => 'day2/views/pengeluaran/index.blade.php',
            'awal'      => TEMPLATES_DIR . '/day2/awal/index.blade.php',
            'kunci'     => TEMPLATES_DIR . '/day2/kunci/index.blade.php',
            'editable'  => true,
            'cm_mode'   => 'application/x-httpd-php',
        ],
    ];
}

function get_laravel_readonly_files(): array
{
    return [
        '.env'                                      => ['virtual' => 'env'],
        'artisan'                                   => ['path' => '/opt/laravel-base/artisan'],
        'composer.json'                             => ['path' => '/opt/laravel-base/composer.json'],
        'bootstrap/app.php'                         => ['path' => '/opt/laravel-base/bootstrap/app.php'],
        'config/database.php'                       => ['path' => '/opt/laravel-base/config/database.php'],
        'config/view.php'                           => ['path' => '/opt/laravel-base/config/view.php'],
        'public/index.php'                          => ['path' => '/opt/laravel-base/public/index.php'],
        'resources/views/layouts/app.blade.php'     => ['path' => '/opt/laravel-base/resources/views/layouts/app.blade.php'],
        'resources/views/partials/form.blade.php'   => ['path' => '/opt/laravel-base/resources/views/partials/form.blade.php'],
    ];
}

function get_whitelist(string $mode): ?array
{
    if ($mode === 'day1') {
        return get_day1_whitelist();
    }
    if ($mode === 'day2') {
        return get_day2_whitelist();
    }
    return null;
}

function load_registry(): array
{
    if (!is_file(REGISTRY_FILE)) {
        return ['created_at' => null, 'count' => 0, 'participants' => []];
    }
    $raw = file_get_contents(REGISTRY_FILE);
    if ($raw === false) {
        return ['created_at' => null, 'count' => 0, 'participants' => []];
    }
    $data = json_decode($raw, true);
    return is_array($data) ? $data : ['created_at' => null, 'count' => 0, 'participants' => []];
}

function save_registry(array $registry): void
{
    if (!is_dir(PARTICIPANTS_DIR)) {
        mkdir(PARTICIPANTS_DIR, 0755, true);
    }
    $tmp = REGISTRY_FILE . '.tmp.' . getmypid();
    file_put_contents($tmp, json_encode($registry, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    rename($tmp, REGISTRY_FILE);
    chmod(REGISTRY_FILE, 0640);
}

function find_participant_by_token(string $token): ?array
{
    if (!preg_match('/^[a-z0-9]{8,32}$/', $token)) {
        return null;
    }
    $reg = load_registry();
    foreach ($reg['participants'] ?? [] as $p) {
        if (isset($p['token']) && hash_equals($p['token'], $token)) {
            return $p;
        }
    }
    return null;
}

function participant_dir(string $id): string
{
    return PARTICIPANTS_DIR . '/' . $id;
}

function generate_buat_db_awal_php(string $dbName): string
{
    return <<<PHP
<?php
// FILE: buat_db.php (TAHAP 1 - Membuat Database & Tabel)

// Isi dengan nama database pribadimu (cek di halaman Database)
\$sqlDb = "CREATE DATABASE IF NOT EXISTS ____";

// tabel: pengeluaran | kolom: keterangan, nominal, tanggal
\$sqlTabel = "CREATE TABLE IF NOT EXISTS ____ (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    ____       VARCHAR(100) NOT NULL,
    ____       INT NOT NULL,
    ____       DATE NOT NULL
)";

PHP;
}

function generate_koneksi_awal_php(string $dbUser, string $dbPass, string $dbName): string
{
    return <<<PHP
<?php
// FILE: koneksi.php (TAHAP 2 - Koneksi ke MySQL)

// Lengkapi kredensial databasemu (cek di halaman Database)
\$host     = "db";
\$user     = "____";
\$password = "____";
\$database = "____";

\$koneksi = mysqli_connect(\$host, \$user, \$password, \$database);

PHP;
}

function invalidate_file_cache(string $filePath): void
{
    clearstatcache(true, $filePath);
    if (function_exists('opcache_invalidate')) {
        @opcache_invalidate($filePath, true);
    }
}

function clear_blade_views_cache(string $participantId): void
{
    $viewsDir = participant_dir($participantId) . '/storage/framework/views';
    if (!is_dir($viewsDir)) {
        return;
    }
    $files = glob($viewsDir . '/*.php');
    if ($files === false) {
        return;
    }
    foreach ($files as $f) {
        invalidate_file_cache($f);
        @unlink($f);
    }
}

function update_participant_meta(string $participantId, string $file): void
{
    $metaFile = participant_dir($participantId) . '/meta.json';
    $meta = [
        'last_saved_at'   => date('Y-m-d H:i:s'),
        'last_saved_ts'   => time(),
        'last_saved_file' => $file,
    ];
    @file_put_contents($metaFile, json_encode($meta, JSON_PRETTY_PRINT));
}

function read_participant_meta(string $participantId): array
{
    $metaFile = participant_dir($participantId) . '/meta.json';
    if (!is_file($metaFile)) {
        return ['last_saved_at' => null, 'last_saved_ts' => null, 'last_saved_file' => null];
    }
    $data = json_decode((string) @file_get_contents($metaFile), true);
    return is_array($data) ? $data : ['last_saved_at' => null, 'last_saved_ts' => null, 'last_saved_file' => null];
}

function get_public_host(): string
{
    $envIp = trim((string) getenv('HOST_LAN_IP'));
    $httpHost = $_SERVER['HTTP_HOST'] ?? '';
    if ($httpHost !== '' && !str_starts_with($httpHost, 'localhost') && !str_starts_with($httpHost, '127.0.0.1')) {
        return $httpHost;
    }
    $port = getenv('WEB_PORT') ?: '8000';
    if ($envIp !== '') {
        return $envIp . ':' . $port;
    }
    return $httpHost !== '' ? $httpHost : ('localhost:' . $port);
}

function is_https_request(): bool
{
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        return true;
    }
    $xfp = strtolower(trim((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')));
    if ($xfp !== '') {
        return explode(',', $xfp)[0] === 'https';
    }
    return false;
}

function get_public_base_url(): string
{
    $scheme = is_https_request() ? 'https' : 'http';
    return $scheme . '://' . get_public_host();
}

function get_pma_url(): string
{
    $custom = trim((string) getenv('PMA_PUBLIC_URL'));
    if ($custom === 'disabled' || $custom === 'none') {
        return '';
    }
    if ($custom !== '') {
        return $custom;
    }
    $scheme = is_https_request() ? 'https' : 'http';
    $hostOnly = explode(':', get_public_host())[0];
    $pmaPort = getenv('PHPMYADMIN_PORT') ?: '8080';
    return $scheme . '://' . $hostOnly . ':' . $pmaPort . '/';
}

function load_settings(): array
{
    if (!is_file(SETTINGS_FILE)) {
        return ['laravel_enabled' => true];
    }
    $d = json_decode((string) @file_get_contents(SETTINGS_FILE), true);
    return [
        'laravel_enabled' => array_key_exists('laravel_enabled', is_array($d) ? $d : []) ? (bool) $d['laravel_enabled'] : true,
    ];
}

function save_settings(array $settings): void
{
    if (!is_dir(PARTICIPANTS_DIR)) {
        mkdir(PARTICIPANTS_DIR, 0755, true);
    }
    $tmp = SETTINGS_FILE . '.tmp.' . getmypid();
    file_put_contents($tmp, json_encode([
        'laravel_enabled' => !empty($settings['laravel_enabled']),
    ], JSON_PRETTY_PRINT));
    rename($tmp, SETTINGS_FILE);
    @chmod(SETTINGS_FILE, 0640);
}

function is_laravel_enabled(): bool
{
    return load_settings()['laravel_enabled'];
}

function start_workshop_session(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_name('WORKSHOP_SID');
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => is_https_request(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

function rrmdir_safe(string $dir): void
{
    if (!is_dir($dir)) {
        return;
    }
    $items = scandir($dir);
    if ($items === false) {
        return;
    }
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        $path = $dir . '/' . $item;
        if (is_dir($path) && !is_link($path)) {
            rrmdir_safe($path);
        } else {
            @unlink($path);
        }
    }
    @rmdir($dir);
}

function find_participant_by_nim(string $nim): ?array
{
    $clean = strtolower(trim($nim));
    if ($clean === '') {
        return null;
    }
    $reg = load_registry();
    foreach ($reg['participants'] ?? [] as $p) {
        $pNim = strtolower((string) ($p['nim'] ?? $p['db_user'] ?? ''));
        if ($pNim !== '' && hash_equals($pNim, $clean)) {
            return $p;
        }
    }
    return null;
}

function register_participant_by_nim(string $nimRaw, string $password): array
{
    $nim = strtolower(trim($nimRaw));
    if (!preg_match('/^[a-z0-9]{4,24}$/', $nim)) {
        return ['ok' => false, 'error' => 'NIM harus terdiri dari 4–24 huruf/angka saja tanpa spasi atau simbol (contoh: f12202600069).'];
    }
    if (strlen($password) < 4 || strlen($password) > 64) {
        return ['ok' => false, 'error' => 'Password minimal 4 karakter.'];
    }

    if (!is_dir(PARTICIPANTS_DIR)) {
        mkdir(PARTICIPANTS_DIR, 0755, true);
    }

    // Gunakan lock file agar aman jika 60 peserta klik Daftar bersamaan
    $lockFp = fopen(PARTICIPANTS_DIR . '/.register.lock', 'c+');
    if (!$lockFp || !flock($lockFp, LOCK_EX)) {
        return ['ok' => false, 'error' => 'Server sedang sibuk, silakan klik Daftar lagi.'];
    }

    try {
        $reg = load_registry();
        $participants = $reg['participants'] ?? [];
        if (count($participants) >= MAX_PARTICIPANTS) {
            return ['ok' => false, 'error' => 'Kuota pendaftaran di server sudah penuh. Hubungi panitia.'];
        }

        $usedTokens = [];
        $maxNum = 0;
        foreach ($participants as $p) {
            $usedTokens[$p['token'] ?? ''] = true;
            $maxNum = max($maxNum, (int) ($p['number'] ?? 0));
            $existingNim = strtolower((string) ($p['nim'] ?? $p['db_user'] ?? ''));
            if ($existingNim === $nim) {
                return ['ok' => false, 'error' => "NIM '{$nim}' sudah terdaftar. Jika itu akunmu, silakan masuk lewat tab Login."];
            }
        }

        do {
            $token = bin2hex(random_bytes(5));
        } while (isset($usedTokens[$token]));

        $id     = 'nim_' . $nim;
        $dbName = 'db_' . $nim;
        $dbUser = $nim;
        $dbPass = $nim; // Opsi A: kredensial DB simpel = NIM

        // 1. Buat user MySQL dengan hak CREATE terbatas hanya untuk db_<nim>
        $dbHost = getenv('DB_HOST') ?: 'db';
        $dbRootPass = getenv('MYSQL_ROOT_PASSWORD') ?: 'workshop_root_2026';
        mysqli_report(MYSQLI_REPORT_OFF);
        $mysqli = @mysqli_connect($dbHost, 'root', $dbRootPass);
        if (!$mysqli) {
            return ['ok' => false, 'error' => 'Database server belum siap. Coba lagi beberapa detik.'];
        }
        mysqli_set_charset($mysqli, 'utf8mb4');
        @mysqli_query($mysqli, "DROP DATABASE IF EXISTS `{$dbName}`");
        @mysqli_query($mysqli, "DROP USER IF EXISTS '{$dbUser}'@'%'");
        $okUser = @mysqli_query($mysqli, "CREATE USER '{$dbUser}'@'%' IDENTIFIED BY '{$dbPass}' WITH MAX_USER_CONNECTIONS 15");
        $okGrant = $okUser && @mysqli_query($mysqli, "GRANT CREATE, ALTER, DROP, SELECT, INSERT, UPDATE, DELETE ON `{$dbName}`.* TO '{$dbUser}'@'%'");
        @mysqli_query($mysqli, "FLUSH PRIVILEGES");
        mysqli_close($mysqli);

        if (!$okGrant) {
            return ['ok' => false, 'error' => 'Gagal membuat akun database untuk NIM tersebut.'];
        }

        // 2. Buat folder kerja Day 1 & Day 2
        $pDir = participant_dir($id);
        rrmdir_safe($pDir);
        foreach ([
            "{$pDir}/day1",
            "{$pDir}/day2/views/pengeluaran",
            "{$pDir}/storage/framework/views",
            "{$pDir}/storage/framework/cache/data",
            "{$pDir}/storage/framework/sessions",
            "{$pDir}/storage/logs",
        ] as $d) {
            mkdir($d, 0775, true);
        }

        file_put_contents("{$pDir}/day1/buat_db.php", generate_buat_db_awal_php($dbName));
        file_put_contents("{$pDir}/day1/koneksi.php", generate_koneksi_awal_php($dbUser, $dbPass, $dbName));
        copy(TEMPLATES_DIR . '/day1/awal/tampil.php', "{$pDir}/day1/tampil.php");
        copy(TEMPLATES_DIR . '/day1/awal/tambah.php', "{$pDir}/day1/tambah.php");
        copy(TEMPLATES_DIR . '/day1/awal/ubah.php', "{$pDir}/day1/ubah.php");
        copy(TEMPLATES_DIR . '/day1/awal/hapus.php', "{$pDir}/day1/hapus.php");
        copy(TEMPLATES_DIR . '/day1/common/style.css', "{$pDir}/day1/style.css");

        copy(TEMPLATES_DIR . '/day2/awal/create_pengeluaran_table.php', "{$pDir}/day2/create_pengeluaran_table.php");
        copy(TEMPLATES_DIR . '/day2/awal/Pengeluaran.php', "{$pDir}/day2/Pengeluaran.php");
        copy(TEMPLATES_DIR . '/day2/awal/web.php', "{$pDir}/day2/web.php");
        copy(TEMPLATES_DIR . '/day2/awal/PengeluaranController.php', "{$pDir}/day2/PengeluaranController.php");
        copy(TEMPLATES_DIR . '/day2/awal/index.blade.php', "{$pDir}/day2/views/pengeluaran/index.blade.php");

        $newP = [
            'number'        => $maxNum + 1,
            'id'            => $id,
            'nim'           => $nim,
            'label'         => $nim,
            'token'         => $token,
            'db_name'       => $dbName,
            'db_user'       => $dbUser,
            'db_pass'       => $dbPass,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'registered_at' => date('Y-m-d H:i:s'),
        ];

        $participants[] = $newP;
        $reg['created_at'] = $reg['created_at'] ?: date('Y-m-d H:i:s');
        $reg['count'] = count($participants);
        $reg['participants'] = $participants;
        save_registry($reg);

        return ['ok' => true, 'participant' => $newP];
    } finally {
        flock($lockFp, LOCK_UN);
        fclose($lockFp);
    }
}

function delete_participant_by_id(string $id): bool
{
    $lockFp = fopen(PARTICIPANTS_DIR . '/.register.lock', 'c+');
    if (!$lockFp || !flock($lockFp, LOCK_EX)) {
        return false;
    }
    try {
        $reg = load_registry();
        $found = null;
        $remaining = [];
        foreach ($reg['participants'] ?? [] as $p) {
            if (($p['id'] ?? '') === $id) {
                $found = $p;
            } else {
                $remaining[] = $p;
            }
        }
        if ($found === null) {
            return false;
        }

        $dbName = preg_replace('/[^a-z0-9_]/', '', (string) ($found['db_name'] ?? ''));
        $dbUser = preg_replace('/[^a-z0-9_]/', '', (string) ($found['db_user'] ?? ''));
        $dbHost = getenv('DB_HOST') ?: 'db';
        $dbRootPass = getenv('MYSQL_ROOT_PASSWORD') ?: 'workshop_root_2026';
        mysqli_report(MYSQLI_REPORT_OFF);
        $mysqli = @mysqli_connect($dbHost, 'root', $dbRootPass);
        if ($mysqli) {
            if ($dbName !== '') {
                @mysqli_query($mysqli, "DROP DATABASE IF EXISTS `{$dbName}`");
            }
            if ($dbUser !== '') {
                @mysqli_query($mysqli, "DROP USER IF EXISTS '{$dbUser}'@'%'");
            }
            @mysqli_query($mysqli, "FLUSH PRIVILEGES");
            mysqli_close($mysqli);
        }

        rrmdir_safe(participant_dir($id));
        $reg['participants'] = $remaining;
        $reg['count'] = count($remaining);
        save_registry($reg);
        return true;
    } finally {
        flock($lockFp, LOCK_UN);
        fclose($lockFp);
    }
}

function reset_participant_password_by_id(string $id): ?string
{
    $lockFp = fopen(PARTICIPANTS_DIR . '/.register.lock', 'c+');
    if (!$lockFp || !flock($lockFp, LOCK_EX)) {
        return null;
    }
    try {
        $reg = load_registry();
        $newPass = null;
        foreach ($reg['participants'] as &$p) {
            if (($p['id'] ?? '') === $id) {
                $newPass = (string) ($p['nim'] ?? $p['db_user'] ?? 'workshop');
                $p['password_hash'] = password_hash($newPass, PASSWORD_DEFAULT);
                break;
            }
        }
        unset($p);
        if ($newPass !== null) {
            save_registry($reg);
        }
        return $newPass;
    } finally {
        flock($lockFp, LOCK_UN);
        fclose($lockFp);
    }
}

function reset_participant_workspace_by_id(string $id): bool
{
    $lockFp = fopen(PARTICIPANTS_DIR . '/.register.lock', 'c+');
    if (!$lockFp || !flock($lockFp, LOCK_EX)) {
        return false;
    }
    try {
        $reg = load_registry();
        $found = null;
        foreach ($reg['participants'] ?? [] as $p) {
            if (($p['id'] ?? '') === $id) {
                $found = $p;
                break;
            }
        }
        if ($found === null) {
            return false;
        }

        $pDir = participant_dir($id);
        if (!is_dir("{$pDir}/day1")) {
            mkdir("{$pDir}/day1", 0775, true);
        }
        if (!is_dir("{$pDir}/day2/views/pengeluaran")) {
            mkdir("{$pDir}/day2/views/pengeluaran", 0775, true);
        }

        // 1. Reset semua file Day 1 ke template awal
        file_put_contents("{$pDir}/day1/buat_db.php", generate_buat_db_awal_php((string) $found['db_name']));
        file_put_contents("{$pDir}/day1/koneksi.php", generate_koneksi_awal_php((string) $found['db_user'], (string) $found['db_pass'], (string) $found['db_name']));
        copy(TEMPLATES_DIR . '/day1/awal/tampil.php', "{$pDir}/day1/tampil.php");
        copy(TEMPLATES_DIR . '/day1/awal/tambah.php', "{$pDir}/day1/tambah.php");
        copy(TEMPLATES_DIR . '/day1/awal/ubah.php', "{$pDir}/day1/ubah.php");
        copy(TEMPLATES_DIR . '/day1/awal/hapus.php', "{$pDir}/day1/hapus.php");
        copy(TEMPLATES_DIR . '/day1/common/style.css', "{$pDir}/day1/style.css");
        @unlink("{$pDir}/day1/.seeded");

        foreach (['buat_db.php', 'koneksi.php', 'tampil.php', 'tambah.php', 'ubah.php', 'hapus.php'] as $f) {
            invalidate_file_cache("{$pDir}/day1/{$f}");
        }

        // 2. Reset semua file Day 2 ke template awal
        copy(TEMPLATES_DIR . '/day2/awal/create_pengeluaran_table.php', "{$pDir}/day2/create_pengeluaran_table.php");
        copy(TEMPLATES_DIR . '/day2/awal/Pengeluaran.php', "{$pDir}/day2/Pengeluaran.php");
        copy(TEMPLATES_DIR . '/day2/awal/web.php', "{$pDir}/day2/web.php");
        copy(TEMPLATES_DIR . '/day2/awal/PengeluaranController.php', "{$pDir}/day2/PengeluaranController.php");
        copy(TEMPLATES_DIR . '/day2/awal/index.blade.php', "{$pDir}/day2/views/pengeluaran/index.blade.php");

        foreach (['create_pengeluaran_table.php', 'Pengeluaran.php', 'web.php', 'PengeluaranController.php'] as $f) {
            invalidate_file_cache("{$pDir}/day2/{$f}");
        }

        // 3. Bersihkan cache Blade & meta.json
        clear_blade_views_cache($id);
        @unlink("{$pDir}/meta.json");

        // 4. Hapus database peserta (user & GRANT tetap ada agar bisa buat_db.php lagi)
        $dbName = preg_replace('/[^a-z0-9_]/', '', (string) ($found['db_name'] ?? ''));
        if ($dbName !== '') {
            $dbHost = getenv('DB_HOST') ?: 'db';
            $dbRootPass = getenv('MYSQL_ROOT_PASSWORD') ?: 'workshop_root_2026';
            mysqli_report(MYSQLI_REPORT_OFF);
            $mysqli = @mysqli_connect($dbHost, 'root', $dbRootPass);
            if ($mysqli) {
                @mysqli_query($mysqli, "DROP DATABASE IF EXISTS `{$dbName}`");
                mysqli_close($mysqli);
            }
        }

        return true;
    } finally {
        flock($lockFp, LOCK_UN);
        fclose($lockFp);
    }
}

function require_admin_auth(): void
{
    $expected = getenv('ADMIN_PASSWORD') ?: 'instruktur2026';

    // 1. Cek session login (dari form /admin/login)
    start_workshop_session();
    if (!empty($_SESSION['is_admin'])) {
        return;
    }

    // 2. Cek HTTP Basic Auth (agar curl -u admin:... & skrip otomatis tetap jalan)
    $user = $_SERVER['PHP_AUTH_USER'] ?? '';
    $pass = $_SERVER['PHP_AUTH_PW'] ?? '';
    if ($user === 'admin' && hash_equals($expected, $pass)) {
        $_SESSION['is_admin'] = true;
        return;
    }

    // Jika request dari browser (Accept: text/html), arahkan ke halaman form login /admin/login
    $accept = (string) ($_SERVER['HTTP_ACCEPT'] ?? '');
    if (str_contains($accept, 'text/html') && $user === '') {
        header('Location: /admin/login');
        exit;
    }

    header('WWW-Authenticate: Basic realm="Panel Instruktur Workshop"');
    http_response_code(401);
    echo 'Unauthorized';
    exit;
}

function render_friendly_php_error(string $title, string $message, string $file = '', int $line = 0): void
{
    $titleE = e($title);
    $msgE   = e($message);
    $fileE  = e($file !== '' ? basename($file) : '');
    $loc    = $fileE !== '' ? "File: {$fileE}" . ($line > 0 ? " (baris {$line})" : '') : '';
    http_response_code(500);
    echo <<<HTML
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{$titleE}</title>
<style>
  * { box-sizing: border-box; }
  body { font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #fafaf9; color: #18181b; margin: 0; padding: 24px 16px; line-height: 1.55; -webkit-font-smoothing: antialiased; }
  .box { max-width: 680px; margin: 20px auto; background: #fff; border: 1px solid #e4e4e7; border-radius: 6px; padding: 24px; }
  .badge { display: inline-block; font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; font-weight: 600; font-size: 11px; letter-spacing: 0.05em; text-transform: uppercase; padding: 2px 7px; border-radius: 3px; margin-bottom: 10px; }
  h1 { margin: 0 0 12px; font-size: 20px; font-weight: 700; letter-spacing: -0.02em; color: #18181b; }
  .tip { background: #fff7ed; border: 1px dashed #fdba74; color: #9a3412; padding: 12px 14px; border-radius: 4px; margin: 14px 0; font-size: 13.5px; line-height: 1.55; }
  .detail-label { margin: 14px 0 6px; font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #71717a; }
  .detail { background: #18181b; color: #f4f4f5; padding: 12px 14px; border-radius: 4px; font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size: 12.5px; overflow-x: auto; line-height: 1.5; }
  code { background: #f4f4f5; border: 1px solid #e4e4e7; color: #18181b; padding: 1px 5px; border-radius: 3px; font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size: 0.9em; }
</style>
</head>
<body>
  <div class="box">
    <span class="badge">Pesan Bantuan</span>
    <h1>{$titleE}</h1>
    <div class="tip">Cek kembali tanda kutip (<code>"</code> atau <code>'</code>), tanda titik koma (<code>;</code>), atau bagian <code>____</code> yang belum diisi. Jika masih bingung, cek komentar petunjuk di atas baris yang kosong atau minta bantuan panitia.</div>
    <div class="detail-label">Pesan Teknis</div>
    <div class="detail">{$msgE}<br><span style="color:#a1a1aa;">{$loc}</span></div>
  </div>
</body>
</html>
HTML;
}
