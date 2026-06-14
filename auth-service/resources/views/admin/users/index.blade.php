@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h3>Manajemen User</h3>
    <p class="text-muted small">Gunakan kotak <strong>Cari</strong> untuk menemukan user (username atau email). Pilih <strong>Status</strong> atau <strong>Per halaman</strong> untuk menyesuaikan tampilan. Gunakan ikon di kolom <strong>Aksi</strong> untuk <em>Lihat</em>, <em>Blokir/Buka blokir</em>, atau <em>Reset password</em>.</p>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form method="GET" class="row g-2 mb-3 align-items-center">
        <div class="col-md-4 col-sm-6">
            <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Cari username atau email...">
            <small class="text-muted">Ketik sebagian username atau email lalu tekan Cari.</small>
        </div>
        <div class="col-md-2 col-sm-3">
            <select name="status" class="form-select">
                <option value="">Semua</option>
                <option value="active" {{ request('status')=='active' ? 'selected' : '' }}>Aktif</option>
                <option value="banned" {{ request('status')=='banned' ? 'selected' : '' }}>Diblokir</option>
            </select>
        </div>
        <div class="col-auto">
            <select name="per" class="form-select">
                <option value="10" {{ request('per')==10 ? 'selected' : '' }}>10 / halaman</option>
                <option value="25" {{ request('per')==25 ? 'selected' : '' }}>25 / halaman</option>
                <option value="50" {{ request('per')==50 ? 'selected' : '' }}>50 / halaman</option>
            </select>
        </div>
        <div class="col-auto">
            <button class="btn btn-primary">Cari</button>
            <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">Reset</a>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Tanggal Bergabung</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $u)
                    <tr>
                        <td><a href="{{ route('admin.users.show', $u->id) }}">{{ $u->username }}</a></td>
                        <td>{{ $u->email }}</td>
                        <td title="{{ $u->created_at ? \Carbon\Carbon::parse($u->created_at)->toDayDateTimeString() : '' }}">{{ $u->created_at ? \Carbon\Carbon::parse($u->created_at)->format('Y-m-d') : '-' }}</td>
                        <td>{{ $u->tipe_akun ?? '-' }}</td>
                        <td>
                            @if($u->is_banned)
                                <span class="badge bg-danger" title="Pengguna diblokir">Diblokir</span>
                            @else
                                <span class="badge bg-success" title="Pengguna aktif">Aktif</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <a href="{{ route('admin.users.show', $u->id) }}" class="btn btn-sm btn-outline-primary" title="Lihat" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Lihat User" aria-label="Lihat user {{ $u->username }}"><i class="bi bi-eye" aria-hidden="true"></i> <span class="visually-hidden">Lihat</span></a>

                            <form id="ban-form-{{ $u->id }}" method="POST" action="{{ route('admin.users.ban', $u->id) }}" style="display:inline-block">
                                @csrf
                                <button type="button" class="btn btn-sm btn-{{ $u->is_banned ? 'success' : 'danger' }} btn-confirm-action" data-title="{{ $u->is_banned ? 'Buka Blokir User' : 'Blokir User' }}" data-message="Anda yakin ingin {{ $u->is_banned ? 'membuka blokir' : 'memblokir' }} user {{ $u->username }} ?" data-form="#ban-form-{{ $u->id }}" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="{{ $u->is_banned ? 'Buka blokir' : 'Blokir' }}" aria-label="{{ $u->is_banned ? 'Buka blokir user '.$u->username : 'Blokir user '.$u->username }}">
                                    <i class="bi {{ $u->is_banned ? 'bi-unlock' : 'bi-lock' }}" aria-hidden="true"></i> <span class="visually-hidden">{{ $u->is_banned ? 'Buka blokir' : 'Blokir' }}</span>
                                </button>
                            </form>

                            <form id="reset-form-{{ $u->id }}" method="POST" action="{{ route('admin.users.reset_password', $u->id) }}" style="display:inline-block">
                                @csrf
                                <button type="button" class="btn btn-sm btn-warning btn-confirm-action" data-title="Reset Password" data-message="Anda akan mereset password user {{ $u->username }} menjadi password sementara. Lanjutkan?" data-form="#reset-form-{{ $u->id }}" title="Reset Password" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="Reset Password" aria-label="Reset password user {{ $u->username }}">
                                    <i class="bi bi-arrow-clockwise" aria-hidden="true"></i> <span class="visually-hidden">Reset</span>
                                </button>
                            </form>

                            <form method="POST" action="{{ route('admin.help.start', $u->id) }}" style="display:inline-block">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-info" title="Respond (Get Help)" data-bs-toggle="tooltip" data-bs-placement="top">
                                    <i class="bi bi-chat-dots"></i> <span class="visually-hidden">Respond</span>
                                </button>
                            </form>

                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- CUSTOM PAGINATION START --}}
    <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
        <div class="text-muted small">
            Menampilkan <strong>{{ $users->firstItem() ?? 0 }}</strong> sampai <strong>{{ $users->lastItem() ?? 0 }}</strong> dari total <strong>{{ $users->total() }}</strong> user
        </div>
        <div>
            {{-- Tombol Previous --}}
            @if(!$users->onFirstPage())
                <a href="{{ $users->previousPageUrl() }}" class="btn btn-outline-secondary btn-sm me-1">
                    <i class="bi bi-chevron-left"></i> Sebelumnya
                </a>
            @else
                <button class="btn btn-outline-secondary btn-sm me-1" disabled>
                    <i class="bi bi-chevron-left"></i> Sebelumnya
                </button>
            @endif

            {{-- Tombol Next --}}
            @if($users->hasMorePages())
                <a href="{{ $users->nextPageUrl() }}" class="btn btn-primary btn-sm">
                    Selanjutnya <i class="bi bi-chevron-right"></i>
                </a>
            @else
                <button class="btn btn-outline-secondary btn-sm" disabled>
                    Selanjutnya <i class="bi bi-chevron-right"></i>
                </button>
            @endif
        </div>
    </div>
    {{-- CUSTOM PAGINATION END --}}

    <div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="confirmModalTitle">Konfirmasi</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body" id="confirmModalBody">Apakah Anda yakin?</div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
            <button type="button" class="btn btn-primary" id="confirmModalConfirm">Ya, lanjutkan</button>
          </div>
        </div>
      </div>
    </div>

    @push('scripts')
    <script>
        console.log("User management script loaded.");
        document.addEventListener('DOMContentLoaded', function(){
            console.log("DOM fully loaded and parsed.");
            let targetFormSelector = null;

            // Initialize bootstrap tooltips
            try {
                var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
                tooltipTriggerList.forEach(function (tooltipTriggerEl) {
                    new bootstrap.Tooltip(tooltipTriggerEl)
                })
                console.log("Tooltips initialized.");
            } catch (e) {
                console.error("Error initializing tooltips: ", e);
            }


            document.querySelectorAll('.btn-confirm-action').forEach(btn => {
                console.log("Attaching listener to button: ", btn);
                btn.addEventListener('click', function(e){
                    console.log("Confirm button clicked.");
                    targetFormSelector = this.getAttribute('data-form');
                    console.log("Target form selector: ", targetFormSelector);
                    document.getElementById('confirmModalTitle').textContent = this.getAttribute('data-title') || 'Konfirmasi';
                    document.getElementById('confirmModalBody').textContent = this.getAttribute('data-message') || 'Anda yakin?';

                    try {
                        var confirmModal = new bootstrap.Modal(document.getElementById('confirmModal'));
                        confirmModal.show();
                        console.log("Modal shown.");
                    } catch (e) {
                        console.error("Error showing modal: ", e);
                    }
                });
            });

            document.getElementById('confirmModalConfirm').addEventListener('click', function(){
                console.log("Modal confirm button clicked.");
                if (!targetFormSelector) {
                    console.error("No target form selector set.");
                    return;
                }
                var form = document.querySelector(targetFormSelector);
                if (form) {
                    console.log("Submitting form: ", form);
                    form.submit();
                } else {
                    console.error("Form not found with selector: ", targetFormSelector);
                }
            });
        });
    </script>
    @endpush
</div>
@endsection
