<x-app-layout title="Buat Transaksi Kas">
    <x-page-header judul="Buat Transaksi Kas"
                   deskripsi="Catat arus masuk atau keluar kas koperasi."
                   :kembali="route('kas.index')" />

    <form method="POST" action="{{ route('kas.store') }}" class="card max-w-5xl p-6">
        @csrf
        @include('kas._form')

        <div class="mt-8 flex justify-end gap-3">
            <a href="{{ route('kas.index') }}" class="btn-danger">Batalkan</a>
            <button type="submit" class="btn-primary">Simpan</button>
        </div>
    </form>

    {{-- Form terpisah agar pengiriman kategori tidak ikut membawa data transaksi. --}}
    <form id="form-kategori" method="POST" action="{{ route('kategori-kas.store') }}" class="hidden">@csrf</form>
</x-app-layout>
