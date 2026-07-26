<?php

namespace App\Http\Controllers;

use App\Models\DanaTalangan;
use App\Models\PembayaranPinjaman;
use App\Models\Pinjaman;
use App\Models\TransaksiKas;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class LaporanController extends Controller
{
    public const JENIS = [
        'kas' => 'Laporan Kas',
        'pinjaman' => 'Laporan Pinjaman',
        'dana_talangan' => 'Laporan Dana Talangan',
        'akhir_tahun' => 'Laporan Akhir Tahun',
    ];

    public function index(): View
    {
        return view('laporan.index', [
            'daftarJenis' => self::JENIS,
            'daftarTahun' => range(now()->year, now()->year - 5),
        ]);
    }

    public function hasil(Request $request): View
    {
        $data = $request->validate([
            'jenis' => ['required', 'in:'.implode(',', array_keys(self::JENIS))],
            'dari' => ['required_unless:jenis,akhir_tahun', 'nullable', 'date'],
            'sampai' => ['required_unless:jenis,akhir_tahun', 'nullable', 'date', 'after_or_equal:dari'],
            'tahun' => ['required_if:jenis,akhir_tahun', 'nullable', 'integer', 'min:2000', 'max:2100'],
        ], attributes: [
            'dari' => 'tanggal mulai',
            'sampai' => 'tanggal akhir',
        ]);

        if ($data['jenis'] === 'akhir_tahun') {
            return $this->akhirTahun((int) $data['tahun']);
        }

        $dari = Carbon::parse($data['dari'])->startOfDay();
        $sampai = Carbon::parse($data['sampai'])->endOfDay();

        return match ($data['jenis']) {
            'kas' => $this->laporanKas($dari, $sampai),
            'pinjaman' => $this->laporanPinjaman($dari, $sampai),
            'dana_talangan' => $this->laporanDanaTalangan($dari, $sampai),
        };
    }

    private function laporanKas(Carbon $dari, Carbon $sampai): View
    {
        $transaksi = TransaksiKas::with(['kategori', 'kelompokTani'])
            ->whereBetween('tanggal', [$dari, $sampai])
            ->orderBy('tanggal')
            ->orderBy('id')
            ->get();

        // Saldo awal periode dipakai sebagai titik mulai kolom saldo berjalan.
        $saldoAwal = (float) TransaksiKas::whereDate('tanggal', '<', $dari)
            ->selectRaw("COALESCE(SUM(CASE WHEN jenis = 'masuk' THEN jumlah ELSE -jumlah END), 0) AS saldo")
            ->value('saldo');

        return view('laporan.kas', [
            'judul' => self::JENIS['kas'],
            'dari' => $dari,
            'sampai' => $sampai,
            'daftarTransaksi' => $transaksi,
            'saldoAwal' => $saldoAwal,
            'totalMasuk' => $transaksi->where('jenis', TransaksiKas::JENIS_MASUK)->sum('jumlah'),
            'totalKeluar' => $transaksi->where('jenis', TransaksiKas::JENIS_KELUAR)->sum('jumlah'),
        ]);
    }

    private function laporanPinjaman(Carbon $dari, Carbon $sampai): View
    {
        $pinjaman = Pinjaman::with(['kelompokTani', 'pembayaran'])
            ->whereBetween('tanggal_transaksi', [$dari, $sampai])
            ->orderBy('tanggal_transaksi')
            ->get();

        return view('laporan.pinjaman', [
            'judul' => self::JENIS['pinjaman'],
            'dari' => $dari,
            'sampai' => $sampai,
            'daftarPinjaman' => $pinjaman,
            'totalPokok' => $pinjaman->sum('jumlah_pinjaman'),
            'totalPotongan' => $pinjaman->sum('potongan_nominal'),
            'totalSisa' => $pinjaman->sum(fn (Pinjaman $item) => $item->sisa_pokok),
        ]);
    }

    private function laporanDanaTalangan(Carbon $dari, Carbon $sampai): View
    {
        $transaksi = DanaTalangan::with('kelompokTani')
            ->whereBetween('tanggal', [$dari, $sampai])
            ->orderBy('tanggal')
            ->get();

        return view('laporan.dana-talangan', [
            'judul' => self::JENIS['dana_talangan'],
            'dari' => $dari,
            'sampai' => $sampai,
            'daftarTransaksi' => $transaksi,
            'totalMasuk' => $transaksi->where('jenis', DanaTalangan::JENIS_MASUK)->sum('jumlah'),
            'totalKeluar' => $transaksi->where('jenis', DanaTalangan::JENIS_KELUAR)->sum('jumlah'),
        ]);
    }

    private function akhirTahun(int $tahun): View
    {
        $pinjaman = Pinjaman::with('pembayaran')->whereYear('tanggal_transaksi', $tahun)->get();

        $pendapatanBunga = (float) PembayaranPinjaman::whereYear('tanggal', $tahun)->sum('bunga_nominal');
        $pendapatanPotongan = (float) $pinjaman->sum('potongan_nominal');
        $kontribusiTalangan = (float) DanaTalangan::whereYear('tanggal', $tahun)
            ->where('jenis', DanaTalangan::JENIS_MASUK)
            ->sum('jumlah');

        $klaimTalangan = (float) DanaTalangan::whereYear('tanggal', $tahun)
            ->where('jenis', DanaTalangan::JENIS_KELUAR)
            ->sum('jumlah');
        $biayaOperasional = (float) TransaksiKas::whereYear('tanggal', $tahun)->sum('biaya');

        $pendapatan = $pendapatanBunga + $pendapatanPotongan;
        $pengeluaran = $klaimTalangan + $biayaOperasional;

        return view('laporan.akhir-tahun', [
            'judul' => self::JENIS['akhir_tahun'],
            'tahun' => $tahun,
            'saldoKas' => TransaksiKas::saldoTotal(),
            'saldoTalangan' => DanaTalangan::saldoTotal(),
            'totalPinjamanBeredar' => Pinjaman::with('pembayaran')
                ->where('status', '!=', Pinjaman::STATUS_LUNAS)
                ->get()
                ->sum(fn (Pinjaman $item) => $item->sisa_pokok),
            'pendapatanBunga' => $pendapatanBunga,
            'pendapatanPotongan' => $pendapatanPotongan,
            'kontribusiTalangan' => $kontribusiTalangan,
            'klaimTalangan' => $klaimTalangan,
            'biayaOperasional' => $biayaOperasional,
            'totalPendapatan' => $pendapatan,
            'totalPengeluaran' => $pengeluaran,
            'sisaHasilUsaha' => $pendapatan - $pengeluaran,
        ]);
    }
}
