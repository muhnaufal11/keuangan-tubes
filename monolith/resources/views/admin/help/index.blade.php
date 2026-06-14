@extends('layouts.app')

@section('header_title', 'Help Sessions')

@section('content')
<div class="container mt-4">
    <h4>Help Sessions</h4>
    <table class="table">
        <thead><tr><th>ID</th><th>User</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
        @foreach($sessions as $s)
            <tr>
                <td>{{ $s->id }}</td>
                <td>{{ $s->user->name ?? $s->user->username }}</td>
                <td>{{ $s->status }}</td>
                <td><a href="{{ route('admin.help.show', $s->id) }}" class="btn btn-sm btn-primary">Buka</a></td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endsection
