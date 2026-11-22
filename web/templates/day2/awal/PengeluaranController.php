<?php
// FILE: app/Http/Controllers/PengeluaranController.php (TAHAP 3 - Controller)

namespace App\Http\Controllers;

use App\Models\Pengeluaran;
use Illuminate\Http\Request;

class PengeluaranController extends Controller
{
    public function index(Request $request)
    {
        // Isi dengan nama kolom untuk mengurutkan dari data terbaru
        $data = Pengeluaran::orderBy('____', 'desc')->get();

        $editItem = $request->query('edit')
            ? Pengeluaran::find($request->query('edit'))
            : null;

        return view('pengeluaran.index', compact('data', 'editItem'));
    }

    public function simpan(Request $request)
    {
        $valid = $request->validate([
            'keterangan' => 'required|string|max:100',
            'nominal'    => 'required|integer|min:1',
            'tanggal'    => 'required|date',
        ]);

        // Panggil method create() milik Model yang sesuai
        ____::create($valid);

        return redirect('/')->with('pesan', 'Data baru berhasil disimpan lewat Laravel!');
    }

    public function ubah(Request $request, $id)
    {
        $valid = $request->validate([
            'keterangan' => 'required|string|max:100',
            'nominal'    => 'required|integer|min:1',
            'tanggal'    => 'required|date',
        ]);

        // Panggil method Eloquent untuk memperbarui data yang ditemukan
        Pengeluaran::findOrFail($id)->____($valid);

        return redirect('/')->with('pesan', 'Perubahan data berhasil disimpan!');
    }

    public function hapus($id)
    {
        // Panggil method Eloquent untuk menghapus data berdasarkan $id
        Pengeluaran::____($id);

        return redirect('/')->with('pesan', 'Data berhasil dihapus!');
    }
}
