@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h3>Tambah Kategori Default</h3>

    <form method="POST" action="{{ route('admin.default_categories.store') }}">
        @csrf
        <div class="mb-3">
            <label class="form-label">Nama</label>
            <input type="text" name="name" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Tipe</label>
            <select name="type" class="form-control">
                <option value="pengeluaran">Pengeluaran</option>
                <option value="pemasukan">Pemasukan</option>
            </select>
        </div>

        <div class="mb-4">
            <label class="form-label">Tambahkan ke User (Opsional)</label>
            <div class="form-text mb-2">Biarkan kosong untuk hanya simpan default, atau pilih user untuk langsung tambah kategori</div>
            
            <div class="mb-3">
                <input type="text" id="search_user" class="form-control" placeholder="Cari username atau email...">
            </div>

            <div style="max-height: 300px; overflow-y: auto; border: 1px solid #ddd; border-radius: 5px; padding: 10px;">
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="select_all_users">
                    <label class="form-check-label fw-bold" for="select_all_users">Pilih Semua User</label>
                </div>
                <hr>
                @forelse($users ?? [] as $user)
                    <div class="form-check mb-2 user-item" data-username="{{ $user->username }}" data-email="{{ $user->email ?? '' }}">
                        <input class="form-check-input user-checkbox" type="checkbox" name="user_ids[]" value="{{ $user->id }}" id="user_{{ $user->id }}">
                        <label class="form-check-label" for="user_{{ $user->id }}">
                            {{ $user->username }}
                            @if($user->email)
                                <small class="text-muted">({{ $user->email }})</small>
                            @endif
                        </label>
                    </div>
                @empty
                    <p class="text-muted">Tidak ada user ditemukan</p>
                @endforelse
            </div>
        </div>

        <div>
            <a href="{{ route('admin.default_categories.index') }}" class="btn btn-secondary">Batal</a>
            <button type="submit" class="btn btn-primary">Simpan</button>
        </div>
    </form>
</div>

<script>
    const searchInput = document.getElementById('search_user');
    const userCheckboxes = document.querySelectorAll('.user-checkbox');
    const userItems = document.querySelectorAll('.user-item');

    searchInput?.addEventListener('keyup', function() {
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

    document.getElementById('select_all_users')?.addEventListener('change', function() {
        document.querySelectorAll('.user-checkbox:not([style*="display: none"])').forEach(cb => {
            cb.checked = this.checked;
        });
    });

    document.querySelectorAll('.user-checkbox').forEach(cb => {
        cb.addEventListener('change', function() {
            const visibleCheckboxes = Array.from(document.querySelectorAll('.user-checkbox')).filter(c => c.parentElement.style.display !== 'none');
            const allVisibleChecked = visibleCheckboxes.length > 0 && visibleCheckboxes.every(c => c.checked);
            const anyVisibleChecked = visibleCheckboxes.some(c => c.checked);
            document.getElementById('select_all_users').checked = allVisibleChecked;
            document.getElementById('select_all_users').indeterminate = !allVisibleChecked && anyVisibleChecked;
        });
    });
</script>
@endsection