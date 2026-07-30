@php $padat = $cetak ? 'px-3 py-3' : 'px-6 py-4'; @endphp

<div class="overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="table-head {{ $cetak ? 'border-y border-slate-200' : '' }}">
            <tr>
                <th class="{{ $cetak ? 'px-3 py-3' : 'px-6 py-3.5' }} text-left">Tanggal</th>
                <th class="{{ $cetak ? 'px-3 py-3' : 'px-6 py-3.5' }} text-left">ID Transaksi</th>
                <th class="{{ $cetak ? 'px-3 py-3' : 'px-6 py-3.5' }} text-left">Nama Kelompok</th>
                <th class="{{ $cetak ? 'px-3 py-3' : 'px-6 py-3.5' }} text-left">Penerima / Sumber</th>
                <th class="{{ $cetak ? 'px-3 py-3' : 'px-6 py-3.5' }} text-right">Jumlah</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($hasil['baris'] as $transaksi)
                <tr @class(['hover:bg-slate-50/60' => ! $cetak])>
                    <td class="whitespace-nowrap {{ $padat }} text-slate-600">{{ tanggal_id($transaksi->tanggal) }}</td>
                    <td class="whitespace-nowrap {{ $padat }} font-medium text-slate-700">{{ $transaksi->kode }}</td>
                    <td class="{{ $padat }} font-semibold text-slate-900">
                        {{ $transaksi->kelompokTani?->nama_kelompok ?? '-' }}
                    </td>
                    <td class="{{ $padat }}">
                        @if ($transaksi->jenis === \App\Models\DanaTalangan::JENIS_KELUAR)
                            <p class="text-slate-900">{{ $transaksi->penerima ?: '-' }}</p>
                            <p class="text-xs text-slate-500">Klaim santunan</p>
                        @else
                            <p class="text-slate-900">{{ $transaksi->pinjaman?->kode ?? 'Setoran langsung' }}</p>
                            <p class="text-xs text-slate-500">Kontribusi masuk</p>
                        @endif
                    </td>
                    <td class="whitespace-nowrap {{ $padat }} text-right font-bold {{ $transaksi->jenis === 'masuk' ? 'text-brand-600' : 'text-danger-600' }}">
                        {{ rupiah_bertanda($transaksi->nilai) }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-6 py-12 text-center text-sm text-slate-400">
                        Tidak ada transaksi dana talangan pada periode ini.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if (! $cetak && $hasil['baris'] instanceof \Illuminate\Contracts\Pagination\Paginator)
    <x-tabel-kaki :paginator="$hasil['baris']" />
@endif
