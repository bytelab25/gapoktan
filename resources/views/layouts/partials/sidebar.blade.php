@php
    $menu = collect([
        ['rute' => 'beranda',       'aktif' => 'beranda*',        'ikon' => 'beranda',   'label' => 'Beranda'],
        ['rute' => 'pengguna.index','aktif' => 'pengguna.*',      'ikon' => 'pengguna',  'label' => 'Pengguna',
         'izin' => 'lihat-pengurus'],
        ['rute' => 'kas.index',     'aktif' => 'kas.*',           'ikon' => 'kas',       'label' => 'Kas'],
        ['rute' => 'pinjaman.index','aktif' => 'pinjaman.*|pembayaran.*', 'ikon' => 'pinjaman', 'label' => 'Pinjaman Kelompok'],
        ['rute' => 'talangan.index','aktif' => 'talangan.*',      'ikon' => 'talangan',  'label' => 'Dana Talangan'],
        ['rute' => 'kelompok.index','aktif' => 'kelompok.*',      'ikon' => 'kelompok',  'label' => 'Kelompok Tani',
         'izin' => 'lihat-pengurus'],
        ['rute' => 'laporan.index', 'aktif' => 'laporan.*',       'ikon' => 'laporan',   'label' => 'Laporan Keuangan'],
    ])->filter(fn (array $item) => ! isset($item['izin']) || Gate::allows($item['izin']));
@endphp

<aside x-bind:class="sidebarTerbuka ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
       class="fixed inset-y-0 left-0 z-40 flex w-sidebar flex-col border-r border-slate-200 bg-white transition-transform duration-200">

    <a href="{{ route('beranda') }}" class="flex items-center gap-3 border-b border-slate-200 px-5 py-4">
        <img src="{{ asset('img/logo-256.png') }}" alt="" class="h-11 w-11 shrink-0 object-contain">
        <span class="min-w-0">
            <span class="block truncate text-[15px] font-bold leading-tight text-slate-900">Swagotra Arthagiri</span>
            <span class="block text-xs text-slate-500">Sistem Manajemen</span>
        </span>
    </a>

    <nav class="flex-1 overflow-y-auto py-4">
        @foreach ($menu as $item)
            @php $sedangAktif = request()->routeIs(explode('|', $item['aktif'])); @endphp
            <a href="{{ route($item['rute']) }}"
               @class([
                   'relative flex items-center gap-3 px-5 py-3 text-sm transition',
                   'bg-brand-50 font-semibold text-brand-700' => $sedangAktif,
                   'text-slate-600 hover:bg-slate-50 hover:text-slate-900' => ! $sedangAktif,
               ])
               @if ($sedangAktif) aria-current="page" @endif>
                @if ($sedangAktif)
                    <span class="absolute inset-y-0 left-0 w-1 bg-brand-700"></span>
                @endif
                <x-icon :name="$item['ikon']" class="h-5 w-5 shrink-0" />
                {{ $item['label'] }}
            </a>
        @endforeach
    </nav>

    <div class="border-t border-slate-200 p-4">
        <form method="POST" action="{{ route('logout') }}" onsubmit="return konfirmasiKeluar(this)">
            @csrf
            <button type="submit"
                    class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm text-slate-600 transition hover:bg-danger-50 hover:text-danger-600">
                <x-icon name="keluar" class="h-5 w-5" />
                Keluar
            </button>
        </form>
    </div>
</aside>
