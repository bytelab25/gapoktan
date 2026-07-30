

<div class="overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="table-head">
            <tr>
                <th class="px-6 py-3.5 text-left">Tanggal</th>
                <th class="px-6 py-3.5 text-left">ID Transaksi</th>
                <th class="px-6 py-3.5 text-left">Nama Poktan / Deskripsi</th>
                <th class="px-6 py-3.5 text-left">Kategori</th>
                <th class="px-6 py-3.5 text-right">Jumlah</th>
                <th class="px-6 py-3.5 text-right">Saldo</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($hasil['baris'] as $transaksi)
                <tr class="hover:bg-slate-50/60">
                    <td class="whitespace-nowrap px-6 py-4 text-slate-600">{{ tanggal_id($transaksi->tanggal) }}</td>
                    <td class="whitespace-nowrap px-6 py-4 font-medium text-slate-700">{{ $transaksi->kode }}</td>
                    <td class="max-w-sm px-6 py-4">
                        <p class="font-semibold text-slate-900">{{ $transaksi->kelompokTani?->nama_kelompok ?? 'Umum' }}</p>
                        <p class="text-xs text-slate-500">{{ $transaksi->deskripsi ?: '-' }}</p>
                    </td>
                    <td class="px-6 py-4">
                        <span class="badge {{ $transaksi->jenis === 'masuk' ? 'bg-brand-50 text-brand-700' : 'bg-slate-100 text-slate-600' }}">
                            {{ $transaksi->kategori->nama }}
                        </span>
                    </td>
                    <td class="whitespace-nowrap px-6 py-4 text-right font-medium {{ $transaksi->jenis === 'masuk' ? 'text-slate-900' : 'text-danger-600' }}">
                        {{ rupiah_bertanda($transaksi->nilai) }}
                    </td>
                    <td class="whitespace-nowrap px-6 py-4 text-right font-bold {{ $transaksi->jenis === 'masuk' ? 'text-brand-600' : 'text-slate-900' }}">
                        {{ rupiah(\App\Models\TransaksiKas::saldoSampai($transaksi)) }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center text-sm text-slate-400">
                        Tidak ada transaksi kas pada periode ini.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if ($hasil['baris'] instanceof \Illuminate\Contracts\Pagination\Paginator)
    <x-tabel-kaki :paginator="$hasil['baris']" />
@endif
