<?php

namespace App\Services;

use App\Models\KategoriKas;
use App\Models\KelompokTani;
use App\Models\PembayaranPinjaman;
use App\Models\Pinjaman;
use App\Models\TransaksiKas;
use App\Models\User;
use Closure;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Setoran pembayaran pinjaman satu kelompok tani.
 *
 * Aturan koperasi yang diwakili kelas ini:
 * 1. Bunga selalu didahulukan; pokok baru terpotong setelah bunga tertutup.
 *    Karena itu kelompok boleh nyicil — kurang dari bunga pun tetap diterima
 *    dan kekurangannya jadi tunggakan bunga.
 * 2. Bila kelompok punya lebih dari satu pinjaman, uang masuk ke pinjaman
 *    tertua lebih dulu (FIFO). Sisanya baru mengalir ke pinjaman berikutnya.
 * 3. Setoran boleh dilakukan sejak pencairan. Bila bunga sudah lunas dan jatuh
 *    tempo terlewati, jatuh tempo maju 4 bulan selama pokok belum lunas.
 * 4. Setoran bisa disunting dan dihapus. Karena poin 1–3 bergantung pada
 *    setoran sebelumnya, riwayat kelompok selalu diputar ulang dari awal.
 */
class SetoranPinjaman
{
    /**
     * Kode baris yang sudah terpakai selama satu putaran ulang.
     *
     * @var list<string>
     */
    private array $kodeDipesan = [];

    public function __construct(private readonly KelompokTani $kelompok) {}

    /**
     * Pinjaman yang sudah boleh ditagih, urut tertua dulu.
     *
     * @return Collection<int, Pinjaman>
     */
    public function tagihan(?Carbon $sampai = null): Collection
    {
        $sampai ??= Carbon::today();

        return $this->kelompok
            ->pinjaman()
            ->with('pembayaran')
            ->belumLunas()
            ->get()
            ->filter(fn (Pinjaman $pinjaman) => $pinjaman->bisaDibayar($sampai))
            ->values();
    }

    /**
     * Pinjaman dengan tanggal pencairan setelah tanggal setoran.
     *
     * @return Collection<int, Pinjaman>
     */
    public function belumDicairkan(?Carbon $sampai = null): Collection
    {
        $sampai ??= Carbon::today();

        return $this->kelompok
            ->pinjaman()
            ->with('pembayaran')
            ->belumLunas()
            ->get()
            ->reject(fn (Pinjaman $pinjaman) => $pinjaman->bisaDibayar($sampai))
            ->values();
    }

    public function totalSisaPokok(?Carbon $sampai = null): float
    {
        return round($this->tagihan($sampai)->sum(fn (Pinjaman $pinjaman) => $pinjaman->sisa_pokok), 2);
    }

    public function totalBungaTerhutang(?Carbon $sampai = null): float
    {
        return round($this->tagihan($sampai)->sum(fn (Pinjaman $pinjaman) => $pinjaman->bungaTerhutang($sampai)), 2);
    }

    /**
     * Nominal untuk melunasi seluruh pinjaman kelompok pada tanggal tertentu.
     */
    public function totalPelunasan(?Carbon $sampai = null): float
    {
        return round($this->totalSisaPokok($sampai) + $this->totalBungaTerhutang($sampai), 2);
    }

    /**
     * Catat setoran baru. Seluruh setoran kelompok disusun ulang menurut
     * tanggal, jadi setoran bertanggal mundur pun teralokasi dengan benar.
     *
     * @return Collection<int, PembayaranPinjaman> baris pembayaran yang terbentuk
     */
    public function catat(Carbon $tanggal, float $jumlah, ?string $deskripsi, User $pencatat): Collection
    {
        $kodeSetoran = $this->kodeSetoranBerikutnya();

        $this->susunUlang(fn (SupportCollection $daftar) => $daftar->push([
            'kode_setoran' => $kodeSetoran,
            'tanggal' => $tanggal->copy()->startOfDay(),
            'jumlah' => round($jumlah, 2),
            'deskripsi' => $deskripsi,
            'user_id' => $pencatat->id,
            'kode_baris' => [],
            'urutan' => PHP_INT_MAX,
        ]), $pencatat);

        return PembayaranPinjaman::where('kode_setoran', $kodeSetoran)->orderBy('id')->get();
    }

