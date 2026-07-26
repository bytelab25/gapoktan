@props(['label', 'nilai', 'ikon' => null, 'warna' => 'brand'])

@php
    $warnaIkon = [
        'brand' => 'bg-brand-50 text-brand-600',
        'biru' => 'bg-sky-50 text-sky-600',
        'kuning' => 'bg-amber-50 text-amber-600',
        'merah' => 'bg-danger-50 text-danger-500',
    ][$warna] ?? 'bg-brand-50 text-brand-600';

    $warnaNilai = $warna === 'brand' ? 'text-brand-600' : 'text-slate-900';
@endphp

<div {{ $attributes->merge(['class' => 'card p-5']) }}>
    <div class="flex items-start justify-between gap-3">
        <p class="text-[11px] font-semibold uppercase leading-relaxed tracking-wider text-slate-500">{{ $label }}</p>
        @if ($ikon)
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg {{ $warnaIkon }}">
                <x-icon :name="$ikon" class="h-5 w-5" />
            </span>
        @endif
    </div>

    <p class="mt-3 text-2xl font-bold {{ $warnaNilai }}">{{ $nilai }}</p>

    @isset($catatan)
        <p class="mt-1 text-xs text-slate-500">{{ $catatan }}</p>
    @endisset
</div>
