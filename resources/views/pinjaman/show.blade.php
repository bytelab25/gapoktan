<x-app-layout title="Detail Pinjaman">
    <x-page-header :judul="$pinjaman->kelompokTani->nama_kelompok"
                   :deskripsi="'Pinjaman ' . $pinjaman->kode"
                   :kembali="route('pinjaman.kelompok', $pinjaman->kelompok_tani_id)">
        <x-slot:aksi>
            <a href="{{ route('pinjaman.faktur', $pinjaman) }}" class="btn-ghost">
                <x-icon name="nota" class="h-4 w-4" />
                Faktur
            </a>
            @if ($pinjaman->status !== \App\Models\Pinjaman::STATUS_LUNAS)
                @can('kelola')
                    <a href="{{ route('pembayaran.create', $pinjaman->kelompok_tani_id) }}" class="btn-primary">
                        Bayar Pinjaman
                    </a>
                @endcan
            @endif
        </x-slot:aksi>
    </x-page-header>

    <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card label="Pinjaman Pokok" :nilai="rupiah($pinjaman->jumlah_pinjaman)" ikon="bank" />
        <x-stat-card label="Sisa Pokok" :nilai="rupiah($pinjaman->sisa_pokok)" ikon="keluar-uang" warna="merah" />
        <x-stat-card label="Bunga Terbayar" :nilai="rupiah($pinjaman->total_bunga_dibayar)" ikon="persen" warna="kuning" />
        <x-stat-card label="Bunga Terhutang"
                     :nilai="rupiah($bungaTerhutang)" ikon="riwayat" warna="biru">
            <x-slot:catatan>
                {{ $bulanBerjalan }} bulan × {{ (float) $pinjaman->bunga_persen }}%
                @if ((float) $pinjaman->tunggakan_bunga > 0)
                    + tunggakan {{ rupiah($pinjaman->tunggakan_bunga) }}
                @endif
            </x-slot:catatan>
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
                    'Tanggal Jatuh Tempo' => tanggal_id($pinjaman->tanggal_jatuh_tempo, 'd F Y')
                        . ' (termin ' . $pinjaman->termin . ' — bulan ke-' . $pinjaman->bulan_termin . ')',
                    'Potongan Muka' => (float) $pinjaman->potongan_persen . '% — ' . rupiah($pinjaman->potongan_nominal),
                    'Jumlah Diterima' => rupiah($pinjaman->jumlah_diterima),
                    'Kontribusi Dana Talangan' => (float) $pinjaman->potongan_talangan_persen . '% — '
                        . rupiah($pinjaman->danaTalangan->sum('jumlah')),
                    'Bunga' => (float) $pinjaman->bunga_persen . '% per bulan, diakumulasi per '
                        . \App\Models\Pinjaman::BULAN_PER_TERMIN . ' bulan',
                    'Deskripsi' => $pinjaman->deskripsi ?: '-',
                ] as $label => $nilai)
                    <div class="py-3">
                        <dt class="text-xs text-slate-500">{{ $label }}</dt>
                        <dd class="mt-0.5 text-sm font-medium text-slate-900">{{ $nilai }}</dd>
                    </div>
                @endforeach
            </dl>

            @can('kelola')
                @if ($pinjaman->bisaDiubah())
                    <div class="mt-6 flex flex-wrap justify-end gap-3">
                        <x-tombol-hapus :aksi="route('pinjaman.destroy', $pinjaman)"
                                        :pesan="'Pinjaman ' . $pinjaman->kode . ' akan dihapus beserta baris kas dan kontribusi dana talangannya.'" />
                        <a href="{{ route('pinjaman.edit', $pinjaman) }}" class="btn-primary">Sunting</a>
                    </div>
                @else
                    <div class="mt-6 flex items-start gap-3 rounded-lg bg-slate-50 p-4 text-sm text-slate-600">
                        <x-icon name="peringatan" class="mt-0.5 h-5 w-5 shrink-0 text-slate-400" />
                        <span>
                            Pinjaman sudah punya transaksi pembayaran, jadi tidak bisa disunting atau dihapus.
                            Kelola setorannya di
                            <a href="{{ route('pinjaman.kelompok', $pinjaman->kelompok_tani_id) }}" class="font-semibold text-brand-700 underline">riwayat transaksi kelompok</a>.
                        </span>
                    </div>
                @endif
            @endcan
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
