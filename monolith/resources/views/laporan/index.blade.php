@extends('layouts.app')

@section('title', 'Laporan Keuangan')
@section('header_title', 'Laporan Keuangan')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <div class="card p-3">
            <form action="{{ route('laporan.index') }}" method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Bulan</label>
                    <select name="bulan" class="form-select">
                        @for($i = 1; $i <= 12; $i++)
                            <option value="{{ sprintf('%02d', $i) }}" {{ $bulan == sprintf('%02d', $i) ? 'selected' : '' }}>
                                {{ DateTime::createFromFormat('!m', $i)->format('F') }}
                            </option>
                        @endfor
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Tahun</label>
                    <select name="tahun" class="form-select">
                        @for($i = date('Y'); $i >= date('Y')-5; $i--)
                            <option value="{{ $i }}" {{ $tahun == $i ? 'selected' : '' }}>{{ $i }}</option>
                        @endfor
                    </select>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary w-100">Tampilkan Laporan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-4">
        <div class="card p-3 text-center border-primary border-2">
            <h6 class="text-muted">Total Pemasukan</h6>
            <h3 class="text-primary">Rp {{ number_format($totalPemasukan, 0, ',', '.') }}</h3>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card p-3 text-center border-danger border-2">
            <h6 class="text-muted">Total Pengeluaran</h6>
            <h3 class="text-danger">Rp {{ number_format($totalPengeluaran, 0, ',', '.') }}</h3>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card p-3 text-center {{ $selisih >= 0 ? 'bg-success text-white' : 'bg-danger text-white' }}">
            <h6>Selisih (Cashflow)</h6>
            <h3>Rp {{ number_format($selisih, 0, ',', '.') }}</h3>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-6 mb-4">
        <div class="card p-3 h-100">
            <h5 class="card-title text-center">Pemasukan per Kategori</h5>
            <canvas id="chartPemasukan"></canvas>
        </div>
    </div>
    <div class="col-md-6 mb-4">
        <div class="card p-3 h-100">
            <h5 class="card-title text-center">Pengeluaran per Kategori</h5>
            <canvas id="chartPengeluaran"></canvas>
        </div>
    </div>
</div>

<div class="card p-4">
    <h5 class="mb-3">Rincian Transaksi ({{ date('F Y', mktime(0, 0, 0, $bulan, 10)) }})</h5>
    <div class="table-responsive">
        <table class="table table-striped table-hover align-middle">
            <thead class="table-dark">
                <tr>
                    <th>Tanggal</th>
                    <th>Tipe</th>
                    <th>Kategori</th>
                    <th>Deskripsi</th>
                    <th>Rekening</th>
                    <th class="text-end">Jumlah</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transaksi as $item)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($item->tanggal)->format('d M Y') }}</td>
                    <td>
                        @if($item->jenis == 'pemasukan')
                            <span class="badge bg-success">Pemasukan</span>
                        @else
                            <span class="badge bg-danger">Pengeluaran</span>
                        @endif
                    </td>
                    <td>{{ $item->kategori }}</td>
                    <td>{{ $item->deskripsi }}</td>
                    <td>{{ $item->rekening->nama_rekening ?? '-' }}</td>
                    <td class="text-end fw-bold {{ $item->jenis == 'pemasukan' ? 'text-success' : 'text-danger' }}">
                        {{ $item->jenis == 'pemasukan' ? '+' : '-' }} Rp {{ number_format($item->jumlah, 0, ',', '.') }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center py-3">Tidak ada data transaksi pada periode ini.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Data Pemasukan
    const ctxMasuk = document.getElementById('chartPemasukan');
    new Chart(ctxMasuk, {
        type: 'doughnut',
        data: {
            labels: {!! json_encode($pemasukanPerKategori->pluck('kategori')) !!},
            datasets: [{
                data: {!! json_encode($pemasukanPerKategori->pluck('total')) !!},
                backgroundColor: ['#28a745', '#20c997', '#198754', '#0f5132', '#A3E4D7'],
            }]
        }
    });

    // Data Pengeluaran
    const ctxKeluar = document.getElementById('chartPengeluaran');
    new Chart(ctxKeluar, {
        type: 'pie',
        data: {
            labels: {!! json_encode($pengeluaranPerKategori->pluck('kategori')) !!},
            datasets: [{
                data: {!! json_encode($pengeluaranPerKategori->pluck('total')) !!},
                backgroundColor: ['#dc3545', '#fd7e14', '#ffc107', '#6c757d', '#F1948A', '#85C1E9'],
            }]
        }
    });
</script>
@endsection