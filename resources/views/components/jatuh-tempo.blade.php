@props(['pinjaman', 'format' => null, 'sebaris' => false])

@php
    $tanggal = $format ? tanggal_id($pinjaman->tanggal_jatuh_tempo, $format) : tanggal_id($pinjaman->tanggal_jatuh_tempo);
    $lewat = $pinjaman->lewatJatuhTempo();
@endphp

@if ($lewat)
    <span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 font-semibold text-danger-700']) }}
          title="Lewat jatuh tempo {{ $pinjaman->hariLewatJatuhTempo() }} hari">
        <x-icon name="segitiga-peringatan" class="h-4 w-4 shrink-0" />
        {{ $tanggal }}
        @if ($sebaris)
            <span class="font-medium">(lewat {{ $pinjaman->hariLewatJatuhTempo() }} hari)</span>
        @endif
    </span>
    @unless ($sebaris)
        <span class="block text-xs font-medium text-danger-600">Lewat {{ $pinjaman->hariLewatJatuhTempo() }} hari</span>
    @endunless
@else
    <span {{ $attributes->merge(['class' => 'text-slate-600']) }}>{{ $tanggal }}</span>
@endif
