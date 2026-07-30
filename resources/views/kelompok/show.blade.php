<x-app-layout title="Profil Kelompok Tani">
    <x-page-header judul="Informasi Kelompok Tani"
                   :deskripsi="$kelompok->nama_kelompok"
                   :kembali="route('kelompok.index')" />

    @include('kelompok._tabs', ['kelompok' => $kelompok, 'aktif' => 'profil'])

    <div class="card max-w-3xl p-6">
        <h2 class="text-xs font-bold uppercase tracking-wider text-brand-700">Identitas Kelompok</h2>

        <dl class="mt-5 divide-y divide-slate-100">
            @foreach ([
                'ID' => $kelompok->kode,
                'Nama Kelompok Tani' => $kelompok->nama_kelompok,
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
