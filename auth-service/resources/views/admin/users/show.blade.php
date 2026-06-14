@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h3>Detail User: {{ $user->username }}</h3>

    <table class="table">
        <tr><th>Username</th><td>{{ $user->username }}</td></tr>
        <tr><th>Email</th><td>{{ $user->email }}</td></tr>
        <tr><th>Tipe Akun</th><td>{{ $user->tipe_akun }}</td></tr>
        <tr><th>Terdaftar</th><td>{{ $user->created_at }}</td></tr>
        <tr><th>Last Login</th><td>{{ $user->last_login_at ?? '-' }}</td></tr>
        <tr><th>Status</th><td>{{ $user->is_banned ? 'Diblokir' : 'Aktif' }}</td></tr>
    </table>

    <form method="POST" action="{{ route('admin.help.start', $user->id) }}" style="display:inline-block">
        @csrf
        <button type="submit" class="btn btn-info">Respond (Get Help)</button>
    </form>
    <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">Kembali</a>
</div>
@endsection
