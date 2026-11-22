<?php
// =========================================================
// FILE: ubah.php (Kunci Jawaban - UPDATE)
// Data dari form edit sudah disiapkan: $id, $keterangan, $nominal, $tanggal
// =========================================================

// TAHAP 4: Ubah data di tabel pengeluaran berdasarkan id
$sql = "UPDATE pengeluaran SET keterangan = '$keterangan', nominal = '$nominal', tanggal = '$tanggal' WHERE id = $id";

// Jalankan perintah SQL ke database
$hasil = mysqli_query($koneksi, $sql);
