@php $saldoBerjalan = $saldoAwal; @endphp

<x-laporan-layout :title="$judul">
    <x-laporan-kop :judul="$judul"
                   :periode="'Periode ' . tanggal_id($dari, 'd F Y') . ' s.d. ' . tanggal_id($sampai, 'd F Y')" />

    <div class="mb-6 grid gap-4 sm:grid-cols-4">
        @foreach ([
            ['Saldo Awal', rupiah($saldoAwal), 'text-slate-900'],
            ['Total Masuk', rupiah($totalMasuk), 'text-brand-600'],
            ['Total Keluar', rupiah($totalKeluar), 'text-danger-600'],
            ['Saldo Akhir', rupiah($saldoAwal + $totalMasuk - $totalKeluar), 'text-slate-900'],
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
                    <th class="px-3 py-3 text-left">Nama Poktan / Deskripsi</th>
                    <th class="px-3 py-3 text-left">Kategori</th>
                    <th class="px-3 py-3 text-right">Jumlah</th>
                    <th class="px-3 py-3 text-right">Saldo</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($daftarTransaksi as $transaksi)
                    @php $saldoBerjalan += $transaksi->nilai; @endphp
                    <tr>
                        <td class="whitespace-nowrap px-3 py-3 text-slate-600">{{ tanggal_id($transaksi->tanggal) }}</td>
                        <td class="whitespace-nowrap px-3 py-3 text-slate-700">{{ $transaksi->kode }}</td>
                        <td class="px-3 py-3">
                            <p class="font-medium text-slate-900">{{ $transaksi->kelompokTani?->nama_kelompok ?? 'Umum' }}</p>
                            <p class="text-xs text-slate-500">{{ $transaksi->deskripsi ?: '-' }}</p>
                        </td>
                        <td class="px-3 py-3 text-slate-600">{{ $transaksi->kategori->nama }}</td>
                        <td class="whitespace-nowrap px-3 py-3 text-right {{ $transaksi->jenis === 'masuk' ? 'text-slate-900' : 'text-danger-600' }}">
                            {{ rupiah_bertanda($transaksi->nilai) }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-3 text-right font-semibold text-slate-900">
                            {{ rupiah($saldoBerjalan) }}
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
                        <td colspan="4" class="px-3 py-3 text-right font-semibold text-slate-700">Saldo Akhir Periode</td>
                        <td colspan="2" class="px-3 py-3 text-right text-base font-bold text-brand-700">
                            {{ rupiah($saldoBerjalan) }}
                        </td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
</x-laporan-layout>
