<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Pemasukan;
use App\Models\Pengeluaran;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function stats()
    {
        return response()->json([
            'total_users' => User::count(),
            'total_transaksi_pemasukan' => Pemasukan::count(),
            'total_transaksi_pengeluaran' => Pengeluaran::count(),
            'server_time' => now()->toDateTimeString(),
        ]);
    }
}
