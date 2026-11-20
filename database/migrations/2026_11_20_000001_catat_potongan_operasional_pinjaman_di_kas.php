<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $kategoriOperasional = DB::table('kategori_kas')
            ->where('nama', 'Potongan Operasional Pinjaman')->value('id');

        if (! $kategoriOperasional) {
            $kategoriOperasional = DB::table('kategori_kas')->insertGetId([
                'nama' => 'Potongan Operasional Pinjaman',
                'bawaan' => true,
                'sistem' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            DB::table('kategori_kas')->where('id', $kategoriOperasional)
                ->update(['bawaan' => true, 'sistem' => true]);
        }

        $kategoriPencairan = DB::table('kategori_kas')
            ->where('nama', 'Pencairan Pinjaman')->value('id');

        if (! $kategoriPencairan) {
            return;
        }

        $kodeTertinggi = DB::table('transaksi_kas')->where('kode', 'like', 'TXN-%')
            ->orderByDesc('kode')->value('kode');
        $nomorBerikutnya = max(
            10001 + (int) DB::table('transaksi_kas')->max('id'),
            (int) substr((string) $kodeTertinggi, 4) + 1,
        );

        DB::table('transaksi_kas')
            ->where('kategori_kas_id', $kategoriPencairan)
            ->whereNotNull('pinjaman_id')
            ->where('biaya', '>', 0)
            ->chunkById(100, function ($pencairan) use ($kategoriOperasional, &$nomorBerikutnya): void {
                foreach ($pencairan as $baris) {
                    DB::transaction(function () use ($baris, $kategoriOperasional, &$nomorBerikutnya): void {
                        $sudahAda = DB::table('transaksi_kas')
                            ->where('pinjaman_id', $baris->pinjaman_id)
                            ->where('kategori_kas_id', $kategoriOperasional)
                            ->exists();

                        if ($sudahAda) {
                            return;
                        }

                        $nominal = round((float) $baris->biaya, 2);
                        DB::table('transaksi_kas')->where('id', $baris->id)->update([
                            'jumlah' => round((float) $baris->jumlah + $nominal, 2),
                            'updated_at' => now(),
                        ]);

                        DB::table('transaksi_kas')->insert([
                            'kode' => sprintf('TXN-%05d', $nomorBerikutnya++),
                            'tanggal' => $baris->tanggal,
                            'jenis' => 'masuk',
                            'kategori_kas_id' => $kategoriOperasional,
                            'kelompok_tani_id' => $baris->kelompok_tani_id,
                            'pinjaman_id' => $baris->pinjaman_id,
                            'jumlah' => $nominal,
                            'biaya' => 0,
                            'deskripsi' => 'Potongan operasional dari pinjaman '
                                .DB::table('pinjaman')->where('id', $baris->pinjaman_id)->value('kode'),
                            'user_id' => $baris->user_id,
                            'diubah_oleh_id' => $baris->diubah_oleh_id,
                            'created_at' => $baris->created_at,
                            'updated_at' => now(),
                        ]);
                    });
                }
            });
    }

    public function down(): void
    {
        $kategoriOperasional = DB::table('kategori_kas')
            ->where('nama', 'Potongan Operasional Pinjaman')->value('id');
        $kategoriPencairan = DB::table('kategori_kas')
            ->where('nama', 'Pencairan Pinjaman')->value('id');

        if (! $kategoriOperasional || ! $kategoriPencairan) {
            return;
        }

        DB::table('transaksi_kas')->where('kategori_kas_id', $kategoriOperasional)
            ->whereNotNull('pinjaman_id')
            ->chunkById(100, function ($pemasukan) use ($kategoriPencairan): void {
                foreach ($pemasukan as $baris) {
                    DB::transaction(function () use ($baris, $kategoriPencairan): void {
                        $pencairan = DB::table('transaksi_kas')
                            ->where('pinjaman_id', $baris->pinjaman_id)
                            ->where('kategori_kas_id', $kategoriPencairan)
                            ->first();

                        if ($pencairan) {
                            DB::table('transaksi_kas')->where('id', $pencairan->id)->update([
                                'jumlah' => round((float) $pencairan->jumlah - (float) $baris->jumlah, 2),
                            ]);
                        }

                        DB::table('transaksi_kas')->where('id', $baris->id)->delete();
                    });
                }
            });

        // Kategori dibiarkan agar rollback tidak menghapus kategori dengan nama
        // sama yang mungkin sudah dibuat pengguna sebelum migrasi ini.
    }
};
