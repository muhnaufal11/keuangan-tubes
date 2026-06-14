@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h3 class="mb-4">Pilih User untuk Sinkron Kategori</h3>

    @if(session('error')) <div class="alert alert-danger">{{ session('error') }}</div> @endif

    <div class="mb-4">
        <input type="text" id="search_user" class="form-control form-control-lg" placeholder="Cari username atau email...">
    </div>

    <form method="POST" action="{{ route('admin.default_categories.bulk_sync') }}" onsubmit="return confirm('Sinkron kategori default ke user yang dipilih?')">
        @csrf

        <div class="card">
            <div class="card-header">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="select_all">
                    <label class="form-check-label fw-bold" for="select_all">Pilih Semua User</label>
                </div>
            </div>
            <div class="card-body" style="max-height: 500px; overflow-y: auto;">
                <div class="row">
                    @forelse($users as $user)
                        <div class="col-md-6 mb-3 user-item" data-username="{{ $user->username }}" data-email="{{ $user->email ?? '' }}">
                            <div class="form-check">
                                <input class="form-check-input user-checkbox" type="checkbox" name="user_ids[]" value="{{ $user->id }}" id="user_{{ $user->id }}">
                                <label class="form-check-label" for="user_{{ $user->id }}">
                                    <strong>{{ $user->username }}</strong>
                                    @if($user->email)
                                        <br><small class="text-muted">{{ $user->email }}</small>
                                    @endif
                                    @if($user->is_banned)
                                        <br><span class="badge bg-danger">Diblokir</span>
                                    @endif
                                </label>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted">Tidak ada user ditemukan.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="mt-4">
            <a href="{{ route('admin.default_categories.index') }}" class="btn btn-secondary">Batal</a>
            <button type="submit" class="btn btn-primary">Sinkron Kategori</button>
        </div>
    </form>
</div>

<script>
    const searchInput = document.getElementById('search_user');
    const userCheckboxes = document.querySelectorAll('.user-checkbox');
    const userItems = document.querySelectorAll('.user-item');

    searchInput.addEventListener('keyup', function() {
        const query = this.value.toLowerCase();
        
        userItems.forEach(item => {
            const username = item.getAttribute('data-username').toLowerCase();
            const email = (item.getAttribute('data-email') || '').toLowerCase();
            
            if (username.includes(query) || email.includes(query)) {
                item.style.display = '';
            } else {
                item.style.display = 'none';
            }
        });
    });

    document.getElementById('select_all').addEventListener('change', function() {
        document.querySelectorAll('.user-checkbox:not([style*="display: none"])').forEach(cb => {
            cb.checked = this.checked;
        });
    });

    document.querySelectorAll('.user-checkbox').forEach(cb => {
        cb.addEventListener('change', function() {
            const visibleCheckboxes = Array.from(document.querySelectorAll('.user-checkbox:not([style*="display: none"])')).filter(c => c.parentElement.style.display !== 'none');
            const allVisibleChecked = visibleCheckboxes.length > 0 && visibleCheckboxes.every(c => c.checked);
            const anyVisibleChecked = visibleCheckboxes.some(c => c.checked);
            document.getElementById('select_all').checked = allVisibleChecked;
            document.getElementById('select_all').indeterminate = !allVisibleChecked && anyVisibleChecked;
        });
    });
</script>
@endsection
