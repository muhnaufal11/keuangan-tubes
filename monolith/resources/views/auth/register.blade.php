<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun Baru</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    
    <style>
        body {
            background-color: #f0f2f5;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .register-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.08);
            overflow: hidden;
            width: 100%;
            max-width: 500px;
            padding: 2rem;
        }
        .logo-area {
            text-align: center;
            margin-bottom: 1.5rem;
        }
        .logo-area img {
            width: 80px; /* Ukuran Logo */
            height: auto;
            margin-bottom: 10px;
        }
        .form-control, .form-select {
            border-radius: 10px;
            padding: 12px;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
        }
        .form-control:focus, .form-select:focus {
            background-color: #fff;
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.15);
        }
        .btn-register {
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
            background-color: #0d6efd;
            border: none;
        }
        .btn-register:hover {
            background-color: #0b5ed7;
        }
        .login-link {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.9rem;
        }
        .login-link a {
            text-decoration: none;
            color: #0d6efd;
            font-weight: 600;
        }
    </style>
</head>
<body>

    <div class="register-card">
        
        <div class="logo-area">
            <img src="{{ asset('images/logo.svg') }}" alt="Logo Aplikasi">
            <h4>Buat Akun Baru</h4>
            <p class="text-muted small">Kelola keuanganmu dengan lebih baik</p>
        </div>

        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <ul class="mb-0 ps-3">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <form method="POST" action="{{ route('register') }}">
            @csrf
            
            <div class="mb-3">
                <label class="form-label text-muted small">Username</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-person"></i></span>
                    <input type="text" name="username" class="form-control border-start-0 ps-0" placeholder="Pilih username unik" required value="{{ old('username') }}">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label text-muted small">Password</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-lock"></i></span>
                    <input type="password" name="password" class="form-control border-start-0 ps-0" placeholder="Minimal 6 karakter" required>
                </div>
            </div>

            <hr class="my-4 text-muted opacity-25">
            <p class="small text-muted mb-2"><i class="bi bi-shield-lock"></i> Keamanan Akun (Untuk Reset Password)</p>

            <div class="mb-3">
                <label class="form-label text-muted small">Pertanyaan Keamanan</label>
                <select name="security_question" class="form-select" required>
                    <option value="" selected disabled>Pilih pertanyaan...</option>
                    <option value="Siapa nama hewan peliharaan pertama Anda?">Siapa nama hewan peliharaan pertama Anda?</option>
                    <option value="Apa nama jalan tempat Anda tinggal saat kecil?">Apa nama jalan tempat Anda tinggal saat kecil?</option>
                    <option value="Siapa nama guru favorit Anda?">Siapa nama guru favorit Anda?</option>
                    <option value="Di kota mana ibu Anda lahir?">Di kota mana ibu Anda lahir?</option>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label text-muted small">Jawaban Anda</label>
                <input type="text" name="security_answer" class="form-control" placeholder="Tulis jawaban rahasia..." required>
            </div>

            <button type="submit" class="btn btn-primary w-100 btn-register mt-3">
                Daftar Sekarang
            </button>
        </form>

        <div class="login-link">
            Sudah punya akun? <a href="{{ route('login') }}">Masuk di sini</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>