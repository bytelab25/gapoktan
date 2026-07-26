<?php

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

        return \Illuminate\Support\Carbon::parse($tanggal)->translatedFormat($format);
    }
}
