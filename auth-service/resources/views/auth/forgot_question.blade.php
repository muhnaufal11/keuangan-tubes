@extends('layouts.auth')

@section('content')
<div class="container">
    <h2>Jawab Pertanyaan Keamanan</h2>

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card p-4">
        <p><strong>Pertanyaan:</strong> {{ $user->security_question }}</p>

        <form method="POST" action="{{ route('password.answer') }}">
            @csrf
            <input type="hidden" name="user_id" value="{{ $user->id }}">

            <div class="mb-3">
                <label for="security_answer" class="form-label">Jawaban</label>
                <input id="security_answer" name="security_answer" class="form-control" required>
                @error('security_answer')<div class="text-danger">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Password Baru</label>
                <input id="password" name="password" type="password" class="form-control" required>
                @error('password')<div class="text-danger">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label for="password_confirmation" class="form-label">Konfirmasi Password</label>
                <input id="password_confirmation" name="password_confirmation" type="password" class="form-control" required>
            </div>

            <button class="btn btn-primary">Reset Password</button>
        </form>
    </div>
</div>
@endsection
