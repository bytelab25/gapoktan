@props([
    // Label sumbu X, mis. nama bulan.
    'label' => [],
    // list<array{nama: string, warna: string, nilai: list<float>}>; warna berupa nilai CSS.
    'seri' => [],
    // true = kolom bertumpuk (bagian dari satu total); false = berdampingan.
    'tumpuk' => false,
    'tinggi' => 220,
    'judulTabel' => 'Bulan',
])

@php
    $jumlahTitik = count($label);

    // Nilai tertinggi yang harus muat di sumbu Y.
    $puncak = 0.0;
    for ($i = 0; $i < $jumlahTitik; $i++) {
        $nilaiTitik = array_map(fn ($s) => (float) ($s['nilai'][$i] ?? 0), $seri);
        $puncak = max($puncak, $tumpuk ? array_sum($nilaiTitik) : max($nilaiTitik ?: [0]));
    }

    // Skala rapi: langkah 1/2/2,5/5 × 10^n supaya label sumbu bulat.
    $jumlahGaris = 4;
    if ($puncak <= 0) {
        $langkah = 1;
    } else {
        $kasar = $puncak / $jumlahGaris;
        $pangkat = 10 ** floor(log10($kasar));
        $langkah = collect([1, 2, 2.5, 5, 10])->map(fn ($k) => $k * $pangkat)->first(fn ($k) => $k >= $kasar);
    }
    $atas = $puncak <= 0 ? $jumlahGaris : $langkah * $jumlahGaris;
    $garis = collect(range(0, $jumlahGaris))->map(fn ($i) => $i * $langkah);

    $persen = fn (float $nilai) => $atas > 0 ? max(0, min(100, $nilai / $atas * 100)) : 0;
    $kosong = $puncak <= 0;
@endphp

