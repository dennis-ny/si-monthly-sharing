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

$file = ltrim((string) ($_GET['file'] ?? ''), '/');
$day1Dir = participant_dir($participant['id']) . '/day1';

if ($file === 'style.css') {
    $cssPath = $day1Dir . '/style.css';
    if (!is_file($cssPath)) {
        http_response_code(404);
        exit;
    }
    header('Content-Type: text/css; charset=UTF-8');
    header('Cache-Control: public, max-age=300');
    readfile($cssPath);
    exit;
}

// Semua request aplikasi Day 1 dilayani melalui shell ini
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Content-Type: text/html; charset=UTF-8');

// Batas resource & isolasi direktori (peserta A tidak bisa baca/tulis file peserta B)
@ini_set('max_execution_time', '5');
@ini_set('memory_limit', '64M');
@ini_set('open_basedir', $day1Dir);

register_shutdown_function(function (): void {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        if (!headers_sent()) {
            header('Content-Type: text/html; charset=UTF-8');
        }
        render_friendly_php_error(
            'Ups! Ada kesalahan penulisan di kode PHP kamu',
            $err['message'],
            $err['file'] ?? '',
            (int) ($err['line'] ?? 0)
        );
    }
});

chdir($day1Dir);
mysqli_report(MYSQLI_REPORT_OFF);

$buatDbError = null;
$buatDbSql = '';
$buatDbStage = '';
$koneksi = null;
$koneksiError = null;
$actionError = null;
$actionNotice = null;

// 0. Jalankan Tahap 1: buat_db.php (CREATE DATABASE + CREATE TABLE)
$sqlDb = '';
$sqlTabel = '';
try {
    require $day1Dir . '/buat_db.php';
} catch (\Throwable $e) {
    render_friendly_php_error(
        'Ups! Ada kesalahan di file buat_db.php',
        $e->getMessage(),
        $e->getFile(),
        (int) $e->getLine()
    );
    exit;
}

$dbHost = getenv('DB_HOST') ?: 'db';
$srvConn = @mysqli_connect($dbHost, $participant['db_user'], $participant['db_pass']);
if (!$srvConn) {
    $buatDbStage = 'Koneksi ke Server MySQL';
    $buatDbError = mysqli_connect_error() ?: 'Gagal terhubung ke server MySQL.';
} else {
    mysqli_set_charset($srvConn, 'utf8mb4');
    if (trim((string) $sqlDb) === '' || !@mysqli_query($srvConn, (string) $sqlDb)) {
        $buatDbStage = 'Membuat Database (Tahap 1A)';
        $buatDbError = mysqli_error($srvConn) ?: 'Perintah $sqlDb kosong atau gagal.';
        $buatDbSql = (string) $sqlDb;
    } elseif (!@mysqli_select_db($srvConn, $participant['db_name'])) {
        $buatDbStage = 'Memilih Database (Tahap 1A)';
        $buatDbError = "Database '{$participant['db_name']}' belum ditemukan. Pastikan nama database di \$sqlDb adalah {$participant['db_name']}.";
        $buatDbSql = (string) $sqlDb;
    } elseif (trim((string) $sqlTabel) === '' || !@mysqli_query($srvConn, (string) $sqlTabel)) {
        $buatDbStage = 'Membuat Tabel pengeluaran (Tahap 1B)';
        $buatDbError = mysqli_error($srvConn) ?: 'Perintah $sqlTabel kosong atau gagal.';
        $buatDbSql = (string) $sqlTabel;
    } else {
        // Pastikan kolom penting ada pada tabel pengeluaran
        $chkCols = @mysqli_query($srvConn, 'DESCRIBE pengeluaran');
        $colNames = [];
        if ($chkCols instanceof mysqli_result) {
            while ($c = mysqli_fetch_assoc($chkCols)) {
                $colNames[] = strtolower((string) ($c['Field'] ?? ''));
            }
        }
        $missing = array_diff(['id', 'keterangan', 'nominal', 'tanggal'], $colNames);
        if (!empty($missing)) {
            $buatDbStage = 'Membuat Tabel pengeluaran (Tahap 1B)';
            $buatDbError = 'Tabel pengeluaran belum memiliki kolom: ' . implode(', ', $missing) . '. Cek kembali $sqlTabel di buat_db.php.';
            $buatDbSql = (string) $sqlTabel;
        }
    }
    mysqli_close($srvConn);
}

