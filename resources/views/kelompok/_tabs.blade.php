@props(['kelompok', 'aktif'])

@php
    $tabs = [
        'profil' => ['label' => 'Profil', 'rute' => route('kelompok.show', $kelompok)],
        'transaksi' => ['label' => 'Transaksi', 'rute' => route('kelompok.transaksi', $kelompok)],
    ];
@endphp

<div class="mb-6 flex gap-1 border-b border-slate-200">
    @foreach ($tabs as $kunci => $tab)
        <a href="{{ $tab['rute'] }}"
           @class([
               '-mb-px border-b-2 px-5 py-3 text-sm font-semibold transition',
               'border-brand-700 text-brand-700' => $aktif === $kunci,
               'border-transparent text-slate-500 hover:text-slate-900' => $aktif !== $kunci,
           ])>{{ $tab['label'] }}</a>
    @endforeach
</div>
