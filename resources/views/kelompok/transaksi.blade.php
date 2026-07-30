<x-app-layout title="Transaksi Kelompok Tani">
    <x-page-header judul="Informasi Kelompok Tani"
                   :deskripsi="$kelompok->nama_kelompok"
                   :kembali="route('kelompok.index')">
        <x-slot:aksi>
            @can('kelola')
                <a href="{{ route('pembayaran.create', $kelompok) }}" class="btn-ghost">
                    <x-icon name="kas" class="h-4 w-4" />
                    Bayar Pinjaman
                </a>
                <a href="{{ route('pinjaman.create', ['kelompok' => $kelompok->id]) }}" class="btn-primary">
                    <x-icon name="plus" class="h-4 w-4" />
                    Buat Pinjaman
                </a>
            @endcan
        </x-slot:aksi>
    </x-page-header>

    @include('kelompok._tabs', ['kelompok' => $kelompok, 'aktif' => 'transaksi'])

    <div class="mb-6 grid gap-4 sm:grid-cols-2">
        <x-stat-card label="Total Pinjaman Kelompok Tani" :nilai="rupiah($totalPinjaman)" ikon="bank" />
        <x-stat-card label="Total Bunga Pinjaman Dibayar" :nilai="rupiah($totalBunga)" ikon="persen" warna="kuning" />
    </div>

    <div class="card overflow-hidden">
        <div class="border-b border-slate-200 p-5">
            <h2 class="text-lg font-semibold text-slate-900">Data Pinjaman {{ $kelompok->nama_kelompok }}</h2>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="table-head">
                    <tr>
                        <th class="px-6 py-3.5 text-left">Tanggal Transaksi</th>
                        <th class="px-6 py-3.5 text-left">ID</th>
                        <th class="px-6 py-3.5 text-left">Tanggal Jatuh Tempo</th>
                        <th class="px-6 py-3.5 text-left">Deskripsi</th>
                        <th class="px-6 py-3.5 text-right">Jumlah Pinjaman</th>
                        <th class="px-6 py-3.5 text-left">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($daftarPinjaman as $pinjaman)
                        <tr class="cursor-pointer hover:bg-slate-50/60"
                            onclick="window.location='{{ route('pinjaman.show', $pinjaman) }}'">
                            <td class="whitespace-nowrap px-6 py-4 text-slate-600">{{ tanggal_id($pinjaman->tanggal_transaksi) }}</td>
                            <td class="whitespace-nowrap px-6 py-4 font-medium text-brand-700">{{ $pinjaman->kode }}</td>
                            <td class="whitespace-nowrap px-6 py-4 text-slate-600">{{ tanggal_id($pinjaman->tanggal_jatuh_tempo) }}</td>
                            <td class="max-w-xs px-6 py-4 text-slate-500">{{ Str::limit($pinjaman->deskripsi, 70) ?: '-' }}</td>
                            <td class="whitespace-nowrap px-6 py-4 text-right font-semibold text-slate-900">
                                {{ rupiah($pinjaman->jumlah_pinjaman) }}
                            </td>
                            <td class="px-6 py-4"><x-status-badge :status="$pinjaman->status" /></td>
                        </tr>
                    @empty
                        <x-empty-state kolom="6" pesan="Kelompok ini belum punya pinjaman." />
                    @endforelse
                </tbody>
            </table>
        </div>

        <x-tabel-kaki :paginator="$daftarPinjaman" />
    </div>
</x-app-layout>
