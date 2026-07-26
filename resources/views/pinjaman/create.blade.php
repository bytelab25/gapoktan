<x-app-layout title="Pengajuan Pinjaman Baru">
    <x-page-header judul="Pengajuan Pinjaman Baru"
                   deskripsi="Lengkapi formulir di bawah ini untuk memproses dana kelompok."
                   :kembali="route('pinjaman.index')" />

    <form method="POST" action="{{ route('pinjaman.store') }}" class="card max-w-4xl p-6">
        @csrf
        @include('pinjaman._form')

        <div class="mt-8 flex justify-end gap-3">
            <a href="{{ route('pinjaman.index') }}" class="btn-danger">Batalkan</a>
            <button type="submit" class="btn-primary">Buat Pinjaman</button>
        </div>
    </form>
</x-app-layout>
