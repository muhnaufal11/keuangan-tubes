<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Setting;

class SystemController extends Controller
{
    public function showMaintenance()
    {
        $enabled = Setting::get('maintenance_mode', '0');
        $message = Setting::get('maintenance_message', 'Sistem sedang dalam perbaikan.');
        return view('admin.system.maintenance', compact('enabled','message'));
    }

    public function toggleMaintenance(Request $request)
    {
        $data = $request->validate([
            'maintenance_mode' => 'nullable|in:1',
            'maintenance_message' => 'nullable|string'
        ]);

        Setting::set('maintenance_mode', $request->has('maintenance_mode') ? '1' : '0');
        Setting::set('maintenance_message', $request->input('maintenance_message','Sistem sedang dalam perbaikan.'));

        return back()->with('success','Pengaturan maintenance diperbarui.');
    }
}
