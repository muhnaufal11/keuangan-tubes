@extends('layouts.app')

@section('title', 'Pemasukan')
@section('header_title', 'Manajemen Pemasukan')

@section('content')
<div class="row">
    <div class="col-lg-4 mb-4">
        
        <div class="card p-4 mb-4">
            <h5 class="mb-3"><i class="bi bi-plus-circle"></i> Catat Pemasukan</h5>
            <form method="POST" action="{{ route('pemasukan.store') }}">
                @csrf
                
                <div class="mb-3">
                    <label class="form-label">Kategori</label>
                    <div class="input-group">
                        <select name="kategori" id="selectKategori" class="form-select" required onchange="toggleDeskripsi()">
                            @foreach($kategori as $kat)
                                <option value="{{ $kat->nama_kategori }}">{{ $kat->nama_kategori }}</option>
                            @endforeach
                            <option value="Lain-lain">Lain-lain</option>
                        </select>
                        <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#kategoriModal">
                            +
                        </button>
                    </div>
                </div>

                <div class="mb-3" id="deskripsiDiv" style="display: none;">
                    <label class="form-label">Deskripsi Tambahan</label>
                    <input type="text" name="deskripsi" class="form-control" placeholder="Keterangan...">
                </div>

                <div class="mb-3">
                    <label class="form-label">Jumlah (Rp)</label>
                    <input type="number" name="jumlah" class="form-control" required placeholder="0">
                </div>

                <div class="mb-3">
                    <label class="form-label">Masuk ke Rekening</label>
                    <select name="rekening_id" class="form-select" required>
                        @foreach($rekening as $rek)
                            <option value="{{ $rek->id }}">{{ $rek->nama_rekening }} (Saldo: Rp {{ number_format($rek->saldo, 0, ',', '.') }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Tanggal</label>
                    <input type="date" name="tanggal" class="form-control" value="{{ date('Y-m-d') }}" required>
                </div>

                <button type="submit" class="btn btn-primary w-100">Simpan Pemasukan</button>
            </form>
        </div>

    </div>

    <div class="col-lg-8">
        <div class="card p-4">
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
                <h5 class="mb-2">Riwayat Pemasukan</h5>
                
                <div class="btn-group">
                    <a href="{{ route('pemasukan.index', ['filter' => 'harian']) }}" 
                       class="btn btn-sm btn-outline-primary {{ request('filter') == 'harian' ? 'active' : '' }}">Hari Ini</a>
                    <a href="{{ route('pemasukan.index', ['filter' => 'mingguan']) }}" 
                       class="btn btn-sm btn-outline-primary {{ request('filter') == 'mingguan' ? 'active' : '' }}">Minggu Ini</a>
                    <a href="{{ route('pemasukan.index', ['filter' => 'bulanan']) }}" 
                       class="btn btn-sm btn-outline-primary {{ request('filter') == 'bulanan' ? 'active' : '' }}">Bulan Ini</a>
                    <a href="{{ route('pemasukan.index') }}" 
                       class="btn btn-sm btn-outline-secondary {{ !request('filter') ? 'active' : '' }}">Semua</a>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Tanggal</th>
                            <th>Kategori / Deskripsi</th>
                            <th>Rekening</th>
                            <th class="text-end">Jumlah</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($pemasukan as $item)
                        <tr>
                            <td>
                                <div class="fw-bold">{{ \Carbon\Carbon::parse($item->tanggal)->format('d') }}</div>
                                <div class="small text-muted">{{ \Carbon\Carbon::parse($item->tanggal)->format('M Y') }}</div>
                            </td>
                            <td>
                                <div class="fw-bold text-primary">{{ $item->kategori }}</div>
                                @if($item->deskripsi && $item->deskripsi != $item->kategori)
                                    <div class="small text-muted">{{ $item->deskripsi }}</div>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">{{ $item->rekening->nama_rekening ?? 'Dihapus' }}</span>
                            </td>
                            <td class="text-end fw-bold text-success">
                                + Rp {{ number_format($item->jumlah, 0, ',', '.') }}
                            </td>
                            <td class="text-center">
                                <form action="{{ route('pemasukan.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Hapus data ini? Saldo rekening akan dikembalikan (dikurangi).');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus">
                                        &times;
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">
                                Belum ada data pemasukan.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="kategoriModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('pemasukan.store_kategori') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Kelola Kategori</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label>Nama Kategori Baru</label>
                        <input type="text" name="nama_kategori" class="form-control" required placeholder="Contoh: Gaji, Bonus, Penjualan">
                    </div>
                    <hr>
                    <h6>Daftar Kategori Anda:</h6>
                    <ul class="list-group list-group-flush">
                        @foreach($kategori as $kat)
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            {{ $kat->nama_kategori }}
                            </li>
                        @endforeach
                    </ul>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Kategori</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function toggleDeskripsi() {
        const kategori = document.getElementById('selectKategori').value;
        const deskripsiDiv = document.getElementById('deskripsiDiv');
        
        if (kategori === 'Lain-lain') {
            deskripsiDiv.style.display = 'block';
        } else {
            deskripsiDiv.style.display = 'none';
        }
    }
    // Jalankan saat halaman dimuat (jika validasi gagal dan kembali ke form)
    document.addEventListener('DOMContentLoaded', toggleDeskripsi);
</script>

@endsection