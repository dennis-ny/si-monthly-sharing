<?php
// =========================================================
// FILE: tampil.php (Kunci Jawaban - READ)
// =========================================================

// TAHAP 0: Judul halaman
$judul = "Catatan Pengeluaran";

// TAHAP 2: Ambil semua data dari tabel pengeluaran
$sql = "SELECT * FROM pengeluaran ORDER BY id DESC";

// Jalankan perintah SQL ke database
$hasil = mysqli_query($koneksi, $sql);
