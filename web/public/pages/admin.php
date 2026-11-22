<?php

declare(strict_types=1);

$reg = load_registry();
$participants = $reg['participants'] ?? [];
$baseUrl = get_public_base_url();
$pmaUrl = get_pma_url();
$laravelOn = is_laravel_enabled();
$flash = (string) ($_GET['msg'] ?? '');

// Ambil jumlah baris pengeluaran per peserta lewat 1 koneksi root agar cepat
$rowCounts = [];
mysqli_report(MYSQLI_REPORT_OFF);
$dbHost = getenv('DB_HOST') ?: 'db';
$dbRootPass = getenv('MYSQL_ROOT_PASSWORD') ?: 'workshop_root_2026';
$conn = @mysqli_connect($dbHost, 'root', $dbRootPass);
if ($conn) {
    foreach ($participants as $p) {
        $dbName = preg_replace('/[^a-z0-9_]/', '', $p['db_name']);
        $res = @mysqli_query($conn, "SELECT COUNT(*) AS c FROM `{$dbName}`.`pengeluaran`");
        if ($res) {
            $r = mysqli_fetch_assoc($res);
            $rowCounts[$p['id']] = (int) ($r['c'] ?? 0);
        }
    }
    mysqli_close($conn);
}

function check_stage_status(string $id): array
{
    $pDir   = participant_dir($id);
    $buatDb = (string) @file_get_contents("{$pDir}/day1/buat_db.php");
    $kon    = (string) @file_get_contents("{$pDir}/day1/koneksi.php");
    $tampil = (string) @file_get_contents("{$pDir}/day1/tampil.php");
    $tambah = (string) @file_get_contents("{$pDir}/day1/tambah.php");
    $ubah   = (string) @file_get_contents("{$pDir}/day1/ubah.php");
    $hapus  = (string) @file_get_contents("{$pDir}/day1/hapus.php");
    $mig    = (string) @file_get_contents("{$pDir}/day2/create_pengeluaran_table.php");
    $model  = (string) @file_get_contents("{$pDir}/day2/Pengeluaran.php");
    $web    = (string) @file_get_contents("{$pDir}/day2/web.php");
    $ctrl   = (string) @file_get_contents("{$pDir}/day2/PengeluaranController.php");
    $blade  = (string) @file_get_contents("{$pDir}/day2/views/pengeluaran/index.blade.php");

    $dbOk     = ($buatDb !== '' && !str_contains($buatDb, '____'));
    $konOk    = ($kon !== '' && !str_contains($kon, '"____"'));
    $tampilOk = ($tampil !== '' && !str_contains($tampil, 'FROM ____') && preg_match('/FROM\s+pengeluaran/i', $tampil));
    $tambahOk = ($tambah !== '' && !str_contains($tambah, 'INSERT INTO ____') && preg_match('/INSERT\s+INTO\s+pengeluaran/i', $tambah));
    $ubahOk   = ($ubah !== '' && !str_contains($ubah, 'UPDATE ____') && !str_contains($ubah, '____'));
    $hapusOk  = ($hapus !== '' && !str_contains($hapus, 'DELETE FROM ____') && !str_contains($hapus, '____'));
    $stripComments = static fn(string $s): string => (string) preg_replace(['/\{\{--[\s\S]*?--\}\}/', '/\/\/[^\r\n]*/'], '', $s);
    $d2Done   = ($mig !== '' && !str_contains($stripComments($mig), '____') ? 1 : 0)
              + ($model !== '' && !str_contains($stripComments($model), '____') ? 1 : 0)
              + ($web !== '' && !str_contains($stripComments($web), '____') ? 1 : 0)
              + ($ctrl !== '' && !str_contains($stripComments($ctrl), '____') ? 1 : 0)
              + ($blade !== '' && !str_contains($stripComments($blade), '____') ? 1 : 0);

    return [
        'buat_db' => $dbOk,
        'kon'     => $konOk,
        'tampil'  => $tampilOk,
        'tambah'  => $tambahOk,
        'ubah'    => $ubahOk,
        'hapus'   => $hapusOk,
        'd2_done' => $d2Done,
    ];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Admin &amp; Instruktur — Workshop</title>
    <link rel="stylesheet" href="/assets/app.css">
    <style>
        .flash-ok { background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; padding: 11px 14px; border-radius: 4px; margin-bottom: 18px; font-weight: 600; font-size: 13.5px; }
        .btn-xs { padding: 5px 9px; font-size: 12px; border-radius: 5px; border: 1px solid transparent; cursor: pointer; font-weight: 600; font-family: inherit; }
        .btn-xs-reset { background: #f4f4f5; color: #18181b; border-color: #d4d4d8; }
        .btn-xs-reset:hover { background: #e4e4e7; }
        .btn-xs-warn { background: #fef3c7; color: #92400e; border-color: #fde68a; }
        .btn-xs-warn:hover { background: #fde68a; }
        .btn-xs-del { background: #fee2e2; color: #b91c1c; border-color: #fecaca; }
        .btn-xs-del:hover { background: #fecaca; }
    </style>
</head>
<body>
<div class="topbar">
    <div class="brand">Dashboard Admin</div>
    <div class="nav">
        <a href="/admin/logout" style="color:#b91c1c;">Logout</a>
    </div>
</div>

<div class="container-wide">
    <?php if ($flash !== ''): ?>
        <div class="flash-ok"><?= e($flash) ?></div>
    <?php endif; ?>

    <!-- Kontrol Hari-H -->
    <div class="card">
        <span class="badge">Kontrol Sesi Workshop</span>
        <h1>Dashboard Admin (<?= count($participants) ?> Peserta Terdaftar)</h1>
        <p class="lead" style="margin-bottom:14px;">
            Peserta dapat mendaftar mandiri pakai <strong>NIM</strong> di <code><?= e($baseUrl) ?>/</code>.
        </p>

        <div style="display:flex; align-items:center; flex-wrap:wrap; gap:12px; padding:14px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:6px;">
            <div>
                <strong>Status Halaman Laravel (Day 2):</strong>
                <?php if ($laravelOn): ?>
                    <span class="badge badge-ok" style="margin:0 0 0 6px;">AKTIF (Peserta bisa buka Day 2)</span>
                <?php else: ?>
                    <span class="badge badge-warn" style="margin:0 0 0 6px;">DINONAKTIFKAN (Mode Day 1)</span>
                <?php endif; ?>
            </div>
            <form method="post" action="/admin/action" style="margin:0;">
                <input type="hidden" name="aksi" value="toggle_laravel">
                <input type="hidden" name="enable" value="<?= $laravelOn ? '0' : '1' ?>">
                <?php if ($laravelOn): ?>
                    <button type="submit" class="btn btn-dark" style="padding:8px 14px; font-size:13.5px;">Nonaktifkan Halaman Laravel (untuk Day 1)</button>
                <?php else: ?>
                    <button type="submit" class="btn btn-laravel" style="padding:8px 14px; font-size:13.5px;">Aktifkan Halaman Laravel (untuk Day 2)</button>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Daftar & Monitoring Peserta -->
    <div class="card">
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; margin-bottom:14px;">
            <h2 style="margin:0;">Monitoring &amp; Manajemen Peserta</h2>
            <div style="display:flex; gap:8px; flex-wrap:wrap;">
                <a class="btn btn-primary" href="/admin?t=<?= time() ?>" style="padding:8px 14px; font-size:13.5px;">Muat Ulang Status</a>
                <a class="btn btn-outline" href="/admin/export.csv" style="padding:8px 14px; font-size:13.5px;">Unduh CSV</a>
                <?php if ($pmaUrl !== ''): ?>
                    <a class="btn btn-outline" href="<?= e($pmaUrl) ?>" target="_blank" style="padding:8px 14px; font-size:13.5px;">Buka phpMyAdmin &nearr;</a>
                <?php endif; ?>
            </div>
        </div>

        <?php if (empty($participants)): ?>
            <p style="color:#64748b;">Belum ada peserta yang mendaftar. Peserta dapat mendaftar langsung di <code><?= e($baseUrl) ?>/</code>.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>NIM / Peserta</th>
                            <th>Link Portal</th>
                            <th>Terakhir Simpan</th>
                            <th>Baris DB</th>
                            <th>Buat DB</th>
                            <th>Koneksi</th>
                            <th>CRUD</th>
                            <th>Day 2</th>
                            <th>Aksi Admin</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($participants as $p): ?>
                            <?php
                            $meta = read_participant_meta($p['id']);
                            $st = check_stage_status($p['id']);
                            $rows = $rowCounts[$p['id']] ?? 'Belum dibuat';
                            $portalPath = '/p/' . $p['token'] . '/';
                            $crudDone = ($st['tampil'] ? 1 : 0) + ($st['tambah'] ? 1 : 0) + ($st['ubah'] ? 1 : 0) + ($st['hapus'] ? 1 : 0);
                            $nimDisp = (string) ($p['nim'] ?? $p['label']);
                            ?>
                            <tr>
                                <td>
                                    <strong><?= e($nimDisp) ?></strong><br>
                                    <span style="font-size:12px; color:#64748b;">DB: <code><?= e($p['db_name']) ?></code></span>
                                </td>
                                <td><a href="<?= e($portalPath) ?>" target="_blank"><code><?= e($portalPath) ?></code></a></td>
                                <td>
                                    <?php if ($meta['last_saved_at']): ?>
                                        <?= e($meta['last_saved_at']) ?><br>
                                        <span style="font-size:12px; color:#64748b;"><?= e((string) $meta['last_saved_file']) ?></span>
                                    <?php else: ?>
                                        <span style="color:#94a3b8;">Belum</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?= e((string) $rows) ?></strong>
                                </td>
                                <td><?= $st['buat_db'] ? '<span class="badge badge-ok" style="margin:0;">OK</span>' : '<span class="badge badge-warn" style="margin:0;">Belum</span>' ?></td>
                                <td><?= $st['kon'] ? '<span class="badge badge-ok" style="margin:0;">OK</span>' : '<span class="badge badge-warn" style="margin:0;">Belum</span>' ?></td>
                                <td>
                                    <span class="badge <?= $crudDone === 4 ? 'badge-ok' : 'badge-warn' ?>" style="margin:0;">
                                        <?= $st['tampil'] ? 'R' : '-' ?><?= $st['tambah'] ? 'C' : '-' ?><?= $st['ubah'] ? 'U' : '-' ?><?= $st['hapus'] ? 'D' : '-' ?> (<?= $crudDone ?>/4)
                                    </span>
                                </td>
                                <td>
                                    <span class="badge <?= $st['d2_done'] === 5 ? 'badge-ok' : 'badge-warn' ?>" style="margin:0;">
                                        <?= $st['d2_done'] ?>/5
                                    </span>
                                </td>
                                <td>
                                    <div style="display:flex; gap:6px; flex-wrap:nowrap;">
                                        <form method="post" action="/admin/action" style="margin:0;" onsubmit="return confirm('Reset semua file Day 1 &amp; Day 2 milik <?= e($nimDisp) ?> ke versi awal dan hapus database <?= e($p['db_name']) ?>? (Akun &amp; password tetap ada)');">
                                            <input type="hidden" name="aksi" value="reset_workspace">
                                            <input type="hidden" name="id" value="<?= e($p['id']) ?>">
                                            <button type="submit" class="btn-xs btn-xs-reset" title="Reset file kode &amp; database ke awal">Reset</button>
                                        </form>
                                        <form method="post" action="/admin/action" style="margin:0;" onsubmit="return confirm('Reset password login <?= e($nimDisp) ?> menjadi sama dengan NIM-nya?');">
                                            <input type="hidden" name="aksi" value="reset_pw">
                                            <input type="hidden" name="id" value="<?= e($p['id']) ?>">
                                            <button type="submit" class="btn-xs btn-xs-warn" title="Reset password login ke NIM">Reset PW</button>
                                        </form>
                                        <form method="post" action="/admin/action" style="margin:0;" onsubmit="return confirm('HAPUS permanen akun <?= e($nimDisp) ?> beserta database <?= e($p['db_name']) ?> dan seluruh filenya?');">
                                            <input type="hidden" name="aksi" value="delete_user">
                                            <input type="hidden" name="id" value="<?= e($p['id']) ?>">
                                            <button type="submit" class="btn-xs btn-xs-del">Hapus</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
