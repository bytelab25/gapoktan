<?php

namespace App\Http\Controllers;

use App\Models\LogAktivitas;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LogAktivitasController extends Controller
{
    public function __invoke(Request $request): View
    {
        $log = LogAktivitas::query()
            ->with('user')
            ->when($request->date('dari'), fn ($query, $dari) => $query->whereDate('created_at', '>=', $dari))
            ->when($request->date('sampai'), fn ($query, $sampai) => $query->whereDate('created_at', '<=', $sampai))
            ->when($request->string('tindakan')->trim()->value(), fn ($query, $tindakan) => $query->where('jenis_tindakan', $tindakan))
            ->latest()
            ->paginate($request->integer('per_halaman', 10))
            ->withQueryString();

        return view('log.index', [
            'daftarLog' => $log,
        ]);
    }
}
