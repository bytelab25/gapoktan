<x-app-layout title="Log Aktivitas">
    <x-page-header judul="Log Aktivitas Sistem"
                   deskripsi="Jejak audit setiap penambahan, perubahan, dan penghapusan data.">
        <x-slot:aksi>
            <button type="button" onclick="window.print()" class="btn-ghost">
                <x-icon name="cetak" class="h-4 w-4" />
                Cetak Log
            </button>
        </x-slot:aksi>
    </x-page-header>

    <div class="card overflow-hidden">
        <form method="GET" class="tanpa-cetak flex flex-wrap items-end gap-4 border-b border-slate-200 p-5">
            <div>
                <label for="dari" class="label-field">Rentang Tanggal</label>
                <div class="flex items-center gap-2">
                    <input id="dari" type="date" name="dari" value="{{ request('dari') }}" class="input-field py-2 text-sm">
                    <span class="text-slate-400">—</span>
                    <input type="date" name="sampai" value="{{ request('sampai') }}" class="input-field py-2 text-sm">
                </div>
            </div>

            <div>
                <label for="tindakan" class="label-field">Jenis Tindakan</label>
                <select id="tindakan" name="tindakan" class="input-field py-2 text-sm">
                    <option value="">Semua Tindakan</option>
                    @foreach (['input' => 'Input', 'sunting' => 'Sunting', 'hapus' => 'Hapus'] as $nilai => $label)
                        <option value="{{ $nilai }}" @selected(request('tindakan') === $nilai)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <button type="submit" class="btn-primary py-2.5">Terapkan</button>

            @if (request()->hasAny(['dari', 'sampai', 'tindakan']))
                <a href="{{ route('log.index') }}" class="btn-ghost py-2.5">Reset</a>
            @endif
        </form>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="table-head">
                    <tr>
                        <th class="px-6 py-3.5 text-left">Tanggal</th>
                        <th class="px-6 py-3.5 text-left">Nama Pengguna</th>
                        <th class="px-6 py-3.5 text-left">ID Invoice</th>
                        <th class="px-6 py-3.5 text-left">Jenis Tindakan</th>
                        <th class="px-6 py-3.5 text-left">Deskripsi Aktivitas</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($daftarLog as $log)
                        <tr class="hover:bg-slate-50/60">
                            <td class="whitespace-nowrap px-6 py-4 text-slate-600">
                                {{ tanggal_id($log->created_at, 'd M Y H:i') }}
                            </td>
                            <td class="px-6 py-4 font-medium text-slate-900">{{ $log->user?->name ?? 'Sistem' }}</td>
                            <td class="whitespace-nowrap px-6 py-4 text-slate-500">{{ $log->id_invoice ?: '-' }}</td>
                            <td class="px-6 py-4">
                                <span @class([
                                    'badge',
                                    'bg-brand-50 text-brand-700' => $log->jenis_tindakan === 'input',
                                    'bg-warn-50 text-warn-700' => $log->jenis_tindakan === 'sunting',
                                    'bg-danger-50 text-danger-600' => $log->jenis_tindakan === 'hapus',
                                ])>{{ $log->jenis_tindakan }}</span>
                            </td>
                            <td class="px-6 py-4 text-slate-600">{{ $log->deskripsi }}</td>
                        </tr>
                    @empty
                        <x-empty-state kolom="5" pesan="Belum ada aktivitas tercatat." />
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="tanpa-cetak flex flex-wrap items-center justify-between gap-4 border-t border-slate-200 px-6 py-4">
            <div>{{ $daftarLog->links() }}</div>

            <form method="GET" class="flex items-center gap-2 text-sm text-slate-500">
                @foreach (request()->except(['per_halaman', 'page']) as $kunci => $nilai)
                    <input type="hidden" name="{{ $kunci }}" value="{{ $nilai }}">
                @endforeach
                <label for="per_halaman">Baris per halaman</label>
                <select id="per_halaman" name="per_halaman" onchange="this.form.submit()" class="input-field w-20 py-1.5 text-sm">
                    @foreach ([10, 25, 50] as $jumlah)
                        <option value="{{ $jumlah }}" @selected(request('per_halaman', 10) == $jumlah)>{{ $jumlah }}</option>
                    @endforeach
                </select>
            </form>
        </div>
    </div>
</x-app-layout>
