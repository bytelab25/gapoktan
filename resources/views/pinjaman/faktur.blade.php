<x-faktur-layout title="Faktur Pinjaman Kelompok" :kembali="route('pinjaman.show', $pinjaman)">
    <p class="mt-6 text-sm text-slate-500">Nomor Pinjaman</p>
    <p class="text-xl font-bold text-slate-900">{{ $pinjaman->kode }}</p>

    <dl class="mt-6 divide-y divide-slate-100">
        @foreach ([
            'Kelompok Tani' => $pinjaman->kelompokTani->nama_kelompok,
            'Nama Ketua' => $pinjaman->kelompokTani->nama_ketua,
            'Tanggal Transaksi' => tanggal_id($pinjaman->tanggal_transaksi, 'd F Y'),
            'Tanggal Jatuh Tempo' => tanggal_id($pinjaman->tanggal_jatuh_tempo, 'd F Y'),
            'Jumlah Pinjaman' => rupiah($pinjaman->jumlah_pinjaman),
            'Potongan Muka' => (float) $pinjaman->potongan_persen . '% — ' . rupiah($pinjaman->potongan_nominal),
            'Jumlah Diterima' => rupiah($pinjaman->jumlah_diterima),
            'Bunga Berjalan' => (float) $pinjaman->bunga_persen . '% per bulan',
            'Deskripsi' => $pinjaman->deskripsi ?: '-',
        ] as $label => $nilai)
            <div class="grid grid-cols-1 gap-1 py-3.5 sm:grid-cols-3">
                <dt class="text-sm text-slate-500">{{ $label }}</dt>
                <dd class="text-sm font-medium text-slate-900 sm:col-span-2">{{ $nilai }}</dd>
            </div>
        @endforeach
    </dl>

    <div class="mt-6 rounded-lg bg-brand-50 p-5">
        <div class="flex items-center justify-between">
            <span class="text-sm font-semibold text-brand-800">Diterima Kelompok</span>
            <span class="text-2xl font-bold text-brand-700">{{ rupiah($pinjaman->jumlah_diterima) }}</span>
        </div>
    </div>

    <div class="mt-12 flex justify-between gap-8">
        <div class="text-center">
            <p class="text-sm text-slate-500">Penerima</p>
            <div class="mt-16 w-52 border-t border-slate-300 pt-2">
                <p class="text-sm font-semibold text-slate-900">{{ $pinjaman->kelompokTani->nama_ketua }}</p>
                <p class="text-xs text-slate-500">Ketua {{ $pinjaman->kelompokTani->nama_kelompok }}</p>
            </div>
        </div>

        <div class="text-center">
            <p class="text-sm text-slate-500">Disetujui</p>
            <div class="mt-16 w-52 border-t border-slate-300 pt-2">
                <p class="text-sm font-semibold text-slate-900">{{ $pinjaman->penanda_tangan?->name ?? '-' }}</p>
                <p class="text-xs text-slate-500">{{ $pinjaman->penanda_tangan?->label_role ?? '' }}</p>
            </div>
        </div>
    </div>
</x-faktur-layout>
