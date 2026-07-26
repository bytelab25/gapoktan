<?php

namespace App\Http\Controllers;

use App\Models\KategoriKas;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class KategoriKasController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:255', Rule::unique('kategori_kas', 'nama')],
        ], attributes: ['nama' => 'nama kategori']);

        $kategori = KategoriKas::create($data);

        return back()->with('sukses', "Kategori \"{$kategori->nama}\" berhasil ditambahkan.");
    }
}
