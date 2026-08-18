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
    </div>

    <div class="card overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 p-5">
            <h2 class="text-lg font-semibold text-slate-900">Daftar Pinjaman</h2>

            <form method="GET" class="flex flex-wrap items-center gap-3">
                <select name="kelompok" onchange="this.form.submit()" class="input-field w-48 py-2 text-sm">
                    <option value="">Semua Kelompok</option>
                    @foreach ($daftarKelompok as $kelompok)
                        <option value="{{ $kelompok->id }}" @selected(request('kelompok') == $kelompok->id)>
                            {{ $kelompok->nama_kelompok }}
                        </option>
                    @endforeach
                </select>

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
                        <th class="px-6 py-3.5 text-left">ID Pinjaman</th>
                        <th class="px-6 py-3.5 text-right">Pinjaman Pokok</th>
                        <th class="px-6 py-3.5 text-right">Sisa Pokok</th>
                        <th class="px-6 py-3.5 text-left">Jatuh Tempo</th>
                        <th class="px-6 py-3.5 text-left">Status Pelunasan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($daftarPinjaman as $pinjaman)
                        <tr class="cursor-pointer hover:bg-slate-50/60"
                            onclick="window.location='{{ route('pinjaman.show', $pinjaman) }}'">
                            <td class="px-6 py-4 font-semibold text-slate-900">
                                {{ $pinjaman->kelompokTani->nama_kelompok }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 font-medium text-brand-700">{{ $pinjaman->kode }}</td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-slate-600">
                                {{ rupiah($pinjaman->jumlah_pinjaman) }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right font-semibold text-slate-900">
                                {{ rupiah($pinjaman->sisa_pokok) }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-slate-600">
                                {{ tanggal_id($pinjaman->tanggal_jatuh_tempo) }}
                                <span class="block text-xs text-slate-400">Termin {{ $pinjaman->termin }}</span>
                            </td>
                            <td class="px-6 py-4"><x-status-badge :status="$pinjaman->status" /></td>
                        </tr>
                    @empty
                        <x-empty-state kolom="6" pesan="Belum ada pinjaman tercatat.">
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

        <x-tabel-kaki :paginator="$daftarPinjaman" />
    </div>
</x-app-layout>
