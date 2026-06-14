@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h3 class="mb-4">Kirim Notifikasi & Pengumuman</h3>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.notifications.store') }}">
        @csrf

        <div class="row">
            <div class="col-md-8">
                <div class="mb-3">
                    <label class="form-label">Judul</label>
                    <input type="text" name="title" class="form-control @error('title') is-invalid @enderror" placeholder="Judul notifikasi" value="{{ old('title') }}" required>
                    @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Pesan</label>
                    <textarea name="message" class="form-control @error('message') is-invalid @enderror" rows="6" placeholder="Isi pesan notifikasi" required>{{ old('message') }}</textarea>
                    @error('message') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Tipe Notifikasi</label>
                    <select name="type" class="form-select @error('type') is-invalid @enderror" required>
                        <option value="">-- Pilih Tipe --</option>
                        <option value="info" {{ old('type') === 'info' ? 'selected' : '' }}>Info (Biru)</option>
                        <option value="warning" {{ old('type') === 'warning' ? 'selected' : '' }}>Warning (Kuning)</option>
                        <option value="success" {{ old('type') === 'success' ? 'selected' : '' }}>Success (Hijau)</option>
                        <option value="danger" {{ old('type') === 'danger' ? 'selected' : '' }}>Danger (Merah)</option>
                    </select>
                    @error('type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="col-md-4">
                <div class="card bg-light">
                    <div class="card-header">
                        <strong>Penerima</strong>
                    </div>
                    <div class="card-body">
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="send_all" value="1" id="send_all">
                            <label class="form-check-label fw-bold" for="send_all">Kirim ke Semua User</label>
                        </div>

                        <hr>

                        <div class="mb-3">
                            <input type="text" id="search_user" class="form-control form-control-sm" placeholder="Cari user...">
                        </div>

                        <div style="max-height: 350px; overflow-y: auto; border: 1px solid #ddd; border-radius: 5px; padding: 10px;">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="select_all_users">
                                <label class="form-check-label" for="select_all_users">Pilih Semua</label>
                            </div>
                            <hr>
                            @forelse($users as $user)
                                <div class="form-check mb-2 user-item" data-username="{{ $user->username }}" data-email="{{ $user->email ?? '' }}">
                                    <input class="form-check-input user-checkbox" type="checkbox" name="user_ids[]" value="{{ $user->id }}" id="user_{{ $user->id }}">
                                    <label class="form-check-label" for="user_{{ $user->id }}">
                                        <small>{{ $user->username }}</small>
                                    </label>
                                </div>
                            @empty
                                <p class="text-muted text-center">Tidak ada user</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-4">
            <a href="{{ route('admin.notifications.index') }}" class="btn btn-secondary">Batal</a>
            <button type="submit" class="btn btn-primary btn-lg">Kirim Notifikasi</button>
        </div>
    </form>
</div>

<script>
    const searchInput = document.getElementById('search_user');
    const userCheckboxes = document.querySelectorAll('.user-checkbox');
    const userItems = document.querySelectorAll('.user-item');
    const sendAllCheckbox = document.getElementById('send_all');

    searchInput?.addEventListener('keyup', function() {
        const query = this.value.toLowerCase();
        userItems.forEach(item => {
            const username = item.getAttribute('data-username').toLowerCase();
            const email = (item.getAttribute('data-email') || '').toLowerCase();
            item.style.display = username.includes(query) || email.includes(query) ? '' : 'none';
        });
    });

    document.getElementById('select_all_users')?.addEventListener('change', function() {
        document.querySelectorAll('.user-checkbox:not([style*="display: none"])').forEach(cb => {
            cb.checked = this.checked && !sendAllCheckbox.checked;
        });
    });

    sendAllCheckbox?.addEventListener('change', function() {
        const selectAllBtn = document.getElementById('select_all_users');
        if (this.checked) {
            document.querySelectorAll('.user-checkbox').forEach(cb => cb.checked = false);
            selectAllBtn.disabled = true;
            selectAllBtn.checked = false;
        } else {
            selectAllBtn.disabled = false;
        }
    });

    document.querySelectorAll('.user-checkbox').forEach(cb => {
        cb.addEventListener('change', function() {
            if (sendAllCheckbox.checked) return;
            const visibleCheckboxes = Array.from(document.querySelectorAll('.user-checkbox')).filter(c => c.parentElement.style.display !== 'none');
            const allChecked = visibleCheckboxes.length > 0 && visibleCheckboxes.every(c => c.checked);
            const anyChecked = visibleCheckboxes.some(c => c.checked);
            document.getElementById('select_all_users').checked = allChecked;
            document.getElementById('select_all_users').indeterminate = !allChecked && anyChecked;
        });
    });
</script>
@endsection
