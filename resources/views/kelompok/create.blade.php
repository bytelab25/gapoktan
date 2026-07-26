<x-app-layout title="Buat Kelompok Tani">
    <x-page-header judul="Buat Kelompok Tani"
                   deskripsi="Lengkapi data kelompok tani dan ketuanya."
                   :kembali="route('kelompok.index')" />

    <form method="POST" action="{{ route('kelompok.store') }}" class="card max-w-4xl p-6">
        @csrf
        @include('kelompok._form')

        <div class="mt-8 flex justify-end gap-3">
            <a href="{{ route('kelompok.index') }}" class="btn-danger">Batalkan</a>
            <button type="submit" class="btn-primary">Buat Kelompok Tani</button>
        </div>
    </form>
</x-app-layout>
