<x-app-layout title="Detail Transaksi Kas">
    <x-page-header judul="Detail Transaksi Kas"
                   :deskripsi="$transaksi->kode"
                   :kembali="route('kas.index')">
        <x-slot:aksi>
            <a href="{{ route('kas.faktur', $transaksi) }}" class="btn-ghost">
                <x-icon name="nota" class="h-4 w-4" />
                Faktur
            </a>
        </x-slot:aksi>
    </x-page-header>

    <div class="card max-w-3xl p-6">
        <dl class="divide-y divide-slate-100">
            <div class="grid grid-cols-1 gap-1 py-3.5 sm:grid-cols-3">
                <dt class="text-sm text-slate-500">Tanggal Transaksi</dt>
                <dd class="text-sm font-medium text-slate-900 sm:col-span-2">{{ tanggal_id($transaksi->tanggal, 'd F Y') }}</dd>
            </div>
            <div class="grid grid-cols-1 gap-1 py-3.5 sm:grid-cols-3">
                <dt class="text-sm text-slate-500">Jenis Transaksi</dt>
                <dd class="sm:col-span-2">
                    <span class="badge {{ $transaksi->jenis === 'masuk' ? 'bg-brand-50 text-brand-700' : 'bg-danger-50 text-danger-600' }}">
                        Dana {{ ucfirst($transaksi->jenis) }}
                    </span>
                </dd>
            </div>
            <div class="grid grid-cols-1 gap-1 py-3.5 sm:grid-cols-3">
                <dt class="text-sm text-slate-500">Kategori</dt>
                <dd class="text-sm font-medium text-slate-900 sm:col-span-2">{{ $transaksi->kategori->nama }}</dd>
            </div>
            <div class="grid grid-cols-1 gap-1 py-3.5 sm:grid-cols-3">
                <dt class="text-sm text-slate-500">Kelompok Tani</dt>
                <dd class="text-sm font-medium text-slate-900 sm:col-span-2">
                    {{ $transaksi->kelompokTani?->nama_kelompok ?? 'Tidak terkait kelompok' }}
                </dd>
            </div>
            <div class="grid grid-cols-1 gap-1 py-3.5 sm:grid-cols-3">
                <dt class="text-sm text-slate-500">Jumlah (IDR)</dt>
                <dd class="text-lg font-bold sm:col-span-2 {{ $transaksi->jenis === 'masuk' ? 'text-brand-600' : 'text-danger-600' }}">
                    {{ rupiah_bertanda($transaksi->nilai) }}
                </dd>
            </div>
            <div class="grid grid-cols-1 gap-1 py-3.5 sm:grid-cols-3">
                <dt class="text-sm text-slate-500">Deskripsi / Catatan</dt>
                <dd class="text-sm text-slate-700 sm:col-span-2">{{ $transaksi->deskripsi ?: '-' }}</dd>
            </div>
        </dl>

        <div class="mt-6 grid gap-4 border-t border-slate-200 pt-6 sm:grid-cols-2">
            <div class="rounded-lg bg-slate-50 p-4">
                <p class="text-xs text-slate-500">Biaya yang Dihitung</p>
                <p class="mt-1 text-xl font-bold text-brand-600">{{ rupiah($transaksi->biaya) }}</p>
            </div>
            <div class="rounded-lg bg-slate-50 p-4">
                <p class="text-xs text-slate-500">Saldo Setelah Transaksi</p>
                <p class="mt-1 text-xl font-bold text-slate-900">{{ rupiah($saldoSetelah) }}</p>
            </div>
        </div>

        @if ($transaksi->otomatis)
            <div class="mt-8 flex items-start gap-3 rounded-lg bg-brand-50 p-4 text-sm text-brand-800">
                <x-icon name="peringatan" class="mt-0.5 h-5 w-5 shrink-0 text-brand-600" />
                <span>
                    Transaksi ini dibuat otomatis dari pencairan pinjaman
                    <a href="{{ route('pinjaman.show', $transaksi->pinjaman) }}" class="font-semibold underline">{{ $transaksi->pinjaman->kode }}</a>.
                    Ubah data pinjamannya bila nominalnya salah.
                </span>
            </div>
        @else
            @can('kelola')
                <div class="mt-8 flex flex-wrap justify-end gap-3">
                    <x-tombol-hapus :aksi="route('kas.destroy', $transaksi)"
                                    :pesan="'Transaksi ' . $transaksi->kode . ' dihapus, saldo semua transaksi setelahnya dihitung ulang.'"
                                    label="Hapus Transaksi" />
                    <a href="{{ route('kas.edit', $transaksi) }}" class="btn-primary">Sunting</a>
                </div>
            @endcan
        @endif
    </div>
</x-app-layout>
