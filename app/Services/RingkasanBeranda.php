<?php

namespace App\Services;

use App\Models\DanaTalangan;
use App\Models\KelompokTani;
use App\Models\PembayaranPinjaman;
use App\Models\Pinjaman;
use App\Models\TransaksiKas;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Semua angka yang tampil di beranda, dihitung sekali per permintaan.
 *
 * Pengelompokan per bulan sengaja dilakukan di PHP, bukan dengan fungsi
 * tanggal SQL, supaya kueri tetap sama di MySQL maupun SQLite (pengujian).
 * Datanya kecil: paling banyak transaksi dua belas bulan.
 */
class RingkasanBeranda
{
    public const JUMLAH_BULAN = 12;

    public const HARI_JATUH_TEMPO_MENDATANG = 30;

    private readonly Carbon $hariIni;

    private readonly Carbon $awalPeriode;

    /** @var Collection<int, Pinjaman> */
    private Collection $semuaPinjaman;

    public function __construct(?Carbon $hariIni = null)
    {
        $this->hariIni = ($hariIni ?? Carbon::today())->copy()->startOfDay();
        $this->awalPeriode = $this->hariIni->copy()->startOfMonth()->subMonthsNoOverflow(self::JUMLAH_BULAN - 1);
        $this->semuaPinjaman = Pinjaman::with(['pembayaran', 'kelompokTani'])->get();
    }

    /**
     * @return array<string, mixed>
     */
    public function data(): array
    {
        $belumLunas = $this->semuaPinjaman
            ->where('status', '!=', Pinjaman::STATUS_LUNAS)
            ->sortBy('tanggal_jatuh_tempo')
            ->values();

        return [
            'saldoKas' => TransaksiKas::saldoTotal(),
            'saldoTalangan' => DanaTalangan::saldoTotal(),
            'danaBeredar' => $belumLunas->sum(fn (Pinjaman $pinjaman) => $pinjaman->sisa_pokok),
            'lewatJatuhTempo' => $belumLunas->filter->lewatJatuhTempo()->values(),
            'jatuhTempoMendatang' => $this->jatuhTempoMendatang($belumLunas),
            'ikhtisar' => $this->ikhtisar($belumLunas),
            'labelBulan' => $this->labelBulan(),
            'arusKas' => $this->arusKas(),
            'setoran' => $this->setoran(),
            'statusPinjaman' => $this->statusPinjaman(),
            'kelompokTeratas' => $this->kelompokTeratas($belumLunas),
            'transaksiTerbaru' => TransaksiKas::with('kategori')
                ->orderByDesc('tanggal')
                ->orderByDesc('id')
                ->limit(6)
                ->get(),
        ];
    }

    /**
     * @return list<string>
     */
    private function labelBulan(): array
    {
        return collect(range(0, self::JUMLAH_BULAN - 1))
            ->map(fn (int $i) => $this->awalPeriode->copy()->addMonthsNoOverflow($i)->translatedFormat('M y'))
            ->all();
    }

    /**
     * @return array{masuk: list<float>, keluar: list<float>}
     */
    private function arusKas(): array
    {
        $baris = TransaksiKas::query()
            ->whereDate('tanggal', '>=', $this->awalPeriode)
            ->whereDate('tanggal', '<=', $this->hariIni)
            ->get(['tanggal', 'jenis', 'jumlah']);

        return [
            'masuk' => $this->perBulan($baris->where('jenis', TransaksiKas::JENIS_MASUK), 'jumlah'),
            'keluar' => $this->perBulan($baris->where('jenis', TransaksiKas::JENIS_KELUAR), 'jumlah'),
        ];
    }

    /**
     * @return array{pokok: list<float>, bunga: list<float>}
     */
    private function setoran(): array
    {
        $baris = PembayaranPinjaman::query()
            ->whereDate('tanggal', '>=', $this->awalPeriode)
            ->whereDate('tanggal', '<=', $this->hariIni)
            ->get(['tanggal', 'bayar_pokok', 'bunga_nominal']);

        return [
            'pokok' => $this->perBulan($baris, 'bayar_pokok'),
            'bunga' => $this->perBulan($baris, 'bunga_nominal'),
        ];
    }

    /**
     * @param  Collection<int, Model>  $baris
     * @return list<float>
     */
    private function perBulan(Collection $baris, string $kolom): array
    {
        $total = array_fill(0, self::JUMLAH_BULAN, 0.0);

        foreach ($baris as $item) {
            $tanggal = Carbon::parse($item->tanggal);
            $indeks = ($tanggal->year - $this->awalPeriode->year) * 12 + $tanggal->month - $this->awalPeriode->month;

            if ($indeks >= 0 && $indeks < self::JUMLAH_BULAN) {
                $total[$indeks] += (float) $item->{$kolom};
            }
        }

        return array_map(fn (float $nilai) => round($nilai, 2), $total);
    }

