@extends('layouts.auth.forgot')

@section('content')
    <h2 class="text-center mb-4">Reset Password</h2>

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <form method="POST" action="{{ route('password.update') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $token ?? '' }}">

        <div class="mb-3">
            <label for="password" class="form-label">Password Baru</label>
            <input id="password" name="password" type="password" class="form-control" required>
            @error('password')<div class="text-danger">{{ $message }}</div>@enderror
        </div>

        <div class="mb-3">
            <label for="password_confirmation" class="form-label">Konfirmasi Password</label>
            <input id="password_confirmation" name="password_confirmation" type="password" class="form-control" required>
        </div>

        <button class="btn btn-primary w-100">Reset Password</button>
    </form>
@endsection
