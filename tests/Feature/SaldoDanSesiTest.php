<?php

namespace Tests\Feature;

use App\Models\DanaTalangan;
use App\Models\KategoriKas;
use App\Models\KelompokTani;
use App\Models\Pinjaman;
use App\Models\TransaksiKas;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class SaldoDanSesiTest extends TestCase
{
    use RefreshDatabase;

    public function test_pengeluaran_kas_melebihi_saldo_ditolak(): void
    {
        $kategori = KategoriKas::create(['nama' => 'Operasional']);
        $this->modalKas(100000);

        $this->actingAs($this->pengguna())->post(route('kas.store'), [
            'tanggal' => '2026-10-08',
            'jenis' => TransaksiKas::JENIS_KELUAR,
            'kategori_kas_id' => $kategori->id,
            'jumlah' => '100001',
        ])->assertSessionHasErrors('jumlah');
        $this->assertSame(1, TransaksiKas::count());

        $this->post(route('kas.store'), [
            'tanggal' => '2026-10-08',
            'jenis' => TransaksiKas::JENIS_KELUAR,
            'kategori_kas_id' => $kategori->id,
            'jumlah' => '100000',
        ])->assertSessionHasNoErrors();
        $this->assertSame(0.0, TransaksiKas::saldoTotal());
    }

    public function test_sunting_pengeluaran_kas_melebihi_saldo_ditolak(): void
    {
        $kategori = KategoriKas::create(['nama' => 'Operasional']);
        $this->modalKas(100000);
        $keluar = TransaksiKas::create([
            'kode' => 'TXN-KELUAR', 'tanggal' => '2026-10-08', 'jenis' => TransaksiKas::JENIS_KELUAR,
            'kategori_kas_id' => $kategori->id, 'jumlah' => 60000,
        ]);

        $this->actingAs($this->pengguna())->put(route('kas.update', $keluar), [
            'tanggal' => '2026-10-08',
            'jenis' => TransaksiKas::JENIS_KELUAR,
            'kategori_kas_id' => $kategori->id,
            'jumlah' => '150000',
        ])->assertSessionHasErrors('jumlah');
        $this->assertSame('60000.00', $keluar->fresh()->jumlah);
    }

    public function test_hapus_pemasukan_yang_sudah_terpakai_ditolak(): void
    {
        $kategori = KategoriKas::create(['nama' => 'Operasional']);
        $modal = $this->modalKas(100000);
        TransaksiKas::create([
            'kode' => 'TXN-KELUAR', 'tanggal' => '2026-10-08', 'jenis' => TransaksiKas::JENIS_KELUAR,
            'kategori_kas_id' => $kategori->id, 'jumlah' => 60000,
        ]);

        $this->actingAs($this->pengguna())->delete(route('kas.destroy', $modal))
            ->assertSessionHas('gagal');
        $this->assertModelExists($modal);
    }

    public function test_klaim_dana_talangan_melebihi_saldo_ditolak(): void
    {
        $kelompok = $this->kelompok();
        DanaTalangan::create([
            'kode' => 'DT-0001', 'tanggal' => '2026-10-01', 'jenis' => DanaTalangan::JENIS_MASUK, 'jumlah' => 50000,
        ]);
        $formulir = [
            'kelompok_tani_id' => $kelompok->id,
            'tanggal' => '2026-10-08',
            'jenis' => DanaTalangan::JENIS_KELUAR,
            'penerima' => 'Petani Uji',
            'jumlah' => '50001',
        ];

        $this->actingAs($this->pengguna())->post(route('talangan.store'), $formulir)
            ->assertSessionHasErrors('jumlah');
        $this->assertSame(1, DanaTalangan::count());

        $this->post(route('talangan.store'), [...$formulir, 'jumlah' => '50000'])
            ->assertSessionHasNoErrors();
        $this->assertSame(0.0, DanaTalangan::saldoTotal());
    }

    public function test_pinjaman_ditolak_bila_saldo_kas_kurang(): void
    {
        $kelompok = $this->kelompok();
        $this->modalKas(900000);
        $formulir = [
            'kelompok_tani_id' => $kelompok->id,
            'tanggal_transaksi' => '2026-10-08',
            'tanggal_jatuh_tempo' => '2027-02-08',
            'jumlah_pinjaman' => '1000000',
        ];

        // Kas keluar bersih = dana diterima kelompok + kontribusi talangan = Rp 995.000.
        $this->actingAs($this->pengguna())->post(route('pinjaman.store'), $formulir)
            ->assertSessionHasErrors('jumlah_pinjaman');
        $this->assertSame(0, Pinjaman::count());
        $this->assertSame(1, TransaksiKas::count());
        $this->assertSame(0, DanaTalangan::count());

        $this->modalKas(95000);
        $this->post(route('pinjaman.store'), $formulir)->assertSessionHasNoErrors();
        $this->assertSame(1, Pinjaman::count());
        $this->assertSame(0.0, TransaksiKas::saldoTotal());
    }

    public function test_menaikkan_nominal_pinjaman_ditolak_bila_saldo_kas_kurang(): void
    {
        $kelompok = $this->kelompok();
        $this->modalKas(995000);
        $formulir = [
            'kelompok_tani_id' => $kelompok->id,
            'tanggal_transaksi' => '2026-10-08',
            'tanggal_jatuh_tempo' => '2027-02-08',
            'jumlah_pinjaman' => '1000000',
        ];
        $this->actingAs($this->pengguna())->post(route('pinjaman.store'), $formulir)->assertSessionHasNoErrors();
        $pinjaman = Pinjaman::sole();

        $this->put(route('pinjaman.update', $pinjaman), [...$formulir, 'jumlah_pinjaman' => '2000000'])
            ->assertSessionHasErrors('jumlah_pinjaman');
        $this->assertSame('1000000.00', $pinjaman->fresh()->jumlah_pinjaman);
        $this->assertSame(0.0, TransaksiKas::saldoTotal());
    }

    public function test_hapus_pinjaman_ditolak_bila_kontribusi_talangan_sudah_terpakai(): void
    {
        $kelompok = $this->kelompok();
        $this->modalKas();
        $this->actingAs($this->pengguna())->post(route('pinjaman.store'), [
            'kelompok_tani_id' => $kelompok->id,
            'tanggal_transaksi' => '2026-10-08',
            'tanggal_jatuh_tempo' => '2027-02-08',
            'jumlah_pinjaman' => '1000000',
        ])->assertSessionHasNoErrors();
        $pinjaman = Pinjaman::sole();

        $this->post(route('talangan.store'), [
            'kelompok_tani_id' => $kelompok->id,
            'tanggal' => '2026-10-09',
            'jenis' => DanaTalangan::JENIS_KELUAR,
            'penerima' => 'Petani Uji',
            'jumlah' => '10000',
        ])->assertSessionHasNoErrors();

        $this->delete(route('pinjaman.destroy', $pinjaman))->assertSessionHas('gagal');
        $this->assertModelExists($pinjaman);
        $this->assertSame(0.0, DanaTalangan::saldoTotal());
    }

    public function test_pengguna_yang_sedang_login_tidak_bisa_dihapus(): void
    {
        config(['session.driver' => 'database']);
        $admin = $this->pengguna();
        $target = $this->pengguna(User::ROLE_USER);
        $this->sesi($target, now()->timestamp);

        $this->actingAs($admin)->delete(route('pengguna.destroy', $target))
            ->assertSessionHas('gagal');
        $this->assertModelExists($target);
    }

    public function test_pengguna_dengan_sesi_kedaluwarsa_bisa_dihapus(): void
    {
        config(['session.driver' => 'database']);
        $admin = $this->pengguna();
        $target = $this->pengguna(User::ROLE_USER);
        $this->sesi($target, now()->subMinutes((int) config('session.lifetime') + 1)->timestamp);

        $this->actingAs($admin)->delete(route('pengguna.destroy', $target))
            ->assertSessionHas('sukses');
        $this->assertModelMissing($target);
    }

    private function sesi(User $pengguna, int $aktivitasTerakhir): void
    {
        DB::table('sessions')->insert([
            'id' => Str::random(40),
            'user_id' => $pengguna->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'payload' => '',
            'last_activity' => $aktivitasTerakhir,
        ]);
    }

    private function pengguna(string $role = User::ROLE_SEKRETARIS): User
    {
        return User::factory()->create(['username' => Str::uuid()->toString(), 'role' => $role]);
    }

    private function kelompok(): KelompokTani
    {
        return KelompokTani::create([
            'kode' => 'KT-'.Str::uuid(),
            'nama_kelompok' => 'Kelompok Uji',
            'nama_ketua' => 'Ketua Uji',
            'nik_ketua' => '1234567890123456',
            'no_hp' => '081234567890',
            'alamat' => 'Alamat uji',
        ]);
    }

    private function modalKas(int $jumlah = 10000000): TransaksiKas
    {
        return TransaksiKas::create([
            'kode' => 'MODAL-'.Str::uuid(),
            'tanggal' => '2026-01-01',
            'jenis' => TransaksiKas::JENIS_MASUK,
            'kategori_kas_id' => KategoriKas::firstOrCreate(['nama' => 'Modal Awal'])->id,
            'jumlah' => $jumlah,
        ]);
    }
}
