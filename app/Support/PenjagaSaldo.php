<?php

namespace App\Support;

use App\Models\DanaTalangan;
use App\Models\Pengaturan;
use App\Models\TransaksiKas;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Menjalankan perubahan keuangan di dalam transaksi database, lalu
 * membatalkannya bila saldo kas atau saldo dana talangan menjadi minus
 * karena perubahan itu.
 *
 * Perubahan yang tidak memperburuk saldo tetap diizinkan, supaya data lama
 * yang terlanjur minus masih bisa dikoreksi.
 */
final class PenjagaSaldo
{
    /**
     * @template T
     *
     * @param  callable(): T  $perubahan
     * @param  string  $kolom  Nama field yang menerima pesan galat.
     * @param  int  $percobaan  Jumlah percobaan ulang bila terjadi deadlock.
     * @return T
     *
     * @throws ValidationException
     */
    public static function jalankan(callable $perubahan, string $kolom = 'jumlah', int $percobaan = 1): mixed
    {
        return DB::transaction(function () use ($perubahan, $kolom) {
            // Baris tunggal pengaturan dipakai sebagai kunci global supaya dua
            // pengeluaran bersamaan tidak sama-sama lolos memakai saldo yang sama.
            Pengaturan::lockForUpdate()->orderBy('id')->first();

            $kasAwal = TransaksiKas::saldoTotal();
            $talanganAwal = DanaTalangan::saldoTotal();

            $hasil = $perubahan();

            self::periksa('kas', $kasAwal, TransaksiKas::saldoTotal(), $kolom);
            self::periksa('dana talangan', $talanganAwal, DanaTalangan::saldoTotal(), $kolom);

            return $hasil;
        }, $percobaan);
    }

    /**
     * Pesan galat pertama dari pengecualian saldo, untuk dipakai sebagai
     * notifikasi "gagal" pada aksi yang tidak punya form (mis. hapus).
     */
    public static function pesan(ValidationException $galat): string
    {
        return (string) collect($galat->errors())->flatten()->first();
    }

    private static function periksa(string $pos, float $awal, float $akhir, string $kolom): void
    {
        $awal = round($awal, 2);
        $akhir = round($akhir, 2);

        if ($akhir >= 0 || $akhir >= $awal) {
            return;
        }

        throw ValidationException::withMessages([
            $kolom => "Saldo {$pos} tidak mencukupi. Saldo tersedia ".rupiah(max($awal, 0))
                .', transaksi ini membutuhkan '.rupiah($awal - $akhir)
                .' (kurang '.rupiah(-$akhir).').',
        ]);
    }
}
