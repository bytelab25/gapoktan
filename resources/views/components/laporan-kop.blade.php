@props(['judul', 'periode'])

<div class="tanpa-cetak mb-6 flex flex-wrap items-center justify-between gap-3">
    <a href="{{ route('laporan.index') }}" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-900">
        <x-icon name="kembali" class="h-4 w-4" />
        Kembali
    </a>

    <button type="button" onclick="window.print()" class="btn-primary">
        <x-icon name="unduh" class="h-4 w-4" />
        Unduh Laporan
    </button>
</div>

<div class="mb-8 border-b-2 border-slate-900 pb-5 text-center">
    <div class="flex items-center justify-center gap-3">
        <img src="{{ asset('img/logo-256.png') }}" alt="" class="h-14 w-14 object-contain">
        <div class="text-left">
            <p class="text-lg font-bold uppercase tracking-wide text-slate-900">Gapoktan Swagotra Arthagiri</p>
            <p class="text-sm text-slate-500">Sistem Manajemen Koperasi</p>
        </div>
    </div>

    <h1 class="mt-5 text-xl font-bold uppercase tracking-wide text-slate-900">{{ $judul }}</h1>
    <p class="mt-1 text-sm text-slate-500">{{ $periode }}</p>
</div>
