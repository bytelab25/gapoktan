<?php

namespace Database\Seeders;

use App\Models\DanaTalangan;
use App\Models\KategoriKas;
use App\Models\KelompokTani;
use App\Models\Pengaturan;
use App\Models\Pinjaman;
use App\Models\TransaksiKas;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $pengaturan = Pengaturan::ambil();

        $admin = User::create([
            'name' => 'Admin Utama',
            'username' => 'admin',
            'email' => 'admin@swagotra.test',
            'no_hp' => '081234567890',
            'alamat' => 'Jl. Raya Kuta, Badung, Bali',
            'role' => User::ROLE_SYSTEM_MANAGER,
            'password' => 'password',
        ]);

        User::create([
            'name' => 'I Wayan Poktan',
            'username' => 'wayan',
            'no_hp' => '085123746473',
            'alamat' => 'Jl. Raya Kuta, Badung, Bali',
            'role' => User::ROLE_KETUA_GAPOKTAN,
            'password' => 'password',
        ]);

        foreach (['Saldo Awal', 'Gaji', 'Operasional', 'Pinjaman', 'Pembayaran Pinjaman'] as $nama) {
            KategoriKas::create(['nama' => $nama, 'bawaan' => true]);
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
            'tanggal' => Carbon::today()->subMonths(8),
            'jenis' => TransaksiKas::JENIS_MASUK,
            'kategori_kas_id' => KategoriKas::where('nama', 'Saldo Awal')->value('id'),
            'jumlah' => 500_000_000,
            'deskripsi' => 'Modal awal koperasi Gapoktan Swagotra Arthagiri',
            'user_id' => $admin->id,
        ]);

        // Saldo awal dana talangan yang sudah terkumpul sebelum sistem dipakai.
        DanaTalangan::create([
            'kode' => 'DT-0001',
            'kelompok_tani_id' => $kelompok->first()->id,
            'tanggal' => Carbon::today()->subMonths(8),
            'jenis' => DanaTalangan::JENIS_MASUK,
            'jumlah' => 25_000_000,
            'deskripsi' => 'Saldo awal dana talangan',
            'user_id' => $admin->id,
        ]);

        $nominalPinjaman = [75_000_000, 80_000_000, 120_000_000, 50_000_000, 65_000_000, 60_000_000];

        // Potongan muka pinjaman = biaya operasional + kontribusi dana talangan.
        $persenOperasional = (float) $pengaturan->potongan_operasional;
        $persenTalangan = (float) $pengaturan->potongan_dana_talangan;
        $persenPotongan = $persenOperasional + $persenTalangan;

        $kelompok->each(function (KelompokTani $kt, int $i) use (
            $nominalPinjaman, $persenOperasional, $persenTalangan, $persenPotongan, $pengaturan, $admin
        ) {
            $jumlah = $nominalPinjaman[$i];
            $potongan = round($jumlah * $persenPotongan / 100, 2);
            $tanggal = Carbon::today()->subMonths(4)->addDays($i);

            Pinjaman::create([
                'kode' => sprintf('LP-%s-%03d', now()->year, $i + 1),
                'kelompok_tani_id' => $kt->id,
                'tanggal_transaksi' => $tanggal,
                'tanggal_jatuh_tempo' => $tanggal->copy()->addYear(),
                'jumlah_pinjaman' => $jumlah,
                'potongan_persen' => $persenPotongan,
                'potongan_nominal' => $potongan,
                'jumlah_diterima' => $jumlah - $potongan,
                'bunga_persen' => (float) $pengaturan->suku_bunga_pinjaman,
                'status' => Pinjaman::STATUS_BELUM_BAYAR,
                'deskripsi' => 'Pinjaman modal tanam musim ini',
                'user_id' => $admin->id,
            ]);

            // Dana yang benar-benar keluar dari kas hanyalah jumlah yang diterima kelompok.
            TransaksiKas::create([
                'kode' => sprintf('TXN-2%04d', $i + 1),
                'tanggal' => $tanggal,
                'jenis' => TransaksiKas::JENIS_KELUAR,
                'kategori_kas_id' => KategoriKas::where('nama', 'Pinjaman')->value('id'),
                'kelompok_tani_id' => $kt->id,
                'jumlah' => $jumlah - $potongan,
                'biaya' => round($jumlah * $persenOperasional / 100, 2),
                'deskripsi' => 'Pencairan pinjaman '.$kt->nama_kelompok,
                'user_id' => $admin->id,
            ]);

            DanaTalangan::create([
                'kode' => sprintf('DT-%04d', $i + 2),
                'kelompok_tani_id' => $kt->id,
                'tanggal' => $tanggal,
                'jenis' => DanaTalangan::JENIS_MASUK,
                'jumlah' => round($jumlah * $persenTalangan / 100, 2),
                'deskripsi' => 'Kontribusi dana talangan dari pencairan pinjaman',
                'user_id' => $admin->id,
            ]);
        });

        DanaTalangan::create([
            'kode' => 'DT-9001',
            'kelompok_tani_id' => $kelompok->first()->id,
            'tanggal' => Carbon::today()->subMonth(),
            'jenis' => DanaTalangan::JENIS_KELUAR,
            'penerima' => 'I Wayan Klempit',
            'jumlah' => 5_000_000,
            'deskripsi' => 'Santunan kematian anggota kelompok',
            'user_id' => $admin->id,
        ]);
    }
}
