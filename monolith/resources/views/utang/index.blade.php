@extends('layouts.app')

@section('title', 'Utang & Piutang')
@section('header_title', 'Manajemen Utang & Piutang')

@section('content')
<div class="row">
    <!-- Form Input (Unified) -->
    <div class="col-lg-4 mb-4">
        <div class="card p-4 shadow-sm border-0">
            <h4 class="mb-3"><i class="bi bi-plus-circle"></i> Catat Baru</h4>
            <form method="POST" action="{{ route('utang.store') }}">
                @csrf
                
                <div class="mb-3">
                    <label class="form-label fw-bold">Jenis Catatan</label>
                    <div class="d-flex gap-3">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="jenis" id="jenisUtang" value="utang" checked>
                            <label class="form-check-label" for="jenisUtang">
                                Utang (Saya Ngutang)
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="jenis" id="jenisPiutang" value="piutang">
                            <label class="form-check-label" for="jenisPiutang">
                                Piutang (Orang Ngutang)
                            </label>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Deskripsi (Siapa & Untuk Apa)</label>
                    <input type="text" name="deskripsi" class="form-control" required placeholder="Contoh: Pinjam ke Budi / Pinjamin Ani">
                </div>
                <div class="mb-3">
                    <label class="form-label">Jumlah (Rp)</label>
                    <input type="number" name="jumlah" class="form-control" required placeholder="0">
                </div>
                <div class="mb-3">
                    <label class="form-label">Jatuh Tempo</label>
                    <input type="date" name="jatuh_tempo" class="form-control">
                </div>
                <button type="submit" class="btn btn-primary w-100">Simpan Catatan</button>
            </form>
        </div>
    </div>

    <!-- Data Lists (Tabs) -->
    <div class="col-lg-8">
        <div class="card p-4 shadow-sm border-0">
            
            <ul class="nav nav-tabs mb-3" id="myTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="utang-tab" data-bs-toggle="tab" data-bs-target="#utang" type="button" role="tab" aria-controls="utang" aria-selected="true">
                        <i class="bi bi-arrow-down-circle text-danger"></i> Utang Saya
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="piutang-tab" data-bs-toggle="tab" data-bs-target="#piutang" type="button" role="tab" aria-controls="piutang" aria-selected="false">
                        <i class="bi bi-arrow-up-circle text-success"></i> Piutang Saya
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="myTabContent">
                
                <!-- TAB UTANG -->
                <div class="tab-pane fade show active" id="utang" role="tabpanel" aria-labelledby="utang-tab">
                    <h5 class="mb-3 text-danger">Daftar hutang saya ke orang lain</h5>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Deskripsi</th>
                                    <th>Sisa / Total</th>
                                    <th>Jatuh Tempo</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($utang as $u)
                                <tr class="{{ $u->status == 'Lunas' ? 'table-light text-muted' : '' }}">
                                    <td>
                                        <div class="fw-bold">{{ $u->deskripsi }}</div>
                                        @if($u->status == 'Lunas')
                                            <small class="text-success"><i class="bi bi-check-all"></i> Lunas</small>
                                        @endif
                                    </td>
                                    <td>
                                        @if($u->status == 'Lunas')
                                            <span class="text-decoration-line-through">Rp {{ number_format($u->jumlah, 0, ',', '.') }}</span>
                                        @else
                                            <div class="text-danger fw-bold">Sisa: Rp {{ number_format($u->sisa_jumlah, 0, ',', '.') }}</div>
                                            <small class="text-muted">Total: Rp {{ number_format($u->jumlah, 0, ',', '.') }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        @if($u->jatuh_tempo)
                                            {{ \Carbon\Carbon::parse($u->jatuh_tempo)->format('d M Y') }}
                                            @if(\Carbon\Carbon::parse($u->jatuh_tempo)->isPast() && $u->status != 'Lunas')
                                                <span class="badge bg-danger">Lewat</span>
                                            @endif
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge {{ $u->status == 'Lunas' ? 'bg-success' : 'bg-warning text-dark' }}">{{ $u->status }}</span>
                                    </td>
                                    <td>
                                        @if($u->status != 'Lunas')
                                            <button class="btn btn-sm btn-success me-1" data-bs-toggle="modal" data-bs-target="#bayarModal-{{ $u->id }}">
                                                Bayar
                                            </button>
                                            <button class="btn btn-sm btn-info me-1 text-white" onclick="showRiwayat({{ $u->id }})">
                                                Riwayat
                                            </button>
                                            <button class="btn btn-sm btn-primary me-1" onclick="isiModalEdit({{ $u->id }}, '{{ $u->deskripsi }}', {{ $u->jumlah }}, '{{ $u->jatuh_tempo }}')" data-bs-toggle="modal" data-bs-target="#editModal">
                                                Edit
                                            </button>
                                        @endif
                                        <form action="{{ route('utang.destroy', $u->id) }}" method="POST" style="display:inline;">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus data ini?')">Hapus</button>
                                        </form>
                                    </td>
                                </tr>

                                <!-- Modal Bayar Utang -->
                                @if($u->status != 'Lunas')
                                <div class="modal fade" id="bayarModal-{{ $u->id }}" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form method="POST" action="{{ route('utang.bayar') }}">
                                                @csrf
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Bayar Utang</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <input type="hidden" name="id_utang" value="{{ $u->id }}">
                                                    <div class="alert alert-warning">
                                                        Sisa hutang kamu: <strong>Rp {{ number_format($u->sisa_jumlah, 0, ',', '.') }}</strong>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label>Jumlah Bayar</label>
                                                        <input type="number" name="jumlah_bayar" class="form-control" value="{{ $u->sisa_jumlah }}" max="{{ $u->sisa_jumlah }}" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label>Rekening Sumber</label>
                                                        <select name="rekening_id" class="form-select" required>
                                                            @foreach($rekening as $rek)
                                                                <option value="{{ $rek->id }}">{{ $rek->nama_rekening }} (Rp {{ number_format($rek->saldo) }})</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label>Catatan Pengeluaran</label>
                                                        <input type="text" name="deskripsi_utang" class="form-control" value="Bayar Utang: {{ $u->deskripsi }}">
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                    <button type="submit" class="btn btn-danger">Bayar Sekarang</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                @endif
                                @empty
                                <tr><td colspan="5" class="text-center py-4 text-muted">Tidak ada data utang.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- TAB PIUTANG -->
                <div class="tab-pane fade" id="piutang" role="tabpanel" aria-labelledby="piutang-tab">
                    <h5 class="mb-3 text-success">Daftar pinjaman saya ke orang lain (Piutang)</h5>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Deskripsi</th>
                                    <th>Sisa / Total</th>
                                    <th>Jatuh Tempo</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($piutang as $p)
                                <tr class="{{ $p->status == 'Lunas' ? 'table-light text-muted' : '' }}">
                                    <td>
                                        <div class="fw-bold">{{ $p->deskripsi }}</div>
                                        @if($p->status == 'Lunas')
                                            <small class="text-success"><i class="bi bi-check-all"></i> Lunas</small>
                                        @endif
                                    </td>
                                    <td>
                                        @if($p->status == 'Lunas')
                                            <span class="text-decoration-line-through">Rp {{ number_format($p->jumlah, 0, ',', '.') }}</span>
                                        @else
                                            <div class="text-success fw-bold">Sisa: Rp {{ number_format($p->sisa_jumlah, 0, ',', '.') }}</div>
                                            <small class="text-muted">Total: Rp {{ number_format($p->jumlah, 0, ',', '.') }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        @if($p->jatuh_tempo)
                                            {{ \Carbon\Carbon::parse($p->jatuh_tempo)->format('d M Y') }}
                                            @if(\Carbon\Carbon::parse($p->jatuh_tempo)->isPast() && $p->status != 'Lunas')
                                                <span class="badge bg-danger">Lewat</span>
                                            @endif
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge {{ $p->status == 'Lunas' ? 'bg-success' : 'bg-warning text-dark' }}">{{ $p->status }}</span>
                                    </td>
                                    <td>
                                        @if($p->status != 'Lunas')
                                            <button class="btn btn-sm btn-success me-1" data-bs-toggle="modal" data-bs-target="#bayarModal-{{ $p->id }}">
                                                Terima
                                            </button>
                                            <button class="btn btn-sm btn-info me-1 text-white" onclick="showRiwayat({{ $p->id }})">
                                                Riwayat
                                            </button>
                                            <button class="btn btn-sm btn-primary me-1" onclick="isiModalEdit({{ $p->id }}, '{{ $p->deskripsi }}', {{ $p->jumlah }}, '{{ $p->jatuh_tempo }}')" data-bs-toggle="modal" data-bs-target="#editModal">
                                                Edit
                                            </button>
                                        @endif
                                        <form action="{{ route('utang.destroy', $p->id) }}" method="POST" style="display:inline;">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus data ini?')">Hapus</button>
                                        </form>
                                    </td>
                                </tr>

                                <!-- Modal Terima Piutang -->
                                @if($p->status != 'Lunas')
                                <div class="modal fade" id="bayarModal-{{ $p->id }}" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <form method="POST" action="{{ route('utang.bayar') }}">
                                                @csrf
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Terima Pembayaran Piutang</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <input type="hidden" name="id_utang" value="{{ $p->id }}">
                                                    <div class="alert alert-success">
                                                        Sisa piutang mereka: <strong>Rp {{ number_format($p->sisa_jumlah, 0, ',', '.') }}</strong>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label>Jumlah Diterima</label>
                                                        <input type="number" name="jumlah_bayar" class="form-control" value="{{ $p->sisa_jumlah }}" max="{{ $p->sisa_jumlah }}" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label>Masuk ke Rekening</label>
                                                        <select name="rekening_id" class="form-select" required>
                                                            @foreach($rekening as $rek)
                                                                <option value="{{ $rek->id }}">{{ $rek->nama_rekening }}</option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label>Catatan Pemasukan</label>
                                                        <input type="text" name="deskripsi_utang" class="form-control" value="Terima Piutang: {{ $p->deskripsi }}">
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                    <button type="submit" class="btn btn-success">Konfirmasi Terima</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                @endif
                                @empty
                                <tr><td colspan="5" class="text-center py-4 text-muted">Tidak ada data piutang.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- Global Modal Edit (Shared) -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="" id="editForm">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">Edit Data</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label>Keterangan</label>
                        <input type="text" name="deskripsi" id="editDeskripsi" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Jumlah (Rp)</label>
                        <input type="number" name="jumlah" id="editJumlah" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Jatuh Tempo</label>
                        <input type="date" name="jatuh_tempo" id="editJatuhTempo" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Riwayat -->
<div class="modal fade" id="riwayatModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Riwayat Pembayaran</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <table class="table table-bordered table-sm">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Jumlah</th>
                            <th>Ket</th>
                        </tr>
                    </thead>
                    <tbody id="riwayatTableBody">
                        <tr><td colspan="3" class="text-center">Memuat...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    function isiModalEdit(id, deskripsi, jumlah, jatuhTempo) {
        document.getElementById('editForm').action = '/utang/' + id;
        document.getElementById('editDeskripsi').value = deskripsi;
        document.getElementById('editJumlah').value = jumlah;
        document.getElementById('editJatuhTempo').value = jatuhTempo;
    }

    function showRiwayat(id) {
        var modal = new bootstrap.Modal(document.getElementById('riwayatModal'));
        modal.show();
        
        document.getElementById('riwayatTableBody').innerHTML = '<tr><td colspan="3" class="text-center">Memuat...</td></tr>';

        fetch('/utang/' + id + '/riwayat')
            .then(response => response.json())
            .then(data => {
                let html = '';
                if(data.length === 0) {
                    html = '<tr><td colspan="3" class="text-center">Belum ada riwayat pembayaran.</td></tr>';
                } else {
                    data.forEach(item => {
                        let date = new Date(item.tanggal).toLocaleDateString('id-ID');
                        let amount = new Intl.NumberFormat('id-ID').format(item.jumlah);
                        html += `<tr>
                            <td>${date}</td>
                            <td>Rp ${amount}</td>
                            <td>${item.keterangan || '-'}</td>
                        </tr>`;
                    });
                }
                document.getElementById('riwayatTableBody').innerHTML = html;
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('riwayatTableBody').innerHTML = '<tr><td colspan="3" class="text-center text-danger">Gagal memuat data.</td></tr>';
            });
    }
</script>
@endsection