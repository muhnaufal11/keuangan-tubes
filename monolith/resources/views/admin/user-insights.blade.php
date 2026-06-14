@extends('layouts.app')

@section('content')
<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>📊 User Insights & Behavior</h2>
        <a href="{{ route('admin.dashboard') }}" class="btn btn-sm btn-outline-primary">← Kembali Dashboard</a>
    </div>

    <!-- Summary Stats -->
    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted">💵 Avg Spending per User</h6>
                    <h3 class="mb-0">Rp {{ number_format($avgSpending, 0, ',', '.') }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted">💰 Avg Income per User</h6>
                    <h3 class="mb-0">Rp {{ number_format($avgIncome, 0, ',', '.') }}</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Two Columns -->
    <div class="row g-4 mb-4">
        <!-- Most Active Users -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light">
                    <h6 class="mb-0">🔥 Top 10 Most Active Users</h6>
                </div>
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Last Activity</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($mostActiveUsers as $user)
                                <tr>
                                    <td>
                                        <a href="{{ route('admin.users.show', $user->id) }}" class="text-decoration-none">
                                            <strong>{{ $user->username }}</strong>
                                        </a>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            {{ $user->last_login_at ? $user->last_login_at->diffForHumans() : 'Belum login' }}
                                        </small>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="2" class="text-muted text-center">Tidak ada data</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Top Spenders -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light">
                    <h6 class="mb-0">💸 Top 10 Spenders</h6>
                </div>
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Total Spent</th>
                                <th>Transaksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($topSpenders as $record)
                                <tr>
                                    <td>
                                        @if($record->user)
                                            <a href="{{ route('admin.users.show', $record->user->id) }}" class="text-decoration-none">
                                                <strong>{{ $record->user->username }}</strong>
                                            </a>
                                        @else
                                            <span class="text-muted">Deleted User</span>
                                        @endif
                                    </td>
                                    <td><strong class="text-danger">Rp {{ number_format($record->total_spent, 0, ',', '.') }}</strong></td>
                                    <td><span class="badge bg-secondary">{{ $record->transaction_count }}</span></td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="text-muted text-center">Tidak ada data pengeluaran</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- User Stats Breakdown -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-light">
            <h6 class="mb-0">📈 User Financial Summary (Top 20)</h6>
        </div>
        <div class="table-responsive">
            <table class="table mb-0 small">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th class="text-end">Total Income</th>
                        <th class="text-end">Total Spending</th>
                        <th class="text-end">Balance</th>
                        <th>Last Login</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($userStats as $user)
                        <tr>
                            <td>
                                <a href="{{ route('admin.users.show', $user->id) }}" class="text-decoration-none">
                                    {{ $user->username }}
                                    @if($user->is_banned)
                                        <br><span class="badge bg-danger">Banned</span>
                                    @endif
                                </a>
                            </td>
                            <td class="text-end">
                                <span class="text-success"><strong>Rp {{ number_format($user->total_income, 0, ',', '.') }}</strong></span>
                            </td>
                            <td class="text-end">
                                <span class="text-danger"><strong>Rp {{ number_format($user->total_spending, 0, ',', '.') }}</strong></span>
                            </td>
                            <td class="text-end">
                                <strong>Rp {{ number_format($user->total_income - $user->total_spending, 0, ',', '.') }}</strong>
                            </td>
                            <td>
                                <small class="text-muted">
                                    {{ $user->last_login_at ? $user->last_login_at->format('d M Y') : 'Never' }}
                                </small>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-muted text-center py-4">Tidak ada user aktif</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
