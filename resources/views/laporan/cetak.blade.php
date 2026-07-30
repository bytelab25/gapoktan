<x-laporan-layout :title="$hasil['judul']">
    <x-laporan-kop :judul="$hasil['judul']" :periode="$hasil['periode']" />

    @if (isset($hasil['ringkasan']))
        <div class="mb-6 grid gap-4 sm:grid-cols-4">
            @foreach ($hasil['ringkasan'] as $kartu)
                <div class="rounded-lg bg-slate-50 p-4">
                    <p class="text-xs text-slate-500">{{ $kartu['label'] }}</p>
                    <p class="mt-1 text-base font-bold {{ $kartu['warna'] ?? 'text-slate-900' }}">{{ $kartu['nilai'] }}</p>
                </div>
            @endforeach
        </div>
    @endif

    @include('laporan.hasil.'.str_replace('_', '-', $hasil['jenis']), ['hasil' => $hasil, 'cetak' => true])
</x-laporan-layout>
