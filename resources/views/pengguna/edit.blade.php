<x-app-layout title="Sunting Pengguna">
    <x-page-header judul="Sunting Pengguna"
                   :deskripsi="$pengguna->name"
                   :kembali="route('pengguna.show', $pengguna)" />

    <form method="POST" action="{{ route('pengguna.update', $pengguna) }}" class="card max-w-4xl p-6">
        @csrf
        @method('PUT')
        @include('pengguna._form')

        <div class="mt-8 flex justify-end gap-3">
            <a href="{{ route('pengguna.show', $pengguna) }}" class="btn-danger">Batalkan</a>
            <button type="submit" class="btn-primary">Simpan Perubahan</button>
        </div>
    </form>
</x-app-layout>
