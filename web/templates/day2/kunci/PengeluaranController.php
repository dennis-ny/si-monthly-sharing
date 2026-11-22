<?php
// =========================================================
// FILE: PengeluaranController.php (Kunci Jawaban - Full CRUD)
// =========================================================

namespace App\Http\Controllers;

use App\Models\Pengeluaran;
use Illuminate\Http\Request;

class PengeluaranController extends Controller
{
    // READ: Ambil semua data pengeluaran terbaru dulu
    public function index(Request $request)
    {
        $data = Pengeluaran::orderBy('id', 'desc')->get();

        $editItem = $request->query('edit')
            ? Pengeluaran::find($request->query('edit'))
            : null;

        return view('pengeluaran.index', compact('data', 'editItem'));
    }

    // CREATE: Simpan data baru ke tabel pengeluaran (DB yang sama dengan Day 1)
    public function simpan(Request $request)
    {
        $valid = $request->validate([
            'keterangan' => 'required|string|max:100',
            'nominal'    => 'required|integer|min:1',
            'tanggal'    => 'required|date',
        ]);

        Pengeluaran::create($valid);

        return redirect('/')->with('pesan', 'Data baru berhasil disimpan lewat Laravel!');
    }

    // UPDATE: Ubah data berdasarkan id
    public function ubah(Request $request, $id)
    {
        $valid = $request->validate([
            'keterangan' => 'required|string|max:100',
            'nominal'    => 'required|integer|min:1',
            'tanggal'    => 'required|date',
        ]);

        Pengeluaran::findOrFail($id)->update($valid);

        return redirect('/')->with('pesan', 'Perubahan data berhasil disimpan!');
    }

    // DELETE: Hapus 1 baris berdasarkan id
    public function hapus($id)
    {
        Pengeluaran::destroy($id);

        return redirect('/')->with('pesan', 'Data berhasil dihapus!');
    }
}
