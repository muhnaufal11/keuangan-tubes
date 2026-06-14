<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Setting;

class PublicController extends Controller
{
    public function index()
    {
        $settings = Setting::where('key', 'like', 'landing_%')->get()->keyBy('key');
        return view('landing', compact('settings'));
    }
}
