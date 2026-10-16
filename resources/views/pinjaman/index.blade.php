<x-app-layout title="Pinjaman Kelompok">
    <x-page-header judul="Pinjaman Kelompok"
                   deskripsi="Monitoring real-time saldo dan status cicilan kelompok tani binaan.">
        <x-slot:aksi>
            @can('kelola')
                <a href="{{ route('pembayaran.pilih') }}" class="btn-ghost">
                    <x-icon name="kas" class="h-4 w-4" />
                    Bayar Pinjaman
                </a>
                <a href="{{ route('pinjaman.create') }}" class="btn-primary">
                    <x-icon name="plus" class="h-4 w-4" />
                    Buat Pinjaman
                </a>
            @endcan
        </x-slot:aksi>
    </x-page-header>

    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <x-stat-card label="Total Pinjaman Pokok" :nilai="rupiah($totalPokok)" ikon="bank" />
        <x-stat-card label="Sisa Pokok Belum Terbayar" :nilai="rupiah($totalSisaPokok)" ikon="keluar-uang" warna="merah" />
    </div>

    <div class="card overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 p-5">
            <h2 class="text-lg font-semibold text-slate-900">Daftar Pinjaman</h2>

            <form method="GET" class="flex flex-wrap items-center gap-3">
                <div class="relative">
                    <x-icon name="cari" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                    <input type="search" name="cari" value="{{ request('cari') }}" placeholder="Cari kelompok tani"
                           class="input-field w-56 py-2 pl-9 text-sm">
                </div>

                <select name="status" onchange="this.form.submit()" class="input-field w-44 py-2 text-sm">
                    <option value="">Semua Status</option>
                    @foreach (\App\Models\Pinjaman::LABEL_STATUS as $nilai => $label)
                        <option value="{{ $nilai }}" @selected(request('status') === $nilai)>{{ $label }}</option>
                    @endforeach
                </select>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="table-head">
                    <tr>
                        <th class="px-6 py-3.5 text-left">Nama Kelompok</th>
                        <th class="px-6 py-3.5 text-left">Jumlah Transaksi</th>
                        <th class="px-6 py-3.5 text-right">Pinjaman Pokok</th>
                        <th class="px-6 py-3.5 text-right">Sisa Pokok</th>
                        <th class="px-6 py-3.5 text-left">Status Pelunasan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($daftarKelompok as $kelompok)
                        @php
                            $aktif = $kelompok->pinjaman->where('status', '!=', \App\Models\Pinjaman::STATUS_LUNAS)->count();
                        @endphp
                        <tr class="cursor-pointer hover:bg-slate-50/60"
                            onclick="window.location='{{ route('pinjaman.kelompok', $kelompok) }}'">
                            <td class="px-6 py-4">
                                <span class="font-semibold text-slate-900">{{ $kelompok->nama_kelompok }}</span>
                                <span class="block text-xs text-slate-500">{{ $kelompok->kode }} · {{ $kelompok->nama_ketua }}</span>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-slate-600">
                                {{ $kelompok->pinjaman->count() }} pinjaman
                                <span class="block text-xs text-slate-400">
                                    {{ $aktif ? $aktif . ' masih berjalan' : 'semua lunas' }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-slate-600">
                                {{ rupiah($kelompok->pinjaman->sum('jumlah_pinjaman')) }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right font-semibold text-slate-900">
                                {{ rupiah($kelompok->total_pinjaman) }}
                            </td>
                            <td class="px-6 py-4"><x-status-badge :status="$kelompok->status_pinjaman" /></td>
                        </tr>
                    @empty
                        <x-empty-state kolom="5" pesan="Belum ada kelompok tani yang memiliki pinjaman.">
                            <x-slot:aksi>
                                @can('kelola')
                                    <a href="{{ route('pinjaman.create') }}" class="btn-primary">Buat Pinjaman</a>
                                @endcan
                            </x-slot:aksi>
                        </x-empty-state>
                    @endforelse
                </tbody>
            </table>
        </div>

        <x-tabel-kaki :paginator="$daftarKelompok" />
    </div>
</x-app-layout>
