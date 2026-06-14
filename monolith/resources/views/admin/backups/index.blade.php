@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h3>Backup Database</h3>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="mb-3">
        <form action="{{ route('admin.backups.store') }}" method="POST" style="display:inline-block">
            @csrf
            <button class="btn btn-primary">Buat Backup Sekarang</button>
        </form>

        <button class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#restoreModal">Restore dari file</button>
    </div>

    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Nama File</th>
                    <th>Ukuran</th>
                    <th>Dibuat Oleh</th>
                    <th>Dibuat Pada</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($backups as $b)
                    <tr>
                        <td>{{ $b->filename }}</td>
                        <td>{{ $b->size ? number_format($b->size/1024,2) . ' KB' : '-' }}</td>
                        <td>{{ $b->created_by ?? '-' }}</td>
                        <td>{{ $b->created_at->format('Y-m-d H:i') }}</td>
                        <td class="text-end">
                            <a href="{{ route('admin.backups.download', $b->id) }}" class="btn btn-sm btn-outline-primary">Download</a>
                            <form action="{{ route('admin.backups.destroy', $b->id) }}" method="POST" style="display:inline-block">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{ $backups->links() }}
</div>

<!-- Restore Modal -->
<div class="modal fade" id="restoreModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Restore Backup</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="{{ route('admin.backups.restore') }}" method="POST" enctype="multipart/form-data">
      @csrf
      <div class="modal-body">
        <div class="mb-3">
            <label class="form-label">Pilih file .sql</label>
            <input type="file" name="sql_file" class="form-control">
            <small class="text-muted">Batas ukuran: 10MB</small>
        </div>
        <div class="alert alert-warning">Restore akan menjalankan SQL yang Anda upload. Pastikan Anda sudah membuat backup terlebih dahulu.</div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-danger">Jalankan Restore</button>
      </div>
      </form>
    </div>
  </div>
</div>

@endsection
