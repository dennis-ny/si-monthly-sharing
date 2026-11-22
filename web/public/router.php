<?php

declare(strict_types=1);

require_once __DIR__ . '/lib/common.php';

$uriPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$uriPath = rtrim($uriPath, '/') ?: '/';

// Healthcheck
if ($uriPath === '/health') {
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'ok';
    exit;
}

// Halaman publik / peserta
if ($uriPath === '/') {
    require __DIR__ . '/pages/home.php';
    exit;
}
if ($uriPath === '/logout') {
    start_workshop_session();
    unset($_SESSION['participant_token']);
    header('Location: /?tab=login');
    exit;
}

// Login/Logout & Panel Admin (session + HTTP Basic Auth)
if ($uriPath === '/admin/login') {
    require __DIR__ . '/pages/admin_login.php';
    exit;
}
if ($uriPath === '/admin/logout') {
    start_workshop_session();
    unset($_SESSION['is_admin']);
    header('Location: /admin/login');
    exit;
}
if ($uriPath === '/admin/action' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_admin_auth();
    $aksi = (string) ($_POST['aksi'] ?? '');
    $msg = '';
    if ($aksi === 'toggle_laravel') {
        $enable = ((string) ($_POST['enable'] ?? '0')) === '1';
        save_settings(['laravel_enabled' => $enable]);
        $msg = $enable ? 'Halaman Laravel (Day 2) telah DIAKTIFKAN.' : 'Halaman Laravel (Day 2) telah DINONAKTIFKAN (Mode Day 1).';
    } elseif ($aksi === 'delete_user') {
        $id = (string) ($_POST['id'] ?? '');
        $ok = delete_participant_by_id($id);
        $msg = $ok ? "Akun peserta ({$id}) beserta database dan filenya telah dihapus." : 'Gagal menghapus akun peserta.';
    } elseif ($aksi === 'reset_pw') {
        $id = (string) ($_POST['id'] ?? '');
        $newPw = reset_participant_password_by_id($id);
        $msg = $newPw !== null ? "Password login peserta ({$id}) telah direset menjadi: {$newPw}" : 'Gagal mereset password.';
    } elseif ($aksi === 'reset_workspace') {
        $id = (string) ($_POST['id'] ?? '');
        $ok = reset_participant_workspace_by_id($id);
        $msg = $ok ? "Workspace peserta ({$id}) telah direset ke awal (semua file & database dikosongkan)." : 'Gagal mereset workspace peserta.';
    }
    header('Location: /admin?msg=' . rawurlencode($msg));
    exit;
}
if ($uriPath === '/admin') {
    require_admin_auth();
    require __DIR__ . '/pages/admin.php';
    exit;
}
if ($uriPath === '/admin/export.csv') {
    require_admin_auth();
    $reg = load_registry();
    $baseUrl = get_public_base_url();
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="peserta-workshop.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['nomor', 'id', 'label', 'token', 'url_portal', 'db_name', 'db_user', 'db_pass']);
    foreach ($reg['participants'] ?? [] as $p) {
        fputcsv($out, [
            $p['number'],
            $p['id'],
            $p['label'],
            $p['token'],
            $baseUrl . '/p/' . $p['token'] . '/',
            $p['db_name'],
            $p['db_user'],
            $p['db_pass'],
        ]);
    }
    fclose($out);
    exit;
}

// Route peserta: /p/<token>, /p/<token>/editor, /p/<token>/editor-laravel, /p/<token>/db
if (preg_match('#^/p/([a-zA-Z0-9]+)(?:/(.*))?$#', $uriPath, $m)) {
    $token = $m[1];
    $sub   = $m[2] ?? '';
    $participant = find_participant_by_token($token);
    if (!$participant) {
        http_response_code(404);
        echo '<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><title>Link Tidak Valid</title>'
            . '<link rel="stylesheet" href="/assets/app.css"></head><body>'
            . '<div class="container"><div class="card"><span class="badge">404 • Tidak Ditemukan</span><h1>Link peserta tidak ditemukan</h1>'
            . '<p class="lead">Pastikan alamat yang kamu buka sudah benar, atau minta bantuan panitia.</p>'
            . '<a class="btn btn-primary" href="/">Kembali ke Dashboard</a></div></div></body></html>';
        exit;
    }

    if ($sub === '' || $sub === 'index.php') {
        require __DIR__ . '/pages/portal.php';
        exit;
    }
    if ($sub === 'editor') {
        $editorMode = 'day1';
        require __DIR__ . '/pages/editor.php';
        exit;
    }
    if ($sub === 'editor-laravel') {
        if (!is_laravel_enabled()) {
            http_response_code(403);
            echo '<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><title>Materi Day 2 Belum Dibuka</title>'
                . '<link rel="stylesheet" href="/assets/app.css"></head><body>'
                . '<div class="container"><div class="card"><span class="badge badge-warn">Nonaktif</span>'
                . '<h1>Editor Laravel (Day 2) belum dibuka</h1>'
                . '<p class="lead">Halaman ini masih nonaktif. Materi Day 2 (Laravel) akan dibuka pada sesi berikutnya.</p>'
                . '<a class="btn btn-primary" href="/p/' . e($token) . '/">&larr; Kembali ke Dashboard</a>'
                . '</div></div></body></html>';
            exit;
        }
        $editorMode = 'day2';
        require __DIR__ . '/pages/editor.php';
        exit;
    }
    if ($sub === 'db') {
        require __DIR__ . '/pages/db_view.php';
        exit;
    }
}

http_response_code(404);
echo '<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><title>Halaman Tidak Ditemukan</title>'
    . '<link rel="stylesheet" href="/assets/app.css"></head><body>'
    . '<div class="container"><div class="card"><span class="badge">404</span><h1>Halaman tidak ditemukan</h1>'
    . '<p class="lead">Alamat <code>' . e($uriPath) . '</code> tidak tersedia.</p>'
    . '<a class="btn btn-primary" href="/">Kembali ke Dashboard</a></div></div></body></html>';
