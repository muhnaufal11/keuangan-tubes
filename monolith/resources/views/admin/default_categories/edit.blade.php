@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h3>Edit Kategori Default</h3>

    <form method="POST" action="{{ route('admin.default_categories.update', $cat->id) }}">
        @csrf @method('PUT')
        <div class="mb-3">
            <label class="form-label">Nama</label>
            <input type="text" name="name" class="form-control" value="{{ $cat->name }}" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Tipe</label>
            <select name="type" class="form-control">
                <option value="pengeluaran" {{ $cat->type=='pengeluaran' ? 'selected' : '' }}>Pengeluaran</option>
                <option value="pemasukan" {{ $cat->type=='pemasukan' ? 'selected' : '' }}>Pemasukan</option>
            </select>
        </div>
        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" name="sync_all" value="1" id="sync_all">
            <label class="form-check-label" for="sync_all">Tambahkan ke semua user (jika belum ada)</label>
        </div>
        <button class="btn btn-primary">Simpan</button>
    </form>
</div>
@endsection