    /**
     * Ganti tanggal, nominal, atau catatan satu setoran.
     */
    public function ubah(string $kodeSetoran, Carbon $tanggal, float $jumlah, ?string $deskripsi, User $pencatat): void
    {
        $this->susunUlang(fn (SupportCollection $daftar) => $daftar->map(
            fn (array $setoran) => $setoran['kode_setoran'] === $kodeSetoran
                ? [...$setoran, 'tanggal' => $tanggal->copy()->startOfDay(), 'jumlah' => round($jumlah, 2), 'deskripsi' => $deskripsi]
                : $setoran,
        ), $pencatat);
    }

    public function hapus(string $kodeSetoran, User $pencatat): void
    {
        $this->susunUlang(fn (SupportCollection $daftar) => $daftar->reject(
            fn (array $setoran) => $setoran['kode_setoran'] === $kodeSetoran,
        ), $pencatat);
    }

    /**
     * Putar ulang setoran tanpa perubahan, mis. setelah pinjaman kelompok
     * ditambah atau disunting dengan tanggal cair mundur.
     */
    public function hitungUlang(User $pencatat): void
    {
        $adaSetoran = PembayaranPinjaman::whereIn('pinjaman_id', $this->kelompok->pinjaman()->select('id'))->exists();

        if ($adaSetoran) {
            $this->susunUlang(fn (SupportCollection $daftar) => $daftar, $pencatat);
        }
    }

    /**
     * Apakah ada setoran kelompok yang bertanggal setelah tanggal ini.
     */
    public function adaSetoranSesudah(Carbon $tanggal): bool
    {
        return PembayaranPinjaman::whereIn('pinjaman_id', $this->kelompok->pinjaman()->select('id'))
            ->whereDate('tanggal', '>', $tanggal->toDateString())
            ->exists();
    }

    /**
     * Bunga dan alokasi FIFO bergantung pada setoran sebelumnya, jadi setiap
     * perubahan riwayat dihitung ulang dari awal: semua baris pembayaran
     * kelompok dibuang, pinjaman dikembalikan ke kondisi pencairan, lalu
     * setoran diputar ulang urut tanggal. Kode setoran dan kode baris lama
     * dipakai lagi supaya faktur yang sudah dicetak tetap merujuk kode sama.
     *
     * @param  Closure(SupportCollection<int, array<string, mixed>>): SupportCollection<int, array<string, mixed>>  $ubah
     */
    private function susunUlang(Closure $ubah, User $pencatat): void
    {
        DB::transaction(function () use ($ubah, $pencatat) {
            $daftarPinjaman = $this->kelompok->pinjaman()->lockForUpdate()->get();
            $barisLama = PembayaranPinjaman::whereIn('pinjaman_id', $daftarPinjaman->modelKeys())
                ->orderBy('tanggal')->orderBy('id')->get();

            $daftar = $barisLama
                ->groupBy(fn (PembayaranPinjaman $baris) => $baris->kode_setoran ?? $baris->kode)
                ->map(fn (Collection $grup, string $kode) => [
                    'kode_setoran' => $kode,
                    'tanggal' => $grup->first()->tanggal->copy(),
                    'jumlah' => round((float) $grup->sum('total_bayar'), 2),
                    'deskripsi' => $grup->first()->deskripsi,
                    'user_id' => $grup->first()->user_id,
                    'kode_baris' => $grup->sortBy('id')->pluck('kode')->all(),
                    'urutan' => $grup->min('id'),
                ])
                ->values()
                ->toBase();

            $daftar = $ubah($daftar)
                ->sortBy(fn (array $setoran) => $setoran['tanggal']->format('Ymd').sprintf('%020d', $setoran['urutan']))
                ->values();

            $this->kodeDipesan = $daftar->pluck('kode_baris')->flatten()->all();

            PembayaranPinjaman::whereKey($barisLama->modelKeys())->delete();

            foreach ($daftarPinjaman as $pinjaman) {
                $pinjaman->termin = 1;
                $pinjaman->tanggal_jatuh_tempo_awal = $pinjaman->jatuhTempoAwal();
                $pinjaman->tanggal_jatuh_tempo = $pinjaman->jatuhTempoAwal();
                $pinjaman->tunggakan_bunga = 0;
                $pinjaman->segarkanStatus();
            }

            foreach ($daftar as $setoran) {
                $this->putarUlang($setoran, $pencatat);
            }

            // Baris kas setoran yang sudah tidak ada ikut dibuang.
            TransaksiKas::where('kelompok_tani_id', $this->kelompok->id)
                ->whereNotNull('kode_setoran')
                ->whereNotIn('kode_setoran', $daftar->pluck('kode_setoran')->all())
                ->delete();

            $this->kodeDipesan = [];
        });
    }

