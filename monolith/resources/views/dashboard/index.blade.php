@extends('layouts.app')

@section('title', 'Dashboard')
@section('header_title', 'Dashboard')

@section('content')
    <h2>Selamat Datang, {{ Auth::user()->username }}!</h2>
    <p class="text-muted">Ringkasan keuangan bulan ini.</p>

    @if(isset($tip) && $tip)
    <div class="card p-3 mb-4">
        <h6 class="mb-2">Tip: {{ $tip->title }}</h6>
        <p class="mb-2">{{ \Illuminate\Support\Str::limit($tip->body, 250) }}</p>
        @if($tip->image)
            <img src="{{ asset('storage/'.$tip->image) }}" alt="tip" class="img-fluid mb-2" style="max-height:160px;">
        @endif
        <small class="text-muted">Tip dipilih acak untuk setiap login</small>
    </div>
    @endif

    <div class="row mt-4">
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card p-3">
                <h6>Pemasukan (Bulan Ini)</h6>
                <h4 class="text-primary">Rp {{ number_format($totalPemasukan, 0, ',', '.') }}</h4>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card p-3">
                <h6>Pengeluaran (Bulan Ini)</h6>
                <h4 class="text-danger">Rp {{ number_format($totalPengeluaran, 0, ',', '.') }}</h4>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card p-3 bg-success text-white">
                <h6>Saldo Bisa Dipakai</h6>
                <h4>Rp {{ number_format($totalSaldoBisaDipakai, 0, ',', '.') }}</h4>
            </div>
        </div>
        <div class="col-lg-3 col-md-6 mb-4">
            <div class="card p-3 text-center">
                <h6>Aksi Cepat</h6>
                <button class="btn btn-primary mt-2" data-bs-toggle="modal" data-bs-target="#tarikTunaiModal">Tarik Tunai</button>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8 mb-4">
            <div class="card p-4 h-100">
                <h4>Rincian Saldo Rekening</h4>
                <div class="row">
                    @foreach ($rekeningList as $rekening)
                        @if (strtoupper($rekening->tipe) != 'TUNAI')
                        <div class="col-md-6 mb-3">
                            <div class="card p-3">
                                <h6>{{ $rekening->nama_rekening }}</h6>
                                <h5 class="mb-1">Rp {{ number_format($rekening->saldo, 0, ',', '.') }}</h5>
                                <small class="text-muted">Bisa dipakai: <span class="text-success">Rp {{ number_format($rekening->saldo - $rekening->minimum_saldo, 0, ',', '.') }}</span></small>
                            </div>
                        </div>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>
        <div class="col-lg-4 mb-4">
            <div class="card p-4 bg-light h-100 mb-3">
                <h4>Saldo Tunai</h4>
                <h6>Uang Tunai di Tangan</h6>
                <h4 class="display-6 mt-2">Rp {{ number_format($saldoTunai, 0, ',', '.') }}</h4>
            </div>


        </div>
    </div>

    <div class="modal fade" id="tarikTunaiModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('dashboard.tarik') }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Tarik Tunai ke Dompet</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label>Dari Rekening</label>
                            <select name="rekening_sumber_id" class="form-select" required>
                                @foreach ($rekeningList as $rek)
                                    @if(strtoupper($rek->tipe) != 'TUNAI')
                                    <option value="{{ $rek->id }}">{{ $rek->nama_rekening }} (Rp {{ number_format($rek->saldo,0,',','.') }})</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label>Jumlah (Rp)</label>
                            <input type="number" name="jumlah_tarik" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">Tarik</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection