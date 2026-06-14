<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tip;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TipController extends Controller
{
    public function index()
    {
        $tips = Tip::orderBy('created_at','desc')->paginate(20);
        return view('admin.tips.index', compact('tips'));
    }

    public function create()
    {
        return view('admin.tips.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'image' => 'nullable|image|max:2048',
            'is_active' => 'nullable|boolean',
            'publish_at' => 'nullable|date'
        ]);

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('tips');
            $data['image'] = $path;
        }
        $data['is_active'] = $request->has('is_active') ? 1 : 0;
        Tip::create($data);
        return redirect()->route('admin.tips.index')->with('success','Tip dibuat.');
    }

    public function edit(Tip $tip)
    {
        return view('admin.tips.edit', compact('tip'));
    }

    public function update(Request $request, Tip $tip)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'image' => 'nullable|image|max:2048',
            'is_active' => 'nullable|boolean',
            'publish_at' => 'nullable|date'
        ]);
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('tips');
            $data['image'] = $path;
        }
        $data['is_active'] = $request->has('is_active') ? 1 : 0;
        $tip->update($data);
        return redirect()->route('admin.tips.index')->with('success','Tip diperbarui.');
    }

    public function destroy(Tip $tip)
    {
        if ($tip->image) Storage::delete($tip->image);
        $tip->delete();
        return back()->with('success','Tip dihapus.');
    }
}
