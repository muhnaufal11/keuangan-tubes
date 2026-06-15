<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DefaultCategory;
use App\Models\Kategori;
use App\Models\KategoriPengeluaran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CategoryController extends Controller
{
    private function uid(Request $r)
    {
        return $r->attributes->get('user_id');
    }

    public function index(Request $r)
    {
        $type = $r->query('type', 'expense');
        $data = $type === 'income'
            ? Kategori::where('user_id', $this->uid($r))->get()
            : KategoriPengeluaran::where('user_id', $this->uid($r))->get();

        return response()->json(['type' => $type, 'data' => $data]);
    }

    public function store(Request $r)
    {
        $v = Validator::make($r->all(), [
            'nama_kategori' => 'required|string',
            'type' => 'required|in:income,expense',
        ]);
        if ($v->fails()) return response()->json($v->errors(), 422);

        $cat = $r->type === 'income'
            ? Kategori::create(['user_id' => $this->uid($r), 'nama_kategori' => $r->nama_kategori])
            : KategoriPengeluaran::create(['user_id' => $this->uid($r), 'nama_kategori' => $r->nama_kategori]);

        return response()->json(['message' => 'Kategori dibuat', 'data' => $cat], 201);
    }

    public function defaults()
    {
        return response()->json(['data' => DefaultCategory::all()]);
    }

    /** Salin kategori default ke kategori milik user (idempotent berdasarkan nama). */
    public function syncDefaults(Request $r)
    {
        $uid = $this->uid($r);
        $created = ['income' => 0, 'expense' => 0];

        foreach (DefaultCategory::all() as $d) {
            if ($d->type === 'pemasukan') {
                if (!Kategori::where('user_id', $uid)->where('nama_kategori', $d->name)->exists()) {
                    Kategori::create(['user_id' => $uid, 'nama_kategori' => $d->name]);
                    $created['income']++;
                }
            } else {
                if (!KategoriPengeluaran::where('user_id', $uid)->where('nama_kategori', $d->name)->exists()) {
                    KategoriPengeluaran::create(['user_id' => $uid, 'nama_kategori' => $d->name]);
                    $created['expense']++;
                }
            }
        }

        return response()->json(['message' => 'Kategori default disinkronkan', 'created' => $created]);
    }
}
