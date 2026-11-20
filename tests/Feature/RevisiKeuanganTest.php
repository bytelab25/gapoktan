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
        $pencairan = $pinjaman->transaksiKas()->whereRelation('kategori', 'nama', KategoriKas::PENCAIRAN_PINJAMAN)->firstOrFail();
        $this->assertSame('990000.00', $pencairan->jumlah);
        $this->assertSame('5000.00', $pencairan->biaya);
        $operasional = $pinjaman->transaksiKas()->whereRelation('kategori', 'nama', KategoriKas::POTONGAN_OPERASIONAL_PINJAMAN)->firstOrFail();
        $this->assertSame(TransaksiKas::JENIS_MASUK, $operasional->jenis);
        $this->assertSame('5000.00', $operasional->jumlah);
        $this->assertTrue($operasional->otomatis);
        $this->assertSame('10000.00', $pinjaman->danaTalangan()->firstOrFail()->jumlah);
        $this->assertSame(-995000.0, TransaksiKas::saldoTotal());

        Pengaturan::ambil()->update(['potongan_operasional' => 2]);

        $this->get(route('kas.index'))->assertOk()
            ->assertSee('Potongan operasional Rp 5.000')
            ->assertSee('Potongan Operasional Pinjaman')
            ->assertSee(route('kas.faktur', $operasional));
        $this->get(route('kas.show', $pencairan))->assertOk()
            ->assertSee('Potongan Operasional (Kas)')->assertSee(rupiah(5000))
            ->assertSee('Diterima Kelompok')->assertSee(rupiah(985000));
        $this->get(route('kas.faktur', $pencairan))->assertOk()
            ->assertSee('Potongan Operasional (Kas)')->assertSee(rupiah(5000))
            ->assertSee('Diterima Kelompok')->assertSee(rupiah(985000));
        $this->get(route('kas.faktur', $operasional))->assertOk()
            ->assertSee('Potongan Operasional Pinjaman')->assertSee(rupiah(5000));
    }

    public function test_potongan_operasional_pinjaman_delapan_juta_masuk_kas_dan_tetap_benar_saat_disunting(): void
    {
        $kelompok = $this->kelompok();
        $pengguna = $this->pengguna();
        $this->actingAs($pengguna)->post(route('pinjaman.store'), [
            'kelompok_tani_id' => $kelompok->id,
            'tanggal_transaksi' => '2026-10-08',
            'tanggal_jatuh_tempo' => '2027-02-08',
            'jumlah_pinjaman' => '8000000',
        ])->assertSessionHasNoErrors();

        $pinjaman = Pinjaman::firstOrFail();
        $pencairan = $pinjaman->transaksiKas()->whereRelation('kategori', 'nama', KategoriKas::PENCAIRAN_PINJAMAN)->firstOrFail();
        $operasional = $pinjaman->transaksiKas()->whereRelation('kategori', 'nama', KategoriKas::POTONGAN_OPERASIONAL_PINJAMAN)->firstOrFail();

        $this->assertSame('7880000.00', $pinjaman->jumlah_diterima);
        $this->assertSame('7920000.00', $pencairan->jumlah);
        $this->assertSame('40000.00', $operasional->jumlah);
        $this->assertSame('80000.00', $pinjaman->danaTalangan()->sole()->jumlah);
        $this->assertSame(-7960000.0, TransaksiKas::saldoTotal());

        $this->put(route('pinjaman.update', $pinjaman), [
            'kelompok_tani_id' => $kelompok->id,
            'tanggal_transaksi' => '2026-10-09',
            'tanggal_jatuh_tempo' => '2027-02-09',
            'jumlah_pinjaman' => '7000000',
        ])->assertSessionHasNoErrors();

        $this->assertSame($pencairan->id, $pencairan->fresh()->id);
        $this->assertSame('6930000.00', $pencairan->fresh()->jumlah);
        $this->assertSame($operasional->id, $operasional->fresh()->id);
        $this->assertSame('35000.00', $operasional->fresh()->jumlah);
        $this->assertSame('70000.00', $pinjaman->danaTalangan()->sole()->jumlah);
        $this->assertSame(-6965000.0, TransaksiKas::saldoTotal());
    }

    public function test_migrasi_membuat_faktur_kas_masuk_pinjaman_lama_tanpa_mengubah_saldo(): void
    {
        $kelompok = $this->kelompok();
        $this->actingAs($this->pengguna())->post(route('pinjaman.store'), [
            'kelompok_tani_id' => $kelompok->id,
            'tanggal_transaksi' => '2026-10-08',
            'tanggal_jatuh_tempo' => '2027-02-08',
            'jumlah_pinjaman' => '8000000',
        ])->assertSessionHasNoErrors();

        $pinjaman = Pinjaman::firstOrFail();
        $pencairan = $pinjaman->transaksiKas()->whereRelation('kategori', 'nama', KategoriKas::PENCAIRAN_PINJAMAN)->firstOrFail();
        $operasional = $pinjaman->transaksiKas()->whereRelation('kategori', 'nama', KategoriKas::POTONGAN_OPERASIONAL_PINJAMAN)->firstOrFail();
        $operasional->delete();
        $pencairan->update(['jumlah' => 7880000]);
        $saldoSebelum = TransaksiKas::saldoTotal();

        $migrasi = require database_path('migrations/2026_11_20_000001_catat_potongan_operasional_pinjaman_di_kas.php');
        $migrasi->up();
        $migrasi->up();

        $this->assertSame($saldoSebelum, TransaksiKas::saldoTotal());
        $this->assertSame('7920000.00', $pencairan->fresh()->jumlah);
        $this->assertSame(1, $pinjaman->transaksiKas()->whereRelation('kategori', 'nama', KategoriKas::POTONGAN_OPERASIONAL_PINJAMAN)->count());
        $pemasukan = $pinjaman->transaksiKas()->whereRelation('kategori', 'nama', KategoriKas::POTONGAN_OPERASIONAL_PINJAMAN)->firstOrFail();
        $this->get(route('kas.faktur', $pemasukan))->assertOk()->assertSee(rupiah(40000));

        $migrasi->down();
        $this->assertSame($saldoSebelum, TransaksiKas::saldoTotal());
        $this->assertSame('7880000.00', $pencairan->fresh()->jumlah);
        $this->assertSame(0, $pinjaman->transaksiKas()->whereRelation('kategori', 'nama', KategoriKas::POTONGAN_OPERASIONAL_PINJAMAN)->count());
    }

    public function test_kode_pinjaman_melanjutkan_urutan_setelah_pinjaman_lama_dihapus(): void
    {
        $lama = $this->pinjaman(['kode' => 'LP-2026-001']);
        $this->pinjaman(['kode' => 'LP-2026-002']);
        $lama->delete();

        $kelompok = $this->kelompok();
        $this->actingAs($this->pengguna())->post(route('pinjaman.store'), [
            'kelompok_tani_id' => $kelompok->id,
            'tanggal_transaksi' => '2026-09-04',
            'tanggal_jatuh_tempo' => '2027-01-04',
            'jumlah_pinjaman' => '8000000',
        ])->assertSessionHasNoErrors()->assertRedirect();

        $pinjaman = $kelompok->pinjaman()->sole();
        $this->assertSame('LP-2026-003', $pinjaman->kode);
        $this->assertSame('2026-09-04', $pinjaman->tanggal_transaksi->toDateString());
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

    public function test_setoran_bunga_saja_menutup_bunga_semua_pinjaman_tanpa_memotong_pokok(): void
    {
        $pertama = $this->pinjaman(['tanggal_jatuh_tempo' => '2026-10-08']);
        $kedua = $this->pinjaman(['kelompok_tani_id' => $pertama->kelompok_tani_id, 'tanggal_transaksi' => '2026-09-08']);

        // Bunga: 2 bulan × 20.000 untuk pinjaman pertama, 1 bulan untuk kedua.
        $this->actingAs($this->pengguna())->post(route('pembayaran.store', $pertama->kelompok_tani_id), [
            'tanggal' => '2026-10-08', 'jumlah' => '60000', 'hanya_bunga' => '1',
        ])->assertSessionHasNoErrors()->assertRedirect();

        $this->assertEquals(1000000, $pertama->fresh()->sisa_pokok);
        $this->assertEquals(1000000, $kedua->fresh()->sisa_pokok);
        $this->assertSame('0.00', $pertama->fresh()->tunggakan_bunga);
        $this->assertSame('0.00', $kedua->fresh()->tunggakan_bunga);
        $this->assertSame(['40000.00', '20000.00'], PembayaranPinjaman::orderBy('id')->pluck('bunga_nominal')->all());
        $this->assertTrue(PembayaranPinjaman::firstOrFail()->hanya_bunga);
        // Bunga termin lunas tepat di jatuh tempo, maka termin maju.
        $this->assertSame('2027-02-08', $pertama->fresh()->tanggal_jatuh_tempo->toDateString());
    }

    public function test_setoran_bunga_saja_melewati_pinjaman_yang_belum_berbunga(): void
    {
        $tua = $this->pinjaman();
        $muda = $this->pinjaman(['kelompok_tani_id' => $tua->kelompok_tani_id, 'tanggal_transaksi' => '2026-09-05']);
        $this->actingAs($this->pengguna());

        // Bunga pinjaman tua dilunasi 1 Oktober, jadi per 8 Oktober bunganya Rp 0.
        $this->post(route('pembayaran.store', $tua->kelompok_tani_id), [
            'tanggal' => '2026-10-01', 'jumlah' => '20000', 'hanya_bunga' => '1',
        ])->assertSessionHasNoErrors();
        $this->post(route('pembayaran.store', $tua->kelompok_tani_id), [
            'tanggal' => '2026-10-08', 'jumlah' => '20000', 'hanya_bunga' => '1',
        ])->assertSessionHasNoErrors();

        // Pinjaman tua tidak boleh dapat baris Rp 0 yang memajukan awal hitung bunganya.
        $this->assertSame(1, $tua->pembayaran()->count());
        $this->assertSame('2026-10-01', $tua->fresh()->load('pembayaran')->mulai_bunga->toDateString());
        $this->assertSame('20000.00', $muda->pembayaran()->firstOrFail()->bunga_nominal);
        $this->assertSame(0, PembayaranPinjaman::where('total_bayar', 0)->count());
    }

    public function test_setoran_bunga_saja_tidak_boleh_melebihi_total_bunga(): void
    {
        $pinjaman = $this->pinjaman();

        $this->actingAs($this->pengguna())->post(route('pembayaran.store', $pinjaman->kelompok_tani_id), [
            'tanggal' => '2026-10-08', 'jumlah' => '50000', 'hanya_bunga' => '1',
        ])->assertSessionHasErrors('jumlah');

        $this->assertDatabaseCount('pembayaran_pinjaman', 0);
    }

    public function test_jenis_bunga_saja_bertahan_saat_riwayat_diputar_ulang(): void
    {
        $pinjaman = $this->pinjaman();
        $pengguna = $this->pengguna();
        $this->actingAs($pengguna)->post(route('pembayaran.store', $pinjaman->kelompok_tani_id), [
            'tanggal' => '2026-10-08', 'jumlah' => '20000', 'hanya_bunga' => '1',
        ])->assertSessionHasNoErrors();
        // Setoran mundur memutar ulang setoran 8 Oktober; jenisnya harus tetap bunga saja.
        $this->post(route('pembayaran.store', $pinjaman->kelompok_tani_id), [
            'tanggal' => '2026-09-08', 'jumlah' => '20000', 'hanya_bunga' => '1',
        ])->assertSessionHasNoErrors();

        $this->assertSame([true, true], PembayaranPinjaman::orderBy('tanggal')->pluck('hanya_bunga')->all());
        $this->assertEquals(1000000, $pinjaman->fresh()->sisa_pokok);
    }

    public function test_pinjaman_lewat_jatuh_tempo_diberi_peringatan(): void
    {
        $lewat = $this->pinjaman(['tanggal_jatuh_tempo' => '2026-10-01']);
        $aman = $this->pinjaman();

        $this->assertTrue($lewat->lewatJatuhTempo());
        $this->assertSame(7, $lewat->hariLewatJatuhTempo());
        $this->assertFalse($aman->lewatJatuhTempo());
        $this->assertFalse($this->pinjaman(['tanggal_jatuh_tempo' => '2026-10-01', 'status' => Pinjaman::STATUS_LUNAS])->lewatJatuhTempo());

        $this->actingAs($this->pengguna(User::ROLE_USER))
            ->get(route('beranda'))->assertOk()->assertSee('1 pinjaman lewat jatuh tempo');
        $this->get(route('pinjaman.show', $lewat))->assertOk()->assertSee('Pinjaman lewat jatuh tempo 7 hari');
        $this->get(route('pinjaman.show', $aman))->assertOk()->assertDontSee('lewat jatuh tempo');
        $this->get(route('pinjaman.kelompok', $lewat->kelompok_tani_id))->assertOk()->assertSee('lewat 7 hari');
    }

    public function test_bunga_lunas_sebelum_jatuh_tempo_tidak_ditandai_lewat(): void
    {
        $pinjaman = $this->pinjaman(['tanggal_jatuh_tempo' => '2026-10-01']);

        $this->actingAs($this->pengguna())->post(route('pembayaran.store', $pinjaman->kelompok_tani_id), [
            'tanggal' => '2026-09-30', 'jumlah' => '20000', 'hanya_bunga' => '1',
        ])->assertSessionHasNoErrors();

        $this->assertFalse($pinjaman->fresh()->load('pembayaran')->lewatJatuhTempo());
        $this->get(route('beranda'))->assertDontSee('lewat jatuh tempo');
    }

    public function test_faktur_ditandatangani_penyunting_terakhir(): void
    {
        $pencatat = User::factory()->create(['username' => 'pencatat', 'name' => 'Pencatat Awal', 'role' => User::ROLE_SEKRETARIS]);
        $penyunting = User::factory()->create(['username' => 'penyunting', 'name' => 'Penyunting Akhir', 'role' => User::ROLE_BENDAHARA]);
        $pinjaman = $this->pinjaman(['user_id' => $pencatat->id]);

        $this->actingAs($pencatat)->post(route('pembayaran.store', $pinjaman->kelompok_tani_id), [
            'tanggal' => '2026-10-08', 'jumlah' => '40000',
        ])->assertSessionHasNoErrors();
        $bayar = PembayaranPinjaman::firstOrFail();
        $this->get(route('pembayaran.faktur', $bayar))->assertSee('Pencatat Awal');

        $this->actingAs($penyunting)->put(route('pembayaran.update', $bayar), [
            'tanggal' => '2026-10-08', 'jumlah' => '50000',
        ])->assertSessionHasNoErrors();

        $baru = PembayaranPinjaman::firstOrFail();
        $this->assertSame($pencatat->id, $baru->user_id);
        $this->assertSame($penyunting->id, $baru->diubah_oleh_id);
        $this->get(route('pembayaran.faktur', $baru))->assertSee('Penyunting Akhir')->assertDontSee('Pencatat Awal');
        $this->assertSame($penyunting->id, TransaksiKas::firstOrFail()->diubah_oleh_id);

        $belumBayar = $this->pinjaman(['user_id' => $pencatat->id]);
        $this->put(route('pinjaman.update', $belumBayar), [
            'kelompok_tani_id' => $belumBayar->kelompok_tani_id, 'tanggal_transaksi' => '2026-08-08',
            'tanggal_jatuh_tempo' => '2026-12-08', 'jumlah_pinjaman' => '2000000',
        ])->assertSessionHasNoErrors();
        $this->assertSame($pencatat->id, $belumBayar->fresh()->user_id);
        $this->get(route('pinjaman.faktur', $belumBayar))->assertSee('Penyunting Akhir')->assertDontSee('Pencatat Awal');
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

    public static function bukanKetua(): array
    {
        return [[User::ROLE_SEKRETARIS], [User::ROLE_BENDAHARA], [User::ROLE_USER]];
    }

    #[DataProvider('bukanKetua')]
    public function test_log_aktivitas_tertutup_untuk_selain_ketua(string $role): void
    {
        $this->actingAs($this->pengguna($role))
            ->get(route('beranda'))->assertOk()->assertDontSee('Aktivitas Terkini');
        $this->get(route('log.index'))->assertForbidden();
    }

    public function test_ketua_dapat_melihat_log_aktivitas(): void
    {
        $this->actingAs($this->pengguna(User::ROLE_KETUA))
            ->get(route('beranda'))->assertOk()->assertSee('Aktivitas Terkini');
        $this->get(route('log.index'))->assertOk();
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

    public function test_url_setoran_tetap_sama_setelah_disunting(): void
    {
        $pinjaman = $this->pinjaman();
        $this->actingAs($this->pengguna())->post(route('pembayaran.store', $pinjaman->kelompok_tani_id), [
            'tanggal' => '2026-10-08', 'jumlah' => '40000',
        ]);
        $url = route('pembayaran.faktur', PembayaranPinjaman::firstOrFail());

        $this->put(route('pembayaran.update', PembayaranPinjaman::firstOrFail()), [
            'tanggal' => '2026-10-08', 'jumlah' => '50000',
        ])->assertSessionHasNoErrors();

        $this->get($url)->assertOk()->assertSee('50.000');
    }

    public function test_setoran_bertanggal_mundur_teralokasi_urut_tanggal(): void
    {
        $pinjaman = $this->pinjaman();
        $this->actingAs($this->pengguna())->post(route('pembayaran.store', $pinjaman->kelompok_tani_id), [
            'tanggal' => '2026-10-08', 'jumlah' => '40000',
        ])->assertSessionHasNoErrors();

        // Setoran September dicatat belakangan: bunga 1 bulan 20.000 + pokok 80.000.
        $this->post(route('pembayaran.store', $pinjaman->kelompok_tani_id), [
            'tanggal' => '2026-09-08', 'jumlah' => '100000',
        ])->assertSessionHasNoErrors();

        $september = PembayaranPinjaman::whereDate('tanggal', '2026-09-08')->firstOrFail();
        $oktober = PembayaranPinjaman::whereDate('tanggal', '2026-10-08')->firstOrFail();
        $this->assertSame('20000.00', $september->bunga_nominal);
        $this->assertSame('80000.00', $september->bayar_pokok);
        // Oktober kini menanggung bunga 2% dari 920.000, sisanya memotong pokok.
        $this->assertSame('18400.00', $oktober->bunga_nominal);
        $this->assertSame('21600.00', $oktober->bayar_pokok);
        $this->assertSame(2, TransaksiKas::count());
    }

    public function test_setoran_mundur_yang_membuat_setoran_lain_kelebihan_ditolak(): void
    {
        $pinjaman = $this->pinjaman();
        $this->actingAs($this->pengguna())->post(route('pembayaran.store', $pinjaman->kelompok_tani_id), [
            'tanggal' => '2026-10-08', 'jumlah' => '1040000',
        ]);

        $this->post(route('pembayaran.store', $pinjaman->kelompok_tani_id), [
            'tanggal' => '2026-09-08', 'jumlah' => '100000',
        ])->assertSessionHasErrors('jumlah');

        $this->assertSame(1, PembayaranPinjaman::count());
        $this->assertSame(Pinjaman::STATUS_LUNAS, $pinjaman->fresh()->status);
    }

    public function test_pinjaman_baru_bertanggal_mundur_menghitung_ulang_fifo(): void
    {
        $baru = $this->pinjaman(['tanggal_transaksi' => '2026-09-08']);
        $this->actingAs($this->pengguna())->post(route('pembayaran.store', $baru->kelompok_tani_id), [
            'tanggal' => '2026-10-08', 'jumlah' => '20000',
        ]);
        $this->assertSame(1, $baru->pembayaran()->count());

        $this->post(route('pinjaman.store'), [
            'kelompok_tani_id' => $baru->kelompok_tani_id,
            'tanggal_transaksi' => '2026-08-08',
            'tanggal_jatuh_tempo' => '2026-12-08',
            'jumlah_pinjaman' => '1000000',
        ])->assertSessionHasNoErrors();

        $lama = Pinjaman::where('id', '!=', $baru->id)->firstOrFail();
        $this->assertSame(1, $lama->pembayaran()->count());
        $this->assertSame(0, $baru->pembayaran()->count());
        $this->assertSame(Pinjaman::STATUS_BELUM_BAYAR, $baru->fresh()->status);
    }

    public function test_akses_ditolak_kembali_ke_halaman_asal_dengan_pesan(): void
    {
        $this->actingAs($this->pengguna(User::ROLE_KETUA))
            ->from(route('pinjaman.index'))
            ->get(route('pinjaman.create'))
            ->assertRedirect(route('pinjaman.index'))
            ->assertSessionHas('gagal', fn ($pesan) => str_contains($pesan, 'Ketua Gapoktan'));

        $this->from(route('pengaturan.edit'))->post(route('kas.store'))
            ->assertRedirect(route('pengaturan.edit'))->assertSessionHas('gagal');
    }

    public function test_akses_ditolak_tanpa_halaman_asal_menampilkan_halaman_403(): void
    {
        $this->actingAs($this->pengguna(User::ROLE_USER))
            ->get(route('kelompok.index'))
            ->assertForbidden()
            ->assertSee('Akses Ditolak')
            ->assertSee('Ke Beranda');

        $this->get('/pinjaman/999999')->assertNotFound()->assertSee('Halaman Tidak Ditemukan');
    }

    public function test_kelompok_dengan_pinjaman_belum_bayar_tidak_bisa_meminjam_walau_ada_yang_menunggak(): void
    {
        $lama = $this->pinjaman();
        $baru = $this->pinjaman(['kelompok_tani_id' => $lama->kelompok_tani_id, 'tanggal_transaksi' => '2026-09-08']);
        $kelompokLain = $this->pinjaman(['status' => Pinjaman::STATUS_LUNAS]);
        $pinjamanLain = $this->pinjaman(['kelompok_tani_id' => $kelompokLain->kelompok_tani_id, 'status' => Pinjaman::STATUS_LUNAS]);

        // Setoran FIFO hanya mengenai pinjaman lama: kelompok menunggak, pinjaman baru belum bayar.
        $this->actingAs($this->pengguna())->post(route('pembayaran.store', $lama->kelompok_tani_id), [
            'tanggal' => '2026-10-08', 'jumlah' => '40000',
        ]);
        $this->assertSame(Pinjaman::STATUS_MENUNGGAK, $lama->fresh()->status);
        $this->assertSame(Pinjaman::STATUS_BELUM_BAYAR, $baru->fresh()->status);

        $formulir = [
            'kelompok_tani_id' => $lama->kelompok_tani_id,
            'tanggal_transaksi' => '2026-10-08',
            'tanggal_jatuh_tempo' => '2027-02-08',
            'jumlah_pinjaman' => '500000',
        ];
        $this->post(route('pinjaman.store'), $formulir)
            ->assertSessionHasErrors(['kelompok_tani_id' => 'Kelompok Uji masih punya pinjaman berstatus Belum Bayar ('.$baru->kode.'), jadi belum bisa mengajukan pinjaman baru.']);
        $this->assertSame(4, Pinjaman::count());

        // Memindah pinjaman lain ke kelompok itu juga ditolak.
        $this->put(route('pinjaman.update', $pinjamanLain->fresh()), [...$formulir, 'tanggal_transaksi' => '2026-08-08'])
            ->assertSessionHasErrors('kelompok_tani_id');
        $this->assertSame($kelompokLain->kelompok_tani_id, $pinjamanLain->fresh()->kelompok_tani_id);

        // Pokok lama 1.000.000 lunas, sisanya bunga 20.000 + pokok 10.000 pinjaman baru.
        $this->post(route('pembayaran.store', $lama->kelompok_tani_id), [
            'tanggal' => '2026-10-08', 'jumlah' => '1030000',
        ])->assertSessionHasNoErrors();
        $this->assertSame(Pinjaman::STATUS_LUNAS, $lama->fresh()->status);
        $this->assertSame(Pinjaman::STATUS_MENUNGGAK, $baru->fresh()->status);
        $this->post(route('pinjaman.store'), $formulir)->assertSessionHasNoErrors();
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
