<?php

declare(strict_types=1);

require_once __DIR__ . '/lib/common.php';

$token = (string) ($_GET['token'] ?? '');
$participant = find_participant_by_token($token);
if (!$participant) {
    http_response_code(404);
    echo '<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><title>Link Tidak Ditemukan</title></head>'
        . '<body style="font-family:sans-serif;padding:40px;text-align:center;">'
        . '<h2>Link peserta tidak valid.</h2><p>Periksa kembali link yang kamu buka atau hubungi panitia.</p>'
        . '</body></html>';
    exit;
}

header('Cache-Control: no-store, no-cache, must-revalidate');

if (!is_laravel_enabled()) {
    http_response_code(403);
    echo '<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><title>Materi Day 2 Belum Dibuka</title>'
        . '<link rel="stylesheet" href="/assets/app.css"></head><body>'
        . '<div class="container"><div class="card"><span class="badge badge-warn">Nonaktif</span>'
        . '<h1>Materi Day 2 (Laravel) belum dibuka</h1>'
        . '<p class="lead">Halaman ini masih nonaktif. Materi Day 2 (Laravel) akan dibuka pada sesi berikutnya.</p>'
        . '<a class="btn btn-primary" href="/p/' . e($token) . '/">&larr; Kembali ke Dashboard</a>'
        . '</div></div></body></html>';
    exit;
}

$pDir = participant_dir($participant['id']);
$day2Dir = $pDir . '/day2';
$storageDir = $pDir . '/storage';
$laravelBase = '/opt/laravel-base';
$dbHost = getenv('DB_HOST') ?: 'db';
$dbNameSafe = preg_replace('/[^a-z0-9_]/', '', (string) $participant['db_name']);

// Pastikan folder storage peserta siap
foreach (['framework/views', 'framework/cache/data', 'framework/sessions', 'logs'] as $sub) {
    $d = $storageDir . '/' . $sub;
    if (!is_dir($d)) {
        @mkdir($d, 0775, true);
    }
}

