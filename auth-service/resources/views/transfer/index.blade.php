@extends('layouts.app')

@section('title', 'Transfer')
@section('header_title', 'Transfer Antar Rekening')

@section('content')
<div class="row">
    <div class="col-lg-4 mb-4">
        <div class="card p-4">
            <h5 class="mb-3"><i class="bi bi-arrow-left-right"></i> Transfer Baru</h5>
            <form method="POST" action="{{ route('transfer.store') }}">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Dari Rekening</label>
                    <select name="rekening_sumber_id" class="form-select" required>
                        @foreach($rekening as $rek)
                            @if(strtoupper($rek->tipe) != 'TUNAI')
                                <option value="{{ $rek->id }}">{{ $rek->nama_rekening }} (Rp {{ number_format($rek->saldo, 0, ',', '.') }})</option>
                            @endif
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Ke Rekening</label>
                    <select name="rekening_tujuan_id" class="form-select" required>
                        @foreach($rekening as $rek)
                             <option value="{{ $rek->id }}">{{ $rek->nama_rekening }} (Rp {{ number_format($rek->saldo, 0, ',', '.') }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Jumlah (Rp)</label>
                    <input type="number" name="jumlah" class="form-control" required placeholder="0">
                </div>

                <div class="mb-3">
                    <label class="form-label">Keterangan (Opsional)</label>
                    <input type="text" name="deskripsi" class="form-control" placeholder="Contoh: Tabungan, Bayar Hutang">
                </div>

                <div class="mb-3">
                    <label class="form-label">Tanggal</label>
                    <input type="date" name="tanggal" class="form-control" value="{{ date('Y-m-d') }}" required>
                </div>

                <button type="submit" class="btn btn-primary w-100">Kirim Transfer</button>
            </form>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card p-4">
            <h5 class="mb-3">Riwayat Transfer</h5>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Tanggal</th>
                            <th>Dari / Ke</th>
                            <th>Keterangan</th>
                            <th class="text-end">Jumlah</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transfers as $tf)
                        <tr>
                            <td>
                                <div class="fw-bold">{{ \Carbon\Carbon::parse($tf->tanggal)->format('d M Y') }}</div>
                            </td>
                            <td>
                                <div><small class="text-muted">Dari:</small> {{ $tf->rekeningSumber->nama_rekening ?? '?' }}</div>
                                <div><small class="text-muted">Ke:</small> <strong>{{ $tf->rekeningTujuan->nama_rekening ?? '?' }}</strong></div>
                            </td>
                            <td>{{ $tf->deskripsi ?? '-' }}</td>
                            <td class="text-end fw-bold">Rp {{ number_format($tf->jumlah, 0, ',', '.') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center py-4 text-muted">Belum ada data transfer.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
