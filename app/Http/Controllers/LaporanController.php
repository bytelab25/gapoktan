<?php

namespace App\Http\Controllers;

use App\Models\DanaTalangan;
use App\Models\KategoriKas;
use App\Models\PembayaranPinjaman;
use App\Models\Pinjaman;
use App\Models\TransaksiKas;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class LaporanController extends Controller
{
    public const JENIS = [
        'kas' => 'Kas',
        'pinjaman' => 'Pinjaman',
        'dana_talangan' => 'Dana Talangan',
        'akhir_tahun' => 'Laporan Akhir Tahun',
    ];

    public const JUDUL = [
        'kas' => 'Laporan Kas',
        'pinjaman' => 'Laporan Pinjaman',
        'dana_talangan' => 'Laporan Dana Talangan',
        'akhir_tahun' => 'Laporan Akhir Tahun',
    ];

    /**
     * Satu halaman: bar filter di atas, hasil laporan langsung di bawahnya.
     */
    public function index(Request $request): View
    {
        $filter = [
            'jenis' => 'kas',
            'dari' => now()->startOfMonth()->format('Y-m-d'),
            'sampai' => now()->format('Y-m-d'),
            'tahun' => (string) now()->year,
        ];

        $hasil = null;

        if ($request->filled('jenis')) {
            $data = $this->validasi($request);
            $filter = array_merge($filter, array_filter($data, fn ($nilai) => $nilai !== null));
            $hasil = $this->rakit($data, $request->integer('per_halaman', 10));
        }

        return view('laporan.index', [
            'daftarJenis' => self::JENIS,
            'daftarTahun' => range(now()->year, now()->year - 5),
            'filter' => $filter,
            'hasil' => $hasil,
        ]);
    }

    /**
     * Versi berkop untuk dicetak atau disimpan sebagai PDF.
     */
    public function cetak(Request $request): View
    {
        return view('laporan.cetak', [
            'hasil' => $this->rakit($this->validasi($request), perHalaman: null),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validasi(Request $request): array
    {
        return $request->validate([
            'jenis' => ['required', 'in:'.implode(',', array_keys(self::JENIS))],
            'dari' => ['required_unless:jenis,akhir_tahun', 'nullable', 'date'],
            'sampai' => ['required_unless:jenis,akhir_tahun', 'nullable', 'date', 'after_or_equal:dari'],
            'tahun' => ['required_if:jenis,akhir_tahun', 'nullable', 'integer', 'min:2000', 'max:2100'],
        ], attributes: [
            'dari' => 'tanggal mulai',
            'sampai' => 'tanggal akhir',
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function rakit(array $data, ?int $perHalaman): array
    {
        if ($data['jenis'] === 'akhir_tahun') {
            return $this->akhirTahun((int) $data['tahun']);
        }

        $dari = Carbon::parse($data['dari'])->startOfDay();
        $sampai = Carbon::parse($data['sampai'])->endOfDay();

        $inti = match ($data['jenis']) {
            'kas' => $this->laporanKas($dari, $sampai, $perHalaman),
            'pinjaman' => $this->laporanPinjaman($dari, $sampai, $perHalaman),
            'dana_talangan' => $this->laporanDanaTalangan($dari, $sampai, $perHalaman),
        };

        return [
            ...$inti,
            'jenis' => $data['jenis'],
            'judul' => self::JUDUL[$data['jenis']],
            'periode' => 'Periode '.tanggal_id($dari, 'd F Y').' s.d. '.tanggal_id($sampai, 'd F Y'),
            'dari' => $dari,
            'sampai' => $sampai,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function laporanKas(Carbon $dari, Carbon $sampai, ?int $perHalaman): array
    {
        $dasar = TransaksiKas::with(['kategori', 'kelompokTani'])
            ->whereBetween('tanggal', [$dari, $sampai])
            ->orderByDesc('tanggal')
            ->orderByDesc('id');

        $saldoAwal = (float) TransaksiKas::whereDate('tanggal', '<', $dari)
            ->selectRaw("COALESCE(SUM(CASE WHEN jenis = 'masuk' THEN jumlah ELSE -jumlah END), 0) AS saldo")
            ->value('saldo');

        $totalMasuk = (float) TransaksiKas::whereBetween('tanggal', [$dari, $sampai])
            ->where('jenis', TransaksiKas::JENIS_MASUK)->sum('jumlah');
        $totalKeluar = (float) TransaksiKas::whereBetween('tanggal', [$dari, $sampai])
            ->where('jenis', TransaksiKas::JENIS_KELUAR)->sum('jumlah');

        return [
            'baris' => $perHalaman ? $dasar->paginate($perHalaman)->withQueryString() : $dasar->get(),
            'ringkasan' => [
                ['label' => 'Saldo Awal', 'nilai' => rupiah($saldoAwal)],
                ['label' => 'Total Masuk', 'nilai' => rupiah($totalMasuk), 'warna' => 'text-brand-600'],
                ['label' => 'Total Keluar', 'nilai' => rupiah($totalKeluar), 'warna' => 'text-danger-600'],
                ['label' => 'Saldo Akhir', 'nilai' => rupiah($saldoAwal + $totalMasuk - $totalKeluar)],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function laporanPinjaman(Carbon $dari, Carbon $sampai, ?int $perHalaman): array
    {
        $dasar = Pinjaman::with(['kelompokTani', 'pembayaran'])
            ->whereBetween('tanggal_transaksi', [$dari, $sampai])
            ->orderByDesc('tanggal_transaksi')
            ->orderByDesc('id');

        $semua = Pinjaman::with('pembayaran')->whereBetween('tanggal_transaksi', [$dari, $sampai])->get();
        $bungaDiterima = (float) PembayaranPinjaman::whereBetween('tanggal', [$dari, $sampai])->sum('bunga_nominal');

        return [
            'baris' => $perHalaman ? $dasar->paginate($perHalaman)->withQueryString() : $dasar->get(),
            'ringkasan' => [
                ['label' => 'Total Pinjaman Pokok', 'nilai' => rupiah($semua->sum('jumlah_pinjaman'))],
                ['label' => 'Total Potongan Muka', 'nilai' => rupiah($semua->sum('potongan_nominal'))],
                ['label' => 'Bunga Diterima', 'nilai' => rupiah($bungaDiterima), 'warna' => 'text-brand-600'],
                [
                    'label' => 'Sisa Pokok Beredar',
                    'nilai' => rupiah($semua->sum(fn (Pinjaman $item) => $item->sisa_pokok)),
                    'warna' => 'text-danger-600',
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function laporanDanaTalangan(Carbon $dari, Carbon $sampai, ?int $perHalaman): array
    {
        $dasar = DanaTalangan::with(['kelompokTani', 'pinjaman'])
            ->whereBetween('tanggal', [$dari, $sampai])
            ->orderByDesc('tanggal')
            ->orderByDesc('id');

        $saldoAwal = (float) DanaTalangan::whereDate('tanggal', '<', $dari)
            ->selectRaw("COALESCE(SUM(CASE WHEN jenis = 'masuk' THEN jumlah ELSE -jumlah END), 0) AS saldo")
            ->value('saldo');

        $masuk = (float) DanaTalangan::whereBetween('tanggal', [$dari, $sampai])
            ->where('jenis', DanaTalangan::JENIS_MASUK)->sum('jumlah');
        $keluar = (float) DanaTalangan::whereBetween('tanggal', [$dari, $sampai])
            ->where('jenis', DanaTalangan::JENIS_KELUAR)->sum('jumlah');

        return [
            'baris' => $perHalaman ? $dasar->paginate($perHalaman)->withQueryString() : $dasar->get(),
            'ringkasan' => [
                ['label' => 'Saldo Awal Dana', 'nilai' => rupiah($saldoAwal)],
                ['label' => 'Kontribusi Masuk', 'nilai' => rupiah($masuk), 'warna' => 'text-brand-600'],
                ['label' => 'Klaim Dibayar', 'nilai' => rupiah($keluar), 'warna' => 'text-danger-600'],
                ['label' => 'Saldo Akhir Dana', 'nilai' => rupiah($saldoAwal + $masuk - $keluar)],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function akhirTahun(int $tahun): array
    {
        $pinjaman = Pinjaman::with('pembayaran')->whereYear('tanggal_transaksi', $tahun)->get();

        $pendapatanBunga = (float) PembayaranPinjaman::whereYear('tanggal', $tahun)->sum('bunga_nominal');
        // Hanya porsi operasional yang jadi pendapatan; porsi talangan adalah dana amanah.
        $pendapatanPotongan = (float) $pinjaman->sum(fn (Pinjaman $item) => round(
            (float) $item->jumlah_pinjaman
                * ((float) $item->potongan_persen - (float) $item->potongan_talangan_persen) / 100,
            2,
        ));
        $kontribusiTalangan = (float) DanaTalangan::whereYear('tanggal', $tahun)
            ->where('jenis', DanaTalangan::JENIS_MASUK)
            ->sum('jumlah');
        $klaimTalangan = (float) DanaTalangan::whereYear('tanggal', $tahun)
            ->where('jenis', DanaTalangan::JENIS_KELUAR)
            ->sum('jumlah');

        // Beban tunai koperasi, dirinci per kategori. Pencairan pinjaman dan
        // setoran ke pos dana talangan bukan beban — keduanya perpindahan aset.
        $beban = TransaksiKas::with('kategori')
            ->whereYear('tanggal', $tahun)
            ->where('jenis', TransaksiKas::JENIS_KELUAR)
            ->whereRelation('kategori', fn ($query) => $query
                ->whereNotIn('nama', [KategoriKas::PENCAIRAN_PINJAMAN, KategoriKas::SETORAN_TALANGAN]))
            ->get()
            ->groupBy(fn (TransaksiKas $item) => $item->kategori->nama)
            ->map(fn ($kelompok) => (float) $kelompok->sum('jumlah'))
            ->sortDesc()
            ->all();

        $pendapatan = $pendapatanBunga + $pendapatanPotongan;
        $pengeluaran = array_sum($beban);

        $saldoKas = TransaksiKas::saldoTotal();
        $saldoTalangan = DanaTalangan::saldoTotal();
        $pinjamanBeredar = Pinjaman::with('pembayaran')
            ->where('status', '!=', Pinjaman::STATUS_LUNAS)
            ->get()
            ->sum(fn (Pinjaman $item) => $item->sisa_pokok);

        return [
            'jenis' => 'akhir_tahun',
            'judul' => self::JUDUL['akhir_tahun'],
            'periode' => 'Tahun Buku '.$tahun,
            'tahun' => $tahun,
            'aset' => [
                'Saldo Kas' => $saldoKas,
                'Saldo Dana Talangan' => $saldoTalangan,
                'Total Pinjaman Beredar' => $pinjamanBeredar,
            ],
            'totalAset' => $saldoKas + $saldoTalangan + $pinjamanBeredar,
            'pendapatan' => [
                'Pendapatan Bunga Pinjaman' => $pendapatanBunga,
                'Pendapatan Potongan Operasional' => $pendapatanPotongan,
            ],
            'pengeluaran' => $beban,
            'totalPendapatan' => $pendapatan,
            'totalPengeluaran' => $pengeluaran,
            'sisaHasilUsaha' => $pendapatan - $pengeluaran,
            'kontribusiTalangan' => $kontribusiTalangan,
            'klaimTalangan' => $klaimTalangan,
            'saldoTalangan' => $saldoTalangan,
        ];
    }
}
