@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h3>Activity Logs</h3>

    <form method="GET" class="row g-2 mb-3">
        <div class="col-auto">
            <select name="action" class="form-control">
                <option value="">-- Semua Aksi --</option>
                <option value="user.ban">user.ban</option>
                <option value="user.unban">user.unban</option>
                <option value="user.reset_password">user.reset_password</option>
                <option value="default_category.create">default_category.create</option>
                <option value="default_category.update">default_category.update</option>
                <option value="default_category.delete">default_category.delete</option>
            </select>
        </div>
        <div class="col-auto">
            <input type="text" name="actor" placeholder="Actor ID" class="form-control" value="{{ request('actor') }}">
        </div>
        <div class="col-auto">
            <button class="btn btn-primary">Filter</button>
        </div>
    </form>

    <table class="table table-striped">
        <thead><tr><th>Waktu</th><th>Actor</th><th>Aksi</th><th>Target</th><th>Deskripsi</th></tr></thead>
        <tbody>
            @foreach($logs as $l)
                <tr>
                    <td>{{ $l->created_at }}</td>
                    <td>{{ $l->actor ? $l->actor->username . ' (ID: '.$l->actor->id.')' : '-' }}</td>
                    <td>{{ $l->action }}</td>
                    <td>{{ $l->target_type ? class_basename($l->target_type).'#'.$l->target_id : '-' }}</td>
                    <td>{{ $l->description }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{ $logs->links() }}
</div>
@endsection