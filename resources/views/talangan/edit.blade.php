<x-app-layout title="Sunting Dana Talangan">
    <x-page-header judul="Sunting Transaksi Dana Talangan"
                   :deskripsi="$transaksi->kode"
                   :kembali="route('talangan.show', $transaksi)" />

    <form method="POST" action="{{ route('talangan.update', $transaksi) }}" class="card max-w-4xl p-6">
        @csrf
        @method('PUT')
        @include('talangan._form')

        <div class="mt-8 flex justify-end gap-3">
            <a href="{{ route('talangan.show', $transaksi) }}" class="btn-danger">Batalkan</a>
            <button type="submit" class="btn-primary">Simpan Perubahan</button>
        </div>
    </form>
</x-app-layout>
