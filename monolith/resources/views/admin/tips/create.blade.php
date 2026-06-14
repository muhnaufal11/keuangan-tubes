@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h3>Tambah Tip</h3>

    <form action="{{ route('admin.tips.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="mb-3">
            <label class="form-label">Title</label>
            <input type="text" name="title" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Body</label>
            <textarea name="body" class="form-control" rows="4" required></textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">Image (opsional)</label>
            <input type="file" name="image" class="form-control">
        </div>
        <div class="form-check mb-3">
            <input type="hidden" name="is_active" value="0">
            <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" checked>
            <label class="form-check-label" for="is_active">Tampilkan</label>
        </div>
        <div class="mb-3">
            <label class="form-label">Publish At (opsional)</label>
            <input type="date" name="publish_at" class="form-control">
        </div>
        <button class="btn btn-primary">Simpan</button>
    </form>
</div>
@endsection