// 1. Jalankan Tahap 2: koneksi.php milik peserta (hanya jika Tahap 1 sudah lulus)
if ($buatDbError === null) {
    try {
        require $day1Dir . '/koneksi.php';
    } catch (\Throwable $e) {
        render_friendly_php_error(
            'Ups! Ada kesalahan di file koneksi.php',
            $e->getMessage(),
            $e->getFile(),
            (int) $e->getLine()
        );
        exit;
    }

    if (!($koneksi instanceof mysqli) || mysqli_connect_errno() !== 0) {
        $koneksiError = mysqli_connect_error() ?: 'Variabel $koneksi belum tersambung ke database.';
    } else {
        mysqli_set_charset($koneksi, 'utf8mb4');
    }
}

// 2. Proses aksi CRUD (POST: tambah, ubah, hapus) jika Tahap 1 & 2 berhasil
if ($buatDbError === null && $koneksiError === null && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = (string) ($_POST['aksi'] ?? 'tambah');

    if ($aksi === 'tambah') {
        $keteranganRaw = trim((string) ($_POST['keterangan'] ?? ''));
        $nominal       = (int) ($_POST['nominal'] ?? 0);
        $tanggalRaw    = trim((string) ($_POST['tanggal'] ?? date('Y-m-d')));

        if ($keteranganRaw === '' || $nominal <= 0) {
            $actionError = [
                'file'  => '4. tambah.php',
                'title' => 'Data belum lengkap!',
                'msg'   => 'Pastikan keterangan tidak kosong dan nominal lebih dari 0.',
                'sql'   => '',
            ];
        } else {
            $keterangan = mysqli_real_escape_string($koneksi, $keteranganRaw);
            $tanggal    = mysqli_real_escape_string($koneksi, $tanggalRaw);
            $sql        = '';
            $hasil      = false;
            try {
                require $day1Dir . '/tambah.php';
            } catch (\Throwable $e) {
                render_friendly_php_error('Ups! Ada kesalahan di file tambah.php', $e->getMessage(), $e->getFile(), (int) $e->getLine());
                exit;
            }
            if ($hasil) {
                header('Location: index.php?ok=tambah');
                exit;
            }
            $actionError = [
                'file'  => '4. tambah.php',
                'title' => 'Gagal menyimpan data baru (CREATE)',
                'msg'   => mysqli_error($koneksi) ?: 'Query mengembalikan false.',
                'sql'   => (string) $sql,
            ];
        }
    } elseif ($aksi === 'ubah') {
        $id            = (int) ($_POST['id'] ?? 0);
        $keteranganRaw = trim((string) ($_POST['keterangan'] ?? ''));
        $nominal       = (int) ($_POST['nominal'] ?? 0);
        $tanggalRaw    = trim((string) ($_POST['tanggal'] ?? date('Y-m-d')));

        if ($id <= 0 || $keteranganRaw === '' || $nominal <= 0) {
            $actionError = [
                'file'  => '5. ubah.php',
                'title' => 'Data ubah belum lengkap!',
                'msg'   => 'Pastikan keterangan tidak kosong dan nominal lebih dari 0.',
                'sql'   => '',
            ];
        } else {
            $keterangan = mysqli_real_escape_string($koneksi, $keteranganRaw);
            $tanggal    = mysqli_real_escape_string($koneksi, $tanggalRaw);
            $sql        = '';
            $hasil      = false;
            try {
                require $day1Dir . '/ubah.php';
            } catch (\Throwable $e) {
                render_friendly_php_error('Ups! Ada kesalahan di file ubah.php', $e->getMessage(), $e->getFile(), (int) $e->getLine());
                exit;
            }
            if ($hasil) {
                header('Location: index.php?ok=ubah');
                exit;
            }
            $actionError = [
                'file'  => '5. ubah.php',
                'title' => 'Gagal mengubah data (UPDATE)',
                'msg'   => mysqli_error($koneksi) ?: 'Query mengembalikan false.',
                'sql'   => (string) $sql,
            ];
        }
    } elseif ($aksi === 'hapus') {
        $id    = (int) ($_POST['id'] ?? 0);
        $sql   = '';
        $hasil = false;
        try {
            require $day1Dir . '/hapus.php';
        } catch (\Throwable $e) {
            render_friendly_php_error('Ups! Ada kesalahan di file hapus.php', $e->getMessage(), $e->getFile(), (int) $e->getLine());
            exit;
        }
        if ($hasil) {
            header('Location: index.php?ok=hapus');
            exit;
        }
        $actionError = [
            'file'  => '6. hapus.php',
            'title' => 'Gagal menghapus data (DELETE)',
            'msg'   => mysqli_error($koneksi) ?: 'Query mengembalikan false.',
            'sql'   => (string) $sql,
        ];
    }
}

