<?php
// FILE: hapus.php (TAHAP 6 - DELETE)

// Isi nama tabel dan kolom kunci di WHERE (cek tab Struktur di halaman Database)
$sql = "DELETE FROM ____ WHERE ____ = $id";

$hasil = mysqli_query($koneksi, $sql);