<figure {{ $attributes->merge(['class' => 'grafik']) }}>
    @if (count($seri) > 1)
        {{-- Legenda selalu ada untuk dua seri atau lebih; warna tidak pernah jadi satu-satunya penanda. --}}
        <figcaption class="mb-4 flex flex-wrap gap-x-5 gap-y-1 text-xs text-slate-600">
            @foreach ($seri as $s)
                <span class="inline-flex items-center gap-1.5">
                    <span class="h-2.5 w-2.5 rounded-sm" style="background: {{ $s['warna'] }}"></span>
                    {{ $s['nama'] }}
                </span>
            @endforeach
        </figcaption>
    @endif

    <div class="flex">
        {{-- Sumbu Y --}}
        <div class="relative w-14 shrink-0 text-right text-[11px] tabular-nums text-slate-400" style="height: {{ $tinggi }}px">
            @foreach ($garis as $nilai)
                <span class="absolute right-2 translate-y-1/2 whitespace-nowrap leading-none" style="bottom: {{ $persen($nilai) }}%">
                    {{ $kosong ? '' : str_replace('Rp ', '', rupiah_ringkas($nilai)) }}
                </span>
            @endforeach
        </div>

        <div class="min-w-0 flex-1">
            {{-- Area plot --}}
            <div class="relative border-b border-slate-300" style="height: {{ $tinggi }}px">
                @foreach ($garis as $nilai)
                    @if ($nilai > 0)
                        <div class="absolute inset-x-0 border-t border-slate-100" style="bottom: {{ $persen($nilai) }}%"></div>
                    @endif
                @endforeach

                @if ($kosong)
                    <p class="absolute inset-0 flex items-center justify-center text-sm text-slate-400">
                        Belum ada data pada periode ini.
                    </p>
                @endif

                <div class="relative flex h-full items-end">
                    @for ($i = 0; $i < $jumlahTitik; $i++)
                        @php
                            $baris = collect($seri)->map(fn ($s) => [
                                'nama' => $s['nama'],
                                'warna' => $s['warna'],
                                'nilai' => (float) ($s['nilai'][$i] ?? 0),
                            ]);
                            $tooltip = [
                                'judul' => $label[$i],
                                'baris' => $baris->map(fn ($b) => [
                                    'nama' => $b['nama'],
                                    'warna' => $b['warna'],
                                    'nilai' => rupiah($b['nilai']),
                                ])->all(),
                            ];
                            if ($tumpuk && count($seri) > 1) {
                                $tooltip['total'] = rupiah($baris->sum('nilai'));
                            }
                            $ringkasAria = $label[$i].': '.$baris->map(fn ($b) => $b['nama'].' '.rupiah($b['nilai']))->implode(', ');
                            $teratas = $baris->keys()->last(fn ($k) => $baris[$k]['nilai'] > 0);
                        @endphp

                        {{-- Seluruh kolom bulan jadi area sentuh, bukan hanya batangnya. --}}
                        <div tabindex="0"
                             data-grafik="{{ json_encode($tooltip) }}"
                             aria-label="{{ $ringkasAria }}"
                             class="group flex h-full min-w-0 flex-1 cursor-default items-end justify-center rounded-t-md outline-none transition-colors hover:bg-slate-50 focus-visible:bg-slate-50 focus-visible:ring-2 focus-visible:ring-brand-500">
                            @if ($tumpuk)
                                <div class="flex h-full w-full max-w-6 flex-col-reverse gap-[2px]" style="width: 55%">
                                    @foreach ($baris as $k => $b)
                                        @if ($b['nilai'] > 0)
                                            <div @class(['w-full', 'rounded-t' => $k === $teratas])
                                                 style="height: {{ $persen($b['nilai']) }}%; background: {{ $b['warna'] }}"></div>
                                        @endif
                                    @endforeach
                                </div>
                            @else
                                <div class="flex h-full w-full items-end justify-center gap-[2px]">
                                    @foreach ($baris as $b)
                                        <div class="w-full max-w-4 rounded-t transition-opacity group-hover:opacity-85"
                                             style="height: {{ $persen($b['nilai']) }}%; background: {{ $b['warna'] }}; width: {{ 70 / max(1, count($seri)) }}%"></div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endfor
                </div>
            </div>

            {{-- Sumbu X. --}}
            <div class="mt-2 flex text-[11px] text-slate-400">
                @foreach ($label as $i => $teks)
                    {{-- Di layar sempit hanya tiap tiga label yang tampil (berakhir di titik terakhir)
                         supaya tidak ada label yang terpotong. --}}
                    <span @class([
                        'min-w-0 flex-1 whitespace-nowrap text-center',
                        'max-sm:invisible' => ($jumlahTitik - 1 - $i) % 3 !== 0,
                        'max-sm:text-right' => $i === $jumlahTitik - 1,
                    ])>{{ $teks }}</span>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Tampilan tabel: setiap angka tetap terbaca tanpa hover. --}}
    <details class="mt-4 text-sm">
        <summary class="cursor-pointer select-none text-xs font-semibold text-brand-700 hover:text-brand-800">Lihat tabel</summary>
        <div class="mt-2 overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="text-slate-500">
                    <tr class="border-b border-slate-200">
                        <th class="py-2 pr-4 font-semibold">{{ $judulTabel }}</th>
                        @foreach ($seri as $s)
                            <th class="py-2 pr-4 text-right font-semibold">{{ $s['nama'] }}</th>
                        @endforeach
                        @if ($tumpuk && count($seri) > 1)
                            <th class="py-2 text-right font-semibold">Total</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 tabular-nums text-slate-700">
                    @foreach ($label as $i => $teks)
                        <tr>
                            <td class="py-1.5 pr-4">{{ $teks }}</td>
                            @foreach ($seri as $s)
                                <td class="py-1.5 pr-4 text-right">{{ rupiah($s['nilai'][$i] ?? 0) }}</td>
                            @endforeach
                            @if ($tumpuk && count($seri) > 1)
                                <td class="py-1.5 text-right font-semibold">
                                    {{ rupiah(collect($seri)->sum(fn ($s) => (float) ($s['nilai'][$i] ?? 0))) }}
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </details>
</figure>
