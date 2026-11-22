<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit("Hanya bisa dijalankan lewat CLI.\n");
}

require_once '/var/www/html/lib/common.php';

$reg = load_registry();
$participants = $reg['participants'] ?? [];

$dbHost = getenv('DB_HOST') ?: 'db';
$dbRootPass = getenv('MYSQL_ROOT_PASSWORD') ?: 'workshop_root_2026';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$mysqli = new mysqli($dbHost, 'root', $dbRootPass);
$mysqli->set_charset('utf8mb4');

$count = 0;
foreach ($participants as $p) {
    $dbName = preg_replace('/[^a-z0-9_]/', '', $p['db_name'] ?? '');
    $dbUser = preg_replace('/[^a-z0-9_]/', '', $p['db_user'] ?? '');
    $id     = preg_replace('/[^a-z0-9_]/', '', $p['id'] ?? '');

    if ($dbName !== '') {
        $mysqli->query("DROP DATABASE IF EXISTS `{$dbName}`");
    }
    if ($dbUser !== '') {
        $mysqli->query("DROP USER IF EXISTS '{$dbUser}'@'%'");
    }
    if ($id !== '') {
        rrmdir_safe(participant_dir($id));
    }
    $count++;
}

$mysqli->query("FLUSH PRIVILEGES");
$mysqli->close();

@unlink(REGISTRY_FILE);
@unlink(PARTICIPANTS_DIR . '/participants.csv');

echo "Teardown selesai: {$count} akun peserta, database, dan folder telah dihapus.\n";
