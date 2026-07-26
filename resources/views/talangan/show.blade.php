<x-app-layout title="Detail Dana Talangan">
    <x-page-header judul="Detail Transaksi Dana Talangan"
                   :deskripsi="$transaksi->kode"
                   :kembali="route('talangan.index')">
        <x-slot:aksi>
            <a href="{{ route('talangan.faktur', $transaksi) }}" class="btn-ghost">
                <x-icon name="nota" class="h-4 w-4" />
                Faktur
            </a>
        </x-slot:aksi>
    </x-page-header>

    <div class="card max-w-3xl p-6">
        <dl class="divide-y divide-slate-100">
            @foreach ([
                'Kelompok Tani' => $transaksi->kelompokTani->nama_kelompok,
                'Tanggal' => tanggal_id($transaksi->tanggal, 'd F Y'),
                'Jenis Transaksi' => 'Dana '.ucfirst($transaksi->jenis),
                'Penerima' => $transaksi->penerima ?: '-',
                'Jumlah' => rupiah($transaksi->jumlah),
                'Deskripsi' => $transaksi->deskripsi ?: '-',
            ] as $label => $nilai)
                <div class="grid grid-cols-1 gap-1 py-3.5 sm:grid-cols-3">
                    <dt class="text-sm text-slate-500">{{ $label }}</dt>
                    <dd class="text-sm font-medium text-slate-900 sm:col-span-2">{{ $nilai }}</dd>
                </div>
            @endforeach
        </dl>

        <div class="mt-8 flex flex-wrap justify-end gap-3">
            <form method="POST" action="{{ route('talangan.destroy', $transaksi) }}"
                  onsubmit="return confirm('Hapus transaksi {{ $transaksi->kode }}?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn-danger">Hapus Transaksi</button>
            </form>
            <a href="{{ route('talangan.edit', $transaksi) }}" class="btn-primary">Sunting</a>
        </div>
    </div>
</x-app-layout>
