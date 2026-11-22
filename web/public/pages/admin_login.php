<?php

declare(strict_types=1);

start_workshop_session();
if (!empty($_SESSION['is_admin'])) {
    header('Location: /admin');
    exit;
}

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = trim((string) ($_POST['username'] ?? ''));
    $pass = (string) ($_POST['password'] ?? '');
    $expected = getenv('ADMIN_PASSWORD') ?: 'instruktur2026';

    if ($user === 'admin' && hash_equals($expected, $pass)) {
        session_regenerate_id(true);
        $_SESSION['is_admin'] = true;
        header('Location: /admin');
        exit;
    }
    $error = 'Username atau password admin salah.';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin — Workshop</title>
    <link rel="stylesheet" href="/assets/app.css">
    <style>
        .login-box { max-width: 420px; margin: 56px auto; }
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
        .err { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; padding: 10px 12px; border-radius: 4px; font-size: 13.5px; margin-bottom: 16px; }
    </style>
</head>
<body>
<div class="topbar">
    <div class="brand">Workshop SI — Dashboard Admin</div>
    <div class="nav">
        <a href="/">&larr; Halaman Peserta</a>
    </div>
</div>

<div class="container">
    <div class="card login-box">
        <span class="badge">Dashboard Admin</span>
        <h1 style="font-size:24px;">Masuk Dashboard</h1>
        <p class="lead" style="font-size:14px;">Masuk dengan akun admin untuk mengakses dashboard pengelolaan workshop.</p>

        <?php if ($error): ?>
            <div class="err"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" action="/admin/login">
            <div class="fg">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" value="admin" required>
            </div>
            <div class="fg">
                <label for="password">Password</label>
                <div class="pw-wrap">
                    <input type="password" id="password" name="password" autofocus required>
                    <button type="button" class="pw-toggle" data-target="password" aria-label="Tampilkan password" aria-pressed="false" title="Tampilkan / sembunyikan password">
                        <svg class="icon-show" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                        <svg class="icon-hide" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c6.5 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/><path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3.5 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/><line x1="2" x2="22" y1="2" y2="22"/></svg>
                    </button>
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-lg" style="width:100%;">Masuk Dashboard</button>
        </form>
    </div>
</div>
<script>
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
</script>
</body>
</html>
