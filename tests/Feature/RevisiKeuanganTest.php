<?php

namespace Tests\Feature;

use App\Models\KategoriKas;
use App\Models\KelompokTani;
use App\Models\PembayaranPinjaman;
use App\Models\Pengaturan;
use App\Models\Pinjaman;
use App\Models\TransaksiKas;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RevisiKeuanganTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-10-08 12:00:00'));
    }

    protected function tearDown(): void
    {
        $this->travelBack();
        parent::tearDown();
    }

    public static function pengurusPembaca(): array
    {
        return [[User::ROLE_SEKRETARIS], [User::ROLE_BENDAHARA]];
    }

    #[DataProvider('pengurusPembaca')]
    public function test_pengurus_bisa_melihat_tetapi_tidak_mengubah_pengaturan(string $role): void
    {
        $pengaturan = Pengaturan::ambil();
        $this->actingAs($this->pengguna($role))
            ->get(route('pengaturan.edit'))
            ->assertOk()
            ->assertSee('readonly', false)
            ->assertDontSee('Simpan Pengaturan');

        $this->put(route('pengaturan.update'), $this->pengaturanBaru())->assertForbidden();
        $this->assertSame('2.00', $pengaturan->fresh()->suku_bunga_pinjaman);
        $this->assertDatabaseCount('log_aktivitas', 0);
    }

    public function test_ketua_dapat_menyimpan_pengaturan(): void
    {
        $this->actingAs($this->pengguna(User::ROLE_KETUA))
            ->get(route('pengaturan.edit'))->assertOk()->assertSee('Simpan Pengaturan');
        $this->put(route('pengaturan.update'), $this->pengaturanBaru())
            ->assertRedirect(route('pengaturan.edit'))->assertSessionHasNoErrors();
        $this->assertSame('3.25', Pengaturan::ambil()->suku_bunga_pinjaman);
    }

    public function test_user_hanya_dapat_melihat_pengaturan(): void
    {
        $this->actingAs($this->pengguna(User::ROLE_USER))
            ->get(route('pengaturan.edit'))->assertOk()
            ->assertSee('readonly', false)
            ->assertDontSee('Simpan Pengaturan');
        $this->put(route('pengaturan.update'), $this->pengaturanBaru())->assertForbidden();
    }

    public function test_tanggal_pilihan_dan_nominal_bulat_pinjaman_tersimpan(): void
    {
        $kelompok = $this->kelompok();
        $this->actingAs($this->pengguna())->post(route('pinjaman.store'), [
            'kelompok_tani_id' => $kelompok->id,
            'tanggal_transaksi' => '2026-10-08',
            'tanggal_jatuh_tempo' => '2026-12-15',
            'jumlah_pinjaman' => '1000000',
        ])->assertSessionHasNoErrors()->assertRedirect();

        $pinjaman = Pinjaman::firstOrFail();
        $this->assertSame('2026-12-15', $pinjaman->tanggal_jatuh_tempo->toDateString());
        $this->assertSame('1000000.00', $pinjaman->jumlah_pinjaman);
        $this->assertSame('15000.00', $pinjaman->potongan_nominal);
        $this->assertSame('985000.00', $pinjaman->jumlah_diterima);
        $this->assertSame('985000.00', $pinjaman->transaksiKas()->whereRelation('kategori', 'nama', KategoriKas::PENCAIRAN_PINJAMAN)->firstOrFail()->jumlah);
        $this->assertSame('10000.00', $pinjaman->danaTalangan()->firstOrFail()->jumlah);
    }

    public function test_nominal_pinjaman_desimal_ditolak(): void
    {
        $kelompok = $this->kelompok();
        $this->actingAs($this->pengguna())->post(route('pinjaman.store'), [
            'kelompok_tani_id' => $kelompok->id,
            'tanggal_transaksi' => '2026-10-08',
            'tanggal_jatuh_tempo' => '2026-12-15',
            'jumlah_pinjaman' => '1000000.50',
        ])->assertSessionHasErrors('jumlah_pinjaman');

        $this->assertSame(0, Pinjaman::count());
    }

    public function test_tanggal_jatuh_tempo_dapat_disunting_tanpa_dihitung_ulang(): void
    {
        $pinjaman = $this->pinjaman();
        $this->actingAs($this->pengguna())->put(route('pinjaman.update', $pinjaman), [
            'kelompok_tani_id' => $pinjaman->kelompok_tani_id,
            'tanggal_transaksi' => '2026-08-08',
            'tanggal_jatuh_tempo' => '2027-01-20',
            'jumlah_pinjaman' => '1000000',
        ])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame('2027-01-20', $pinjaman->fresh()->tanggal_jatuh_tempo->toDateString());
    }

    public static function tanggalTidakValid(): array
    {
        return [['2026-10-07'], [null], ['bukan-tanggal']];
    }

    #[DataProvider('tanggalTidakValid')]
    public function test_jatuh_tempo_wajib_valid_dan_tidak_mendahului_pencairan(?string $tempo): void
    {
        $this->actingAs($this->pengguna())->post(route('pinjaman.store'), [
            'kelompok_tani_id' => $this->kelompok()->id,
            'tanggal_transaksi' => '2026-10-08',
            'tanggal_jatuh_tempo' => $tempo,
            'jumlah_pinjaman' => '1000000.50',
        ])->assertSessionHasErrors('tanggal_jatuh_tempo');
        $this->assertDatabaseCount('pinjaman', 0);
        $this->assertDatabaseCount('transaksi_kas', 0);
    }

    public function test_kas_menyimpan_rupiah_bulat_dan_menolak_desimal(): void
    {
        $kategori = KategoriKas::create(['nama' => 'Hibah']);
        $this->actingAs($this->pengguna())->post(route('kas.store'), [
            'tanggal' => '2026-10-08',
            'jenis' => 'masuk',
            'kategori_kas_id' => $kategori->id,
            'jumlah' => '1234567.89',
        ])->assertSessionHasErrors('jumlah');
        $this->assertDatabaseCount('transaksi_kas', 0);

        $this->post(route('kas.store'), [
            'tanggal' => '2026-10-08',
            'jenis' => 'masuk',
            'kategori_kas_id' => $kategori->id,
            'jumlah' => '1234567',
        ])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame('1234567.00', TransaksiKas::firstOrFail()->jumlah);
    }

    public function test_pembayaran_dua_bulan_dibuka_dan_bunga_dialokasikan_sebelum_pokok(): void
    {
        $pinjaman = $this->pinjaman();
        $this->actingAs($this->pengguna())
            ->get(route('pembayaran.pilih'))->assertOk()
            ->assertSee(route('pembayaran.create', $pinjaman->kelompok_tani_id), false);
        $this->get(route('pembayaran.create', $pinjaman->kelompok_tani_id))
            ->assertOk()->assertViewHas('tagihan', fn ($tagihan) => $tagihan->contains('id', $pinjaman->id));

        $this->post(route('pembayaran.store', $pinjaman->kelompok_tani_id), [
            'tanggal' => '2026-10-08',
            'jumlah' => '140000',
        ])->assertSessionHasNoErrors()->assertRedirect(route('pinjaman.kelompok', $pinjaman->kelompok_tani_id));

        $bayar = PembayaranPinjaman::firstOrFail();
        $this->assertSame(Pinjaman::STATUS_MENUNGGAK, $pinjaman->fresh()->status);
        $this->assertSame('40000.00', $bayar->bunga_nominal);
        $this->assertSame('100000.00', $bayar->bayar_pokok);
        $this->assertEquals(900000, $pinjaman->fresh()->sisa_pokok);
        $this->assertSame('2026-12-08', $pinjaman->fresh()->tanggal_jatuh_tempo->toDateString());
        $this->assertSame('140000.00', TransaksiKas::firstOrFail()->jumlah);
    }

    public function test_setoran_pada_hari_pencairan_langsung_mengurangi_pokok(): void
    {
        $pinjaman = $this->pinjaman(['tanggal_transaksi' => '2026-10-08']);
        $this->actingAs($this->pengguna())->post(route('pembayaran.store', $pinjaman->kelompok_tani_id), [
            'tanggal' => '2026-10-08',
            'jumlah' => '250000',
        ])->assertSessionHasNoErrors()->assertRedirect();
        $bayar = PembayaranPinjaman::firstOrFail();
        $this->assertSame('0.00', $bayar->bunga_nominal);
        $this->assertSame('250000.00', $bayar->bayar_pokok);
    }

    public function test_setoran_tidak_boleh_mendahului_pencairan(): void
    {
        $pinjaman = $this->pinjaman(['tanggal_transaksi' => '2026-10-08']);
        $this->actingAs($this->pengguna())->post(route('pembayaran.store', $pinjaman->kelompok_tani_id), [
            'tanggal' => '2026-10-07',
            'jumlah' => '100000',
        ])->assertSessionHas('gagal');
        $this->assertDatabaseCount('pembayaran_pinjaman', 0);
        $this->assertDatabaseCount('transaksi_kas', 0);
    }

    public function test_setoran_dini_tetap_menolak_kelebihan_tagihan(): void
    {
        $pinjaman = $this->pinjaman();
        $this->actingAs($this->pengguna())->post(route('pembayaran.store', $pinjaman->kelompok_tani_id), [
            'tanggal' => '2026-10-08',
            'jumlah' => '1040001',
        ])->assertSessionHasErrors('jumlah');
        $this->assertDatabaseCount('pembayaran_pinjaman', 0);
    }

    public function test_perpanjangan_termin_mengikuti_tanggal_jatuh_tempo_pilihan(): void
    {
        $pinjaman = $this->pinjaman(['tanggal_jatuh_tempo' => '2026-09-15']);
        $this->actingAs($this->pengguna())->post(route('pembayaran.store', $pinjaman->kelompok_tani_id), [
            'tanggal' => '2026-10-08',
            'jumlah' => '40000',
        ])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame(2, $pinjaman->fresh()->termin);
        $this->assertSame('2027-01-15', $pinjaman->fresh()->tanggal_jatuh_tempo->toDateString());
    }

    public function test_setoran_dini_beberapa_pinjaman_mengikuti_fifo_dan_satu_kas_masuk(): void
    {
        $pertama = $this->pinjaman();
        $kedua = $this->pinjaman(['kelompok_tani_id' => $pertama->kelompok_tani_id, 'tanggal_transaksi' => '2026-09-08']);
        $this->actingAs($this->pengguna())->post(route('pembayaran.store', $pertama->kelompok_tani_id), [
            'tanggal' => '2026-10-08',
            'jumlah' => '1160000',
        ])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame(Pinjaman::STATUS_LUNAS, $pertama->fresh()->status);
        $this->assertEquals(900000, $kedua->fresh()->sisa_pokok);
        $this->assertDatabaseCount('pembayaran_pinjaman', 2);
        $this->assertDatabaseCount('transaksi_kas', 1);
        $this->assertSame('1160000.00', TransaksiKas::firstOrFail()->jumlah);
    }

    public function test_daftar_pinjaman_per_kelompok_termasuk_yang_lunas(): void
    {
        $aktif = $this->pinjaman();
        $lunas = $this->pinjaman(['status' => Pinjaman::STATUS_LUNAS]);
        KelompokTani::create([
            'kode' => 'KT-tanpa', 'nama_kelompok' => 'Tanpa Pinjaman', 'nama_ketua' => 'X',
            'nik_ketua' => '1234567890123456', 'no_hp' => '0812', 'alamat' => 'A',
        ]);

        $this->actingAs($this->pengguna(User::ROLE_USER))
            ->get(route('pinjaman.index'))->assertOk()
            ->assertViewHas('daftarKelompok', fn ($daftar) => $daftar->total() === 2)
            ->assertSee(route('pinjaman.kelompok', $aktif->kelompok_tani_id), false)
            ->assertSee(route('pinjaman.kelompok', $lunas->kelompok_tani_id), false)
            ->assertDontSee('Tanpa Pinjaman');

        $this->get(route('pinjaman.kelompok', $aktif->kelompok_tani_id))->assertOk()->assertSee($aktif->kode);
    }

    public function test_pinjaman_dengan_pembayaran_tidak_bisa_disunting_atau_dihapus(): void
    {
        $pinjaman = $this->pinjaman();
        $this->actingAs($this->pengguna())->post(route('pembayaran.store', $pinjaman->kelompok_tani_id), [
            'tanggal' => '2026-10-08', 'jumlah' => '40000',
        ])->assertSessionHasNoErrors();

        $this->get(route('pinjaman.edit', $pinjaman))->assertRedirect(route('pinjaman.show', $pinjaman));
        $this->put(route('pinjaman.update', $pinjaman), [
            'kelompok_tani_id' => $pinjaman->kelompok_tani_id, 'tanggal_transaksi' => '2026-08-08',
            'tanggal_jatuh_tempo' => '2027-01-20', 'jumlah_pinjaman' => '5000000',
        ])->assertSessionHas('gagal');
        $this->delete(route('pinjaman.destroy', $pinjaman))->assertSessionHas('gagal');

        $this->assertSame('1000000.00', $pinjaman->fresh()->jumlah_pinjaman);
        $this->get(route('pinjaman.show', $pinjaman))->assertDontSee(route('pinjaman.edit', $pinjaman), false);
    }

    public function test_setoran_dapat_disunting_dan_dihitung_ulang(): void
    {
        $pinjaman = $this->pinjaman();
        $this->actingAs($this->pengguna())->post(route('pembayaran.store', $pinjaman->kelompok_tani_id), [
            'tanggal' => '2026-10-08', 'jumlah' => '40000',
        ]);
        $bayar = PembayaranPinjaman::firstOrFail();
        $this->assertSame('2026-12-08', $pinjaman->fresh()->tanggal_jatuh_tempo->toDateString());

        $this->get(route('pembayaran.edit', $bayar))->assertOk();
        $this->put(route('pembayaran.update', $bayar), [
            'tanggal' => '2026-10-08', 'jumlah' => '140000', 'deskripsi' => 'Koreksi',
        ])->assertSessionHasNoErrors()->assertRedirect(route('pinjaman.kelompok', $pinjaman->kelompok_tani_id));

        $baru = PembayaranPinjaman::firstOrFail();
        $this->assertSame($bayar->kode, $baru->kode);
        $this->assertSame($bayar->kode_setoran, $baru->kode_setoran);
        $this->assertSame('40000.00', $baru->bunga_nominal);
        $this->assertSame('100000.00', $baru->bayar_pokok);
        $this->assertEquals(900000, $pinjaman->fresh()->sisa_pokok);
        $this->assertSame(1, $pinjaman->fresh()->termin);
        $this->assertSame(1, TransaksiKas::count());
        $this->assertSame('140000.00', TransaksiKas::firstOrFail()->jumlah);
        $this->assertTrue(TransaksiKas::firstOrFail()->otomatis);
    }

    public function test_sunting_setoran_melebihi_tagihan_ditolak_tanpa_mengubah_data(): void
    {
        $pinjaman = $this->pinjaman();
        $this->actingAs($this->pengguna())->post(route('pembayaran.store', $pinjaman->kelompok_tani_id), [
            'tanggal' => '2026-10-08', 'jumlah' => '40000',
        ]);
        $bayar = PembayaranPinjaman::firstOrFail();

        $this->put(route('pembayaran.update', $bayar), [
            'tanggal' => '2026-10-08', 'jumlah' => '2000000',
        ])->assertSessionHasErrors('jumlah');

        $this->assertSame('40000.00', $bayar->fresh()->total_bayar);
        $this->assertSame('40000.00', TransaksiKas::firstOrFail()->jumlah);
    }

    public function test_hapus_setoran_mengembalikan_pinjaman_dan_menghitung_ulang_setoran_berikutnya(): void
    {
        $pinjaman = $this->pinjaman();
        $pengguna = $this->pengguna();
        $this->actingAs($pengguna)->post(route('pembayaran.store', $pinjaman->kelompok_tani_id), [
            'tanggal' => '2026-09-08', 'jumlah' => '20000',
        ]);
        $this->post(route('pembayaran.store', $pinjaman->kelompok_tani_id), [
            'tanggal' => '2026-10-08', 'jumlah' => '50000',
        ]);
        $pertama = PembayaranPinjaman::orderBy('tanggal')->firstOrFail();

        $this->delete(route('pembayaran.destroy', $pertama))->assertRedirect(route('pinjaman.kelompok', $pinjaman->kelompok_tani_id));

        // Tanpa setoran September, bunga Oktober menumpuk dua bulan: 40.000.
        $sisa = PembayaranPinjaman::firstOrFail();
        $this->assertSame(1, PembayaranPinjaman::count());
        $this->assertSame('40000.00', $sisa->bunga_nominal);
        $this->assertSame('10000.00', $sisa->bayar_pokok);
        $this->assertSame(1, TransaksiKas::count());
        $this->assertSame('50000.00', TransaksiKas::firstOrFail()->jumlah);

        $this->delete(route('pembayaran.destroy', $sisa));
        $this->assertSame(0, PembayaranPinjaman::count());
        $this->assertSame(0, TransaksiKas::count());
        $this->assertSame(Pinjaman::STATUS_BELUM_BAYAR, $pinjaman->fresh()->status);
        $this->assertSame('2026-12-08', $pinjaman->fresh()->tanggal_jatuh_tempo->toDateString());
        $this->assertSame(1, $pinjaman->fresh()->termin);
    }

    public function test_ketua_dapat_mengelola_akun_tetapi_tidak_data_operasional(): void
    {
        $this->actingAs($this->pengguna(User::ROLE_KETUA))
            ->get(route('pengguna.create'))->assertOk();
        $this->get(route('pinjaman.create'))->assertForbidden();
    }

    public function test_transaksi_kelompok_hanya_menampilkan_yang_belum_terbayar(): void
    {
        $pinjaman = $this->pinjaman();
        $this->actingAs($this->pengguna())->post(route('pembayaran.store', $pinjaman->kelompok_tani_id), [
            'tanggal' => '2026-10-08', 'jumlah' => '140000',
        ]);

        $this->get(route('kelompok.transaksi', $pinjaman->kelompok_tani_id))->assertOk()
            ->assertViewHas('totalPinjaman', fn ($nilai) => (float) $nilai === 900000.0)
            ->assertViewHas('totalBunga', fn ($nilai) => (float) $nilai === 0.0);
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

    private function pinjaman(array $atribut = []): Pinjaman
    {
        return Pinjaman::create([
            'kode' => 'LP-'.Str::uuid(),
            'kelompok_tani_id' => $atribut['kelompok_tani_id'] ?? $this->kelompok()->id,
            'tanggal_transaksi' => '2026-08-08',
            'tanggal_jatuh_tempo' => '2026-12-08',
            'termin' => 1,
            'jumlah_pinjaman' => 1000000,
            'potongan_persen' => 1.5,
            'potongan_talangan_persen' => 1,
            'potongan_nominal' => 15000,
            'jumlah_diterima' => 985000,
            'bunga_persen' => 2,
            'tunggakan_bunga' => 0,
            'status' => Pinjaman::STATUS_BELUM_BAYAR,
            ...$atribut,
        ]);
    }

    private function pengaturanBaru(): array
    {
        return ['suku_bunga_pinjaman' => '3.25', 'potongan_operasional' => '0.75', 'potongan_dana_talangan' => '1.25'];
    }
}
