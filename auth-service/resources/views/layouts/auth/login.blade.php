<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Keuangan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f0f2f5; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .login-wrapper { background: white; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); overflow: hidden; max-width: 900px; width: 100%; }
        .login-image { background: linear-gradient(45deg, #0d6efd, #00c6ff); color: white; display: flex; align-items: center; justify-content: center; padding: 2rem; }
        .login-form { padding: 4rem; }
    </style>
</head>
<body>
    <div class="container p-3">
        <div class="login-wrapper row g-0">
            <div class="col-lg-6 d-none d-lg-block login-image">
                <h1>Kelola Keuanganmu</h1>
            </div>
            <div class="col-lg-6">
                <div class="login-form">
                    <h3 class="mb-4">Selamat Datang</h3>
                    
                    @if(session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif

                    <form method="POST" action="{{ route('login') }}">
                        @csrf
                        <div class="mb-3">
                            <label>Username</label>
                            <input type="text" name="username" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 mb-3">Login</button>
                    </form>

                    <div class="text-center">
                        <a href="{{ route('auth.google') }}" class="btn btn-danger w-100">
                            Login dengan Google
                        </a>
                        <p class="mt-3">Belum punya akun? <a href="{{ route('register') }}">Daftar</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>