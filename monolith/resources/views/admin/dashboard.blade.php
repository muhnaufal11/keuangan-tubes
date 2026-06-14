@extends('layouts.app')

@section('content')
<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Admin Dashboard</h2>
        <div class="btn-group" role="group">
            <a href="{{ route('admin.insights') }}" class="btn btn-sm btn-outline-info">📊 User Insights</a>
            <a href="{{ route('admin.notifications.index') }}" class="btn btn-sm btn-outline-warning">📢 Notifikasi</a>
            <a href="{{ route('admin.users.index') }}" class="btn btn-sm btn-outline-primary">👥 Manajemen User</a>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted">Total User</h6>
                    <h2 class="mb-0">{{ $totalUsers }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted">Aktif Hari Ini</h6>
                    <h2 class="mb-0">{{ $activeToday->count() }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted">Aktif Bulan Ini</h6>
                    <h2 class="mb-0">{{ $activeThisMonth->count() }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted">Total Transaksi</h6>
                    <h2 class="mb-0">{{ $totalTransactions }}</h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Financial Overview -->
    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="text-success mb-2">💰 Total Pemasukan</h6>
                    <h3 class="mb-0">Rp {{ number_format($totalIncome, 0, ',', '.') }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="text-danger mb-2">📉 Total Pengeluaran</h6>
                    <h3 class="mb-0">Rp {{ number_format($totalExpense, 0, ',', '.') }}</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light">
                    <h6 class="mb-0">📈 Pemasukan & Pengeluaran (12 Bulan)</h6>
                </div>
                <div class="card-body">
                    <canvas id="incomeExpenseChart" height="300"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light">
                    <h6 class="mb-0">👥 Pertumbuhan User (12 Bulan)</h6>
                </div>
                <div class="card-body">
                    <canvas id="userChart" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Category Analysis -->
    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light">
                    <h6 class="mb-0">🟢 Top 5 Kategori Pemasukan</h6>
                </div>
                <div class="card-body">
                    @forelse($topIncomeCategories as $cat)
                        <div class="d-flex justify-content-between mb-3">
                            <div>
                                <strong>{{ $cat->kategori }}</strong>
                                <br><small class="text-muted">{{ $cat->count }} transaksi</small>
                            </div>
                            <div class="text-end">
                                <strong class="text-success">Rp {{ number_format($cat->total, 0, ',', '.') }}</strong>
                            </div>
                        </div>
                        @if(!$loop->last)<hr>@endif
                    @empty
                        <p class="text-muted">Tidak ada data pemasukan</p>
                    @endforelse
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light">
                    <h6 class="mb-0">🔴 Top 5 Kategori Pengeluaran</h6>
                </div>
                <div class="card-body">
                    @forelse($topExpenseCategories as $cat)
                        <div class="d-flex justify-content-between mb-3">
                            <div>
                                <strong>{{ $cat->kategori }}</strong>
                                <br><small class="text-muted">{{ $cat->count }} transaksi</small>
                            </div>
                            <div class="text-end">
                                <strong class="text-danger">Rp {{ number_format($cat->total, 0, ',', '.') }}</strong>
                            </div>
                        </div>
                        @if(!$loop->last)<hr>@endif
                    @empty
                        <p class="text-muted">Tidak ada data pengeluaran</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Income vs Expense Chart
const ctx1 = document.getElementById('incomeExpenseChart').getContext('2d');
new Chart(ctx1, {
    type: 'line',
    data: {
        labels: {!! json_encode($trendLabels) !!},
        datasets: [
            {
                label: 'Pemasukan',
                data: {!! json_encode($incomeData) !!},
                borderColor: '#198754',
                backgroundColor: 'rgba(25,135,84,0.1)',
                tension: 0.4
            },
            {
                label: 'Pengeluaran',
                data: {!! json_encode($expenseData) !!},
                borderColor: '#dc3545',
                backgroundColor: 'rgba(220,53,69,0.1)',
                tension: 0.4
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: { beginAtZero: true }
        }
    }
});

// User Growth Chart
const ctx2 = document.getElementById('userChart').getContext('2d');
new Chart(ctx2, {
    type: 'bar',
    data: {
        labels: {!! json_encode($labels) !!},
        datasets: [{
            label: 'Pendaftar Baru',
            data: {!! json_encode($data) !!},
            backgroundColor: '#0d6efd'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: { beginAtZero: true }
        }
    }
});
</script>
@endsection