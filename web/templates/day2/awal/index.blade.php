@extends('layouts.app')

@section('content')
<div class="card">
    <span class="badge">Laravel • Day 2 (Full CRUD)</span>

    {{-- Coba ganti judul ini --}}
    <h1>Catatan Pengeluaran (Laravel)</h1>
    @include('partials.form')
</div>

<div class="card">
    <h2>Daftar Pengeluaran</h2>
    <table>
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>Keterangan</th>
                <th class="text-right">Nominal</th>
                <th class="text-right">Aksi</th>
            </tr>
        </thead>
        <tbody>
            {{-- Isi nama variabel penampung tiap baris (gunakan nama yang sama di semua bagian kosong) --}}
            @foreach($data as $____)
                <tr>
                    <td>{{ $____->tanggal }}</td>
                    <td>{{ $____->keterangan }}</td>
                    <td class="text-right">Rp {{ number_format($____->nominal, 0, ',', '.') }}</td>
                    <td class="text-right">
                        <div class="row-actions">
                            <a class="btn-sm btn-edit" href="{{ url('/?edit=' . $____->id) }}">Ubah</a>
                            <form action="{{ url('/hapus/' . $____->id) }}" method="post" style="margin:0;" onsubmit="return confirm('Yakin hapus data ini?');">
                                @csrf
                                <button type="submit" class="btn-sm btn-del">Hapus</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="2">Total Pengeluaran</td>
                <td class="text-right">Rp {{ number_format($data->sum('nominal'), 0, ',', '.') }}</td>
                <td></td>
            </tr>
        </tbody>
    </table>
</div>
@endsection
