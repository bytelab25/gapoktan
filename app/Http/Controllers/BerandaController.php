<?php

namespace App\Http\Controllers;

use App\Models\DanaTalangan;
use App\Models\Pinjaman;
use App\Models\TransaksiKas;
use Illuminate\View\View;

class BerandaController extends Controller
{
    public function __invoke(): View
    {
        // "Dana beredar" adalah sisa pokok seluruh pinjaman yang belum lunas.
        $danaBeredar = Pinjaman::with('pembayaran')
            ->where('status', '!=', Pinjaman::STATUS_LUNAS)
            ->get()
            ->sum(fn (Pinjaman $pinjaman) => $pinjaman->sisa_pokok);

        return view('beranda', [
            'saldoKas' => TransaksiKas::saldoTotal(),
            'saldoTalangan' => DanaTalangan::saldoTotal(),
            'danaBeredar' => $danaBeredar,
        ]);
    }
}
