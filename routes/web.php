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

Route::get('/', function () {
    return redirect('/beranda');
});

Route::middleware('auth')->group(function () {
    /*
     * Rute tulis didaftarkan lebih dulu supaya "kas/create" dan kawan-kawan
     * tidak tertangkap pola "kas/{kas}" milik rute lihat.
     *
     * Sekretaris dan bendahara boleh mengubah data operasional.
     * Role "user" tidak punya satu pun rute tulis.
     */
    Route::middleware('can:kelola')->group(function () {
        Route::get('pembayaran', [PembayaranPinjamanController::class, 'pilih'])->name('pembayaran.pilih');
        Route::get('kelompok/{kelompok}/bayar', [PembayaranPinjamanController::class, 'create'])->name('pembayaran.create');
        Route::post('kelompok/{kelompok}/bayar', [PembayaranPinjamanController::class, 'store'])->name('pembayaran.store');

        Route::resource('kelompok', KelompokTaniController::class)->except(['index', 'show']);
        Route::resource('kas', KasController::class)->except(['index', 'show'])->parameters(['kas' => 'kas']);
        Route::resource('pinjaman', PinjamanController::class)->except(['index', 'show']);
        Route::resource('talangan', DanaTalanganController::class)->except(['index', 'show']);

        Route::post('kategori-kas', KategoriKasController::class)->name('kategori-kas.store');
    });

    /*
     * Pengurus boleh melihat pengaturan; hanya ketua boleh menyunting.
     */
    Route::get('pengaturan', [PengaturanController::class, 'edit'])
        ->middleware('can:lihat-pengaturan')->name('pengaturan.edit');
    Route::put('pengaturan', [PengaturanController::class, 'update'])
        ->middleware('can:kelola-pengaturan')->name('pengaturan.update');

    /*
     * Hanya sekretaris dan bendahara yang boleh mengelola akun.
     */
    Route::middleware('can:kelola-pengguna')->group(function () {
        Route::resource('pengguna', PenggunaController::class)->except(['index', 'show']);
    });

    /*
     * Menu pengurus. Ketua boleh melihat, pengguna biasa tidak.
     */
    Route::middleware('can:lihat-pengurus')->group(function () {
        Route::resource('pengguna', PenggunaController::class)->only(['index', 'show']);

        Route::get('kelompok/{kelompok}/transaksi', [KelompokTaniController::class, 'transaksi'])->name('kelompok.transaksi');
        Route::resource('kelompok', KelompokTaniController::class)->only(['index', 'show']);
    });

    /*
     * Sisanya boleh dilihat semua role, termasuk "user".
     */
    Route::get('beranda', BerandaController::class)->name('beranda');

    Route::get('laporan', [LaporanController::class, 'index'])->name('laporan.index');
    Route::get('laporan/unduh', [LaporanController::class, 'unduh'])->name('laporan.unduh');

    Route::get('log', LogAktivitasController::class)->name('log.index');

    Route::get('profil', [ProfilController::class, 'show'])->name('profil.show');
    Route::get('profil/sunting', [ProfilController::class, 'edit'])->name('profil.edit');
    Route::put('profil', [ProfilController::class, 'update'])->name('profil.update');

    Route::get('kas/{kas}/faktur', [KasController::class, 'faktur'])->name('kas.faktur');
    Route::resource('kas', KasController::class)->only(['index', 'show'])->parameters(['kas' => 'kas']);

    Route::get('pinjaman/{pinjaman}/faktur', [PinjamanController::class, 'faktur'])->name('pinjaman.faktur');
    Route::resource('pinjaman', PinjamanController::class)->only(['index', 'show']);
    Route::get('pembayaran/{pembayaran}/faktur', [PembayaranPinjamanController::class, 'faktur'])->name('pembayaran.faktur');

    Route::get('talangan/{talangan}/faktur', [DanaTalanganController::class, 'faktur'])->name('talangan.faktur');
    Route::resource('talangan', DanaTalanganController::class)->only(['index', 'show']);
});

require __DIR__.'/auth.php';
