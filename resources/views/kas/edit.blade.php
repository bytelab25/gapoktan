<x-app-layout title="Sunting Transaksi Kas">
    <x-page-header judul="Sunting Transaksi Kas"
                   :deskripsi="$transaksi->kode"
                   :kembali="route('kas.show', $transaksi)" />

    <form method="POST" action="{{ route('kas.update', $transaksi) }}" class="card max-w-5xl p-6">
        @csrf
        @method('PUT')
        @include('kas._form')

        <div class="mt-8 flex justify-end gap-3">
            <a href="{{ route('kas.show', $transaksi) }}" class="btn-danger">Batalkan</a>
            <button type="submit" class="btn-primary">Simpan</button>
        </div>
    </form>

    <form id="form-kategori" method="POST" action="{{ route('kategori-kas.store') }}" class="hidden">@csrf</form>
</x-app-layout>
