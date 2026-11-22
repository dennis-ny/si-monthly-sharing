<p class="subtitle">Membaca database yang SAMA dengan Day 1, dengan kode jauh lebih ringkas!</p>

@if(session('pesan'))
    <div class="alert-success">{{ session('pesan') }}</div>
@endif

<h2>{{ !empty($editItem) ? 'Ubah Pengeluaran (ID #' . $editItem->id . ')' : 'Tambah Pengeluaran Baru' }}</h2>
<form action="{{ !empty($editItem) ? url('/ubah/' . $editItem->id) : url('/simpan') }}" method="post">
    @csrf
    <div class="form-grid">
        <div><label>Keterangan</label><input type="text" name="keterangan" value="{{ $editItem->keterangan ?? '' }}" placeholder="Contoh: Beli teh es" required></div>
        <div><label>Nominal (Rp)</label><input type="number" name="nominal" value="{{ $editItem->nominal ?? '' }}" placeholder="10000" min="1" required></div>
        <div><label>Tanggal</label><input type="date" name="tanggal" value="{{ $editItem->tanggal ?? date('Y-m-d') }}" required></div>
    </div>
    <button type="submit" class="btn">{{ !empty($editItem) ? 'Simpan Perubahan' : '+ Simpan lewat Laravel' }}</button>
    @if(!empty($editItem))
        <a href="{{ url('/') }}" class="btn btn-cancel">Batal</a>
    @endif
</form>
