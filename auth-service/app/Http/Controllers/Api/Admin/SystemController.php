<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\App;

class SystemController extends Controller
{
    public function status()
    {
        $isDown = app()->isDownForMaintenance();
        return response()->json([
            'maintenance_mode' => $isDown,
            'message' => $isDown ? 'Aplikasi sedang maintenance' : 'Aplikasi berjalan normal'
        ]);
    }

    public function toggle(Request $request)
    {
        $request->validate(['action' => 'required|in:up,down']);

        try {
            if ($request->action === 'down') {
                // Masukkan secret agar admin tetap bisa akses jika bypass diatur
                Artisan::call('down', [
                    '--secret' => 'admin-bypass-key',
                    '--render' => 'maintenance'
                ]);
                $message = 'Aplikasi masuk mode Maintenance.';
            } else {
                Artisan::call('up');
                $message = 'Aplikasi kembali Online.';
            }

            return response()->json(['message' => $message]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal mengubah status', 'error' => $e->getMessage()], 500);
        }
    }
}
