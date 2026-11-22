<?php
// FILE: routes/web.php (TAHAP 4 - Route)

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PengeluaranController;

// Isi nama class Controller dan method untuk menampilkan halaman utama
Route::get('/', [____::class, '____']);

// Hapus tanda // di bawah untuk mengaktifkan Tambah, Ubah, dan Hapus
// Route::post('/simpan', [PengeluaranController::class, 'simpan']);
// Route::post('/ubah/{id}', [PengeluaranController::class, 'ubah']);
// Route::post('/hapus/{id}', [PengeluaranController::class, 'hapus']);
