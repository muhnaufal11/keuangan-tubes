<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DefaultCategory;
use App\Models\Kategori;
use App\Models\KategoriPengeluaran;
use App\Models\User;
use Illuminate\Http\Request;

class DefaultCategoryController extends Controller
{
    public function index()
    {
        $cats = DefaultCategory::orderBy('type')->orderBy('name')->paginate(25);
        return view('admin.default_categories.index', compact('cats'));
    }

    public function create()
    {
        $users = User::orderBy('username')->get();
        return view('admin.default_categories.create', compact('users'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:191',
            'type' => 'required|in:pemasukan,pengeluaran',
            'sync_all' => 'nullable|boolean',
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'integer',
        ]);
        $cat = DefaultCategory::create($request->only('name','type'));

        if ($request->boolean('sync_all')) {
            $this->syncCategoryToUsers($cat);
        } elseif ($request->filled('user_ids')) {
            $this->syncCategoryToSelectedUsers($cat, $request->user_ids);
        }

        if (class_exists(\App\Helpers\ActivityLogger::class)) {
            \App\Helpers\ActivityLogger::log(auth()->id() ?? null, 'default_category.create', $cat, 'Admin created default category');
        }

        return redirect()->route('admin.default_categories.index')->with('success','Kategori default dibuat.');
    }

    public function edit(DefaultCategory $defaultCategory)
    {
        return view('admin.default_categories.edit', ['cat' => $defaultCategory]);
    }

    public function update(Request $request, DefaultCategory $defaultCategory)
    {
        $request->validate([
            'name' => 'required|string|max:191',
            'type' => 'required|in:pemasukan,pengeluaran',
            'sync_all' => 'nullable|boolean',
        ]);
        $defaultCategory->update($request->only('name','type'));

        if ($request->boolean('sync_all')) {
            $this->syncCategoryToUsers($defaultCategory);
        }

        if (class_exists(\App\Helpers\ActivityLogger::class)) {
            \App\Helpers\ActivityLogger::log(auth()->id() ?? null, 'default_category.update', $defaultCategory, 'Admin updated default category');
        }

        return redirect()->route('admin.default_categories.index')->with('success','Kategori default diperbarui.');
    }

    public function selectUsersForSync()
    {
        $users = User::orderBy('username')->get();
        return view('admin.default_categories.select_users', compact('users'));
    }

    public function syncToUsers()
    {
        $defaults = DefaultCategory::all();
        foreach ($defaults as $cat) {
            $this->syncCategoryToUsers($cat);
        }

        if (class_exists(\App\Helpers\ActivityLogger::class)) {
            \App\Helpers\ActivityLogger::log(auth()->id() ?? null, 'default_category.sync', null, 'Admin synced default categories to all users');
        }

        return back()->with('success','Kategori default disinkronkan ke semua user.');
    }

    public function bulkSync(Request $request)
    {
        $request->validate(['user_ids' => 'required|array|min:1', 'user_ids.*' => 'integer']);

        $defaults = DefaultCategory::all();
        $userIds = $request->user_ids;

        foreach ($defaults as $cat) {
            $this->syncCategoryToSelectedUsers($cat, $userIds);
        }

        if (class_exists(\App\Helpers\ActivityLogger::class)) {
            \App\Helpers\ActivityLogger::log(auth()->id() ?? null, 'default_category.bulk_sync', null, "Admin synced default categories to " . count($userIds) . " users");
        }

        return redirect()->route('admin.default_categories.index')->with('success', 'Kategori default disinkronkan ke ' . count($userIds) . ' user.');
    }

    public function destroy(DefaultCategory $defaultCategory)
    {
        if (class_exists(\App\Helpers\ActivityLogger::class)) {
            \App\Helpers\ActivityLogger::log(auth()->id() ?? null, 'default_category.delete', $defaultCategory, 'Admin deleted default category');
        }

        $defaultCategory->delete();
        return back()->with('success','Kategori default dihapus.');
    }

    protected function syncCategoryToUsers(DefaultCategory $category): void
    {
        $users = User::pluck('id');
        $this->syncCategoryToSelectedUsers($category, $users);
    }

    protected function syncCategoryToSelectedUsers(DefaultCategory $category, array $userIds): void
    {
        foreach ($userIds as $userId) {
            if ($category->type === 'pemasukan') {
                Kategori::firstOrCreate([
                    'user_id' => $userId,
                    'nama_kategori' => $category->name,
                ]);
            } else {
                KategoriPengeluaran::firstOrCreate([
                    'user_id' => $userId,
                    'nama_kategori' => $category->name,
                ]);
            }
        }
    }
}