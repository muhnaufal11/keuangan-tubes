<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sistem dalam Perbaikan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="d-flex align-items-center justify-content-center" style="min-height:100vh; background:#f8f9fa;">
    <div class="card p-4 text-center" style="max-width:560px;">
        <h3>Maaf, Sistem Sedang Perbaikan</h3>
        <p class="mt-3">{{ $message ?? 'Sistem sedang dalam perbaikan. Silakan kembali nanti.' }}</p>
        <p class="text-muted">Jika Anda admin, silakan masuk dengan akun admin untuk mengelola.</p>
        <div class="mt-3">
            @if (Route::has('login'))
                <a href="{{ route('login') }}" class="btn btn-primary">Admin Login</a>
            @endif
        </div>
    </div>
</body>
</html>