<?php

declare(strict_types=1);

start_workshop_session();

// Jika peserta sudah login di session, langsung arahkan ke dashboardnya (kecuali ?stay=1)
if (!empty($_SESSION['participant_token']) && empty($_GET['stay'])) {
    $existing = find_participant_by_token((string) $_SESSION['participant_token']);
    if ($existing) {
        header('Location: /p/' . $existing['token'] . '/');
        exit;
    }
    unset($_SESSION['participant_token']);
}

$activeTab = ($_GET['tab'] ?? '') === 'login' ? 'login' : 'register';
$error = null;
$nimInput = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? 'register');
    $nimInput = trim((string) ($_POST['nim'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($action === 'register') {
        $activeTab = 'register';
        $res = register_participant_by_nim($nimInput, $password);
        if ($res['ok']) {
            $p = $res['participant'];
            session_regenerate_id(true);
            $_SESSION['participant_token'] = $p['token'];
            header('Location: /p/' . $p['token'] . '/');
            exit;
        }
        $error = $res['error'] ?? 'Gagal mendaftar.';
    } elseif ($action === 'login') {
        $activeTab = 'login';
        $p = find_participant_by_nim($nimInput);
        $hash = (string) ($p['password_hash'] ?? '');
        $okPass = false;
        if ($p) {
            if ($hash !== '') {
                $okPass = password_verify($password, $hash);
            } else {
                // Fallback kompatibilitas untuk peserta lama tanpa hash
                $okPass = hash_equals((string) ($p['db_pass'] ?? ''), $password);
            }
        }
        if ($p && $okPass) {
            session_regenerate_id(true);
            $_SESSION['participant_token'] = $p['token'];
            header('Location: /p/' . $p['token'] . '/');
            exit;
        }
        $error = 'NIM atau password tidak cocok. Jika belum punya akun, daftar di tab Daftar.';
    }
}

$reg = load_registry();
$participants = $reg['participants'] ?? [];
$isDemoMode = (count($participants) === 1 && ($participants[0]['id'] ?? '') === 'p01');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk / Daftar — Workshop PHP + Database &amp; Laravel</title>
    <link rel="stylesheet" href="/assets/app.css">
    <style>
        .auth-wrap { max-width: 440px; margin: 44px auto; }
        .auth-tabs { display: flex; gap: 0; border-bottom: 1px solid #e4e4e7; margin-bottom: 22px; }
        .auth-tab { flex: 1; border: none; border-bottom: 2px solid transparent; background: transparent; padding: 10px 6px; font-size: 13.5px; font-weight: 600; cursor: pointer; color: #71717a; margin-bottom: -1px; font-family: inherit; }
        .auth-tab:hover { color: #18181b; }
        .auth-tab.active { color: #18181b; border-bottom-color: #18181b; }
        .fg { margin-bottom: 16px; }
        .fg label { display: block; font-family: var(--mono); font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.06em; color: #52525b; margin-bottom: 5px; }
        .fg input { width: 100%; padding: 10px 12px; border: 1px solid #d4d4d8; border-radius: 4px; font-size: 14.5px; font-family: inherit; color: #18181b; background: #fff; }
        .fg input:focus { outline: none; border-color: #18181b; }
        .pw-wrap { position: relative; }
        .pw-wrap input { padding-right: 42px; }
        .pw-toggle { position: absolute; right: 4px; top: 50%; transform: translateY(-50%); width: 32px; height: 32px; border: none; background: transparent; color: #71717a; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; border-radius: 4px; padding: 0; }
        .pw-toggle:hover { color: #18181b; background: #f4f4f5; }
        .pw-toggle svg { width: 18px; height: 18px; }
        .pw-toggle .icon-hide { display: none; }
        .pw-toggle[aria-pressed="true"] .icon-show { display: none; }
        .pw-toggle[aria-pressed="true"] .icon-hide { display: block; }
        .fg .hint { font-size: 12px; color: #71717a; margin-top: 5px; }
        .err-box { background: #fef2f2; border: 1px solid #fecaca; border-left: 3px solid #b91c1c; color: #991b1b; padding: 10px 12px; border-radius: 4px; font-size: 13.5px; margin-bottom: 16px; }
        .pane { display: none; }
        .pane.active { display: block; }
    </style>
</head>
<body>
<div class="topbar">
    <div class="brand">Workshop SI — PHP + Database &amp; Laravel</div>
</div>

<div class="container">
    <div class="card auth-wrap">
        <span class="badge">Sistem Informasi • Monthly Sharing 2 Hari</span>
        <h1 style="font-size:25px;">Selamat Datang di Server Workshop</h1>
        <p class="lead" style="font-size:14.5px; margin-bottom:18px;">
            Daftar dengan <strong>NIM</strong> untuk akses workspace pribadi.
        </p>

        <div class="auth-tabs">
            <button type="button" class="auth-tab<?= $activeTab === 'register' ? ' active' : '' ?>" data-tab="register">Daftar</button>
            <button type="button" class="auth-tab<?= $activeTab === 'login' ? ' active' : '' ?>" data-tab="login">Masuk</button>
        </div>

        <?php if ($error): ?>
            <div class="err-box"><?= e($error) ?></div>
        <?php endif; ?>

        <!-- Form Register -->
        <form id="form-register" class="pane<?= $activeTab === 'register' ? ' active' : '' ?>" method="post" action="/">
            <input type="hidden" name="action" value="register">
            <div class="fg">
                <label for="reg-nim">NIM</label>
                <input type="text" id="reg-nim" name="nim" value="<?= e($activeTab === 'register' ? $nimInput : '') ?>" placeholder="Contoh: f12202600069" autocomplete="username" required>
                <div class="hint">Huruf kecil tanpa titik. NIM akan digunakan sebagai nama database (<code>db_nim</code>).</div>
            </div>
            <div class="fg">
                <label for="reg-pass">Password</label>
                <div class="pw-wrap">
                    <input type="password" id="reg-pass" name="password" autocomplete="new-password" minlength="4" required>
                    <button type="button" class="pw-toggle" data-target="reg-pass" aria-label="Tampilkan password" aria-pressed="false" title="Tampilkan / sembunyikan password">
                        <svg class="icon-show" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                        <svg class="icon-hide" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c6.5 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/><path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3.5 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/><line x1="2" x2="22" y1="2" y2="22"/></svg>
                    </button>
                </div>
                <div class="hint">Ingat password untuk Day 2.</div>
            </div>
            <button type="submit" class="btn btn-primary btn-lg" style="width:100%;">Daftar &amp; Masuk Dashboard</button>
        </form>

        <!-- Form Login -->
        <form id="form-login" class="pane<?= $activeTab === 'login' ? ' active' : '' ?>" method="post" action="/">
            <input type="hidden" name="action" value="login">
            <div class="fg">
                <label for="login-nim">NIM</label>
                <input type="text" id="login-nim" name="nim" value="<?= e($activeTab === 'login' ? $nimInput : '') ?>" placeholder="Masukkan NIM yang sudah didaftarkan" autocomplete="username" required>
            </div>
            <div class="fg">
                <label for="login-pass">Password</label>
                <div class="pw-wrap">
                    <input type="password" id="login-pass" name="password" placeholder="Masukkan password kamu" autocomplete="current-password" required>
                    <button type="button" class="pw-toggle" data-target="login-pass" aria-label="Tampilkan password" aria-pressed="false" title="Tampilkan / sembunyikan password">
                        <svg class="icon-show" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                        <svg class="icon-hide" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c6.5 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/><path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3.5 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/><line x1="2" x2="22" y1="2" y2="22"/></svg>
                    </button>
                </div>
            </div>
            <button type="submit" class="btn btn-success btn-lg" style="width:100%;">Masuk Dashboard</button>
        </form>

        <?php if ($isDemoMode): ?>
            <div style="margin-top:18px; padding-top:14px; border-top:1px solid #e2e8f0; text-align:center;">
                <a class="btn btn-outline" href="/p/<?= e($participants[0]['token']) ?>/" style="width:100%;">Masuk Mode Demo (Peserta 01) &rarr;</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.querySelectorAll('.auth-tab').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.auth-tab').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.pane').forEach(p => p.classList.remove('active'));
        btn.classList.add('active');
        document.getElementById('form-' + btn.dataset.tab).classList.add('active');
    });
});

document.querySelectorAll('.pw-toggle').forEach(btn => {
    btn.addEventListener('click', () => {
        const input = document.getElementById(btn.dataset.target);
        if (!input) return;
        const showing = input.type === 'text';
        input.type = showing ? 'password' : 'text';
        btn.setAttribute('aria-pressed', showing ? 'false' : 'true');
        btn.setAttribute('aria-label', showing ? 'Tampilkan password' : 'Sembunyikan password');
    });
});

document.getElementById('form-register').addEventListener('submit', function(ev) {
    const nimEl = document.getElementById('reg-nim');
    const nim = (nimEl.value || '').trim().toLowerCase();
    nimEl.value = nim;
    if (!/^[a-z0-9]{4,24}$/.test(nim)) {
        ev.preventDefault();
        alert('NIM harus 4-24 karakter huruf/angka saja tanpa spasi atau tanda baca (contoh: f12202600069).');
        return;
    }
    const ok = confirm('Pastikan NIM kamu sudah benar:\n\n' + nim + '\n\nNIM ini akan dipakai sebagai akun dan nama database (db_' + nim + '). Lanjutkan daftar?');
    if (!ok) {
        ev.preventDefault();
    }
});
</script>
</body>
</html>
