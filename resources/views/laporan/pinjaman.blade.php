<x-laporan-layout :title="$judul">
    <x-laporan-kop :judul="$judul"
                   :periode="'Periode ' . tanggal_id($dari, 'd F Y') . ' s.d. ' . tanggal_id($sampai, 'd F Y')" />

    <div class="mb-6 grid gap-4 sm:grid-cols-3">
        @foreach ([
            ['Total Pinjaman Pokok', rupiah($totalPokok)],
            ['Total Potongan Muka', rupiah($totalPotongan)],
            ['Total Sisa Pokok', rupiah($totalSisa)],
        ] as [$label, $nilai])
            <div class="rounded-lg bg-slate-50 p-4">
                <p class="text-xs text-slate-500">{{ $label }}</p>
                <p class="mt-1 text-base font-bold text-slate-900">{{ $nilai }}</p>
            </div>
        @endforeach
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="table-head border-y border-slate-200">
                <tr>
                    <th class="px-3 py-3 text-left">Tanggal</th>
                    <th class="px-3 py-3 text-left">ID Pinjaman</th>
                    <th class="px-3 py-3 text-left">Kelompok Tani</th>
                    <th class="px-3 py-3 text-left">Jatuh Tempo</th>
                    <th class="px-3 py-3 text-right">Pokok</th>
                    <th class="px-3 py-3 text-right">Potongan</th>
                    <th class="px-3 py-3 text-right">Sisa Pokok</th>
                    <th class="px-3 py-3 text-left">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($daftarPinjaman as $pinjaman)
                    <tr>
                        <td class="whitespace-nowrap px-3 py-3 text-slate-600">{{ tanggal_id($pinjaman->tanggal_transaksi) }}</td>
                        <td class="whitespace-nowrap px-3 py-3 text-slate-700">{{ $pinjaman->kode }}</td>
                        <td class="px-3 py-3 font-medium text-slate-900">{{ $pinjaman->kelompokTani->nama_kelompok }}</td>
                        <td class="whitespace-nowrap px-3 py-3 text-slate-600">{{ tanggal_id($pinjaman->tanggal_jatuh_tempo) }}</td>
                        <td class="whitespace-nowrap px-3 py-3 text-right text-slate-900">{{ rupiah($pinjaman->jumlah_pinjaman) }}</td>
                        <td class="whitespace-nowrap px-3 py-3 text-right text-slate-600">{{ rupiah($pinjaman->potongan_nominal) }}</td>
                        <td class="whitespace-nowrap px-3 py-3 text-right font-semibold text-slate-900">{{ rupiah($pinjaman->sisa_pokok) }}</td>
                        <td class="px-3 py-3"><x-status-badge :status="$pinjaman->status" /></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-3 py-12 text-center text-sm text-slate-400">
                            Tidak ada pinjaman pada periode ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
            @if ($daftarPinjaman->isNotEmpty())
                <tfoot class="border-t-2 border-slate-900">
                    <tr class="font-bold text-slate-900">
                        <td colspan="4" class="px-3 py-3 text-right">Total</td>
                        <td class="px-3 py-3 text-right">{{ rupiah($totalPokok) }}</td>
                        <td class="px-3 py-3 text-right">{{ rupiah($totalPotongan) }}</td>
                        <td class="px-3 py-3 text-right text-brand-700">{{ rupiah($totalSisa) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
</x-laporan-layout>
