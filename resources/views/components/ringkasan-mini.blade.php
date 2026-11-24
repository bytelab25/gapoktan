@props([
    // list<array{label: string, nilai: string, catatan?: string, warna?: string}>
    'isi' => [],
])

{{-- Angka kunci di kepala kartu grafik. Warna seri hanya pada penanda kecil,
     teksnya tetap abu/hitam supaya terbaca. --}}
<dl {{ $attributes->merge(['class' => 'grid gap-3']) }}>
    @foreach ($isi as $item)
        <div class="rounded-lg bg-slate-50 px-3 py-2.5">
            <dt class="flex items-center gap-1.5 text-xs text-slate-500">
                @isset($item['warna'])
                    <span class="h-2 w-2 shrink-0 rounded-sm" style="background: {{ $item['warna'] }}"></span>
                @endisset
                {{ $item['label'] }}
            </dt>
            <dd class="mt-0.5 whitespace-nowrap text-base font-semibold text-slate-900">{{ $item['nilai'] }}</dd>
            @isset($item['catatan'])
                <dd class="text-xs text-slate-500">{{ $item['catatan'] }}</dd>
            @endisset
        </div>
    @endforeach
</dl>
