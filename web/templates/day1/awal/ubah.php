<?php
// FILE: ubah.php (TAHAP 5 - UPDATE)

// Isi nama tabel, kolom yang diubah, dan kolom kunci di WHERE (cek tab Struktur di halaman Database)
$sql = "UPDATE ____ SET ____ = '$keterangan', ____ = '$nominal', ____ = '$tanggal' WHERE ____ = $id";

$hasil = mysqli_query($koneksi, $sql);
