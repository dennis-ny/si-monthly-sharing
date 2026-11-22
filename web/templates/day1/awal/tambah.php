<?php
// FILE: tambah.php (TAHAP 4 - CREATE)

// Isi nama tabel & 3 kolom sesuai urutan VALUES di samping (cek tab Struktur di halaman Database)
$sql = "INSERT INTO ____ (____, ____, ____) VALUES ('$keterangan', '$nominal', '$tanggal')";

$hasil = mysqli_query($koneksi, $sql);
