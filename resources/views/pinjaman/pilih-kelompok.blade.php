<x-app-layout title="Bayar Pinjaman">
    <x-page-header judul="Bayar Pinjaman"
                   deskripsi="Pilih kelompok tani yang menyetor. Setoran dialokasikan ke pinjaman tertua lebih dulu."
                   :kembali="route('pinjaman.index')" />

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="table-head">
                    <tr>
                        <th class="px-6 py-3.5 text-left">Kelompok Tani</th>
                        <th class="px-6 py-3.5 text-left">Ketua</th>
                        <th class="px-6 py-3.5 text-center">Pinjaman Aktif</th>
                        <th class="px-6 py-3.5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($daftarKelompok as $kelompok)
                        <tr class="hover:bg-slate-50/60">
                            <td class="px-6 py-4">
                                <p class="font-semibold text-slate-900">{{ $kelompok->nama_kelompok }}</p>
                                <p class="text-xs text-slate-500">{{ $kelompok->kode }}</p>
                            </td>
                            <td class="px-6 py-4 text-slate-600">{{ $kelompok->nama_ketua }}</td>
                            <td class="px-6 py-4 text-center">
                                @if ($kelompok->pinjaman_aktif_count > 0)
                                    <span class="badge bg-warn-50 text-warn-700">
                                        {{ $kelompok->pinjaman_aktif_count }} pinjaman
                                    </span>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right">
                                @if ($kelompok->pinjaman_aktif_count > 0)
                                    <a href="{{ route('pembayaran.create', $kelompok) }}"
                                       class="font-semibold text-brand-700 hover:underline">Buat Setoran</a>
                                @else
                                    <span class="text-slate-400">Tidak ada tagihan</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <x-empty-state kolom="4" pesan="Belum ada kelompok tani terdaftar." />
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
