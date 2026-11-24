<x-app-layout title="Beranda">
    {{-- Tanggal disebut supaya jelas angka di bawah adalah posisi per hari ini. --}}
    <x-page-header judul="Beranda"
                   :deskripsi="'Posisi kas, pinjaman, dan jatuh tempo per '.now()->translatedFormat('l, j F Y').'.'" />

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
                    <p class="mt-0.5 text-sm text-danger-700/80">Bunga termin pinjaman ini belum dilunasi. Urut dari yang paling lama.</p>
                </div>
            </div>
            <x-daftar-gulir tinggi="15rem" label="Daftar pinjaman lewat jatuh tempo">
            <ul class="divide-y divide-slate-100">
                @foreach ($lewatJatuhTempo as $pinjaman)
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
            </x-daftar-gulir>
        </div>
    @endif

    {{-- Arus kas & status pinjaman --}}
    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
        <section class="card p-5 lg:col-span-2">
            <div class="mb-4 flex flex-wrap items-start justify-between gap-2">
                <div>
                    <h2 class="text-base font-semibold text-slate-900">Arus Kas 12 Bulan Terakhir</h2>
                    <p class="text-sm text-slate-500">Pemasukan dan pengeluaran kas per bulan, termasuk pencairan dan setoran pinjaman.</p>
                </div>
                <a href="{{ route('kas.index') }}" class="text-sm font-semibold text-brand-700 hover:text-brand-800">Lihat kas</a>
            </div>
            @php
                $totalMasuk = array_sum($arusKas['masuk']);
                $totalKeluar = array_sum($arusKas['keluar']);
            @endphp
            <x-ringkasan-mini class="mb-5 grid-cols-1 sm:grid-cols-3" :isi="[
                ['label' => 'Pemasukan 12 bulan', 'nilai' => rupiah($totalMasuk), 'warna' => 'var(--seri-1)'],
                ['label' => 'Pengeluaran 12 bulan', 'nilai' => rupiah($totalKeluar), 'warna' => 'var(--seri-2)'],
                ['label' => 'Selisih', 'nilai' => rupiah_bertanda($totalMasuk - $totalKeluar),
                    'catatan' => $totalMasuk >= $totalKeluar ? 'Kas bertambah' : 'Kas berkurang'],
            ]" />
            <x-grafik-kolom :label="$labelBulan" :seri="[
                ['nama' => 'Pemasukan', 'warna' => 'var(--seri-1)', 'nilai' => $arusKas['masuk']],
                ['nama' => 'Pengeluaran', 'warna' => 'var(--seri-2)', 'nilai' => $arusKas['keluar']],
            ]" />
        </section>

        @php
            $totalPinjaman = collect($statusPinjaman)->sum('jumlah');
            $warnaStatus = [
                'lunas' => 'var(--status-baik)',
                'belum_bayar' => 'var(--seri-1)',
                'menunggak' => 'var(--status-kritis)',
            ];
            $ikonStatus = ['lunas' => 'centang', 'belum_bayar' => 'riwayat', 'menunggak' => 'segitiga-peringatan'];
        @endphp
        <section class="card flex flex-col p-5">
            <div class="mb-4">
                <h2 class="text-base font-semibold text-slate-900">Status Pinjaman</h2>
                <p class="text-sm text-slate-500">{{ $totalPinjaman }} pinjaman tercatat</p>
            </div>

            <x-ringkasan-mini class="mb-5 grid-cols-2" :isi="[
                ['label' => 'Dicairkan '.$ikhtisar['tahun'], 'nilai' => rupiah_ringkas($ikhtisar['dicairkanTahunIni']),
                    'catatan' => $ikhtisar['jumlahDicairkanTahunIni'].' pinjaman'],
                ['label' => 'Kelompok meminjam', 'nilai' => $ikhtisar['kelompokAktif'].' kelompok',
                    'catatan' => 'dari '.$ikhtisar['totalKelompok'].' terdaftar'],
            ]" />

            @if ($totalPinjaman > 0)
                {{-- Satu batang utuh dibagi per status; persentase tercetak bila muat. --}}
                <div class="flex h-7 w-full gap-[2px]" role="img"
                     aria-label="{{ collect($statusPinjaman)->map(fn ($s) => $s['label'].' '.$s['jumlah'])->implode(', ') }}">
                    @foreach ($statusPinjaman as $status)
                        @continue($status['jumlah'] === 0)
                        @php $bagian = $status['jumlah'] / $totalPinjaman * 100; @endphp
                        <div tabindex="0"
                             data-grafik="{{ json_encode(['judul' => $status['label'], 'baris' => [
                                 ['nama' => 'pinjaman', 'nilai' => $status['jumlah'].' ('.round($bagian).'%)', 'warna' => $warnaStatus[$status['kunci']]],
                                 ['nama' => $status['kunci'] === 'lunas' ? 'pokok awal' : 'sisa pokok', 'nilai' => rupiah($status['nominal']), 'warna' => $warnaStatus[$status['kunci']]],
                             ]]) }}"
                             class="flex items-center overflow-visible rounded px-2 text-xs font-semibold text-white outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-1"
                             style="width: {{ $bagian }}%; background: {{ $warnaStatus[$status['kunci']] }}">
                            @if ($bagian >= 14)
                                {{ round($bagian) }}%
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                <p class="rounded-lg bg-slate-50 py-6 text-center text-sm text-slate-400">Belum ada pinjaman.</p>
            @endif

            <ul class="mt-5 divide-y divide-slate-100">
                @foreach ($statusPinjaman as $status)
                    <li class="flex items-center justify-between gap-3 py-3">
                        <span class="flex items-center gap-2 text-sm text-slate-700">
                            <span class="h-2.5 w-2.5 shrink-0 rounded-sm" style="background: {{ $warnaStatus[$status['kunci']] }}"></span>
                            <x-icon :name="$ikonStatus[$status['kunci']]" class="h-4 w-4 text-slate-400" />
                            {{ $status['label'] }}
                        </span>
                        <span class="text-right">
                            <span class="block text-sm font-semibold text-slate-900">{{ $status['jumlah'] }} pinjaman</span>
                            <span class="block text-xs text-slate-500">
                                {{ $status['kunci'] === 'lunas' ? 'Pokok' : 'Sisa pokok' }} {{ rupiah($status['nominal']) }}
                            </span>
                        </span>
                    </li>
                @endforeach
            </ul>

            <a href="{{ route('pinjaman.index') }}" class="mt-auto pt-3 text-sm font-semibold text-brand-700 hover:text-brand-800">Lihat pinjaman</a>
        </section>
    </div>

    {{-- Setoran & kelompok dengan sisa pinjaman terbesar --}}
    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
        <section class="card p-5 lg:col-span-2">
            <div class="mb-4">
                <h2 class="text-base font-semibold text-slate-900">Setoran Pinjaman 12 Bulan Terakhir</h2>
                <p class="text-sm text-slate-500">Setiap setoran dibagi menjadi pembayaran pokok dan bunga.</p>
            </div>
            <x-ringkasan-mini class="mb-5 grid-cols-1 sm:grid-cols-3" :isi="[
                ['label' => 'Setoran '.$ikhtisar['bulan'], 'nilai' => rupiah($ikhtisar['setoranBulanIni']),
                    'catatan' => $ikhtisar['jumlahSetoranBulanIni'].' kali setoran kelompok'],
                ['label' => 'Pokok kembali 12 bulan', 'nilai' => rupiah(array_sum($setoran['pokok'])), 'warna' => 'var(--seri-1)'],
                ['label' => 'Bunga 12 bulan', 'nilai' => rupiah(array_sum($setoran['bunga'])), 'warna' => 'var(--seri-2)',
                    'catatan' => 'Tahun '.$ikhtisar['tahun'].': '.rupiah($ikhtisar['bungaTahunIni'])],
            ]" />
            <x-grafik-kolom :label="$labelBulan" tumpuk :seri="[
                ['nama' => 'Pokok', 'warna' => 'var(--seri-1)', 'nilai' => $setoran['pokok']],
                ['nama' => 'Bunga', 'warna' => 'var(--seri-2)', 'nilai' => $setoran['bunga']],
            ]" />
        </section>

        @php $sisaTerbesar = (float) ($kelompokTeratas->max('sisa') ?: 1); @endphp
        <section class="card p-5">
            <div class="mb-4">
                <h2 class="text-base font-semibold text-slate-900">Sisa Pinjaman Terbesar</h2>
                <p class="text-sm text-slate-500">Lima kelompok dengan sisa pokok tertinggi</p>
            </div>

            <ul class="space-y-4">
                @forelse ($kelompokTeratas as $baris)
                    <li>
                        <a href="{{ route('pinjaman.kelompok', $baris['kelompok']) }}" class="group block">
                            <div class="flex items-baseline justify-between gap-3 text-sm">
                                <span class="truncate font-medium text-slate-800 group-hover:text-brand-700">{{ $baris['kelompok']->nama_kelompok }}</span>
                                <span class="shrink-0 font-semibold text-slate-900">{{ rupiah($baris['sisa']) }}</span>
                            </div>
                            <div class="mt-1.5 h-2.5 w-full">
                                <div class="h-full rounded-r" style="width: {{ max(2, $baris['sisa'] / $sisaTerbesar * 100) }}%; background: var(--seri-1)"></div>
                            </div>
                            <p class="mt-1 text-xs text-slate-500">{{ $baris['jumlah'] }} pinjaman belum lunas</p>
                        </a>
                    </li>
                @empty
                    <li class="rounded-lg bg-slate-50 py-6 text-center text-sm text-slate-400">Tidak ada pinjaman berjalan.</li>
                @endforelse
            </ul>
        </section>
    </div>

    {{-- Jatuh tempo mendatang & transaksi terbaru --}}
    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
        <section class="card overflow-hidden">
            <div class="flex items-start gap-3 border-b border-slate-100 p-5">
                <x-icon name="kalender" class="mt-0.5 h-5 w-5 shrink-0 text-warn-600" />
                <div>
                    <h2 class="text-base font-semibold text-slate-900">Jatuh Tempo 30 Hari ke Depan</h2>
                    <p class="text-sm text-slate-500">{{ $jatuhTempoMendatang->count() }} pinjaman · ingatkan kelompok sebelum terlambat.</p>
                </div>
            </div>
            <x-daftar-gulir tinggi="20.5rem" label="Daftar pinjaman jatuh tempo 30 hari ke depan">
            <ul class="divide-y divide-slate-100">
                @forelse ($jatuhTempoMendatang as $pinjaman)
                    @php $sisaHari = (int) now()->startOfDay()->diffInDays($pinjaman->tanggal_jatuh_tempo); @endphp
                    <li>
                        <a href="{{ route('pinjaman.show', $pinjaman) }}"
                           class="flex flex-wrap items-center justify-between gap-2 px-5 py-3 text-sm hover:bg-slate-50">
                            <span>
                                <span class="font-semibold text-slate-900">{{ $pinjaman->kelompokTani->nama_kelompok }}</span>
                                <span class="text-slate-500">· {{ $pinjaman->kode }}</span>
                            </span>
                            <span class="text-right">
                                <span class="block font-medium text-slate-700">{{ tanggal_id($pinjaman->tanggal_jatuh_tempo) }}</span>
                                <span @class([
                                    'block text-xs font-semibold',
                                    'text-warn-700' => $sisaHari <= 7,
                                    'text-slate-500' => $sisaHari > 7,
                                ])>{{ $sisaHari === 0 ? 'Hari ini' : $sisaHari.' hari lagi' }}</span>
                            </span>
                        </a>
                    </li>
                @empty
                    <li class="px-5 py-8 text-center text-sm text-slate-400">Tidak ada pinjaman jatuh tempo dalam 30 hari.</li>
                @endforelse
            </ul>
            </x-daftar-gulir>
        </section>

        <section class="card overflow-hidden">
            <div class="flex items-start justify-between gap-3 border-b border-slate-100 p-5">
                <div class="flex items-start gap-3">
                    <x-icon name="riwayat" class="mt-0.5 h-5 w-5 shrink-0 text-slate-400" />
                    <div>
                        <h2 class="text-base font-semibold text-slate-900">Transaksi Kas Terbaru</h2>
                        <p class="text-sm text-slate-500">Enam catatan kas paling akhir.</p>
                    </div>
                </div>
                <a href="{{ route('kas.index') }}" class="shrink-0 text-sm font-semibold text-brand-700 hover:text-brand-800">Semua</a>
            </div>
            <ul class="divide-y divide-slate-100">
                @forelse ($transaksiTerbaru as $transaksi)
                    @php $masuk = $transaksi->jenis === \App\Models\TransaksiKas::JENIS_MASUK; @endphp
                    <li>
                        <a href="{{ route('kas.show', $transaksi) }}" class="flex items-center gap-3 px-5 py-3 text-sm hover:bg-slate-50">
                            <span @class([
                                'flex h-8 w-8 shrink-0 items-center justify-center rounded-lg',
                                'bg-brand-50 text-brand-700' => $masuk,
                                'bg-danger-50 text-danger-600' => ! $masuk,
                            ])>
                                <x-icon :name="$masuk ? 'naik' : 'turun'" class="h-4 w-4" />
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate font-medium text-slate-900">{{ $transaksi->kategori?->nama ?? '-' }}</span>
                                <span class="block text-xs text-slate-500">{{ tanggal_id($transaksi->tanggal) }} · {{ $transaksi->kode }}</span>
                            </span>
                            <span @class([
                                'shrink-0 font-semibold tabular-nums',
                                'text-brand-700' => $masuk,
                                'text-danger-600' => ! $masuk,
                            ])>{{ $masuk ? '+' : '−' }}{{ rupiah($transaksi->jumlah) }}</span>
                        </a>
                    </li>
                @empty
                    <li class="px-5 py-8 text-center text-sm text-slate-400">Belum ada transaksi kas.</li>
                @endforelse
            </ul>
        </section>
    </div>
</x-app-layout>
