<?php
// =========================================================
// FILE: tambah.php (Kunci Jawaban - CREATE)
// Data dari form sudah disiapkan: $keterangan, $nominal, $tanggal
// =========================================================

// TAHAP 3: Masukkan data baru ke tabel pengeluaran
$sql = "INSERT INTO pengeluaran (keterangan, nominal, tanggal) VALUES ('$keterangan', '$nominal', '$tanggal')";

// Jalankan perintah SQL ke database
$hasil = mysqli_query($koneksi, $sql);
