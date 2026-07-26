<x-app-layout title="Detail Pinjaman">
    <x-page-header :judul="$pinjaman->kelompokTani->nama_kelompok"
                   :deskripsi="'Pinjaman ' . $pinjaman->kode"
                   :kembali="route('pinjaman.index')">
        <x-slot:aksi>
            <a href="{{ route('pinjaman.faktur', $pinjaman) }}" class="btn-ghost">
                <x-icon name="nota" class="h-4 w-4" />
                Faktur
            </a>
            @if ($pinjaman->status !== \App\Models\Pinjaman::STATUS_LUNAS)
                <a href="{{ route('pinjaman.bayar', $pinjaman) }}" class="btn-primary">Bayar Pinjaman</a>
            @endif
        </x-slot:aksi>
    </x-page-header>

    <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card label="Pinjaman Pokok" :nilai="rupiah($pinjaman->jumlah_pinjaman)" ikon="bank" />
        <x-stat-card label="Sisa Pokok" :nilai="rupiah($pinjaman->sisa_pokok)" ikon="keluar-uang" warna="merah" />
        <x-stat-card label="Bunga Terbayar" :nilai="rupiah($pinjaman->total_bunga_dibayar)" ikon="persen" warna="kuning" />
        <x-stat-card label="Bunga Berjalan"
                     :nilai="rupiah($pinjaman->bungaBerjalan())" ikon="riwayat" warna="biru">
            <x-slot:catatan>{{ $pinjaman->bulanBungaBerjalan() }} bulan × {{ (float) $pinjaman->bunga_persen }}%</x-slot:catatan>
        </x-stat-card>
    </div>

    <div class="grid gap-6 lg:grid-cols-5">
        <div class="card p-6 lg:col-span-2">
            <div class="flex items-center justify-between gap-3">
                <h2 class="text-xs font-bold uppercase tracking-wider text-brand-700">Detail Pinjaman</h2>
                <x-status-badge :status="$pinjaman->status" />
            </div>

            <dl class="mt-5 divide-y divide-slate-100">
                @foreach ([
                    'ID Pinjaman' => $pinjaman->kode,
                    'Tanggal Transaksi' => tanggal_id($pinjaman->tanggal_transaksi, 'd F Y'),
                    'Tanggal Jatuh Tempo' => tanggal_id($pinjaman->tanggal_jatuh_tempo, 'd F Y'),
                    'Potongan Muka' => (float) $pinjaman->potongan_persen . '% — ' . rupiah($pinjaman->potongan_nominal),
                    'Jumlah Diterima' => rupiah($pinjaman->jumlah_diterima),
                    'Bunga' => (float) $pinjaman->bunga_persen . '% per bulan',
                    'Deskripsi' => $pinjaman->deskripsi ?: '-',
                ] as $label => $nilai)
                    <div class="py-3">
                        <dt class="text-xs text-slate-500">{{ $label }}</dt>
                        <dd class="mt-0.5 text-sm font-medium text-slate-900">{{ $nilai }}</dd>
                    </div>
                @endforeach
            </dl>

            <div class="mt-6 flex flex-wrap justify-end gap-3">
                <form method="POST" action="{{ route('pinjaman.destroy', $pinjaman) }}"
                      onsubmit="return confirm('Hapus pinjaman {{ $pinjaman->kode }}?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn-danger">Hapus</button>
                </form>
                <a href="{{ route('pinjaman.edit', $pinjaman) }}" class="btn-primary">Sunting</a>
            </div>
        </div>

        <div class="card overflow-hidden lg:col-span-3">
            <div class="border-b border-slate-200 p-5">
                <h2 class="text-lg font-semibold text-slate-900">Riwayat Pembayaran</h2>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="table-head">
                        <tr>
                            <th class="px-5 py-3.5 text-left">Tanggal</th>
                            <th class="px-5 py-3.5 text-left">ID</th>
                            <th class="px-5 py-3.5 text-right">Bunga</th>
                            <th class="px-5 py-3.5 text-right">Pokok</th>
                            <th class="px-5 py-3.5 text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($pinjaman->pembayaran as $bayar)
                            <tr class="cursor-pointer hover:bg-slate-50/60"
                                onclick="window.location='{{ route('pembayaran.faktur', $bayar) }}'">
                                <td class="whitespace-nowrap px-5 py-4 text-slate-600">{{ tanggal_id($bayar->tanggal) }}</td>
                                <td class="whitespace-nowrap px-5 py-4 font-medium text-brand-700">{{ $bayar->kode }}</td>
                                <td class="whitespace-nowrap px-5 py-4 text-right text-slate-600">
                                    {{ rupiah($bayar->bunga_nominal) }}
                                    <span class="block text-xs text-slate-400">{{ $bayar->bulan_bunga }} bulan</span>
                                </td>
                                <td class="whitespace-nowrap px-5 py-4 text-right text-slate-600">{{ rupiah($bayar->bayar_pokok) }}</td>
                                <td class="whitespace-nowrap px-5 py-4 text-right font-bold text-brand-600">{{ rupiah($bayar->total_bayar) }}</td>
                            </tr>
                        @empty
                            <x-empty-state kolom="5" pesan="Kelompok ini belum pernah membayar." />
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
