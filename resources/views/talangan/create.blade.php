<x-app-layout title="Buat Transaksi Dana Talangan">
    <x-page-header judul="Buat Transaksi Dana Talangan"
                   deskripsi="Catat kontribusi masuk atau klaim santunan kematian."
                   :kembali="route('talangan.index')" />

    <form method="POST" action="{{ route('talangan.store') }}" class="card max-w-4xl p-6">
        @csrf
        @include('talangan._form')

        <div class="mt-8 flex justify-end gap-3">
            <a href="{{ route('talangan.index') }}" class="btn-danger">Batalkan</a>
            <button type="submit" class="btn-primary">Simpan</button>
        </div>
    </form>
</x-app-layout>