    /**
     * @param  array<string, mixed>  $setoran
     */
    private function putarUlang(array $setoran, User $pencatat): void
    {
        $tanggal = $setoran['tanggal'];
        $maksimal = $this->totalPelunasan($tanggal);
        $label = 'Setoran tanggal '.tanggal_id($tanggal, 'd F Y');

        if ($maksimal < 0.01) {
            throw ValidationException::withMessages([
                'jumlah' => $label.' tidak punya tagihan yang bisa dibayar. Pinjaman mungkin belum dicairkan atau sudah lunas.',
            ]);
        }

        // Selisih di bawah Rp 1 akibat pembulatan tagihan ke atas dipangkas.
        if ($setoran['jumlah'] - $maksimal >= 1) {
            throw ValidationException::withMessages([
                'jumlah' => $label.' sebesar '.rupiah($setoran['jumlah'])
                    .' melebihi total tagihan saat itu, '.rupiah(ceil($maksimal)).'.',
            ]);
        }

        $sisaUang = round(min($setoran['jumlah'], $maksimal), 2);
        $kodeBaris = $setoran['kode_baris'];
        $pengisi = User::find($setoran['user_id']) ?? $pencatat;
        $baris = new Collection;

        foreach ($this->tagihan($tanggal) as $pinjaman) {
            if ($sisaUang < 0.01) {
                break;
            }

            $baris->push($this->bayarSatuPinjaman(
                $pinjaman, $tanggal, $sisaUang, $setoran['kode_setoran'], $setoran['deskripsi'], $pengisi,
                array_shift($kodeBaris) ?? $this->kodeBayarBerikutnya(),
            ));
        }

        $this->simpanKasMasuk($tanggal, (float) $baris->sum('total_bayar'), $setoran['kode_setoran'], $pengisi);
    }

