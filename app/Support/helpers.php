<?php

use Illuminate\Support\Carbon;

if (! function_exists('rupiah')) {
    /**
     * Format angka jadi rupiah gaya Indonesia: Rp 1.250.000
     */
    function rupiah(float|int|string|null $nilai, bool $denganPrefix = true): string
    {
        $angka = number_format((float) $nilai, 0, ',', '.');

        return $denganPrefix ? 'Rp '.$angka : $angka;
    }
}

if (! function_exists('rupiah_bertanda')) {
    /**
     * Rupiah dengan tanda minus di depan prefix, mis. -Rp 12.000.000
     */
    function rupiah_bertanda(float|int|string|null $nilai): string
    {
        $angka = (float) $nilai;

        return ($angka < 0 ? '-' : '').rupiah(abs($angka));
    }
}

if (! function_exists('tanggal_id')) {
    /**
     * Tanggal ringkas berbahasa Indonesia: 18 Jun 2026
     */
    function tanggal_id(mixed $tanggal, string $format = 'd M Y'): string
    {
        if (blank($tanggal)) {
            return '-';
        }

        return Carbon::parse($tanggal)->translatedFormat($format);
    }
}

if (! function_exists('rupiah_ringkas')) {
    /**
     * Rupiah ringkas untuk sumbu grafik dan angka sempit: Rp 250 rb, Rp 2,5 jt, Rp 1,2 M.
     */
    function rupiah_ringkas(float|int|string|null $nilai): string
    {
        $angka = (float) $nilai;
        $mutlak = abs($angka);
        $tanda = $angka < 0 ? '-' : '';

        [$pembagi, $satuan] = match (true) {
            $mutlak >= 1_000_000_000 => [1_000_000_000, ' M'],
            $mutlak >= 1_000_000 => [1_000_000, ' jt'],
            $mutlak >= 1_000 => [1_000, ' rb'],
            default => [1, ''],
        };

        $ringkas = $mutlak / $pembagi;
        $desimal = $pembagi > 1 && $ringkas < 10 && fmod($ringkas, 1) >= 0.05 ? 1 : 0;

        return $tanda.'Rp '.number_format($ringkas, $desimal, ',', '.').$satuan;
    }
}
