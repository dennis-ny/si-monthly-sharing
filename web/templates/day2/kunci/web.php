<?php
// =========================================================
// FILE: routes/web.php (Kunci Jawaban - Peta URL CRUD)
// =========================================================

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PengeluaranController;

// READ: Tampilkan daftar pengeluaran
Route::get('/', [PengeluaranController::class, 'index']);

// CREATE, UPDATE, DELETE: Aksi form & tombol
Route::post('/simpan', [PengeluaranController::class, 'simpan']);
Route::post('/ubah/{id}', [PengeluaranController::class, 'ubah']);
Route::post('/hapus/{id}', [PengeluaranController::class, 'hapus']);
