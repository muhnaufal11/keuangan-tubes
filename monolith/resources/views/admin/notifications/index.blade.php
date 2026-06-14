@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0">Notifikasi & Pengumuman</h3>
        <a href="{{ route('admin.notifications.create') }}" class="btn btn-primary">+ Kirim Notifikasi</a>
    </div>

    @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif

    <div class="card">
        <div class="card-header">
            <form class="row g-3" method="GET">
                <div class="col-md-4">
                    <select name="type" class="form-select" onchange="this.form.submit()">
                        <option value="">Semua Tipe</option>
                        <option value="info" {{ request('type') === 'info' ? 'selected' : '' }}>Info</option>
                        <option value="warning" {{ request('type') === 'warning' ? 'selected' : '' }}>Warning</option>
                        <option value="success" {{ request('type') === 'success' ? 'selected' : '' }}>Success</option>
                        <option value="danger" {{ request('type') === 'danger' ? 'selected' : '' }}>Danger</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="">Semua Status</option>
                        <option value="read" {{ request('status') === 'read' ? 'selected' : '' }}>Sudah Dibaca</option>
                        <option value="unread" {{ request('status') === 'unread' ? 'selected' : '' }}>Belum Dibaca</option>
                    </select>
                </div>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Judul</th>
                        <th>Tipe</th>
                        <th>Penerima</th>
                        <th>Status</th>
                        <th>Tanggal</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($notifications as $notif)
                        <tr>
                            <td>
                                <strong>{{ $notif->title }}</strong>
                                <br><small class="text-muted">{{ Str::limit($notif->message, 50) }}</small>
                            </td>
                            <td><span class="badge bg-light text-dark text-uppercase">{{ $notif->type }}</span></td>
                            <td>
                                @if($notif->user)
                                    {{ $notif->user->username }}
                                @else
                                    <span class="text-muted">Semua</span>
                                @endif
                            </td>
                            <td>
                                @if($notif->isRead())
                                    <span class="badge bg-success">Dibaca</span>
                                @else
                                    <span class="badge bg-warning text-dark">Belum Dibaca</span>
                                @endif
                            </td>
                            <td>{{ $notif->created_at->format('d M Y H:i') }}</td>
                            <td>
                                <form method="POST" action="{{ route('admin.notifications.destroy', $notif->id) }}" style="display:inline-block">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-danger" onclick="return confirm('Hapus notifikasi?')">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">Tidak ada notifikasi</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">
        {{ $notifications->links() }}
    </div>
</div>
@endsection
