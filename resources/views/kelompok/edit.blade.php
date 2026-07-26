<x-app-layout title="Sunting Kelompok Tani">
    <x-page-header judul="Sunting Kelompok Tani"
                   :deskripsi="$kelompok->nama_kelompok"
                   :kembali="route('kelompok.show', $kelompok)" />

    <form method="POST" action="{{ route('kelompok.update', $kelompok) }}" class="card max-w-4xl p-6">
        @csrf
        @method('PUT')
        @include('kelompok._form')

        <div class="mt-8 flex justify-end gap-3">
            <a href="{{ route('kelompok.show', $kelompok) }}" class="btn-danger">Batalkan</a>
            <button type="submit" class="btn-primary">Simpan Perubahan</button>
        </div>
    </form>
</x-app-layout>
