@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h3>Edit Tip</h3>

    <form action="{{ route('admin.tips.update', $tip->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <div class="mb-3">
            <label class="form-label">Title</label>
            <input type="text" name="title" class="form-control" value="{{ $tip->title }}" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Body</label>
            <textarea name="body" class="form-control" rows="4" required>{{ $tip->body }}</textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">Image (opsional)</label>
            <input type="file" name="image" class="form-control">
            @if($tip->image)
                <img src="{{ asset('storage/'.$tip->image) }}" alt="image" class="img-fluid mt-2" style="max-height:120px;">
            @endif
        </div>
        <div class="form-check mb-3">
            <input type="hidden" name="is_active" value="0">
            <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" {{ $tip->is_active ? 'checked' : '' }}>
            <label class="form-check-label" for="is_active">Tampilkan</label>
        </div>
        <div class="mb-3">
            <label class="form-label">Publish At (opsional)</label>
            <input type="date" name="publish_at" class="form-control" value="{{ $tip->publish_at ? $tip->publish_at->format('Y-m-d') : '' }}">
        </div>
        <button class="btn btn-primary">Simpan</button>
    </form>
</div>
@endsection