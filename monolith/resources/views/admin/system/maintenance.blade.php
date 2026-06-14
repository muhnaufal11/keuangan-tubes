@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h3>Maintenance Mode</h3>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form action="{{ route('admin.system.maintenance.toggle') }}" method="POST">
        @csrf
        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" name="maintenance_mode" id="maintenance_mode" value="1" {{ $enabled == '1' ? 'checked' : '' }}>
            <label class="form-check-label" for="maintenance_mode">Aktifkan Maintenance Mode</label>
        </div>
        <div class="mb-3">
            <label class="form-label">Pesan untuk pengguna</label>
            <textarea name="maintenance_message" class="form-control" rows="3">{{ $message }}</textarea>
        </div>
        <button class="btn btn-primary">Simpan</button>
    </form>
</div>
@endsection