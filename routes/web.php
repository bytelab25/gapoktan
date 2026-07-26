<?php

use App\Http\Controllers\BerandaController;
use App\Http\Controllers\DanaTalanganController;
use App\Http\Controllers\KasController;
use App\Http\Controllers\KategoriKasController;
use App\Http\Controllers\KelompokTaniController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\LogAktivitasController;
use App\Http\Controllers\PembayaranPinjamanController;
use App\Http\Controllers\PengaturanController;
use App\Http\Controllers\PenggunaController;
use App\Http\Controllers\PinjamanController;
use App\Http\Controllers\ProfilController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/beranda');

Route::middleware('auth')->group(function () {
    Route::get('beranda', BerandaController::class)->name('beranda');

    Route::resource('pengguna', PenggunaController::class);

    // Rute khusus didaftarkan sebelum resource agar tidak tertangkap pola show.
    Route::get('kelompok/{kelompok}/transaksi', [KelompokTaniController::class, 'transaksi'])->name('kelompok.transaksi');
    Route::resource('kelompok', KelompokTaniController::class);

    Route::get('kas/{kas}/faktur', [KasController::class, 'faktur'])->name('kas.faktur');
    Route::resource('kas', KasController::class)->parameters(['kas' => 'kas']);
    Route::post('kategori-kas', KategoriKasController::class)->name('kategori-kas.store');

    Route::get('pinjaman/{pinjaman}/faktur', [PinjamanController::class, 'faktur'])->name('pinjaman.faktur');
    Route::get('pinjaman/{pinjaman}/bayar', [PembayaranPinjamanController::class, 'create'])->name('pinjaman.bayar');
    Route::post('pinjaman/{pinjaman}/bayar', [PembayaranPinjamanController::class, 'store'])->name('pinjaman.bayar.store');
    Route::resource('pinjaman', PinjamanController::class);
    Route::get('pembayaran/{pembayaran}/faktur', [PembayaranPinjamanController::class, 'faktur'])->name('pembayaran.faktur');

    Route::get('talangan/{talangan}/faktur', [DanaTalanganController::class, 'faktur'])->name('talangan.faktur');
    Route::resource('talangan', DanaTalanganController::class);

    Route::get('laporan', [LaporanController::class, 'index'])->name('laporan.index');
    Route::get('laporan/hasil', [LaporanController::class, 'hasil'])->name('laporan.hasil');

    Route::get('log', LogAktivitasController::class)->name('log.index');

    Route::get('profil', [ProfilController::class, 'show'])->name('profil.show');
    Route::get('profil/sunting', [ProfilController::class, 'edit'])->name('profil.edit');
    Route::put('profil', [ProfilController::class, 'update'])->name('profil.update');

    Route::get('pengaturan', [PengaturanController::class, 'edit'])->name('pengaturan.edit');
    Route::put('pengaturan', [PengaturanController::class, 'update'])->name('pengaturan.update');
});

require __DIR__.'/auth.php';
