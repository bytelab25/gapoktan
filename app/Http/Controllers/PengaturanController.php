<?php

namespace App\Http\Controllers;

use App\Models\LogAktivitas;
use App\Models\Pengaturan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PengaturanController extends Controller
{
    public function edit(): View
    {
        return view('pengaturan.edit', [
            'pengaturan' => Pengaturan::ambil(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'suku_bunga_pinjaman' => ['required', 'numeric', 'min:0', 'max:100'],
            'potongan_operasional' => ['required', 'numeric', 'min:0', 'max:100'],
            'potongan_dana_talangan' => ['required', 'numeric', 'min:0', 'max:100'],
        ], attributes: [
            'suku_bunga_pinjaman' => 'suku bunga pinjaman',
            'potongan_operasional' => 'potongan operasional',
            'potongan_dana_talangan' => 'potongan dana talangan',
        ]);

        Pengaturan::ambil()->update($data);

        // Transaksi lama memakai persentase yang sudah disimpan, jadi perubahan
        // di sini hanya berlaku untuk transaksi berikutnya.
        LogAktivitas::catat(LogAktivitas::TINDAKAN_SUNTING, 'Mengubah pengaturan sistem koperasi');

        return redirect()
            ->route('pengaturan.edit')
            ->with('sukses', 'Pengaturan berhasil disimpan. Berlaku untuk transaksi berikutnya.');
    }
}
