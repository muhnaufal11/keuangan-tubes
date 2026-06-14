<!DOCTYPE html>
<html lang="id">
<head>
  <!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=AW-17526921276"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', 'AW-17526921276');
</script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Aplikasi Keuangan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f0f2f5;
        }
        .main-container {
            display: flex;
            min-height: 100vh;
            width: 100%;
            align-items: center;
            justify-content: center;
        }
        .login-wrapper {
            background-color: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
            max-width: 900px;
            width: 100%;
        }
        .login-image {
            background: linear-gradient(45deg, #0d6efd, #00c6ff);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            padding: 2rem;
            text-align: center;
        }
        .login-image h1 {
            font-size: 3.5rem;
            font-weight: 900;
            opacity: 0;
            transition: opacity 0.8s ease-in-out;
            margin-top: 250px;
        }
        .login-form {
            padding: 4rem;
        }
        .form-control {
            border-radius: 10px;
            padding: 12px 20px;
            border: 1px solid #e0e0e0;
        }
        .btn {
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
        }
        .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
        }
        .btn-danger {
            background-color: #DB4437;
            border-color: #DB4437;
        }
        .loader-wrapper {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(5px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 2000;
            opacity: 0;
            transition: opacity 0.3s ease-in-out;
        }
        .loader-wrapper.active {
            display: flex;
            opacity: 1;
        }
        .loader {
            border: 8px solid #f3f3f3;
            border-radius: 50%;
            border-top: 8px solid #007bff;
            width: 60px;
            height: 60px;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        @media (max-width: 991.98px) {
            .login-form {
                padding: 2.5rem;
            }
            .login-wrapper {
                box-shadow: none;
                border-radius: 0;
            }
            .main-container {
                padding: 0;
            }
        }
    </style>
</head>
<body>
    <div class="loader-wrapper" id="loader">
        <div class="loader"></div>
    </div>
    <div class="main-container p-3 p-lg-4">
        <div class="login-wrapper row g-0">
            <div class="col-lg-6 d-none d-lg-block login-image">
                <h1 id="animated-text"></h1>
            </div>
            <div class="col-lg-6">
                <div class="login-form">
                    <div class="text-center mb-4">
                        <img src="{{ asset('logo1.svg') }}" alt="Logo Keuangan" style="width: 100px; height: auto; margin-bottom: 1rem;">
                        <h3>Selamat Datang</h3>
                        <p class="text-muted">Silakan login untuk melanjutkan</p>
                    </div>

                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif

                    @if($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login') }}">
                        @csrf
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" required value="{{ old('username') }}">
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="d-flex justify-content-end align-items-center mb-3">
                            <small><a href="{{ route('password.request') }}">Lupa Password?</a></small>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Login</button>
                        </div>
                    </form>

                    <div class="d-flex align-items-center my-3">
                        <hr class="flex-grow-1">
                        <span class="mx-2 text-muted small">atau</span>
                        <hr class="flex-grow-1">
                    </div>

                    @if(Route::has('auth.google'))
                    <div class="d-grid">
                        <a href="{{ route('auth.google') }}" class="btn btn-danger">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-google me-2" viewBox="0 0 16 16">
                                <path d="M15.545 6.558a9.42 9.42 0 0 1 .139 1.626c0 2.434-.87 4.492-2.384 5.885h.002C11.978 15.292 10.158 16 8 16A8 8 0 1 1 8 0a7.689 7.689 0 0 1 5.352 2.082l-2.284 2.284A4.347 4.347 0 0 0 8 3.166c-2.087 0-3.86 1.408-4.492 3.304a4.792 4.792 0 0 0 0 3.063h.003c.635 1.893 2.405 3.301 4.492 3.301 1.078 0 2.004-.276 2.722-.764h-.003a3.702 3.702 0 0 0 1.599-2.431H8v-3.08h7.545z"/>
                            </svg>
                            Login dengan Google
                        </a>
                    </div>
                    @endif
                    <div class="text-center mt-4">
                        <small class="text-muted">Belum punya akun?
                            @if(Route::has('register'))
                                <a href="{{ route('register') }}">Daftar di sini</a>
                            @else
                                <span class="text-muted">Hubungi admin</span>
                            @endif
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const loader = document.getElementById('loader');
        const form = document.querySelector('form');

        if (form) {
            form.addEventListener('submit', function() {
                loader.classList.add('active');
            });
        }

        const textElement = document.getElementById('animated-text');
        const phrases = [
            "Kelola Keuanganmu",
            "Catat Pemasukan",
            "Lacak Pengeluaran",
            "Raih Tujuan Finansial"
        ];
        let currentIndex = 0;

        function changeText() {
            textElement.style.opacity = 0;
            setTimeout(() => {
                currentIndex = (currentIndex + 1) % phrases.length;
                textElement.textContent = phrases[currentIndex];
                textElement.style.opacity = 1;
            }, 800);
        }

        textElement.textContent = phrases[currentIndex];
        textElement.style.opacity = 1;

        setInterval(changeText, 4000);
    });
    </script>

</body>
</html>
