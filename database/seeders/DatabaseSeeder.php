<?php

namespace Database\Seeders;

use App\Models\DanaTalangan;
use App\Models\KategoriKas;
use App\Models\KelompokTani;
use App\Models\Pengaturan;
use App\Models\Pinjaman;
use App\Models\TransaksiKas;
use App\Models\User;
use App\Services\PencairanPinjaman;
use App\Services\SetoranPinjaman;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $pengaturan = Pengaturan::ambil();

        $sekretaris = User::create([
            'name' => 'Admin Utama',
            'username' => 'admin',
            'email' => 'admin@swagotra.test',
            'no_hp' => '081234567890',
            'alamat' => 'Jl. Raya Kuta, Badung, Bali',
            'role' => User::ROLE_SEKRETARIS,
            'password' => 'password',
        ]);

        User::create([
            'name' => 'Ni Luh Bendahara',
            'username' => 'bendahara',
            'no_hp' => '081234567891',
            'alamat' => 'Jl. Raya Kuta, Badung, Bali',
            'role' => User::ROLE_BENDAHARA,
            'password' => 'password',
        ]);

        User::create([
            'name' => 'I Wayan Ketua',
            'username' => 'ketua',
            'no_hp' => '085123746473',
            'alamat' => 'Jl. Raya Ubud, Gianyar, Bali',
            'role' => User::ROLE_KETUA,
            'password' => 'password',
        ]);

        User::create([
            'name' => 'I Made Pengamat',
            'username' => 'user',
            'no_hp' => '085123746474',
            'alamat' => 'Jl. Raya Tabanan, Tabanan, Bali',
            'role' => User::ROLE_USER,
            'password' => 'password',
        ]);

        foreach (KategoriKas::BAWAAN as $nama) {
            KategoriKas::create(['nama' => $nama, 'bawaan' => true]);
        }

        foreach (KategoriKas::SISTEM as $nama) {
            KategoriKas::sistem($nama);
        }

        $kelompok = collect([
            ['Sekar Wana I', 'I Ketut Sudarma', 'Jl. Raya Ubud, Gianyar, Bali'],
            ['Sekar Wana II', 'I Made Suwitra', 'Jl. Raya Ubud, Gianyar, Bali'],
            ['Gunung Sari', 'I Nyoman Adiana', 'Jl. Raya Tabanan, Tabanan, Bali'],
            ['Giri Kusuma', 'I Gede Anom', 'Jl. Raya Bedugul, Tabanan, Bali'],
            ['Mekar Teganek I', 'I Komang Wira', 'Jl. Raya Kintamani, Bangli, Bali'],
            ['Mekar Teganek II', 'I Kadek Darma', 'Jl. Raya Kintamani, Bangli, Bali'],
        ])->map(fn (array $data, int $i) => KelompokTani::create([
            'kode' => sprintf('KT-%03d', $i + 1),
            'nama_kelompok' => $data[0],
            'nama_ketua' => $data[1],
            'nik_ketua' => fake()->numerify('51########9####'),
            'no_hp' => fake()->numerify('08##########'),
            'alamat' => $data[2],
        ]));

        // Modal awal koperasi.
        TransaksiKas::create([
            'kode' => 'TXN-10001',
            'tanggal' => Carbon::today()->subMonths(10),
            'jenis' => TransaksiKas::JENIS_MASUK,
            'kategori_kas_id' => KategoriKas::where('nama', 'Saldo Awal')->value('id'),
            'jumlah' => 500_000_000,
            'deskripsi' => 'Modal awal koperasi Gapoktan Swagotra Arthagiri',
            'user_id' => $sekretaris->id,
        ]);

        // Saldo dana talangan yang sudah terkumpul sebelum sistem dipakai.
        DanaTalangan::create([
            'kode' => 'DT-0001',
            'kelompok_tani_id' => $kelompok->first()->id,
            'tanggal' => Carbon::today()->subMonths(10),
            'jenis' => DanaTalangan::JENIS_MASUK,
            'jumlah' => 25_000_000,
            'deskripsi' => 'Saldo awal dana talangan',
            'user_id' => $sekretaris->id,
        ]);

        $nominalPinjaman = [75_000_000, 80_000_000, 120_000_000, 50_000_000, 65_000_000, 60_000_000];
        $persenTalangan = (float) $pengaturan->potongan_dana_talangan;
        $persenPotongan = (float) $pengaturan->potongan_operasional + $persenTalangan;

        $kelompok->each(function (KelompokTani $kt, int $i) use (
            $nominalPinjaman, $persenTalangan, $persenPotongan, $pengaturan, $sekretaris
        ) {
            $jumlah = $nominalPinjaman[$i];
            $potongan = round($jumlah * $persenPotongan / 100, 2);
            // Pencairan serempak awal musim, 6 bulan lalu.
            $tanggal = Carbon::today()->subMonths(6)->addDays($i);

            $pinjaman = Pinjaman::create([
                'kode' => sprintf('LP-%s-%03d', now()->year, $i + 1),
                'kelompok_tani_id' => $kt->id,
                'tanggal_transaksi' => $tanggal,
                'tanggal_jatuh_tempo' => $tanggal->copy()->addMonths(Pinjaman::BULAN_PER_TERMIN),
                'tanggal_jatuh_tempo_awal' => $tanggal->copy()->addMonths(Pinjaman::BULAN_PER_TERMIN),
                'termin' => 1,
                'jumlah_pinjaman' => $jumlah,
                'potongan_persen' => $persenPotongan,
                'potongan_talangan_persen' => $persenTalangan,
                'potongan_nominal' => $potongan,
                'jumlah_diterima' => $jumlah - $potongan,
                'bunga_persen' => (float) $pengaturan->suku_bunga_pinjaman,
                'status' => Pinjaman::STATUS_BELUM_BAYAR,
                'deskripsi' => 'Pinjaman modal tanam musim ini',
                'user_id' => $sekretaris->id,
            ]);

            // Kas keluar + kontribusi dana talangan mengikuti pencairan.
            (new PencairanPinjaman($pinjaman))->sinkronkan($sekretaris);
            $pinjaman->segarkanStatus();
        });

        // Dua kelompok menyetor tepat di jatuh tempo termin pertama supaya data
        // contoh memuat ketiga status: lunas, bayar bunga saja, dan menunggak.
        foreach ([0 => 'lunas', 1 => 'bunga'] as $indeks => $mode) {
            $kt = $kelompok[$indeks];
            $layanan = new SetoranPinjaman($kt);
            $tanggal = Carbon::parse($kt->pinjaman()->min('tanggal_jatuh_tempo'));

            $jumlah = $mode === 'bunga'
                ? $layanan->totalBungaTerhutang($tanggal)
                : $layanan->totalPelunasan($tanggal);

            if ($jumlah > 0) {
                $layanan->catat(
                    $tanggal,
                    $jumlah,
                    $mode === 'bunga' ? 'Setoran bunga termin pertama' : 'Pelunasan pinjaman',
                    $sekretaris,
                );
            }
        }

        DanaTalangan::create([
            'kode' => 'DT-9001',
            'kelompok_tani_id' => $kelompok->first()->id,
            'tanggal' => Carbon::today()->subMonth(),
            'jenis' => DanaTalangan::JENIS_KELUAR,
            'penerima' => 'I Wayan Klempit',
            'jumlah' => 5_000_000,
            'deskripsi' => 'Santunan kematian anggota kelompok',
            'user_id' => $sekretaris->id,
        ]);

        // Beban rutin koperasi supaya laporan kas punya isi di sisi pengeluaran.
        foreach ([
            ['Gaji', 4, 2_400_000, 'Honor pengurus triwulan'],
            ['Operasional', 3, 1_150_000, 'Sewa sekretariat dan alat tulis'],
            ['Gaji', 1, 2_400_000, 'Honor pengurus triwulan'],
        ] as $urutan => [$kategori, $bulanLalu, $nominal, $catatan]) {
            TransaksiKas::create([
                'kode' => sprintf('TXN-9%04d', $urutan + 1),
                'tanggal' => Carbon::today()->subMonths($bulanLalu),
                'jenis' => TransaksiKas::JENIS_KELUAR,
                'kategori_kas_id' => KategoriKas::where('nama', $kategori)->value('id'),
                'jumlah' => $nominal,
                'biaya' => 0,
                'deskripsi' => $catatan,
                'user_id' => $sekretaris->id,
            ]);
        }
    }
}
