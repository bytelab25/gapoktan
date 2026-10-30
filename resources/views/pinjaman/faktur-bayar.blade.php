@php
    $pinjamanPertama = $pembayaran->pinjaman;
    $kelompok = $pinjamanPertama->kelompokTani;
    $total = (float) $daftarBaris->sum('total_bayar');
@endphp

<x-faktur-layout title="Faktur Setoran Pinjaman" :kembali="route('pinjaman.kelompok', $kelompok)">
    <p class="mt-6 text-sm text-slate-500">Nomor Setoran</p>
    <p class="text-xl font-bold text-slate-900">{{ $pembayaran->kode_setoran ?? $pembayaran->kode }}</p>

    <dl class="mt-6 divide-y divide-slate-100">
        @foreach ([
            'Kelompok Tani' => $kelompok->nama_kelompok . ' (' . $kelompok->kode . ')',
            'Tanggal Bayar' => tanggal_id($pembayaran->tanggal, 'd F Y'),
            'Jenis Setoran' => $pembayaran->hanya_bunga ? 'Bunga saja' : 'Bunga dan pokok',
            'Jumlah Pinjaman Terbayar' => $daftarBaris->count() . ' pinjaman',
            'Catatan' => $pembayaran->deskripsi ?: '-',
        ] as $label => $nilai)
            <div class="grid grid-cols-1 gap-1 py-3.5 sm:grid-cols-3">
                <dt class="text-sm text-slate-500">{{ $label }}</dt>
                <dd class="text-sm font-medium text-slate-900 sm:col-span-2">{{ $nilai }}</dd>
            </div>
        @endforeach
    </dl>

    <div class="mt-6 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="table-head border-y border-slate-200">
                <tr>
                    <th class="px-3 py-3 text-left">ID Pinjaman</th>
                    <th class="px-3 py-3 text-right">Bunga</th>
                    <th class="px-3 py-3 text-right">Pokok</th>
                    <th class="px-3 py-3 text-right">Subtotal</th>
                    <th class="px-3 py-3 text-right">Sisa Pokok</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach ($daftarBaris as $baris)
                    <tr>
                        <td class="whitespace-nowrap px-3 py-3">
                            <span class="font-semibold text-slate-900">{{ $baris->pinjaman->kode }}</span>
                            <span class="block text-xs text-slate-500">
                                bunga {{ (float) $baris->pinjaman->bunga_persen }}% × {{ $baris->bulan_bunga }} bulan
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-3 py-3 text-right text-slate-700">{{ rupiah($baris->bunga_nominal) }}</td>
                        <td class="whitespace-nowrap px-3 py-3 text-right text-slate-700">{{ rupiah($baris->bayar_pokok) }}</td>
                        <td class="whitespace-nowrap px-3 py-3 text-right font-semibold text-slate-900">{{ rupiah($baris->total_bayar) }}</td>
                        <td class="whitespace-nowrap px-3 py-3 text-right text-slate-600">{{ rupiah($baris->pinjaman->sisa_pokok) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-6 flex items-center justify-between rounded-lg bg-slate-50 px-5 py-4">
        <span class="text-sm font-semibold text-slate-700">Total Setoran</span>
        <span class="text-2xl font-bold text-brand-700">{{ rupiah($total) }}</span>
    </div>

    @if ($daftarBaris->count() > 1)
        <p class="mt-4 text-xs text-slate-500">
            Setoran dibagi ke {{ $daftarBaris->count() }} pinjaman mengikuti aturan pinjaman tertua
            dilunasi lebih dulu, {{ $pembayaran->hanya_bunga
                ? 'dan hanya menutup bunga tanpa memotong pokok.'
                : 'dan pada tiap pinjaman bunga dibayar sebelum pokok.' }}
        </p>
    @endif

    <div class="mt-12 flex justify-between gap-8">
        <div class="text-center">
            <p class="text-sm text-slate-500">Penyetor</p>
            <div class="mt-16 w-52 border-t border-slate-300 pt-2">
                <p class="text-sm font-semibold text-slate-900">{{ $kelompok->nama_ketua }}</p>
                <p class="text-xs text-slate-500">Ketua {{ $kelompok->nama_kelompok }}</p>
            </div>
        </div>

        <div class="text-center">
            <p class="text-sm text-slate-500">Penerima</p>
            <div class="mt-16 w-52 border-t border-slate-300 pt-2">
                <p class="text-sm font-semibold text-slate-900">{{ $pembayaran->penanda_tangan?->name ?? '-' }}</p>
                <p class="text-xs text-slate-500">{{ $pembayaran->penanda_tangan?->label_role ?? '' }}</p>
            </div>
        </div>
    </div>
</x-faktur-layout>