// Helper tampilan progress bertahap Day 2 (serupa Day 1 agar peserta tahu file mana yang sudah lulus)
function render_day2_progress(array $steps, int $currentIdx, string $heading, string $instruction): void
{
    $badgesHtml = '';
    foreach ($steps as $idx => $st) {
        if ($idx < $currentIdx) {
            $badgesHtml .= '<span class="st-badge st-ok">&#10004; ' . e($st['label']) . '</span>';
        } elseif ($idx === $currentIdx) {
            $badgesHtml .= '<span class="st-badge st-now">&#9679; ' . e($st['label']) . '</span>';
        } else {
            $badgesHtml .= '<span class="st-badge st-wait">' . e($st['label']) . '</span>';
        }
    }
    $headingE = e($heading);
    http_response_code(200);
    echo <<<HTML
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{$headingE}</title>
<style>
  * { box-sizing: border-box; }
  body { font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #fafaf9; color: #18181b; margin: 0; padding: 24px 16px; line-height: 1.55; -webkit-font-smoothing: antialiased; }
  .box { max-width: 720px; margin: 16px auto; background: #fff; border: 1px solid #e4e4e7; border-radius: 6px; padding: 24px; }
  .kicker { display: inline-block; font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; font-weight: 600; font-size: 11px; letter-spacing: 0.05em; text-transform: uppercase; padding: 2px 7px; border-radius: 3px; margin-bottom: 12px; }
  .progress-row { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 16px; }
  .st-badge { display: inline-flex; align-items: center; gap: 5px; font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size: 11px; font-weight: 600; padding: 3px 8px; border-radius: 3px; border: 1px solid #e4e4e7; }
  .st-ok { background: #f0fdf4; border-color: #bbf7d0; color: #15803d; }
  .st-now { background: #fff7ed; border-color: #fdba74; color: #9a3412; }
  .st-wait { background: #f4f4f5; border-color: #e4e4e7; color: #71717a; }
  h1 { margin: 0 0 12px; font-size: 20px; font-weight: 700; letter-spacing: -0.02em; color: #18181b; }
  .guide { background: #fff7ed; border: 1px dashed #fdba74; color: #9a3412; padding: 14px 16px; border-radius: 4px; font-size: 13.5px; line-height: 1.6; }
  code { background: #f4f4f5; border: 1px solid #e4e4e7; color: #18181b; padding: 1px 5px; border-radius: 3px; font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size: 0.9em; }
</style>
</head>
<body>
  <div class="box">
    <span class="kicker">Laravel • Day 2</span>
    <div class="progress-row">{$badgesHtml}</div>
    <h1>{$headingE}</h1>
    <div class="guide">{$instruction}</div>
  </div>
</body>
</html>
HTML;
}

// 1. Cek bertahap 5 file Day 2 agar progres per tahap selalu jelas sebelum Laravel dijalankan
$day2Steps = [
    [
        'label'       => '1. Migration',
        'path'        => $day2Dir . '/create_pengeluaran_table.php',
        'heading'     => 'Tahap 1: Lengkapi file Migration (create_pengeluaran_table.php)',
        'instruction' => 'Buka tab <code>create_pengeluaran_table.php</code>, lalu ganti semua tanda <code>____</code> dengan nama tabel dan 3 kolom sesuai struktur database Day 1 (cek di halaman Database). Setelah selesai, klik <strong>Simpan &amp; Jalankan</strong>.',
    ],
    [
        'label'       => '2. Model',
        'path'        => $day2Dir . '/Pengeluaran.php',
        'heading'     => 'Tahap 2: Lengkapi file Model (Pengeluaran.php)',
        'instruction' => 'Migration sudah beres! Lanjut buka tab <code>Pengeluaran.php</code>, lalu isi <code>$table</code> dan daftar kolom pada <code>$fillable</code> (cek tab Struktur di halaman Database), kemudian klik <strong>Simpan &amp; Jalankan</strong>.',
    ],
    [
        'label'       => '3. Controller',
        'path'        => $day2Dir . '/PengeluaranController.php',
        'heading'     => 'Tahap 3: Lengkapi file Controller (PengeluaranController.php)',
        'instruction' => 'Model sudah beres! Lanjut buka tab <code>PengeluaranController.php</code>, lengkapi bagian <code>____</code> pada method <code>index</code>, <code>simpan</code>, <code>ubah</code>, dan <code>hapus</code> sesuai petunjuk komentar, lalu klik <strong>Simpan &amp; Jalankan</strong>.',
    ],
    [
        'label'       => '4. Route',
        'path'        => $day2Dir . '/web.php',
        'heading'     => 'Tahap 4: Lengkapi file Route (web.php)',
        'instruction' => 'Controller sudah beres! Lanjut buka tab <code>web.php</code>, hubungkan <code>Route::get(\'/\', ...)</code> ke Controller &amp; method yang tadi kamu buat (dan hapus tanda <code>//</code> di 3 baris bawahnya untuk mengaktifkan Tambah, Ubah, Hapus), lalu klik <strong>Simpan &amp; Jalankan</strong>.',
    ],
    [
        'label'       => '5. View',
        'path'        => $day2Dir . '/views/pengeluaran/index.blade.php',
        'heading'     => 'Tahap 5: Lengkapi file View (index.blade.php)',
        'instruction' => 'Route sudah beres! Langkah terakhir: buka tab <code>index.blade.php</code>, ganti semua tanda <code>____</code> pada perulangan <code>@foreach</code> dengan nama variabel yang sama, lalu klik <strong>Simpan &amp; Jalankan</strong>.',
    ],
];

$migrationPath = $day2Dir . '/create_pengeluaran_table.php';
foreach ($day2Steps as $idx => $st) {
    $src = is_file($st['path']) ? (string) file_get_contents($st['path']) : '';
    // Abaikan komentar PHP (// ...) dan Blade ({{-- ... --}}) saat mengecek placeholder ____
    $codeOnly = (string) preg_replace(['/\{\{--[\s\S]*?--\}\}/', '/\/\/[^\r\n]*/'], '', $src);
    if (trim($codeOnly) === '' || str_contains($codeOnly, '____')) {
        render_day2_progress($day2Steps, $idx, $st['heading'], $st['instruction']);
        exit;
    }
}

// Pastikan database milik peserta sudah ada agar Migration Laravel bisa membuat tabelnya
// (Jika peserta ikut Day 1, DB & data Day 1 sudah ada dan tidak akan ditimpa!)
mysqli_report(MYSQLI_REPORT_OFF);
$srvConn = @mysqli_connect($dbHost, $participant['db_user'], $participant['db_pass']);
if ($srvConn) {
    @mysqli_query($srvConn, "CREATE DATABASE IF NOT EXISTS `{$dbNameSafe}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    mysqli_close($srvConn);
}

// Konfigurasi environment runtime khusus peserta ini (membaca DB Day 1 yang sama!)
$envVars = [
    'APP_DEBUG'                  => 'false',
    'DB_CONNECTION'              => 'mysql',
    'DB_HOST'                    => $dbHost,
    'DB_PORT'                    => getenv('DB_PORT') ?: '3306',
    'DB_DATABASE'                => $participant['db_name'],
    'DB_USERNAME'                => $participant['db_user'],
    'DB_PASSWORD'                => $participant['db_pass'],
    'SESSION_DRIVER'             => 'file',
    'CACHE_STORE'                => 'file',
    'PARTICIPANT_ROUTES_FILE'    => $day2Dir . '/web.php',
    'PARTICIPANT_VIEWS_PATH'     => $day2Dir . '/views',
    'PARTICIPANT_COMPILED_VIEWS' => $storageDir . '/framework/views',
];

foreach ($envVars as $k => $v) {
    putenv("{$k}={$v}");
    $_ENV[$k] = $v;
    $_SERVER[$k] = $v;
}

// Strip prefix /p/<token>/laravel dari REQUEST_URI agar Route::get('/') dll selalu cocok
$prefix = '/p/' . $token . '/laravel';
$rawUri = $_SERVER['REQUEST_URI'] ?? '/';
if (str_starts_with($rawUri, $prefix)) {
    $subUri = substr($rawUri, strlen($prefix));
    $_SERVER['REQUEST_URI'] = ($subUri === '' || $subUri[0] === '?') ? ('/' . $subUri) : $subUri;
}
$isHttps = is_https_request();
if ($isHttps) {
    $_SERVER['HTTPS'] = 'on';
    $_SERVER['SERVER_PORT'] = 443;
}
$scheme = $isHttps ? 'https' : 'http';
$httpHost = $_SERVER['HTTP_HOST'] ?? 'localhost';
$rootUrl = $scheme . '://' . $httpHost . $prefix;
$_SERVER['APP_URL'] = $rootUrl;
$_ENV['APP_URL'] = $rootUrl;
putenv("APP_URL={$rootUrl}");

unset($_GET['token']);

// Batas resource & isolasi direktori (peserta A tidak bisa membaca/menulis folder peserta B)
@ini_set('max_execution_time', '5');
@ini_set('memory_limit', '96M');
@ini_set('open_basedir', "{$laravelBase}:{$day2Dir}:{$storageDir}:{$pDir}/day1:/tmp");

register_shutdown_function(function (): void {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        if (!headers_sent()) {
            header('Content-Type: text/html; charset=UTF-8');
        }
        render_friendly_php_error(
            'Ups! Ada kesalahan penulisan di kode Laravel kamu',
            $err['message'],
            $err['file'] ?? '',
            (int) ($err['line'] ?? 0)
        );
    }
});

try {
    require $laravelBase . '/vendor/autoload.php';

    // Muat Model (M) dan Controller (C) milik peserta ini
    $modelPath = $day2Dir . '/Pengeluaran.php';
    if (is_file($modelPath)) {
        require $modelPath;
    }
    $controllerPath = $day2Dir . '/PengeluaranController.php';
    if (is_file($controllerPath)) {
        require $controllerPath;
    }

    /** @var \Illuminate\Foundation\Application $app */
    $app = require $laravelBase . '/bootstrap/app.php';
    $app->useStoragePath($storageDir);
    $app->resolving('url', function ($url) use ($rootUrl, $isHttps) {
        $url->forceRootUrl($rootUrl);
        if ($isHttps) {
            $url->forceScheme('https');
        }
    });

    // Jalankan Migration peserta (hanya membuat tabel jika belum ada; data Day 1 tetap utuh!)
    $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    $migration = require $migrationPath;
    if (is_object($migration) && method_exists($migration, 'up')) {
        $migration->up();
    }

    $app->handleRequest(\Illuminate\Http\Request::capture());
} catch (\Throwable $e) {
    render_friendly_php_error(
        'Ups! Kode Laravel masih perlu dilengkapi atau diperbaiki',
        $e->getMessage(),
        $e->getFile(),
        (int) $e->getLine()
    );
}
