<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keuangan App - Kelola Uangmu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .hero-section {
            background: linear-gradient(135deg, #0d6efd 0%, #0043a8 100%);
            color: white;
            padding: 100px 0;
            border-bottom-right-radius: 50px;
            border-bottom-left-radius: 50px;
            position: relative;
            overflow: hidden;
        }
        .hero-title { font-weight: 800; font-size: 3.5rem; }
        .feature-card {
            border: none;
            border-radius: 15px;
            transition: transform 0.3s;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            height: 100%;
        }
        .feature-card:hover { transform: translateY(-10px); }
        .feature-icon {
            font-size: 2.5rem;
            color: #0d6efd;
            margin-bottom: 20px;
        }
        .cta-section { background-color: #f8f9fa; padding: 80px 0; }
        .navbar-brand { font-weight: 800; color: #0d6efd !important; }
        .btn-light-primary {
            background-color: white;
            color: #0d6efd;
            font-weight: 600;
            border: none;
            padding: 12px 30px;
        }
        .btn-light-primary:hover { background-color: #f0f0f0; }

        /* Modern hero slider styles */
        .hero-slider {
            border-radius: 22px;
            overflow: hidden;
            box-shadow: 0 20px 50px rgba(0,0,0,0.15);
            background: linear-gradient(135deg, rgba(255,255,255,0.08), rgba(13,110,253,0.08));
        }
        .hero-slider .carousel-inner {
            transition: transform 0.9s cubic-bezier(0.35, 0.85, 0.25, 1), -webkit-transform 0.9s cubic-bezier(0.35, 0.85, 0.25, 1);
        }
        .hero-slider .carousel-item {
            transition: opacity 0.6s ease;
        }
        .hero-slider .carousel-item img {
            object-fit: cover;
            width: 100%;
            height: auto;
            animation: hero-zoom 14s ease-in-out infinite;
        }
        .hero-slider .carousel-item:nth-child(2) img { animation-delay: 1.5s; }
        .hero-slider .carousel-item:nth-child(3) img { animation-delay: 3s; }
        .hero-slider .carousel-item:nth-child(4) img { animation-delay: 4.5s; }
        .hero-slider .carousel-control-prev-icon,
        .hero-slider .carousel-control-next-icon {
            filter: drop-shadow(0 4px 8px rgba(0,0,0,0.35));
        }
        @keyframes hero-zoom {
            0% { transform: scale(1) translate3d(0,0,0); }
            40% { transform: scale(1.04) translate3d(0, -4px, 0); }
            70% { transform: scale(1.06) translate3d(0, 4px, 0); }
            100% { transform: scale(1) translate3d(0,0,0); }
        }
        /* Soft blur glow behind slider */
        .hero-section::after {
            content: '';
            position: absolute;
            inset: 10% 5% auto 5%;
            height: 200px;
            background: radial-gradient(circle at 50% 50%, rgba(255,255,255,0.2), transparent 60%);
            filter: blur(40px);
            z-index: 0;
        }
        .hero-section .container { position: relative; z-index: 1; }
    </style>
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light fixed-top bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="#">
                <img src="{{ asset('images/logo1.svg') }}" alt="Logo" width="30" height="30" class="d-inline-block align-text-top me-2">
                KEUANGAN
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    @if (Route::has('login'))
                        @auth
                            <li class="nav-item">
                                <a href="{{ url('/dashboard') }}" class="btn btn-primary rounded-pill">Dashboard</a>
                            </li>
                        @else
                            <li class="nav-item me-2">
                                <a href="{{ route('login') }}" class="btn btn-outline-primary rounded-pill px-4">Masuk</a>
                            </li>
                            @if (Route::has('register'))
                                <li class="nav-item">
                                    <a href="{{ route('register') }}" class="btn btn-primary rounded-pill px-4">Daftar</a>
                                </li>
                            @endif
                        @endauth
                    @endif
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section text-center">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <h1 class="hero-title mb-4">{{ data_get($settings, 'landing_hero_title.value', 'Kelola Keuangan Anda dengan Lebih Cerdas') }}</h1>
                    <p class="lead mb-5 opacity-75">{{ data_get($settings, 'landing_hero_subtitle.value', 'Catat pemasukan, atur pengeluaran, dan pantau arus kas Anda dalam satu aplikasi yang mudah digunakan.') }}</p>
                    @auth
                        <a href="{{ route('dashboard') }}" class="btn btn-light-primary btn-lg rounded-pill shadow">Buka Dashboard</a>
                    @else
                        <a href="{{ route('register') }}" class="btn btn-light-primary btn-lg rounded-pill shadow">{{ data_get($settings, 'landing_hero_cta_text.value', 'Mulai Sekarang Gratis') }}</a>
                    @endauth
                </div>
            </div>
            @php($heroArrJson = data_get($settings, 'landing_hero_images.value'))
            @php($heroArr = $heroArrJson ? json_decode($heroArrJson, true) : [])
            @php($heroFallback = data_get($settings, 'landing_hero_image.value', 'https://placehold.co/800x400/2962ff/ffffff?text=Dashboard+Preview'))
            @php($heroSlides = !empty($heroArr) ? $heroArr : [$heroFallback])

            <div class="mt-5">
                @if(count($heroSlides) > 1)
                    <div id="heroCarousel" class="carousel slide hero-slider" data-bs-ride="carousel" data-bs-interval="5200">
                        <div class="carousel-inner">
                            @foreach($heroSlides as $idx => $img)
                                <div class="carousel-item {{ $idx === 0 ? 'active' : '' }}">
                                    <img src="{{ $img }}" class="d-block w-100 rounded-4 shadow-lg" alt="Hero {{ $idx+1 }}" style="border:5px solid rgba(255,255,255,0.2);">
                                </div>
                            @endforeach
                        </div>
                        <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
                            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Previous</span>
                        </button>
                        <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
                            <span class="carousel-control-next-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Next</span>
                        </button>
                    </div>
                @else
                    <img src="{{ $heroSlides[0] }}" alt="App Preview" class="img-fluid rounded-4 shadow-lg" style="border: 5px solid rgba(255,255,255,0.2);">
                @endif
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-5 mt-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="fw-bold">{{ data_get($settings, 'landing_features_title.value', 'Fitur Unggulan') }}</h2>
                <p class="text-muted">{{ data_get($settings, 'landing_features_subtitle.value', 'Semua yang Anda butuhkan untuk mengatur dompet Anda.') }}</p>
            </div>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card feature-card p-4 text-center">
                        <div class="feature-icon">{{ data_get($settings, 'landing_feature_1_icon.value', '💸') }}</div>
                        <h5>{{ data_get($settings, 'landing_feature_1_title.value', 'Pencatatan Mudah') }}</h5>
                        <p class="text-muted">{{ data_get($settings, 'landing_feature_1_description.value', 'Catat pemasukan dan pengeluaran harian Anda hanya dalam beberapa klik. Simpel dan cepat.') }}</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card feature-card p-4 text-center">
                        <div class="feature-icon">{{ data_get($settings, 'landing_feature_2_icon.value', '🏦') }}</div>
                        <h5>{{ data_get($settings, 'landing_feature_2_title.value', 'Multi Rekening') }}</h5>
                        <p class="text-muted">{{ data_get($settings, 'landing_feature_2_description.value', 'Kelola saldo dari berbagai sumber: Tunai, Bank, atau E-Wallet dalam satu tempat.') }}</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card feature-card p-4 text-center">
                        <div class="feature-icon">{{ data_get($settings, 'landing_feature_3_icon.value', '📊') }}</div>
                        <h5>{{ data_get($settings, 'landing_feature_3_title.value', 'Laporan Ringkas') }}</h5>
                        <p class="text-muted">{{ data_get($settings, 'landing_feature_3_description.value', 'Pantau kesehatan finansial Anda melalui dashboard yang informatif dan mudah dipahami.') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-light py-4 mt-5">
        <div class="container text-center">
            <p class="text-muted mb-0">&copy; {{ date('Y') }} Keuangan App. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>