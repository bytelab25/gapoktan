@props(['status'])

@php
    $gaya = match ($status) {
        \App\Models\Pinjaman::STATUS_LUNAS => 'bg-brand-50 text-brand-700',
        \App\Models\Pinjaman::STATUS_MENUNGGAK => 'bg-warn-50 text-warn-700',
        default => 'bg-slate-100 text-slate-600',
    };
@endphp

<span class="badge {{ $gaya }}">{{ \App\Models\Pinjaman::LABEL_STATUS[$status] ?? $status }}</span>
