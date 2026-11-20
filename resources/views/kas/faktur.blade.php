<x-faktur-layout title="Faktur Transaksi Kas" :kembali="route('kas.show', $transaksi)">
    <p class="mt-6 text-sm text-slate-500">Nomor Transaksi</p>
    <p class="text-xl font-bold text-slate-900">{{ $transaksi->kode }}</p>

    <dl class="mt-6 divide-y divide-slate-100">
        <div class="grid grid-cols-1 gap-1 py-3.5 sm:grid-cols-3">
            <dt class="text-sm text-slate-500">Tanggal Transaksi</dt>
            <dd class="text-sm font-medium text-slate-900 sm:col-span-2">{{ tanggal_id($transaksi->tanggal, 'd F Y') }}</dd>
        </div>
        <div class="grid grid-cols-1 gap-1 py-3.5 sm:grid-cols-3">
            <dt class="text-sm text-slate-500">Jenis Transaksi</dt>
            <dd class="text-sm font-medium sm:col-span-2 {{ $transaksi->jenis === 'masuk' ? 'text-brand-700' : 'text-danger-600' }}">
                Dana {{ ucfirst($transaksi->jenis) }}
            </dd>
        </div>
        <div class="grid grid-cols-1 gap-1 py-3.5 sm:grid-cols-3">
            <dt class="text-sm text-slate-500">Kategori</dt>
            <dd class="text-sm font-medium text-slate-900 sm:col-span-2">{{ $transaksi->kategori->nama }}</dd>
        </div>
        <div class="grid grid-cols-1 gap-1 py-3.5 sm:grid-cols-3">
            <dt class="text-sm text-slate-500">Kelompok Tani</dt>
            <dd class="text-sm font-medium text-slate-900 sm:col-span-2">
                {{ $transaksi->kelompokTani?->nama_kelompok ?? '-' }}
            </dd>
        </div>
        <div class="grid grid-cols-1 gap-1 py-3.5 sm:grid-cols-3">
            <dt class="text-sm text-slate-500">{{ $transaksi->potongan_operasional > 0 ? 'Pencairan Sebelum Potongan Operasional' : 'Jumlah (IDR)' }}</dt>
            <dd class="text-lg font-bold text-slate-900 sm:col-span-2">{{ rupiah($transaksi->jumlah) }}</dd>
        </div>
        @if ($transaksi->potongan_operasional > 0)
            <div class="grid grid-cols-1 gap-1 py-3.5 sm:grid-cols-3">
                <dt class="text-sm text-slate-500">Potongan Operasional (Kas)</dt>
                <dd class="text-sm text-slate-900 sm:col-span-2">
                    <span class="font-medium">{{ rupiah($transaksi->potongan_operasional) }}</span>
                    <span class="block text-slate-500">Dipotong saat pencairan dan tetap di kas.</span>
                </dd>
            </div>
            <div class="grid grid-cols-1 gap-1 py-3.5 sm:grid-cols-3">
                <dt class="text-sm text-slate-500">Diterima Kelompok</dt>
                <dd class="text-sm font-medium text-slate-900 sm:col-span-2">{{ rupiah((float) $transaksi->jumlah - $transaksi->potongan_operasional) }}</dd>
            </div>
        @endif
        <div class="grid grid-cols-1 gap-1 py-3.5 sm:grid-cols-3">
            <dt class="text-sm text-slate-500">Deskripsi / Catatan</dt>
            <dd class="text-sm text-slate-700 sm:col-span-2">{{ $transaksi->deskripsi ?: '-' }}</dd>
        </div>
    </dl>

    <div class="mt-6 border-t border-slate-200 pt-6">
        <div class="rounded-lg bg-slate-50 p-4">
            <p class="text-xs text-slate-500">Saldo Setelah Transaksi</p>
            <p class="mt-1 text-lg font-bold text-slate-900">{{ rupiah($saldoSetelah) }}</p>
        </div>
    </div>

    <div class="mt-12 flex justify-end">
        <div class="text-center">
            <p class="text-sm text-slate-500">{{ $transaksi->diubah_oleh_id ? 'Diubah oleh' : 'Dicatat oleh' }}</p>
            <div class="mt-16 w-56 border-t border-slate-300 pt-2">
                <p class="text-sm font-semibold text-slate-900">{{ $transaksi->penanda_tangan?->name ?? '-' }}</p>
                <p class="text-xs text-slate-500">{{ $transaksi->penanda_tangan?->label_role ?? '' }}</p>
            </div>
        </div>
    </div>
</x-faktur-layout>
