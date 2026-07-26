<x-faktur-layout title="Faktur Dana Talangan" :kembali="route('talangan.show', $transaksi)">
    <p class="mt-6 text-sm text-slate-500">Nomor Transaksi</p>
    <p class="text-xl font-bold text-slate-900">{{ $transaksi->kode }}</p>

    <dl class="mt-6 divide-y divide-slate-100">
        @foreach ([
            'Kelompok Tani' => $transaksi->kelompokTani->nama_kelompok,
            'Tanggal' => tanggal_id($transaksi->tanggal, 'd F Y'),
            'Jenis Transaksi' => 'Dana '.ucfirst($transaksi->jenis),
            'Penerima' => $transaksi->penerima ?: '-',
            'Jumlah (Rp)' => rupiah($transaksi->jumlah),
            'Deskripsi' => $transaksi->deskripsi ?: '-',
        ] as $label => $nilai)
            <div class="grid grid-cols-1 gap-1 py-3.5 sm:grid-cols-3">
                <dt class="text-sm text-slate-500">{{ $label }}</dt>
                <dd class="text-sm font-medium text-slate-900 sm:col-span-2">{{ $nilai }}</dd>
            </div>
        @endforeach
    </dl>

    <div class="mt-12 flex justify-between gap-8">
        <div class="text-center">
            <p class="text-sm text-slate-500">Penerima</p>
            <div class="mt-16 w-52 border-t border-slate-300 pt-2">
                <p class="text-sm font-semibold text-slate-900">{{ $transaksi->penerima ?: $transaksi->kelompokTani->nama_ketua }}</p>
            </div>
        </div>

        <div class="text-center">
            <p class="text-sm text-slate-500">Dicatat oleh</p>
            <div class="mt-16 w-52 border-t border-slate-300 pt-2">
                <p class="text-sm font-semibold text-slate-900">{{ $transaksi->user?->name ?? '-' }}</p>
                <p class="text-xs text-slate-500">{{ $transaksi->user?->role ?? '' }}</p>
            </div>
        </div>
    </div>
</x-faktur-layout>