    /**
     * Urutan sengaja lunas → berjalan → menunggak: hijau dan merah tidak
     * boleh bersebelahan karena sulit dibedakan pembaca buta warna.
     *
     * @return list<array{kunci: string, label: string, jumlah: int, nominal: float}>
     */
    private function statusPinjaman(): array
    {
        $urutan = [
            Pinjaman::STATUS_LUNAS => 'Lunas',
            Pinjaman::STATUS_BELUM_BAYAR => 'Berjalan',
            Pinjaman::STATUS_MENUNGGAK => 'Menunggak',
        ];

        return collect($urutan)->map(function (string $label, string $status) {
            $pinjaman = $this->semuaPinjaman->where('status', $status);

            return [
                'kunci' => $status,
                'label' => $label,
                'jumlah' => $pinjaman->count(),
                // Pinjaman lunas tidak punya sisa; yang ditampilkan nilai pokok awalnya.
                'nominal' => (float) ($status === Pinjaman::STATUS_LUNAS
                    ? $pinjaman->sum('jumlah_pinjaman')
                    : $pinjaman->sum(fn (Pinjaman $item) => $item->sisa_pokok)),
            ];
        })->values()->all();
    }

    /**
     * @param  Collection<int, Pinjaman>  $belumLunas
     * @return Collection<int, array{kelompok: KelompokTani, sisa: float, jumlah: int}>
     */
    private function kelompokTeratas(Collection $belumLunas, int $batas = 5): Collection
    {
        return $belumLunas
            ->groupBy('kelompok_tani_id')
            ->map(fn (Collection $pinjaman) => [
                'kelompok' => $pinjaman->first()->kelompokTani,
                'sisa' => (float) $pinjaman->sum(fn (Pinjaman $item) => $item->sisa_pokok),
                'jumlah' => $pinjaman->count(),
            ])
            ->filter(fn (array $baris) => $baris['sisa'] > 0)
            ->sortByDesc('sisa')
            ->take($batas)
            ->values();
    }

    /**
     * Pinjaman yang jatuh tempo dalam 30 hari ke depan, belum termasuk yang
     * sudah lewat (yang itu tampil di peringatan merah).
     *
     * @param  Collection<int, Pinjaman>  $belumLunas
     * @return Collection<int, Pinjaman>
     */
    private function jatuhTempoMendatang(Collection $belumLunas): Collection
    {
        $batas = $this->hariIni->copy()->addDays(self::HARI_JATUH_TEMPO_MENDATANG);

        return $belumLunas
            ->filter(fn (Pinjaman $pinjaman) => ! $pinjaman->lewatJatuhTempo($this->hariIni)
                && $pinjaman->tanggal_jatuh_tempo->betweenIncluded($this->hariIni, $batas))
            ->values();
    }

    /**
     * @param  Collection<int, Pinjaman>  $belumLunas
     * @return array<string, mixed>
     */
    private function ikhtisar(Collection $belumLunas): array
    {
        $tahun = $this->hariIni->year;
        $dicairkan = $this->semuaPinjaman->filter(fn (Pinjaman $item) => $item->tanggal_transaksi->year === $tahun);

        $setoranBulanIni = PembayaranPinjaman::query()
            ->whereDate('tanggal', '>=', $this->hariIni->copy()->startOfMonth())
            ->whereDate('tanggal', '<=', $this->hariIni)
            ->get(['id', 'kode_setoran', 'total_bayar']);

        return [
            'tahun' => $tahun,
            'bulan' => $this->hariIni->translatedFormat('F Y'),
            'bungaTahunIni' => (float) PembayaranPinjaman::whereYear('tanggal', $tahun)->sum('bunga_nominal'),
            'setoranBulanIni' => (float) $setoranBulanIni->sum('total_bayar'),
            // Satu setoran kelompok bisa terpecah ke beberapa pinjaman (FIFO).
            'jumlahSetoranBulanIni' => $setoranBulanIni
                ->unique(fn (PembayaranPinjaman $item) => $item->kode_setoran ?? 'id-'.$item->id)
                ->count(),
            'dicairkanTahunIni' => (float) $dicairkan->sum('jumlah_pinjaman'),
            'jumlahDicairkanTahunIni' => $dicairkan->count(),
            'kelompokAktif' => $belumLunas->pluck('kelompok_tani_id')->unique()->count(),
            'totalKelompok' => KelompokTani::count(),
        ];
    }
}
