<x-app-layout title="Beranda">
    <x-page-header judul="Ringkasan Keuangan"
                   deskripsi="Ikhtisar keuangan dan jadwal operasional hari ini." />

    @php
        // Kartu ringkasan gaya dasbor admin: warna penuh, angka besar,
        // ikon samar di kanan, dan tautan detail di kaki kartu.
        $kartu = [
            [
                'label' => 'Saldo Kas',
                'nilai' => rupiah($saldoKas),
                'ikon' => 'kas',
                'rute' => route('kas.index'),
                'warna' => 'from-emerald-500 to-brand-700',
            ],
            [
                'label' => 'Dana Talangan',
                'nilai' => rupiah($saldoTalangan),
                'ikon' => 'talangan',
                'rute' => route('talangan.index'),
                'warna' => 'from-sky-500 to-cyan-600',
            ],
            [
                'label' => 'Dana Beredar',
                'nilai' => rupiah($danaBeredar),
                'ikon' => 'pinjaman',
                'rute' => route('pinjaman.index'),
                'warna' => 'from-amber-400 to-orange-500',
            ],
            [
                'label' => 'Laporan Keuangan Tahunan',
                'nilai' => 'Tahun '.now()->year,
                'ikon' => 'laporan',
                'rute' => route('laporan.index', ['jenis' => 'akhir_tahun', 'tahun' => now()->year]),
                'warna' => 'from-rose-400 to-pink-600',
            ],
        ];
    @endphp

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($kartu as $item)
            <a href="{{ $item['rute'] }}"
               class="group relative flex flex-col overflow-hidden rounded-xl bg-gradient-to-br {{ $item['warna'] }} text-white shadow-card transition hover:-translate-y-0.5 hover:shadow-pop">
                <x-icon :name="$item['ikon']"
                        class="pointer-events-none absolute bottom-11 right-3 h-14 w-14 text-white/25 transition group-hover:scale-110" />

                <div class="relative flex-1 px-5 pb-5 pt-4">
                    {{-- Nominal tidak boleh terpotong dua baris walau empat kartu berjejer. --}}
                    <p class="whitespace-nowrap text-2xl font-bold tracking-tight xl:text-xl 2xl:text-2xl">{{ $item['nilai'] }}</p>
                    <p class="mt-1 pr-14 text-sm font-medium text-white/90">{{ $item['label'] }}</p>
                </div>

                <span class="relative flex items-center justify-center gap-1.5 bg-black/10 px-5 py-2 text-xs font-semibold text-white/90 transition group-hover:bg-black/20">
                    Lihat Detail
                    <x-icon name="chevron-kanan" class="h-3.5 w-3.5" />
                </span>
            </a>
        @endforeach
    </div>

    @if ($lewatJatuhTempo->isNotEmpty())
        <div class="card mt-6 overflow-hidden border-danger-100">
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
</x-app-layout>
