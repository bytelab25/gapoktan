<x-laporan-layout :title="$judul">
    <x-laporan-kop :judul="$judul"
                   :periode="'Periode ' . tanggal_id($dari, 'd F Y') . ' s.d. ' . tanggal_id($sampai, 'd F Y')" />

    <div class="mb-6 grid gap-4 sm:grid-cols-3">
        @foreach ([
            ['Total Kontribusi Masuk', rupiah($totalMasuk), 'text-brand-600'],
            ['Total Klaim Keluar', rupiah($totalKeluar), 'text-danger-600'],
            ['Selisih Periode', rupiah($totalMasuk - $totalKeluar), 'text-slate-900'],
        ] as [$label, $nilai, $warna])
            <div class="rounded-lg bg-slate-50 p-4">
                <p class="text-xs text-slate-500">{{ $label }}</p>
                <p class="mt-1 text-base font-bold {{ $warna }}">{{ $nilai }}</p>
            </div>
        @endforeach
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="table-head border-y border-slate-200">
                <tr>
                    <th class="px-3 py-3 text-left">Tanggal</th>
                    <th class="px-3 py-3 text-left">ID Transaksi</th>
                    <th class="px-3 py-3 text-left">Kelompok Tani</th>
                    <th class="px-3 py-3 text-left">Penerima</th>
                    <th class="px-3 py-3 text-left">Jenis</th>
                    <th class="px-3 py-3 text-right">Jumlah</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($daftarTransaksi as $transaksi)
                    <tr>
                        <td class="whitespace-nowrap px-3 py-3 text-slate-600">{{ tanggal_id($transaksi->tanggal) }}</td>
                        <td class="whitespace-nowrap px-3 py-3 text-slate-700">{{ $transaksi->kode }}</td>
                        <td class="px-3 py-3 font-medium text-slate-900">{{ $transaksi->kelompokTani->nama_kelompok }}</td>
                        <td class="px-3 py-3 text-slate-600">{{ $transaksi->penerima ?: '-' }}</td>
                        <td class="px-3 py-3 text-slate-600">Dana {{ ucfirst($transaksi->jenis) }}</td>
                        <td class="whitespace-nowrap px-3 py-3 text-right font-semibold {{ $transaksi->jenis === 'masuk' ? 'text-slate-900' : 'text-danger-600' }}">
                            {{ rupiah_bertanda($transaksi->nilai) }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-3 py-12 text-center text-sm text-slate-400">
                            Tidak ada transaksi pada periode ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
            @if ($daftarTransaksi->isNotEmpty())
                <tfoot class="border-t-2 border-slate-900">
                    <tr>
                        <td colspan="5" class="px-3 py-3 text-right font-semibold text-slate-700">Selisih Periode</td>
                        <td class="px-3 py-3 text-right text-base font-bold text-brand-700">
                            {{ rupiah($totalMasuk - $totalKeluar) }}
                        </td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
</x-laporan-layout>
