@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h3>Tips Keuangan</h3>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="mb-3">
        <a href="{{ route('admin.tips.create') }}" class="btn btn-primary">Tambah Tip</a>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Active</th>
                    <th>Publish At</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @foreach($tips as $t)
                    <tr>
                        <td>{{ $t->title }}</td>
                        <td>{{ $t->is_active ? 'Ya' : 'Tidak' }}</td>
                        <td>{{ $t->publish_at ? $t->publish_at->format('Y-m-d') : '-' }}</td>
                        <td class="text-end">
                            <a href="{{ route('admin.tips.edit', $t->id) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                            <form action="{{ route('admin.tips.destroy', $t->id) }}" method="POST" style="display:inline-block">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{ $tips->links() }}
</div>
@endsection