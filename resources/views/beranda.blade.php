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
