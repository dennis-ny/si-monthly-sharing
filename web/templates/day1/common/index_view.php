<?php
// =========================================================
// FILE: index.php (Hanya Baca — dikelola otomatis oleh sistem)
// File ini menggabungkan 6 file PHP milikmu dengan tampilan HTML.
// Kamu tidak perlu mengedit file ini.
// =========================================================

require_once "buat_db.php";   // Tahap 1: CREATE DATABASE & CREATE TABLE
require_once "koneksi.php";   // Tahap 2: mysqli_connect(...)

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $aksi = $_POST["aksi"] ?? "tambah";
    if ($aksi === "tambah") { require "tambah.php"; }  // Tahap 4: INSERT
    if ($aksi === "ubah")   { require "ubah.php";   }  // Tahap 5: UPDATE
    if ($aksi === "hapus")  { require "hapus.php";  }  // Tahap 6: DELETE
}

require "tampil.php";         // Tahap 3: SELECT * FROM pengeluaran
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($judul) ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <div class="card">
        <h1><?= htmlspecialchars($judul) ?></h1>
        <p class="subtitle">Workshop Day 1 — PHP Native + MySQL (CRUD)</p>

        <h2>Tambah Pengeluaran Baru</h2>
        <form action="index.php" method="post">
            <input type="hidden" name="aksi" value="tambah">
            <div class="form-grid">
                <div class="form-group">
                    <label>Keterangan</label>
                    <input type="text" name="keterangan" required>
                </div>
                <div class="form-group">
                    <label>Nominal (Rp)</label>
                    <input type="number" name="nominal" min="1" required>
                </div>
                <div class="form-group">
                    <label>Tanggal</label>
                    <input type="date" name="tanggal" value="<?= date('Y-m-d') ?>" required>
                </div>
            </div>
            <button type="submit" class="btn">+ Simpan Pengeluaran</button>
        </form>
    </div>

    <div class="card">
        <h2>Daftar Pengeluaran</h2>
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
                <?php while ($baris = mysqli_fetch_assoc($hasil)): ?>
                <tr>
                    <td><?= htmlspecialchars($baris["tanggal"]) ?></td>
                    <td><?= htmlspecialchars($baris["keterangan"]) ?></td>
                    <td class="text-right">Rp <?= number_format($baris["nominal"], 0, ",", ".") ?></td>
                    <td class="text-right">
                        <a class="btn-sm btn-edit" href="index.php?edit=<?= $baris['id'] ?>">Ubah</a>
                        <form action="index.php" method="post" style="display:inline;">
                            <input type="hidden" name="aksi" value="hapus">
                            <input type="hidden" name="id" value="<?= $baris['id'] ?>">
                            <button type="submit" class="btn-sm btn-del">Hapus</button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
