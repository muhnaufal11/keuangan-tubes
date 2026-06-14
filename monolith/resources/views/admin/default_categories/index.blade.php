@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">Kategori Default</h3>
        <div class="d-flex gap-2">
            <form method="POST" action="{{ route('admin.default_categories.sync') }}" onsubmit="return confirm('Tambahkan semua kategori default ke seluruh user?')">
                @csrf
                <button class="btn btn-outline-secondary">Sinkron ke Semua User</button>
            </form>
            <a href="{{ route('admin.default_categories.select_users') }}" class="btn btn-outline-info">Pilih User untuk Sinkron</a>
            <a href="{{ route('admin.default_categories.create') }}" class="btn btn-primary">Tambah Kategori</a>
        </div>
    </div>

    @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
    @if(session('error')) <div class="alert alert-danger">{{ session('error') }}</div> @endif

    <table class="table">
        <thead><tr><th>Nama</th><th>Tipe</th><th>Aksi</th></tr></thead>
        <tbody>
            @foreach($cats as $c)
                <tr>
                    <td>{{ $c->name }}</td>
                    <td><span class="badge bg-light text-dark text-uppercase">{{ $c->type }}</span></td>
                    <td>
                        <a href="{{ route('admin.default_categories.edit', $c->id) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                        <form method="POST" action="{{ route('admin.default_categories.destroy', $c->id) }}" style="display:inline-block">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-danger">Hapus</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{ $cats->links() }}
</div>
@endsection