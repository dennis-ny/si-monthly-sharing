<?php

declare(strict_types=1);

$token = $participant['token'];
$nimDisp = (string) ($participant['nim'] ?? $participant['label']);
$laravelOn = is_laravel_enabled();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard: <?= e($nimDisp) ?> — Workshop</title>
    <link rel="stylesheet" href="/assets/app.css">
</head>
<body>
<div class="topbar">
    <div class="brand">Dashboard: <code><?= e($nimDisp) ?></code></div>
    <div class="nav">
        <a href="/logout" style="color:#b91c1c;">Logout</a>
    </div>
</div>

<div class="container">
    <div class="card">
        <span class="badge badge-ok">&#9679; Day 1 • PHP Native + Database MySQL</span>
        <h1>Halo, <?= e($nimDisp) ?>!</h1>
        <p class="lead">
            Database pribadi: <code><?= e($participant['db_name']) ?></code> &bull;
            User/Password database: <code><?= e($participant['db_user']) ?></code>
        </p>

        <div class="btn-grid">
            <a class="btn btn-primary btn-lg" href="/p/<?= e($token) ?>/editor">Buka Editor Kode</a>
            <a class="btn btn-success btn-lg" href="/p/<?= e($token) ?>/app/" target="_blank">Lihat Hasil (Tab Baru)</a>
            <a class="btn btn-dark btn-lg" href="/p/<?= e($token) ?>/db" target="_blank">Lihat Database</a>
        </div>

        <h2 style="margin-top:24px;">Urutan Belajar Singkat (6 Tahap)</h2>
        <div class="steps">
            <div class="step-box">
                <div class="step-num">01 / KONEKSI</div>
                <h3>Buat DB &amp; Koneksi</h3>
                <p style="margin:0; font-size:13.5px; color:#52525b;">Selesaikan <code>1. buat_db.php</code> lalu <code>2. koneksi.php</code> sampai database tersambung.</p>
            </div>
            <div class="step-box">
                <div class="step-num">02 / READ &amp; CREATE</div>
                <h3>Tampil &amp; Tambah</h3>
                <p style="margin:0; font-size:13.5px; color:#52525b;">Lengkapi <code>3. tampil.php</code> (<code>SELECT</code>) dan <code>4. tambah.php</code> (<code>INSERT</code>).</p>
            </div>
            <div class="step-box">
                <div class="step-num">03 / UPDATE &amp; DELETE</div>
                <h3>Ubah &amp; Hapus</h3>
                <p style="margin:0; font-size:13.5px; color:#52525b;">Lengkapi <code>5. ubah.php</code> (<code>UPDATE</code>) dan <code>6. hapus.php</code> (<code>DELETE</code>).</p>
            </div>
        </div>
    </div>

    <div class="card">
        <span class="badge badge-laravel">Day 2 • Pengenalan Laravel</span>
        <h2>Aplikasi yang Sama, Pakai Laravel</h2>
        <?php if ($laravelOn): ?>
            <p class="lead">Membaca database yang sama persis dengan Day 1 menggunakan Laravel (Migration + MVC).</p>
            <div class="btn-grid">
                <a class="btn btn-laravel btn-lg" href="/p/<?= e($token) ?>/editor-laravel">Editor Laravel</a>
                <a class="btn btn-outline btn-lg" href="/p/<?= e($token) ?>/laravel/" target="_blank">Lihat Hasil Laravel (Tab Baru)</a>
            </div>
        <?php else: ?>
            <div style="padding:16px 18px; background:#fff7ed; border:1px dashed #fdba74; border-radius:4px; color:#9a3412;">
                <strong style="color:#7c2d12; font-size:14.5px;">Materi Day 2 (Laravel) belum dibuka.</strong>
                <p style="margin:4px 0 0; font-size:13.5px; color:#9a3412;">Selesaikan praktek Day 1 (PHP + Database) terlebih dahulu. Menu Laravel akan muncul otomatis saat Day 2.</p>
            </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
