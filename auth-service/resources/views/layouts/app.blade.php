<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Keuangan')</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; background-color: #f8f9fa; }
        .body-no-scroll { overflow: hidden; }
        .sidebar { width: 250px; height: 100vh; position: fixed; left: 0; top: 0; background: linear-gradient(to bottom, #007bff, #0056b3); color: white; padding-top: 20px; transition: transform 0.3s ease-in-out; z-index: 1051; display: flex; flex-direction: column; }
        .sidebar-menu { flex-grow: 1; overflow-y: auto; }
        .sidebar a { display: block; padding: 12px 20px; color: white; text-decoration: none; transition: background-color 0.2s; }
        .sidebar a.active, .sidebar a:hover { background: rgba(255, 255, 255, 0.2); }
        /* Admin submenu */
        .admin-toggle { display:block; padding: 12px 20px; color: white; text-decoration: none; cursor: pointer; }
        .admin-toggle .bi { vertical-align: -.125em; }
        .sidebar .collapse a { padding-left: 36px; }
        .sidebar .collapse a .bi { vertical-align: -.125em; }
        .sidebar .collapse a.active, .sidebar .collapse a:hover { background: rgba(255,255,255,0.08); }
        .sidebar .admin-chevron { float: right; margin-top: 2px; }

        .main-content { transition: margin-left 0.3s ease-in-out; }
        .card { border: none; border-radius: 15px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
        .sidebar-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.5); z-index: 1050; display: none; }
        .sidebar-overlay.active { display: block; }

        /* Animasi Loader */
        .loader-wrapper { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(255, 255, 255, 0.8); backdrop-filter: blur(5px); display: none; align-items: center; justify-content: center; z-index: 2000; opacity: 0; transition: opacity 0.3s ease-in-out; }
        .loader-wrapper.active { display: flex; opacity: 1; }
        .loader { border: 8px solid #f3f3f3; border-radius: 50%; border-top: 8px solid #007bff; width: 60px; height: 60px; animation: spin 1s linear infinite; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }

        @media (min-width: 992px) { .main-content { margin-left: 250px; } }
        @media (max-width: 991.98px) { .sidebar { transform: translateX(-100%); } .sidebar.active { transform: translateX(0); } .main-content { margin-left: 0; } }

        /* Constrain large images or SVGs inside main content to avoid accidental oversized assets */
        .main-content img, .main-content svg { max-width: 100%; height: auto; max-height: 160px; display:block; }
        .main-content .img-fluid { max-height: 160px; }

        /* Keep UI icons small (pagination / nav / buttons) so global svg rule above doesn't enlarge them */
        nav[role="navigation"] svg,
        .pagination svg,
        .page-item svg,
        .page-link svg,
        .btn svg,
        i.bi {
            max-height: 24px !important;
            max-width: 24px !important;
            height: auto !important;
            width: auto !important;
            font-size: 1rem !important;
        }

        .sidebar img { max-width: 70px; max-height: 70px; }

    </style>
    <link rel="stylesheet" href="{{ asset('css/chat.css') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; background-color: #f8f9fa; }
        .body-no-scroll { overflow: hidden; }
        .sidebar { width: 250px; height: 100vh; position: fixed; left: 0; top: 0; background: linear-gradient(to bottom, #007bff, #0056b3); color: white; padding-top: 20px; transition: transform 0.3s ease-in-out; z-index: 1051; display: flex; flex-direction: column; }
    </style>
</head>

<body>
    <div class="loader-wrapper" id="loader">
        <div class="loader"></div>
    </div>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <div class="sidebar" id="sidebar">
        <div class="sidebar-menu">
            <div class="text-center mb-4">
                <img src="{{ asset('logo1.svg') }}" alt="Logo" style="width: 70px; height: auto; margin-bottom: 2px;">
                <h3>KEUANGAN</h3>
            </div>

            @if(Auth::check() && Auth::user()->isAdmin())
                <a class="admin-toggle {{ request()->routeIs('admin.*') ? 'active' : '' }}" data-bs-toggle="collapse" href="#adminMenu" role="button" aria-expanded="{{ request()->routeIs('admin.*') ? 'true' : 'false' }}" aria-controls="adminMenu">
                    <i class="bi bi-shield-lock-fill me-1"></i> Admin
                    <i class="bi bi-caret-down-fill admin-chevron"></i>
                </a>
                <div class="collapse {{ request()->routeIs('admin.*') ? 'show' : '' }}" id="adminMenu">
                    <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"> <i class="bi bi-speedometer2 me-1"></i> Dashboard</a>
                    <a href="{{ route('admin.users.index') }}" class="{{ request()->routeIs('admin.users.*') ? 'active' : '' }}"> <i class="bi bi-people me-1"></i> Manajemen User</a>
                    <a href="{{ route('admin.default_categories.index') }}" class="{{ request()->routeIs('admin.default_categories.*') ? 'active' : '' }}"> <i class="bi bi-list-ul me-1"></i> Default Categories</a>
                    <a href="{{ route('admin.logs.index') }}" class="{{ request()->routeIs('admin.logs.*') ? 'active' : '' }}"> <i class="bi bi-journal-text me-1"></i> Activity Logs</a>
                    <a href="{{ route('admin.backups.index') }}" class="{{ request()->routeIs('admin.backups.*') ? 'active' : '' }}"> <i class="bi bi-download me-1"></i> Database Backups</a>
                    <a href="{{ route('admin.tips.index') }}" class="{{ request()->routeIs('admin.tips.*') ? 'active' : '' }}"> <i class="bi bi-lightbulb me-1"></i> Tips Keuangan</a>
                    <a href="{{ route('admin.landing.index') }}" class="{{ request()->routeIs('admin.landing.*') ? 'active' : '' }}"><i class="bi bi-layout-text-window-reverse me-1"></i> Landing Page</a>
                    <a href="{{ route('admin.help.index') }}" class="{{ request()->routeIs('admin.help.*') ? 'active' : '' }}"> <i class="bi bi-chat-dots me-1"></i> Help Sessions</a>
                    <a href="{{ route('admin.system.maintenance') }}" class="{{ request()->routeIs('admin.system.*') ? 'active' : '' }}"> <i class="bi bi-gear me-1"></i> System</a>
                </div>
                @else
                <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">Dashboard</a>
                <a href="{{ route('rekening.index') }}" class="{{ request()->routeIs('rekening.*') ? 'active' : '' }}">Rekening</a>
                <a href="{{ route('pemasukan.index') }}" class="{{ request()->routeIs('pemasukan.*') ? 'active' : '' }}">Pemasukan</a>
                <a href="{{ route('pengeluaran.index') }}" class="{{ request()->routeIs('pengeluaran.*') ? 'active' : '' }}">Pengeluaran</a>
                <a href="{{ route('transfer.index') }}" class="{{ request()->routeIs('transfer.*') ? 'active' : '' }}">Transfer</a>
                <a href="{{ route('utang.index') }}" class="{{ request()->routeIs('utang.*') ? 'active' : '' }}">Utang</a>
                <a href="{{ route('laporan.index') }}" class="{{ request()->routeIs('laporan.*') ? 'active' : '' }}">Laporan</a>
                <a href="{{ route('help.index') }}" class="{{ request()->routeIs('help.*') ? 'active' : '' }}">Get Help</a>
            @endif

            <form action="{{ route('logout') }}" method="POST" id="logout-form">
                @csrf
                <a href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">Logout</a>
            </form>
        </div>
    </div>

    <div class="main-content p-3 p-md-4">
        <!-- Desktop Navbar (visible on large screens) -->
        <nav class="navbar navbar-light bg-light mb-4 d-none d-lg-flex">
            <div class="container-fluid">
                <span class="navbar-brand mb-0 h5">@yield('header_title', 'Dashboard')</span>
                <div class="d-flex gap-2">
                    @if(Auth::check() && !Auth::user()->isAdmin())
                        <a href="{{ route('notifications.index') }}" class="btn btn-light position-relative" title="Notifikasi">
                            <i class="bi bi-bell"></i>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger unread-badge unread-badge-desktop" id="unreadCountDesktop" style="display:none;">
                                <span id="unreadCountNumDesktop">0</span>
                                <span class="visually-hidden">unread notifications</span>
                            </span>
                        </a>
                        <a href="{{ route('help.index') }}" class="btn btn-outline-primary" title="Get Help">
                            <i class="bi bi-life-preserver"></i>
                        </a>
                    @endif
                </div>
            </div>
        </nav>

        <!-- Mobile Navbar -->
        <nav class="navbar navbar-light bg-light mb-4 d-lg-none">
            <div class="container-fluid">
                <button class="navbar-toggler" type="button" id="sidebarToggle">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <span class="navbar-brand mb-0 h1">@yield('header_title', 'Dashboard')</span>
                @if(Auth::check() && !Auth::user()->isAdmin())
                    <a href="{{ route('notifications.index') }}" class="btn btn-light position-relative" title="Notifikasi">
                        <i class="bi bi-bell"></i>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger unread-badge unread-badge-mobile" id="unreadCountMobile" style="display:none;">
                            <span id="unreadCountNumMobile">0</span>
                            <span class="visually-hidden">unread notifications</span>
                        </span>
                    </a>
                @endif
            </div>
        </nav>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger">
                <ul>@foreach($errors->all() as $err) <li>{{ $err }}</li> @endforeach</ul>
            </div>
        @endif

        @yield('content')
    </div>

    @if(Auth::check() && !Auth::user()->isAdmin())
        <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11">
            <div id="notificationToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="toast-header bg-info text-white">
                    <strong class="me-auto">Notifikasi Baru</strong>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
                <div class="toast-body" id="toastMessage">
                    Anda memiliki notifikasi baru
                </div>
            </div>
            <div id="helpToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="toast-header bg-success text-white">
                    <strong class="me-auto">Pesan Baru</strong>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
                <div class="toast-body" id="helpToastMessage">
                    Anda memiliki pesan baru di Get Help
                </div>
            </div>
        </div>
    @endif

    {{-- Floating popup chat for user --}}
    @if(Auth::check() && !Auth::user()->isAdmin())
        <div id="help-popup" style="display:none; position:fixed; right:20px; bottom:20px; width:320px; z-index:2000;">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong>Get Help</strong>
                    <div>
                        <button id="help-popup-toggle-visibility" class="btn btn-sm btn-outline-secondary me-1" title="Toggle Pop-up">👁</button>
                        <button id="help-popup-toggle" class="btn btn-sm btn-light">_</button>
                    </div>
                </div>
                <div class="card-body p-2 chat-popup-body" id="help-popup-body">
                    <div id="help-popup-messages"></div>
                </div>
                <div class="card-footer p-2">
                    <div class="input-group">
                        <input type="text" id="help-popup-input" class="form-control form-control-sm" placeholder="Ketik pesan...">
                        <button id="help-popup-send" class="btn btn-primary btn-sm">Kirim</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Floating popup for admin (most recent open session) --}}
    @if(Auth::check() && Auth::user()->isAdmin())
        <div id="admin-help-popup" style="display:none; position:fixed; right:20px; bottom:20px; width:420px; z-index:2000;">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong>Help (Admin)</strong>
                    <div>
                        <a href="{{ route('admin.help.index') }}" class="btn btn-sm btn-outline-secondary me-1">Buka Daftar</a>
                        <button id="admin-help-popup-toggle-visibility" class="btn btn-sm btn-outline-secondary me-1" title="Toggle Pop-up">👁</button>
                        <button id="admin-help-popup-toggle" class="btn btn-sm btn-light">_</button>
                    </div>
                </div>
                <div class="card-body p-2 chat-popup-body" id="admin-help-popup-body">
                    <div id="admin-help-popup-messages"></div>
                </div>
                <div class="card-footer p-2">
                    <div class="input-group">
                        <input type="text" id="admin-help-popup-input" class="form-control form-control-sm" placeholder="Balas pesan...">
                        <button id="admin-help-popup-send" class="btn btn-primary btn-sm">Kirim</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Toast for admin help notifications --}}
    @if(Auth::check() && Auth::user()->isAdmin())
        <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11">
            <div id="adminHelpToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="toast-header bg-warning text-dark">
                    <strong class="me-auto">Pesan Help Baru</strong>
                    <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
                <div class="toast-body" id="adminHelpToastMessage">
                    Ada pesan baru dari user di Help
                </div>
            </div>
        </div>
    @endif

    {{-- Toggle button for help popup --}}
    @if(Auth::check())
        <div id="help-toggle-btn" style="position:fixed; right:20px; bottom:80px; z-index:1999;">
            <button class="btn btn-secondary btn-sm" title="Toggle Help Pop-up">💬</button>
        </div>
    @endif

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        const sidebar = document.getElementById('sidebar');
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebarOverlay = document.getElementById('sidebarOverlay');

        function toggleSidebar() {
            sidebar.classList.toggle('active');
            sidebarOverlay.classList.toggle('active');
            document.body.classList.toggle('body-no-scroll');
        }

        if (sidebarToggle) sidebarToggle.addEventListener('click', toggleSidebar);
        if (sidebarOverlay) sidebarOverlay.addEventListener('click', toggleSidebar);

        // Simple Loader logic for links

        // Notification Bell - Update unread count and check for new notifications
        @if(Auth::check() && !Auth::user()->isAdmin())
            function updateUnreadCount() {
                fetch('{{ route("api.notifications.unread") }}')
                    .then(r => r.json())
                    .then(data => {
                        // Update desktop badge
                        const badgeDesktop = document.getElementById('unreadCountDesktop');
                        const numDesktop = document.getElementById('unreadCountNumDesktop');
                        if (data.count > 0) {
                            numDesktop.textContent = data.count;
                            badgeDesktop.style.display = 'inline-block';
                        } else {
                            badgeDesktop.style.display = 'none';
                        }

                        // Update mobile badge
                        const badgeMobile = document.getElementById('unreadCountMobile');
                        const numMobile = document.getElementById('unreadCountNumMobile');
                        if (data.count > 0) {
                            numMobile.textContent = data.count;
                            badgeMobile.style.display = 'inline-block';
                        } else {
                            badgeMobile.style.display = 'none';
                        }
                    })
                    .catch(e => console.log('Error fetching unread count:', e));
            }

            // Check on page load
            updateUnreadCount();

            // Check every 30 seconds
            setInterval(updateUnreadCount, 30000);

            // Show toast when new notifications arrive
            let lastCount = 0;
            setInterval(() => {
                fetch('{{ route("api.notifications.unread") }}')
                    .then(r => r.json())
                    .then(data => {
                        if (data.count > lastCount) {
                            const toast = new bootstrap.Toast(document.getElementById('notificationToast'));
                            document.getElementById('toastMessage').textContent = `Anda memiliki ${data.count} notifikasi baru`;
                            toast.show();
                            lastCount = data.count;
                        }
                    });
            }, 60000);
        @endif
        document.querySelectorAll('.sidebar a').forEach(link => {
            link.addEventListener('click', function(e) {
                try {
                    const href = this.getAttribute('href') || '';
                    // Skip in-page anchors or bootstrap collapse toggles (they don't navigate page)
                    if (href.startsWith('#') || this.hasAttribute('data-bs-toggle') || href.trim() === '' || href.trim().toLowerCase() === 'javascript:void(0)') {
                        return;
                    }
                    document.getElementById('loader').classList.add('active');
                } catch (err) {
                    // ignore JS errors here
                }
            });
        });
    </script>

    <script src="{{ asset('js/chat.js') }}"></script>
    <script>
        // Chat popup logic
        @if(Auth::check() && !Auth::user()->isAdmin())
            document.addEventListener('DOMContentLoaded', () => {
                const poll = ChatPopup.init({
                    activeUrl: '{{ route('help.active') }}',
                    sendUrl: '{{ route('help.popup.send') }}',
                    popupId: 'help-popup',
                    bodyId: 'help-popup-body',
                    msgsId: 'help-popup-messages',
                    inputId: 'help-popup-input',
                    sendBtnId: 'help-popup-send',
                    toggleId: 'help-popup-toggle',
                    toggleVisId: 'help-popup-toggle-visibility',
                    storageKey: 'helpPopupVisible',
                    isAdmin: false // User side
                });

                // Polling interval
                if(poll) setInterval(poll, 4000);
                if(poll) poll();

                 // Notification check
                let lastMessageCount = 0;
                setInterval(async () => {
                    const data = await poll(); 
                    try {
                        const res = await fetch('{{ route('help.active') }}');
                        const ndata = await res.json();
                        if (ndata.active && ndata.messages.length > lastMessageCount) {
                             if(lastMessageCount !== 0) { 
                                const toast = new bootstrap.Toast(document.getElementById('helpToast'));
                                document.getElementById('helpToastMessage').textContent = 'Anda memiliki pesan baru di Get Help';
                                toast.show();
                             }
                             lastMessageCount = ndata.messages.length;
                        }
                    } catch(e){}
                }, 10000);
            });
        @endif

        @if(Auth::check() && Auth::user()->isAdmin())
            document.addEventListener('DOMContentLoaded', () => {
                const poll = ChatPopup.init({
                    activeUrl: '{{ route('admin.help.active') }}',
                    sendUrl: '{{ route('admin.help.popup.send') }}',
                    popupId: 'admin-help-popup',
                    bodyId: 'admin-help-popup-body',
                    msgsId: 'admin-help-popup-messages',
                    inputId: 'admin-help-popup-input',
                    sendBtnId: 'admin-help-popup-send',
                    toggleId: 'admin-help-popup-toggle',
                    toggleVisId: 'admin-help-popup-toggle-visibility',
                    storageKey: 'adminHelpPopupVisible',
                    isAdmin: true // Admin side
                });

                 if(poll) setInterval(poll, 4000);
                 if(poll) poll();

                 // Admin Notification
                 let lastAdminMessageCount = 0;
                 setInterval(async () => {
                     try {
                        const res = await fetch('{{ route('admin.help.active') }}');
                        const data = await res.json();
                        if (data.active && data.messages.length > lastAdminMessageCount) {
                            if (lastAdminMessageCount !== 0) {
                                const toast = new bootstrap.Toast(document.getElementById('adminHelpToast'));
                                document.getElementById('adminHelpToastMessage').textContent = 'Ada pesan baru dari user di Help';
                                toast.show();
                            }
                            lastAdminMessageCount = data.messages.length;
                        }
                    } catch(e){}
                 }, 10000);
            });
        @endif

        // Toggle button for help popup
        @if(Auth::check())
        const toggleBtn = document.getElementById('help-toggle-btn');
        if (toggleBtn) {
            toggleBtn.addEventListener('click', function(){
                const popup = document.getElementById('help-popup') || document.getElementById('admin-help-popup');
                if (popup) {
                    popup.style.display = popup.style.display === 'none' ? 'block' : 'none';
                }
            });
        }
        @endif
    </script>
</body>
</html>
