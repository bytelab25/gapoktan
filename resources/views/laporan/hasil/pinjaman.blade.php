@php $padat = $cetak ? 'px-3 py-3' : 'px-6 py-4'; @endphp

<div class="overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="table-head {{ $cetak ? 'border-y border-slate-200' : '' }}">
            <tr>
                <th class="{{ $cetak ? 'px-3 py-3' : 'px-6 py-3.5' }} text-left">Tanggal</th>
                <th class="{{ $cetak ? 'px-3 py-3' : 'px-6 py-3.5' }} text-left">ID Pinjaman</th>
                <th class="{{ $cetak ? 'px-3 py-3' : 'px-6 py-3.5' }} text-left">Nama Kelompok</th>
                <th class="{{ $cetak ? 'px-3 py-3' : 'px-6 py-3.5' }} text-right">Pinjaman Pokok</th>
                <th class="{{ $cetak ? 'px-3 py-3' : 'px-6 py-3.5' }} text-right">Potongan</th>
                <th class="{{ $cetak ? 'px-3 py-3' : 'px-6 py-3.5' }} text-right">Sisa Pokok</th>
                <th class="{{ $cetak ? 'px-3 py-3' : 'px-6 py-3.5' }} text-left">Status</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($hasil['baris'] as $pinjaman)
                <tr @class(['hover:bg-slate-50/60' => ! $cetak])>
                    <td class="whitespace-nowrap {{ $padat }} text-slate-600">{{ tanggal_id($pinjaman->tanggal_transaksi) }}</td>
                    <td class="whitespace-nowrap {{ $padat }} font-medium text-slate-700">{{ $pinjaman->kode }}</td>
                    <td class="{{ $padat }}">
                        <p class="font-semibold text-slate-900">{{ $pinjaman->kelompokTani->nama_kelompok }}</p>
                        <p class="text-xs text-slate-500">
                            Jatuh tempo {{ tanggal_id($pinjaman->tanggal_jatuh_tempo) }} · termin {{ $pinjaman->termin }}
                        </p>
                    </td>
                    <td class="whitespace-nowrap {{ $padat }} text-right text-slate-700">{{ rupiah($pinjaman->jumlah_pinjaman) }}</td>
                    <td class="whitespace-nowrap {{ $padat }} text-right text-slate-600">
                        {{ rupiah($pinjaman->potongan_nominal) }}
                        <span class="block text-xs text-slate-400">{{ (float) $pinjaman->potongan_persen }}%</span>
                    </td>
                    <td class="whitespace-nowrap {{ $padat }} text-right font-bold text-slate-900">{{ rupiah($pinjaman->sisa_pokok) }}</td>
                    <td class="{{ $padat }}"><x-status-badge :status="$pinjaman->status" /></td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-6 py-12 text-center text-sm text-slate-400">
                        Tidak ada pinjaman yang dicairkan pada periode ini.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if (! $cetak && $hasil['baris'] instanceof \Illuminate\Contracts\Pagination\Paginator)
    <x-tabel-kaki :paginator="$hasil['baris']" />
@endif
