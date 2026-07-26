<x-app-layout title="Kelompok Tani">
    <x-page-header judul="Kelompok Tani"
                   deskripsi="Data kelompok tani binaan Gapoktan Swagotra Arthagiri.">
        <x-slot:aksi>
            <a href="{{ route('kelompok.create') }}" class="btn-primary">
                <x-icon name="plus" class="h-4 w-4" />
                Buat Kelompok Tani
            </a>
        </x-slot:aksi>
    </x-page-header>

    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <x-stat-card label="Total Pinjaman Berjalan" :nilai="rupiah($totalPinjaman)" ikon="bank" />
        <x-stat-card label="Jumlah Kelompok" :nilai="$daftarKelompok->total()" ikon="kelompok" warna="biru" />
    </div>

    <div class="card overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 p-5">
            <h2 class="text-lg font-semibold text-slate-900">Data Kelompok Tani</h2>

            <form method="GET" class="relative">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                    <x-icon name="cari" class="h-4 w-4" />
                </span>
                <input type="search" name="cari" value="{{ request('cari') }}"
                       placeholder="Cari kelompok atau ketua"
                       class="input-field w-64 py-2 pl-9 text-sm">
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="table-head">
                    <tr>
                        <th class="px-6 py-3.5 text-left">ID</th>
                        <th class="px-6 py-3.5 text-left">Kelompok Tani</th>
                        <th class="px-6 py-3.5 text-left">Ketua</th>
                        <th class="px-6 py-3.5 text-right">Total Pinjaman</th>
                        <th class="px-6 py-3.5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($daftarKelompok as $kelompok)
                        <tr class="hover:bg-slate-50/60">
                            <td class="px-6 py-4 font-medium text-brand-700">{{ $kelompok->kode }}</td>
                            <td class="px-6 py-4">
                                <p class="font-semibold text-slate-900">{{ $kelompok->nama_kelompok }}</p>
                                <p class="text-xs text-slate-500">{{ $kelompok->alamat }}</p>
                            </td>
                            <td class="px-6 py-4 text-slate-600">{{ $kelompok->nama_ketua }}</td>
                            <td class="px-6 py-4 text-right font-semibold text-slate-900">
                                {{ rupiah($kelompok->total_pinjaman) }}
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('kelompok.show', $kelompok) }}"
                                   class="font-semibold text-brand-700 hover:underline">Lihat</a>
                            </td>
                        </tr>
                    @empty
                        <x-empty-state kolom="5" pesan="Belum ada kelompok tani terdaftar.">
                            <x-slot:aksi>
                                <a href="{{ route('kelompok.create') }}" class="btn-primary">Buat Kelompok Tani</a>
                            </x-slot:aksi>
                        </x-empty-state>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($daftarKelompok->hasPages())
            <div class="border-t border-slate-200 px-6 py-4">{{ $daftarKelompok->links() }}</div>
        @endif
    </div>
</x-app-layout>
