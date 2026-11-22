<?php
// =========================================================
// FILE: hapus.php (Kunci Jawaban - DELETE)
// Variabel $id sudah disiapkan saat tombol Hapus diklik
// =========================================================

// TAHAP 5: Hapus 1 baris dari tabel pengeluaran berdasarkan id
$sql = "DELETE FROM pengeluaran WHERE id = $id";

// Jalankan perintah SQL ke database
$hasil = mysqli_query($koneksi, $sql);
