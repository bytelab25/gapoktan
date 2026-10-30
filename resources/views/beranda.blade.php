<x-app-layout title="Beranda">
    <x-page-header judul="Ringkasan Keuangan"
                   deskripsi="Ikhtisar keuangan dan jadwal operasional hari ini." />

    @php
        $kartu = [
            ['label' => 'Saldo Kas', 'nilai' => rupiah($saldoKas), 'rute' => route('kas.index')],
            ['label' => 'Dana Talangan', 'nilai' => rupiah($saldoTalangan), 'rute' => route('talangan.index')],
            ['label' => 'Dana Beredar', 'nilai' => rupiah($danaBeredar), 'rute' => route('pinjaman.index')],
            ['label' => 'Laporan Keuangan Tahunan', 'nilai' => null, 'rute' => route('laporan.index')],
        ];
    @endphp

    @if ($lewatJatuhTempo->isNotEmpty())
        <div class="card mb-6 overflow-hidden border-danger-100">
            <div class="flex items-start gap-3 border-b border-danger-100 bg-danger-50 p-5 text-danger-700">
                <x-icon name="segitiga-peringatan" class="mt-0.5 h-5 w-5 shrink-0" />
                <div>
                    <h2 class="font-semibold">{{ $lewatJatuhTempo->count() }} pinjaman lewat jatuh tempo</h2>
                    <p class="mt-0.5 text-sm text-danger-700/80">Bunga termin pinjaman ini belum dilunasi.</p>
                </div>
            </div>
            <ul class="divide-y divide-slate-100">
                @foreach ($lewatJatuhTempo->take(5) as $pinjaman)
                    <li>
                        <a href="{{ route('pinjaman.show', $pinjaman) }}"
                           class="flex flex-wrap items-center justify-between gap-2 px-5 py-3 text-sm hover:bg-slate-50">
                            <span>
                                <span class="font-semibold text-slate-900">{{ $pinjaman->kelompokTani->nama_kelompok }}</span>
                                <span class="text-slate-500">· {{ $pinjaman->kode }}</span>
                            </span>
                            <x-jatuh-tempo :pinjaman="$pinjaman" sebaris class="text-sm" />
                        </a>
                    </li>
                @endforeach
            </ul>
            @if ($lewatJatuhTempo->count() > 5)
                <a href="{{ route('pinjaman.index') }}"
                   class="block border-t border-slate-100 px-5 py-3 text-center text-sm font-semibold text-brand-700 hover:bg-slate-50">
                    Lihat semua pinjaman
                </a>
            @endif
        </div>
    @endif

    <div class="card grid grid-cols-1 overflow-hidden md:grid-cols-2">
        @foreach ($kartu as $i => $item)
            <div @class([
                'p-6',
                'md:border-r border-slate-200' => $i % 2 === 0,
                'border-t border-slate-200' => $i > 1,
                'border-t border-slate-200 md:border-t-0' => $i === 1,
            ])>
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h2 class="text-lg font-semibold text-slate-900">{{ $item['label'] }}</h2>
                    @if ($item['nilai'])
                        <p class="text-xl font-bold text-brand-600">{{ $item['nilai'] }}</p>
                    @endif
                </div>

                <a href="{{ $item['rute'] }}" class="btn-primary mt-5">Lihat Detail</a>
            </div>
        @endforeach
    </div>
</x-app-layout>
