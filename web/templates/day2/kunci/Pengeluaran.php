<?php
// =========================================================
// FILE: app/Models/Pengeluaran.php (Kunci Jawaban - MODEL)
// =========================================================

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pengeluaran extends Model
{
    protected $table = 'pengeluaran';

    public $timestamps = false;

    protected $fillable = [
        'keterangan',
        'nominal',
        'tanggal',
    ];
}
