<p>Halo {{ $user->username }},</p>

<p>Anda menerima email ini karena ada permintaan untuk mereset password akun Anda.</p>

<p>Silakan klik tautan berikut untuk mereset password Anda:</p>

<p><a href="{{ url('/password/reset/' . $token) }}">Reset Password</a></p>

<p>Jika tautan di atas tidak dapat diklik, gunakan token berikut pada halaman reset: <strong>{{ $token }}</strong></p>

<p>Token ini berlaku selama 1 jam.</p>

<p>Jika Anda tidak meminta reset password, abaikan email ini.</p>

<p>Salam,<br>Tim Keuangan App</p>