$okFlag = (string) ($_GET['ok'] ?? '');
if ($okFlag === 'tambah') {
    $actionNotice = 'Berhasil! Data baru sudah disimpan ke database.';
} elseif ($okFlag === 'ubah') {
    $actionNotice = 'Berhasil! Perubahan data sudah disimpan ke database.';
} elseif ($okFlag === 'hapus') {
    $actionNotice = 'Berhasil! Data sudah dihapus dari database.';
}

// 3. Jalankan tampil.php milik peserta (jika Tahap 1 & 2 berhasil)
$judul = 'Catatan Pengeluaran';
$tampilResult = null;
$tampilError = null;
$tampilSql = '';
$editRow = null;

if ($buatDbError === null && $koneksiError === null) {
    $sql = '';
    $hasil = false;
    try {
        require $day1Dir . '/tampil.php';
    } catch (\Throwable $e) {
        render_friendly_php_error('Ups! Ada kesalahan di file tampil.php', $e->getMessage(), $e->getFile(), (int) $e->getLine());
        exit;
    }
    $tampilSql = (string) $sql;
    if ($hasil instanceof mysqli_result) {
        $tampilResult = $hasil;
    } else {
        $tampilError = mysqli_error($koneksi) ?: 'Variabel $hasil belum berisi hasil SELECT.';
    }

    // Jika sedang mode edit (?edit=ID), ambil datanya dari DB untuk mengisi form edit
    $editId = (int) ($_GET['edit'] ?? 0);
    if ($editId > 0) {
        $resEdit = @mysqli_query($koneksi, "SELECT * FROM pengeluaran WHERE id = {$editId} LIMIT 1");
        if ($resEdit instanceof mysqli_result) {
            $editRow = mysqli_fetch_assoc($resEdit) ?: null;
        }
    }
}

