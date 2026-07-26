<x-app-layout title="Sunting Pinjaman">
    <x-page-header judul="Sunting Pinjaman"
                   :deskripsi="$pinjaman->kode . ' — ' . $pinjaman->kelompokTani->nama_kelompok"
                   :kembali="route('pinjaman.show', $pinjaman)" />

    <form method="POST" action="{{ route('pinjaman.update', $pinjaman) }}" class="card max-w-4xl p-6">
        @csrf
        @method('PUT')
        @include('pinjaman._form')

        <div class="mt-8 flex justify-end gap-3">
            <a href="{{ route('pinjaman.show', $pinjaman) }}" class="btn-danger">Batalkan</a>
            <button type="submit" class="btn-primary">Simpan Perubahan</button>
        </div>
    </form>
</x-app-layout>
