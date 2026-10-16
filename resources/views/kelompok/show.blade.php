<x-app-layout title="Profil Kelompok Tani">
    <x-page-header judul="Informasi Kelompok Tani" :kembali="route('kelompok.index')" />

    @include('kelompok._tabs', ['kelompok' => $kelompok, 'aktif' => 'profil'])

    <div class="card max-w-3xl p-6">
        <h2 class="text-xs font-bold uppercase tracking-wider text-brand-700">Identitas Kelompok</h2>

        <div class="mt-4 flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 pb-5">
            <div>
                <p class="text-2xl font-bold text-slate-900">{{ $kelompok->nama_kelompok }}</p>
                <p class="mt-0.5 text-sm text-slate-500">{{ $kelompok->kode }}</p>
            </div>
            @if ($kelompok->pinjaman->isNotEmpty())
                <x-status-badge :status="$kelompok->status_pinjaman" />
            @endif
        </div>

        <dl class="mt-2 divide-y divide-slate-100">
            @foreach ([
                'Nama Ketua' => $kelompok->nama_ketua,
                'NIK' => $kelompok->nik_ketua,
                'Nomor HP' => $kelompok->no_hp,
                'Alamat Rumah (ketua)' => $kelompok->alamat,
            ] as $label => $nilai)
                <div class="grid grid-cols-1 gap-1 py-3.5 sm:grid-cols-3">
                    <dt class="text-sm text-slate-500">{{ $label }}</dt>
                    <dd class="text-sm font-medium text-slate-900 sm:col-span-2">{{ $nilai }}</dd>
                </div>
            @endforeach
        </dl>

        @can('kelola')
            <div class="mt-8 flex flex-wrap justify-end gap-3">
                <x-tombol-hapus :aksi="route('kelompok.destroy', $kelompok)"
                                :pesan="'Kelompok ' . $kelompok->nama_kelompok . ' akan dihapus permanen. Kelompok yang punya riwayat pinjaman tidak bisa dihapus.'"
                                label="Hapus Kelompok Tani" />
                <a href="{{ route('kelompok.edit', $kelompok) }}" class="btn-primary">Sunting Data</a>
            </div>
        @endcan
    </div>
</x-app-layout>
