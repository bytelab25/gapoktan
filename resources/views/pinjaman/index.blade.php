<x-app-layout title="Pinjaman Kelompok">
    <x-page-header judul="Pinjaman Kelompok"
                   deskripsi="Monitoring real-time saldo dan status cicilan kelompok tani binaan.">
        <x-slot:aksi>
            <a href="{{ route('pinjaman.create') }}" class="btn-primary">
                <x-icon name="plus" class="h-4 w-4" />
                Buat Pinjaman
            </a>
        </x-slot:aksi>
    </x-page-header>

    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <x-stat-card label="Total Pinjaman Pokok" :nilai="rupiah($totalPokok)" ikon="bank" />
    </div>

    <div class="card overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 p-5">
            <h2 class="text-lg font-semibold text-slate-900">Daftar Pinjaman</h2>

            <form method="GET">
                <select name="status" onchange="this.form.submit()" class="input-field py-2 text-sm">
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
                        <th class="px-6 py-3.5 text-left">ID Pinjaman</th>
                        <th class="px-6 py-3.5 text-right">Pinjaman Pokok</th>
                        <th class="px-6 py-3.5 text-right">Sisa Pokok</th>
                        <th class="px-6 py-3.5 text-left">Status Pelunasan</th>
                        <th class="px-6 py-3.5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($daftarPinjaman as $pinjaman)
                        <tr class="hover:bg-slate-50/60">
                            <td class="px-6 py-4 font-semibold text-slate-900">
                                {{ $pinjaman->kelompokTani->nama_kelompok }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <a href="{{ route('pinjaman.show', $pinjaman) }}"
                                   class="font-medium text-brand-700 hover:underline">{{ $pinjaman->kode }}</a>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-slate-600">
                                {{ rupiah($pinjaman->jumlah_pinjaman) }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right font-semibold text-slate-900">
                                {{ rupiah($pinjaman->sisa_pokok) }}
                            </td>
                            <td class="px-6 py-4"><x-status-badge :status="$pinjaman->status" /></td>
                            <td class="whitespace-nowrap px-6 py-4 text-right">
                                @if ($pinjaman->status !== \App\Models\Pinjaman::STATUS_LUNAS)
                                    <a href="{{ route('pinjaman.bayar', $pinjaman) }}"
                                       class="font-semibold text-brand-700 hover:underline">Bayar</a>
                                @else
                                    <span class="text-slate-400">Selesai</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <x-empty-state kolom="6" pesan="Belum ada pinjaman tercatat.">
                            <x-slot:aksi>
                                <a href="{{ route('pinjaman.create') }}" class="btn-primary">Buat Pinjaman</a>
                            </x-slot:aksi>
                        </x-empty-state>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($daftarPinjaman->hasPages())
            <div class="border-t border-slate-200 px-6 py-4">{{ $daftarPinjaman->links() }}</div>
        @endif
    </div>
</x-app-layout>
