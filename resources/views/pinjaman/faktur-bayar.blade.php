@php $pinjaman = $pembayaran->pinjaman; @endphp

<x-faktur-layout title="Faktur Bayar Pinjaman" :kembali="route('pinjaman.show', $pinjaman)">
    <p class="mt-6 text-sm text-slate-500">Nomor Pembayaran</p>
    <p class="text-xl font-bold text-slate-900">{{ $pembayaran->kode }}</p>

    <dl class="mt-6 divide-y divide-slate-100">
        @foreach ([
            'Kelompok Tani' => $pinjaman->kelompokTani->nama_kelompok,
            'Pinjaman' => $pinjaman->kode,
            'Tanggal Bayar' => tanggal_id($pembayaran->tanggal, 'd F Y'),
            'Bunga' => (float) $pinjaman->bunga_persen . '% × ' . $pembayaran->bulan_bunga . ' bulan',
            'Catatan' => $pembayaran->deskripsi ?: '-',
        ] as $label => $nilai)
            <div class="grid grid-cols-1 gap-1 py-3.5 sm:grid-cols-3">
                <dt class="text-sm text-slate-500">{{ $label }}</dt>
                <dd class="text-sm font-medium text-slate-900 sm:col-span-2">{{ $nilai }}</dd>
            </div>
        @endforeach
    </dl>

    <div class="mt-6 space-y-3 rounded-lg bg-slate-50 p-5">
        <div class="flex items-center justify-between text-sm">
            <span class="text-slate-600">Bunga Dibayar</span>
            <span class="font-medium text-slate-900">{{ rupiah($pembayaran->bunga_nominal) }}</span>
        </div>
        <div class="flex items-center justify-between text-sm">
            <span class="text-slate-600">Pokok Dibayar</span>
            <span class="font-medium text-slate-900">{{ rupiah($pembayaran->bayar_pokok) }}</span>
        </div>
        <div class="flex items-center justify-between border-t border-slate-200 pt-3">
            <span class="text-sm font-semibold text-slate-700">Total Bayar</span>
            <span class="text-2xl font-bold text-brand-700">{{ rupiah($pembayaran->total_bayar) }}</span>
        </div>
        <div class="flex items-center justify-between text-sm">
            <span class="text-slate-600">Sisa Pokok Setelah Bayar</span>
            <span class="font-medium text-slate-900">{{ rupiah($pinjaman->sisa_pokok) }}</span>
        </div>
    </div>

    <div class="mt-12 flex justify-between gap-8">
        <div class="text-center">
            <p class="text-sm text-slate-500">Penyetor</p>
            <div class="mt-16 w-52 border-t border-slate-300 pt-2">
                <p class="text-sm font-semibold text-slate-900">{{ $pinjaman->kelompokTani->nama_ketua }}</p>
                <p class="text-xs text-slate-500">Ketua {{ $pinjaman->kelompokTani->nama_kelompok }}</p>
            </div>
        </div>

        <div class="text-center">
            <p class="text-sm text-slate-500">Penerima</p>
            <div class="mt-16 w-52 border-t border-slate-300 pt-2">
                <p class="text-sm font-semibold text-slate-900">{{ $pembayaran->user?->name ?? '-' }}</p>
                <p class="text-xs text-slate-500">{{ $pembayaran->user?->role ?? '' }}</p>
            </div>
        </div>
    </div>
</x-faktur-layout>
