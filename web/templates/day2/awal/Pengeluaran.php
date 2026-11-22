<?php
// FILE: app/Models/Pengeluaran.php (TAHAP 2 - Model)

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pengeluaran extends Model
{
    // Isi dengan nama tabel di database (cek di halaman Database)
    protected $table = '____';

    public $timestamps = false;

    // Isi dengan 3 nama kolom selain id (cek tab Struktur di halaman Database)
    protected $fillable = [
        '____',
        '____',
        '____',
    ];
}
