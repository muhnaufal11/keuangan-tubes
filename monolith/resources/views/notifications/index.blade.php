@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0">🔔 Notifikasi Saya</h3>
    </div>

    @if(session('success')) <div class="alert alert-success alert-dismissible fade show" role="alert">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div> @endif

    @forelse($notifications as $notif)
        <div class="card mb-3 border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1">
                        <div class="mb-2">
                            <span class="badge bg-{{ $notif->type === 'danger' ? 'danger' : ($notif->type === 'warning' ? 'warning' : ($notif->type === 'success' ? 'success' : 'info')) }}">{{ strtoupper($notif->type) }}</span>
                        </div>
                        <h5 class="card-title mb-2">{{ $notif->title }}</h5>
                        <p class="card-text text-muted mb-2">{{ $notif->message }}</p>
                        <small class="text-muted">{{ $notif->created_at->diffForHumans() }}</small>
                    </div>
                    <div class="ms-3">
                        <form method="POST" action="{{ route('notifications.destroy', $notif->id) }}" style="display:inline" onsubmit="return confirm('Hapus notifikasi ini?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus">✕</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="alert alert-info text-center py-5">
            <h5>📭 Tidak Ada Notifikasi</h5>
            <p class="mb-0 text-muted">Belum ada notifikasi untuk Anda</p>
        </div>
    @endforelse

    @if($notifications->count())
        <div class="mt-4">
            {{ $notifications->links() }}
        </div>
    @endif
</div>
@endsection
