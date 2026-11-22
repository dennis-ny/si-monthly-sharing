<?php
// FILE: tampil.php (TAHAP 3 - READ)

$judul = "Catatan Pengeluaran";

// Isi dengan nama tabel yang sudah kamu buat (cek di halaman Database)
$sql = "SELECT * FROM ____ ORDER BY id DESC";

$hasil = mysqli_query($koneksi, $sql);