$rows = [];
$total = 0;
if ($tampilResult instanceof mysqli_result) {
    while ($r = mysqli_fetch_assoc($tampilResult)) {
        $rows[] = $r;
        $total += (int) ($r['nominal'] ?? 0);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e((string) $judul) ?></title>
    <link rel="stylesheet" href="style.css">
    <style>
        .conn-badge { display:inline-flex; align-items:center; gap:6px; padding:2px 8px; border-radius:3px; border:1px solid transparent; font-family:ui-monospace,Consolas,monospace; font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:0.04em; margin-bottom:10px; }
        .conn-ok { background:#f0fdf4; border-color:#bbf7d0; color:#15803d; }
        .conn-fail { background:#fef2f2; border-color:#fecaca; color:#b91c1c; }
        .alert-success { background:#f0fdf4; border:1px solid #bbf7d0; border-left:3px solid #15803d; border-radius:4px; padding:10px 13px; color:#166534; margin-bottom:16px; font-weight:600; font-size:13.5px; }
        .row-actions { display:flex; gap:6px; justify-content:flex-end; align-items:center; }
        .row-actions form { display:inline-flex; margin:0; }
        .btn-sm { display:inline-flex; align-items:center; justify-content:center; height:28px; padding:0 10px; font-family:inherit; font-size:12px; line-height:1; border-radius:3px; border:1px solid #d4d4d8; background:#fff; cursor:pointer; text-decoration:none; font-weight:600; color:#18181b; white-space:nowrap; }
        .btn-edit:hover { background:#f4f4f5; border-color:#18181b; }
        .btn-del { color:#b91c1c; border-color:#fecaca; }
        .btn-del:hover { background:#fef2f2; border-color:#b91c1c; }
        .btn-cancel { background:#fff; color:#18181b; border-color:#d4d4d8; margin-left:6px; }
    </style>
</head>
<body>
<div class="container">

    <?php if ($buatDbError !== null): ?>
        <div class="card">
            <span class="conn-badge conn-fail">&#9679; Database / Tabel Belum Dibuat</span>
            <h1>Tahap 1: Buat Database &amp; Tabel</h1>
            <div class="alert-warning" style="margin-top:14px;">
                <strong><?= e($buatDbStage) ?> belum berhasil.</strong>
                Buka tab <code>1. buat_db.php</code> di editor sebelah kiri, lalu ganti tanda <code>____</code> dengan nama database (<code><?= e($participant['db_name']) ?></code>), tabel (<code>pengeluaran</code>), dan 3 kolom sesuai petunjuk di komentar.
                <div class="code-box">Pesan database: <?= e($buatDbError) ?></div>
                <?php if ($buatDbSql !== ''): ?>
                    <div class="code-box">SQL kamu: <?= e($buatDbSql) ?></div>
                <?php endif; ?>
            </div>
        </div>
    <?php elseif ($koneksiError !== null): ?>
        <div class="card">
            <span class="conn-badge conn-ok">&#10004; Database &amp; Tabel Sudah Dibuat (<?= e($participant['db_name']) ?>)</span>
            <span class="conn-badge conn-fail" style="margin-left:6px;">&#9679; Koneksi PHP Belum Tersambung</span>
            <h1>Tahap 2: Hubungkan PHP ke Database</h1>
            <div class="alert-warning" style="margin-top:14px;">
                <strong>Koneksi di <code>2. koneksi.php</code> belum berhasil.</strong>
                Pindah ke tab <code>2. koneksi.php</code> di editor sebelah kiri, lalu ganti tanda <code>____</code> pada <code>$user</code>, <code>$password</code>, dan <code>$database</code> sesuai petunjuk di komentar.
                <div class="code-box">Pesan database: <?= e($koneksiError) ?></div>
            </div>
        </div>
    <?php else: ?>
        <div class="card">
            <span class="conn-badge conn-ok">&#9679; Database Tersambung (<?= e($participant['db_name']) ?>)</span>
            <h1><?= e((string) $judul) ?></h1>
            <p class="subtitle">Workshop Day 1 — PHP Native + MySQL (CRUD)</p>

            <?php if ($actionNotice !== null): ?>
                <div class="alert-success"><?= e($actionNotice) ?></div>
            <?php endif; ?>

            <?php if ($actionError !== null): ?>
                <div class="alert-warning" style="margin-bottom:16px;">
                    <strong><?= e($actionError['title']) ?></strong>
                    Cek kembali file <code><?= e($actionError['file']) ?></code> di editor sebelah kiri. Pastikan tidak ada lagi tanda <code>____</code>.
                    <div class="code-box">Pesan database: <?= e($actionError['msg']) ?></div>
                    <?php if ($actionError['sql'] !== ''): ?>
                        <div class="code-box">SQL kamu: <?= e($actionError['sql']) ?></div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ($editRow !== null): ?>
                <h2>Ubah Pengeluaran (ID #<?= e((string) $editRow['id']) ?>)</h2>
                <form action="index.php" method="post">
                    <input type="hidden" name="aksi" value="ubah">
                    <input type="hidden" name="id" value="<?= e((string) $editRow['id']) ?>">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="keterangan">Keterangan</label>
                            <input type="text" id="keterangan" name="keterangan" value="<?= e((string) $editRow['keterangan']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="nominal">Nominal (Rp)</label>
                            <input type="number" id="nominal" name="nominal" value="<?= e((string) $editRow['nominal']) ?>" min="1" required>
                        </div>
                        <div class="form-group">
                            <label for="tanggal">Tanggal</label>
                            <input type="date" id="tanggal" name="tanggal" value="<?= e((string) $editRow['tanggal']) ?>" required>
                        </div>
                    </div>
                    <button type="submit" class="btn">Simpan Perubahan (UPDATE)</button>
                    <a href="index.php" class="btn btn-cancel">Batal</a>
                </form>
            <?php else: ?>
                <h2>Tambah Pengeluaran Baru</h2>
                <form action="index.php" method="post">
                    <input type="hidden" name="aksi" value="tambah">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="keterangan">Keterangan</label>
                            <input type="text" id="keterangan" name="keterangan" placeholder="Contoh: Beli kopi" required>
                        </div>
                        <div class="form-group">
                            <label for="nominal">Nominal (Rp)</label>
                            <input type="number" id="nominal" name="nominal" placeholder="15000" min="1" required>
                        </div>
                        <div class="form-group">
                            <label for="tanggal">Tanggal</label>
                            <input type="date" id="tanggal" name="tanggal" value="<?= date('Y-m-d') ?>" required>
                        </div>
                    </div>
                    <button type="submit" class="btn">+ Simpan Pengeluaran</button>
                </form>
            <?php endif; ?>
        </div>

        <div class="card">
            <h2>Daftar Pengeluaran</h2>
            <?php if ($tampilError !== null): ?>
                <div class="alert-warning">
                    <strong>Daftar pengeluaran belum bisa ditampilkan (READ).</strong>
                    Buka tab <code>3. tampil.php</code> di editor sebelah kiri, lalu ganti <code>____</code> dengan nama tabel <code>pengeluaran</code>!
                    <div class="code-box">Pesan database: <?= e($tampilError) ?></div>
                    <?php if ($tampilSql !== ''): ?>
                        <div class="code-box">SQL kamu: <?= e($tampilSql) ?></div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Keterangan</th>
                            <th class="text-right">Nominal</th>
                            <th class="text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($rows)): ?>
                            <tr><td colspan="4" class="empty-state">Belum ada data pengeluaran. Coba tambah lewat form di atas!</td></tr>
                        <?php else: ?>
                            <?php foreach ($rows as $baris): ?>
                                <tr>
                                    <td><?= e((string) ($baris['tanggal'] ?? '')) ?></td>
                                    <td><?= e((string) ($baris['keterangan'] ?? '')) ?></td>
                                    <td class="text-right">Rp <?= number_format((int) ($baris['nominal'] ?? 0), 0, ',', '.') ?></td>
                                    <td class="text-right">
                                        <div class="row-actions">
                                            <a class="btn-sm btn-edit" href="index.php?edit=<?= e((string) ($baris['id'] ?? '')) ?>">Ubah</a>
                                            <form action="index.php" method="post" style="margin:0;" onsubmit="return confirm('Yakin hapus data ini?');">
                                                <input type="hidden" name="aksi" value="hapus">
                                                <input type="hidden" name="id" value="<?= e((string) ($baris['id'] ?? '')) ?>">
                                                <button type="submit" class="btn-sm btn-del">Hapus</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <tr class="total-row">
                                <td colspan="2">Total Pengeluaran</td>
                                <td class="text-right">Rp <?= number_format($total, 0, ',', '.') ?></td>
                                <td></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    <?php endif; ?>

</div>
</body>
</html>
