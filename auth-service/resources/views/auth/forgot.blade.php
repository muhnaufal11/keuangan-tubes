@extends('layouts.auth.forgot')

@section('content')
    <h2 class="text-center mb-4">Lupa Password</h2>

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <form method="POST" action="{{ route('password.email') }}">
        @csrf
        <div class="mb-3">
            <label for="identifier" class="form-label">Username atau Email</label>
            <input id="identifier" name="identifier" class="form-control" value="{{ old('identifier') }}" required>
            @error('identifier')<div class="text-danger">{{ $message }}</div>@enderror
        </div>
        <button class="btn btn-primary w-100">Lanjutkan</button>
    </form>
@endsection
