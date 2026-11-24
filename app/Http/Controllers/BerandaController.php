<?php

namespace App\Http\Controllers;

use App\Services\RingkasanBeranda;
use Illuminate\View\View;

class BerandaController extends Controller
{
    public function __invoke(): View
    {
        return view('beranda', (new RingkasanBeranda)->data());
    }
}