    /**
     * Alokasi ke satu pinjaman. Mengurangi $sisaUang sesuai yang terpakai.
     */
    private function bayarSatuPinjaman(
        Pinjaman $pinjaman,
        Carbon $tanggal,
        float &$sisaUang,
        string $kodeSetoran,
        ?string $deskripsi,
        User $pencatat,
        string $kode,
    ): PembayaranPinjaman {
        $bungaTerhutang = $pinjaman->bungaTerhutang($tanggal);
        $bulanBunga = $pinjaman->bulanBungaBerjalan($tanggal);

        $bayarBunga = round(min($sisaUang, $bungaTerhutang), 2);
        $sisaUang = round($sisaUang - $bayarBunga, 2);

        // Pokok hanya terpotong kalau bunga sudah tertutup semua.
        $bayarPokok = 0.0;
        if ($bungaTerhutang - $bayarBunga < 0.01) {
            $bayarPokok = round(min($sisaUang, $pinjaman->sisa_pokok), 2);
            $sisaUang = round($sisaUang - $bayarPokok, 2);
        }

        $pembayaran = $pinjaman->pembayaran()->create([
            'kode' => $kode,
            'kode_setoran' => $kodeSetoran,
            'tanggal' => $tanggal,
            'bulan_bunga' => $bulanBunga,
            'bunga_nominal' => $bayarBunga,
            'bayar_pokok' => $bayarPokok,
            'total_bayar' => round($bayarBunga + $bayarPokok, 2),
            'deskripsi' => $deskripsi,
            'user_id' => $pencatat->id,
        ]);

        $pinjaman->tunggakan_bunga = max(0, round($bungaTerhutang - $bayarBunga, 2));

        // Bunga termin ini beres tapi pokok masih jalan: jatuh tempo maju 4 bulan.
        $pinjaman->load('pembayaran');
        if ($pinjaman->tunggakan_bunga < 0.01 && $pinjaman->sisa_pokok > 0) {
            $pinjaman->majukanTermin($tanggal);
        }

        $pinjaman->segarkanStatus();

        return $pembayaran;
    }

    /**
     * Setoran kelompok masuk kembali ke kas koperasi sebagai satu transaksi.
     * Baris kas lama dipertahankan supaya kode transaksinya tidak berubah.
     */
    private function simpanKasMasuk(Carbon $tanggal, float $total, string $kodeSetoran, User $pencatat): void
    {
        $kas = TransaksiKas::firstOrNew(['kode_setoran' => $kodeSetoran]);

        $kas->fill([
            'kode' => $kas->kode ?? sprintf('TXN-%05d', 10000 + (int) TransaksiKas::max('id') + 1),
            'tanggal' => $tanggal,
            'jenis' => TransaksiKas::JENIS_MASUK,
            'kategori_kas_id' => KategoriKas::sistem(KategoriKas::PEMBAYARAN_PINJAMAN)->id,
            'kelompok_tani_id' => $this->kelompok->id,
            'jumlah' => $total,
            'biaya' => 0,
            'deskripsi' => 'Setoran '.$kodeSetoran.' dari '.$this->kelompok->nama_kelompok,
            'user_id' => $kas->user_id ?? $pencatat->id,
        ])->save();
    }

    private function kodeSetoranBerikutnya(): string
    {
        $tahun = now()->year;

        return sprintf('ST-%s-%03d', $tahun, $this->urutanTerakhir(
            PembayaranPinjaman::where('kode_setoran', 'like', "ST-{$tahun}-%")->distinct()->pluck('kode_setoran')
                ->merge(TransaksiKas::where('kode_setoran', 'like', "ST-{$tahun}-%")->pluck('kode_setoran')),
        ) + 1);
    }

    /**
     * Berbasis nomor terbesar, bukan jumlah baris, karena baris bisa dihapus.
     * Kode milik setoran yang sedang diputar ulang ikut diperhitungkan.
     */
    private function kodeBayarBerikutnya(): string
    {
        $tahun = now()->year;
        $kode = sprintf('BP-%s-%03d', $tahun, $this->urutanTerakhir(
            PembayaranPinjaman::where('kode', 'like', "BP-{$tahun}-%")->pluck('kode')
                ->merge(array_filter($this->kodeDipesan, fn (string $kode) => str_starts_with($kode, "BP-{$tahun}-"))),
        ) + 1);

        $this->kodeDipesan[] = $kode;

        return $kode;
    }

    /**
     * @param  SupportCollection<int, string>  $daftarKode
     */
    private function urutanTerakhir(SupportCollection $daftarKode): int
    {
        return (int) $daftarKode->map(fn (string $kode) => (int) substr((string) strrchr($kode, '-'), 1))->max();
    }
}
