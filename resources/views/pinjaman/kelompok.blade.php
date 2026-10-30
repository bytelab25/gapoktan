<x-app-layout :title="'Pinjaman ' . $kelompok->nama_kelompok">
    <x-page-header :judul="$kelompok->nama_kelompok"
                   :deskripsi="$kelompok->kode . ' — Ketua ' . $kelompok->nama_ketua"
                   :kembali="route('pinjaman.index')">
        <x-slot:aksi>
            @can('kelola')
                <div class="relative" x-data="{ buka: false }" x-on:click.outside="buka = false">
                    <button type="button" x-on:click="buka = ! buka" class="btn-primary">
                        <x-icon name="plus" class="h-4 w-4" />
                        Buat Transaksi
                        <x-icon name="chevron-bawah" class="h-4 w-4" />
                    </button>
                    <div x-show="buka" x-cloak x-transition.origin.top.right
                         class="absolute right-0 z-10 mt-2 w-52 overflow-hidden rounded-xl border border-slate-200 bg-white py-1.5 shadow-pop">
                        @if ($kelompok->bisaMengajukanPinjaman())
                            <a href="{{ route('pinjaman.create', ['kelompok' => $kelompok->id]) }}"
                               class="block px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50">Buat Pinjaman</a>
                        @else
                            <span class="block px-4 py-2.5 text-sm text-slate-400"
                                  title="{{ $kelompok->pesanTidakBisaMeminjam() }}">Buat Pinjaman (ada pinjaman belum bayar)</span>
                        @endif
                        @if ($kelompok->status_pinjaman !== \App\Models\Pinjaman::STATUS_LUNAS)
                            <a href="{{ route('pembayaran.create', $kelompok) }}"
                               class="block px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50">Bayar Pinjaman</a>
                        @endif
                    </div>
                </div>
            @endcan
        </x-slot:aksi>
    </x-page-header>

    <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card label="Total Pinjaman Pokok" :nilai="rupiah($totalPinjaman)" ikon="bank" />
        <x-stat-card label="Sisa Pokok Belum Terbayar" :nilai="rupiah($totalSisaPokok)" ikon="keluar-uang" warna="merah" />
        <x-stat-card label="Bunga Belum Terbayar" :nilai="rupiah($totalBunga)" ikon="persen" warna="kuning" />
        <x-stat-card label="Total Setoran" :nilai="rupiah($totalSetoran)" ikon="kas" warna="biru">
            <x-slot:catatan>
                Status: {{ \App\Models\Pinjaman::LABEL_STATUS[$kelompok->status_pinjaman] }}
            </x-slot:catatan>
        </x-stat-card>
    </div>

    <div class="card overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 p-5">
            <h2 class="text-lg font-semibold text-slate-900">Daftar Transaksi</h2>

            <form method="GET">
                <select name="jenis" onchange="this.form.submit()" class="input-field w-48 py-2 text-sm">
                    <option value="">Semua Transaksi</option>
                    <option value="pinjaman" @selected($jenis === 'pinjaman')>Pinjaman</option>
                    <option value="setoran" @selected($jenis === 'setoran')>Pembayaran</option>
                </select>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="table-head">
                    <tr>
                        <th class="px-5 py-3.5 text-left">Tanggal</th>
                        <th class="px-5 py-3.5 text-left">ID</th>
                        <th class="px-5 py-3.5 text-left">Jenis</th>
                        <th class="px-5 py-3.5 text-left">Keterangan</th>
                        <th class="px-5 py-3.5 text-right">Jumlah</th>
                        <th class="px-5 py-3.5 text-left">Status</th>
                        <th class="px-5 py-3.5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($daftarTransaksi as $baris)
                        @if ($baris['jenis'] === 'pinjaman')
                            @php($pinjaman = $baris['pinjaman'])
                            <tr @class(['hover:bg-slate-50/60', 'bg-danger-50/50' => $pinjaman->lewatJatuhTempo()])>
                                <td class="whitespace-nowrap px-5 py-4 text-slate-600">{{ tanggal_id($baris['tanggal']) }}</td>
                                <td class="whitespace-nowrap px-5 py-4">
                                    <a href="{{ route('pinjaman.show', $pinjaman) }}" class="font-medium text-brand-700 hover:underline">{{ $baris['kode'] }}</a>
                                </td>
                                <td class="px-5 py-4"><span class="badge bg-sky-50 text-sky-700">Pinjaman</span></td>
                                <td class="max-w-xs px-5 py-4 text-slate-600">
                                    {{ Str::limit($baris['deskripsi'], 60) ?: 'Pencairan pinjaman' }}
                                    <span class="block text-xs text-slate-400">
                                        Sisa pokok {{ rupiah($pinjaman->sisa_pokok) }} · jatuh tempo
                                        <x-jatuh-tempo :pinjaman="$pinjaman" sebaris class="text-xs" />
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-5 py-4 text-right font-semibold text-slate-900">{{ rupiah($baris['jumlah']) }}</td>
                                <td class="px-5 py-4"><x-status-badge :status="$pinjaman->status" /></td>
                                <td class="whitespace-nowrap px-5 py-4">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('pinjaman.show', $pinjaman) }}" class="btn-ghost btn-sm">Detail</a>
                                        @can('kelola')
                                            @if ($pinjaman->bisaDiubah())
                                                <a href="{{ route('pinjaman.edit', $pinjaman) }}" class="btn-ghost btn-sm" aria-label="Sunting {{ $baris['kode'] }}">
                                                    <x-icon name="sunting" class="h-4 w-4" />
                                                </a>
                                                <x-tombol-hapus :aksi="route('pinjaman.destroy', $pinjaman)" label=""
                                                                class="btn-danger btn-sm" aria-label="Hapus {{ $baris['kode'] }}"
                                                                :pesan="'Pinjaman ' . $baris['kode'] . ' akan dihapus beserta baris kas dan kontribusi dana talangannya.'" />
                                            @else
                                                <span class="chip-mati" title="Sudah ada transaksi pembayaran">Terkunci</span>
                                            @endif
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @else
                            <tr class="hover:bg-slate-50/60">
                                <td class="whitespace-nowrap px-5 py-4 text-slate-600">{{ tanggal_id($baris['tanggal']) }}</td>
                                <td class="whitespace-nowrap px-5 py-4 font-medium text-brand-700">{{ $baris['kode'] }}</td>
                                <td class="px-5 py-4">
                                    <span class="badge bg-brand-50 text-brand-700">Pembayaran</span>
                                    @if ($baris['baris']->first()->hanya_bunga)
                                        <span class="mt-1 block text-xs font-medium text-warn-700">Bunga saja</span>
                                    @endif
                                </td>
                                <td class="max-w-xs px-5 py-4 text-slate-600">
                                    {{ Str::limit($baris['deskripsi'], 60) ?: 'Setoran pembayaran' }}
                                    <span class="block text-xs text-slate-400">
                                        Bunga {{ rupiah($baris['bunga']) }} · pokok {{ rupiah($baris['pokok']) }}
                                        — {{ $baris['baris']->map(fn ($b) => $b->pinjaman->kode)->unique()->implode(', ') }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-5 py-4 text-right font-bold text-brand-600">{{ rupiah($baris['jumlah']) }}</td>
                                <td class="px-5 py-4 text-slate-400">—</td>
                                <td class="whitespace-nowrap px-5 py-4">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('pembayaran.faktur', $baris['baris']->first()) }}" class="btn-ghost btn-sm">
                                            <x-icon name="nota" class="h-4 w-4" />
                                            Faktur
                                        </a>
                                        @can('kelola')
                                            <a href="{{ route('pembayaran.edit', $baris['baris']->first()) }}" class="btn-ghost btn-sm" aria-label="Sunting {{ $baris['kode'] }}">
                                                <x-icon name="sunting" class="h-4 w-4" />
                                            </a>
                                            <x-tombol-hapus :aksi="route('pembayaran.destroy', $baris['baris']->first())" label=""
                                                            class="btn-danger btn-sm" aria-label="Hapus {{ $baris['kode'] }}"
                                                            :pesan="'Setoran ' . $baris['kode'] . ' dan transaksi kasnya akan dihapus. Bunga dan sisa pokok kelompok dihitung ulang.'" />
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <x-empty-state kolom="7" pesan="Belum ada transaksi untuk filter ini." />
                    @endforelse
                </tbody>
            </table>
        </div>

        <p class="border-t border-slate-200 px-6 py-4 text-sm text-slate-500">
            Menampilkan {{ $daftarTransaksi->count() }} transaksi
        </p>
    </div>
</x-app-layout>
