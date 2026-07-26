<x-app-layout title="Buat Pengguna">
    <x-page-header judul="Buat Pengguna Baru"
                   deskripsi="Akun dibuat pengurus — tidak ada pendaftaran mandiri."
                   :kembali="route('pengguna.index')" />

    <form method="POST" action="{{ route('pengguna.store') }}" class="card max-w-4xl p-6">
        @csrf
        @include('pengguna._form')

        <div class="mt-8 flex justify-end gap-3">
            <a href="{{ route('pengguna.index') }}" class="btn-danger">Batalkan</a>
            <button type="submit" class="btn-primary">Buat Pengguna</button>
        </div>
    </form>
</x-app-layout>
