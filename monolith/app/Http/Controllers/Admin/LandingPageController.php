<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class LandingPageController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // Ambil semua settings yang berawalan 'landing_'
        $settings = Setting::where('key', 'like', 'landing_%')->get()->keyBy('key');
        return view('admin.landing.index', compact('settings'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $allowed = [
            'landing_hero_title',
            'landing_hero_subtitle',
            'landing_hero_cta_text',
            'landing_hero_image',
            'landing_hero_images',
            'landing_features_title',
            'landing_features_subtitle',
            'landing_feature_1_icon',
            'landing_feature_1_title',
            'landing_feature_1_description',
            'landing_feature_2_icon',
            'landing_feature_2_title',
            'landing_feature_2_description',
            'landing_feature_3_icon',
            'landing_feature_3_title',
            'landing_feature_3_description',
        ];

        $rules = [
            'landing_hero_title' => 'nullable|string|max:255',
            'landing_hero_subtitle' => 'nullable|string|max:1000',
            'landing_hero_cta_text' => 'nullable|string|max:100',
            'landing_hero_image' => 'nullable|string|max:1000',
            'landing_hero_images.*' => 'nullable|image|max:4096',
            'landing_features_title' => 'nullable|string|max:255',
            'landing_features_subtitle' => 'nullable|string|max:255',
            'landing_feature_1_icon' => 'nullable|string|max:50',
            'landing_feature_1_title' => 'nullable|string|max:255',
            'landing_feature_1_description' => 'nullable|string|max:500',
            'landing_feature_2_icon' => 'nullable|string|max:50',
            'landing_feature_2_title' => 'nullable|string|max:255',
            'landing_feature_2_description' => 'nullable|string|max:500',
            'landing_feature_3_icon' => 'nullable|string|max:50',
            'landing_feature_3_title' => 'nullable|string|max:255',
            'landing_feature_3_description' => 'nullable|string|max:500',
        ];

        $validated = $request->validate($rules);
        $data = $request->only($allowed);

        // Handle multi hero uploads (store to public/images/hero)
        if ($request->hasFile('landing_hero_images')) {
            $files = $request->file('landing_hero_images');
            $paths = [];
            $destHero = public_path('images/hero');
            if (!is_dir($destHero)) { @mkdir($destHero, 0755, true); }

            foreach ($files as $file) {
                if (!$file) { continue; }
                $ext = strtolower($file->getClientOriginalExtension());
                $filename = 'hero_' . uniqid() . '.' . $ext;
                $file->move($destHero, $filename);
                $paths[] = 'images/hero/' . $filename;
            }

            if (!empty($paths)) {
                $data['landing_hero_images'] = json_encode($paths);
                // Keep first as primary fallback
                $data['landing_hero_image'] = $paths[0];
            }
        }

        foreach ($data as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }

        return redirect()->route('admin.landing.index')->with('success', 'Pengaturan halaman landing berhasil diperbarui.');
    }
}
