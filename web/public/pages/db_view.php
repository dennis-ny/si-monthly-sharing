<?php

declare(strict_types=1);

$token = $participant['token'];
$label = $participant['label'];
$pmaUrl = get_pma_url();

mysqli_report(MYSQLI_REPORT_OFF);
$dbHost = getenv('DB_HOST') ?: 'db';
$conn = @mysqli_connect($dbHost, $participant['db_user'], $participant['db_pass'], $participant['db_name']);
$dbNotReady = false;
$dbError = null;
if (!$conn) {
    $errCode = mysqli_connect_errno();
    if ($errCode === 1049) {
        $dbNotReady = true;
    } else {
        $dbError = mysqli_connect_error();
    }
}

$columns = [];
$rows = [];
if ($conn) {
    mysqli_set_charset($conn, 'utf8mb4');
    $resDesc = @mysqli_query($conn, 'DESCRIBE pengeluaran');
    if ($resDesc) {
        while ($c = mysqli_fetch_assoc($resDesc)) {
            $columns[] = $c;
        }
    } else {
        $dbNotReady = true;
    }
    if (!$dbNotReady) {
        $resRows = @mysqli_query($conn, 'SELECT * FROM pengeluaran ORDER BY id DESC');
        if ($resRows) {
            while ($r = mysqli_fetch_assoc($resRows)) {
                $rows[] = $r;
            }
        }
    }
    mysqli_close($conn);
}
$rowCount = count($rows);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lihat Database — <?= e($label) ?></title>
    <link rel="stylesheet" href="/assets/app.css">
    <style>
        .pma-shell {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(15, 23, 42, 0.06);
        }
        .pma-crumb {
            background: linear-gradient(180deg, #f8fafc 0%, #e2e8f0 100%);
            border-bottom: 1px solid #cbd5e1;
            padding: 10px 16px;
            font-size: 13px;
            color: #334155;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 10px;
        }
        .pma-crumb-path {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
            font-weight: 500;
        }
        .pma-crumb-path strong {
            color: #0f172a;
        }
        .pma-tabs {
            display: flex;
            background: #f1f5f9;
            border-bottom: 1px solid #cbd5e1;
            padding: 8px 16px 0;
            gap: 4px;
        }
        .pma-tab {
            border: 1px solid transparent;
            border-bottom: none;
            background: transparent;
            color: #475569;
            padding: 7px 14px;
            font-size: 13px;
            font-weight: 600;
            border-radius: 6px 6px 0 0;
            cursor: pointer;
        }
        .pma-tab:hover {
            background: #e2e8f0;
            color: #0f172a;
        }
        .pma-tab.active {
            background: #ffffff;
            color: #1d4ed8;
            border-color: #cbd5e1;
            margin-bottom: -1px;
            padding-bottom: 8px;
        }
        .pma-body {
            padding: 16px;
        }
        .pma-sql-bar {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 6px;
            padding: 9px 12px;
            margin-bottom: 14px;
            font-size: 13px;
        }
        .pma-sql-meta {
            color: #166534;
            font-weight: 600;
            margin-bottom: 4px;
        }
        .pma-sql-code {
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
            font-size: 12.5px;
            color: #0f172a;
            background: #ffffff;
            border: 1px solid #dcfce7;
            padding: 6px 9px;
            border-radius: 4px;
            display: block;
        }
        .pma-table-wrap {
            overflow-x: auto;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
        }
        table.pma-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
            font-family: system-ui, -apple-system, sans-serif;
        }
        table.pma-table thead th {
            background: linear-gradient(180deg, #f8fafc 0%, #e5ebf1 100%);
            color: #1e293b;
            font-weight: 700;
            padding: 7px 10px;
            border-right: 1px solid #cbd5e1;
            border-bottom: 1px solid #94a3b8;
            white-space: nowrap;
            font-size: 12.5px;
        }
        table.pma-table thead th:last-child,
        table.pma-table tbody td:last-child {
            border-right: none;
        }
        table.pma-table tbody td {
            padding: 6px 10px;
            border-right: 1px solid #e2e8f0;
            border-bottom: 1px solid #e2e8f0;
            color: #0f172a;
            vertical-align: middle;
        }
        table.pma-table tbody tr:nth-child(even) {
            background: #f8fafc;
        }
        table.pma-table tbody tr:hover {
            background: #fef9c3;
        }
        table.pma-table .col-num {
            text-align: right;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
            font-variant-numeric: tabular-nums;
            white-space: nowrap;
        }
        table.pma-table .col-mono {
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
            font-size: 12.5px;
        }
        .pma-pk {
            color: #ca8a04;
            font-weight: 700;
            margin-right: 4px;
            font-size: 12px;
        }
        .pma-null {
            color: #94a3b8;
            font-style: italic;
        }
        .pma-footer {
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
            padding: 10px 16px;
            font-size: 12.5px;
            color: #475569;
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 8px;
        }
        .pma-btn {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 11px;
            font-size: 12.5px;
            font-weight: 600;
            border-radius: 5px;
            border: 1px solid #cbd5e1;
            background: #ffffff;
            color: #1e293b;
            text-decoration: none;
            cursor: pointer;
        }
        .pma-btn:hover { background: #f1f5f9; }
        .pma-btn-primary {
            background: #2563eb;
            border-color: #1d4ed8;
            color: #ffffff;
        }
        .pma-btn-primary:hover { background: #1d4ed8; }
        .pma-panel { display: none; }
        .pma-panel.active { display: block; }
    </style>
</head>
<body>
<div class="topbar">
    <div class="brand">Database <?= e($label) ?> (<code><?= e($participant['db_name']) ?></code>)</div>
    <div class="nav">
        <a href="/p/<?= e($token) ?>/">&larr; Kembali ke Portal</a>
        <a href="/p/<?= e($token) ?>/editor">Editor Kode</a>
    </div>
</div>

<div class="container">
    <div class="pma-shell">
        <div class="pma-crumb">
            <div class="pma-crumb-path">
                <span>&#128421; Server: <strong>db</strong></span>
                <span>&rsaquo;</span>
                <span>&#128452; Database: <strong><?= e($participant['db_name']) ?></strong></span>
                <span>&rsaquo;</span>
                <span>&#128203; Tabel: <strong>pengeluaran</strong></span>
            </div>
            <div style="display:flex; gap:8px; flex-wrap:wrap;">
                <a class="pma-btn pma-btn-primary" href="/p/<?= e($token) ?>/db?t=<?= time() ?>">&#8635; Muat Ulang</a>
                <?php if ($pmaUrl !== ''): ?>
                    <a class="pma-btn" href="<?= e($pmaUrl) ?>" target="_blank">Buka phpMyAdmin &nearr;</a>
                <?php endif; ?>
            </div>
        </div>

        <div class="pma-tabs" role="tablist">
            <button type="button" class="pma-tab active" data-tab="browse">&#128269; Jelajahi (<?= $rowCount ?>)</button>
            <button type="button" class="pma-tab" data-tab="structure">&#128295; Struktur (<?= count($columns) ?> kolom)</button>
        </div>

        <div class="pma-body">
            <?php if ($dbNotReady): ?>
                <div style="padding:18px; background:#fff7ed; border:1px dashed #fdba74; border-radius:4px; color:#9a3412;">
                    <strong style="color:#7c2d12; font-size:14.5px;">Database <code><?= e($participant['db_name']) ?></code> atau tabel <code>pengeluaran</code> belum dibuat.</strong>
                    <ol style="margin:10px 0 0; padding-left:20px; font-size:13.5px; line-height:1.75; color:#9a3412;">
                        <li>Buka <a href="/p/<?= e($token) ?>/editor" style="color:#7c2d12; font-weight:600;">Editor Kode</a></li>
                        <li>Selesaikan tab <code>1. buat_db.php</code></li>
                        <li>Klik <strong>Simpan &amp; Jalankan</strong></li>
                        <li>Klik <strong>Muat Ulang</strong> di halaman ini</li>
                    </ol>
                </div>
            <?php elseif ($dbError): ?>
                <div style="padding:14px; background:#fef2f2; border:1px solid #ef4444; border-radius:8px; color:#991b1b;">
                    Gagal terhubung ke database: <?= e($dbError) ?>
                </div>
            <?php else: ?>
                <!-- Panel 1: Jelajahi (Browse) -->
                <div id="panel-browse" class="pma-panel active">
                    <div class="pma-sql-bar">
                        <div class="pma-sql-meta">
                            &#10004; Menampilkan baris <?= $rowCount > 0 ? '0 - ' . ($rowCount - 1) : '0' ?> (Data Saat Ini (<?= $rowCount ?> baris))
                        </div>
                        <code class="pma-sql-code">SELECT * FROM `pengeluaran` ORDER BY `id` DESC;</code>
                    </div>

                    <div class="pma-table-wrap">
                        <table class="pma-table">
                            <thead>
                                <tr>
                                    <th style="width:70px; text-align:right;"><span class="pma-pk" title="Primary Key">&#128273;</span>id</th>
                                    <th>keterangan</th>
                                    <th style="width:140px; text-align:right;">nominal</th>
                                    <th style="width:130px;">tanggal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($rows)): ?>
                                    <tr><td colspan="4" style="text-align:center; padding:20px; color:#64748b;">Tabel masih kosong (0 baris).</td></tr>
                                <?php else: ?>
                                    <?php foreach ($rows as $r): ?>
                                        <tr>
                                            <td class="col-num"><?= e((string) $r['id']) ?></td>
                                            <td><?= e((string) $r['keterangan']) ?></td>
                                            <td class="col-num"><?= e((string) $r['nominal']) ?></td>
                                            <td class="col-mono"><?= e((string) $r['tanggal']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Panel 2: Struktur (Structure) -->
                <div id="panel-structure" class="pma-panel">
                    <div class="pma-sql-bar">
                        <div class="pma-sql-meta">&#128295; Struktur tabel <code>pengeluaran</code></div>
                        <code class="pma-sql-code">DESCRIBE `pengeluaran`;</code>
                    </div>

                    <div class="pma-table-wrap">
                        <table class="pma-table">
                            <thead>
                                <tr>
                                    <th style="width:45px; text-align:right;">#</th>
                                    <th>Nama</th>
                                    <th>Jenis</th>
                                    <th>Tak Ternilai (Null)</th>
                                    <th>Indeks</th>
                                    <th>Bawaan</th>
                                    <th>Ekstra</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($columns as $idx => $c): ?>
                                    <?php $isPk = (($c['Key'] ?? '') === 'PRI'); ?>
                                    <tr>
                                        <td class="col-num"><?= $idx + 1 ?></td>
                                        <td class="col-mono">
                                            <?php if ($isPk): ?><span class="pma-pk" title="Primary Key">&#128273;</span><?php endif; ?>
                                            <strong><?= e((string) $c['Field']) ?></strong>
                                        </td>
                                        <td class="col-mono"><?= e((string) $c['Type']) ?></td>
                                        <td><?= ($c['Null'] ?? '') === 'YES' ? 'Ya' : 'Tidak' ?></td>
                                        <td class="col-mono"><?= $isPk ? 'PRIMARY' : '<span class="pma-null">-</span>' ?></td>
                                        <td class="col-mono"><?= $c['Default'] !== null ? e((string) $c['Default']) : '<span class="pma-null">Tidak ada</span>' ?></td>
                                        <td class="col-mono"><?= !empty($c['Extra']) ? e(strtoupper((string) $c['Extra'])) : '<span class="pma-null">-</span>' ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="pma-footer" style="display:block;">
            <div style="font-size:11.5px; font-family:var(--mono); text-transform:uppercase; letter-spacing:0.05em; color:#71717a; margin-bottom:6px; font-weight:600;">Kredensial DB (koneksi.php / phpMyAdmin)</div>
            <ul style="margin:0; padding-left:18px; display:grid; grid-template-columns:repeat(auto-fit, minmax(170px, 1fr)); gap:4px 16px; font-size:13px; color:#3f3f46;">
                <li>Host: <code>db</code></li>
                <li>User: <code><?= e($participant['db_user']) ?></code></li>
                <li>Password: <code><?= e($participant['db_pass']) ?></code></li>
                <li>Database: <code><?= e($participant['db_name']) ?></code></li>
            </ul>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.pma-tab').forEach(function(btn) {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.pma-tab').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.pma-panel').forEach(p => p.classList.remove('active'));
        btn.classList.add('active');
        const panel = document.getElementById('panel-' + btn.dataset.tab);
        if (panel) panel.classList.add('active');
    });
});
</script>
</body>
</html>
