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

        @if ($transaksi->otomatis)
            <div class="mt-8 flex items-start gap-3 rounded-lg bg-brand-50 p-4 text-sm text-brand-800">
                <x-icon name="peringatan" class="mt-0.5 h-5 w-5 shrink-0 text-brand-600" />
                <span>
                    Kontribusi ini lahir otomatis dari pencairan pinjaman
                    <a href="{{ route('pinjaman.show', $transaksi->pinjaman) }}" class="font-semibold underline">{{ $transaksi->pinjaman->kode }}</a>,
                    jadi tidak bisa disunting atau dihapus dari sini.
                </span>
            </div>
        @else
            @can('kelola')
                <div class="mt-8 flex flex-wrap justify-end gap-3">
                    <x-tombol-hapus :aksi="route('talangan.destroy', $transaksi)"
                                    :pesan="'Transaksi ' . $transaksi->kode . ' akan dihapus permanen.'"
                                    label="Hapus Transaksi" />
                    <a href="{{ route('talangan.edit', $transaksi) }}" class="btn-primary">Sunting</a>
                </div>
            @endcan
        @endif
    </div>
</x-app-layout>
