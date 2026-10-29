@php
    // Riwayat singkat untuk dropdown History.
    $riwayatTerkini = auth()->user()->can('lihat-log')
        ? \App\Models\LogAktivitas::with('user')->latest()->limit(3)->get()
        : collect();
@endphp

<header class="sticky top-0 z-20 flex h-16 items-center gap-3 border-b border-slate-200 bg-white px-4 lg:px-6">
    <button type="button" x-on:click="sidebarTerbuka = true"
            class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 lg:hidden" aria-label="Buka menu">
        <x-icon name="beranda" class="h-5 w-5" />
    </button>

    <div class="ml-auto flex items-center gap-1">
        {{-- Pintasan buat data baru --}}
        @can('kelola')
            <div class="relative" x-data="{ buka: false }" x-on:click.outside="buka = false">
                <button type="button" x-on:click="buka = ! buka"
                        class="rounded-lg p-2.5 text-slate-500 transition hover:bg-slate-100 hover:text-slate-900"
                        aria-label="Buat data baru">
                    <x-icon name="plus" class="h-5 w-5" />
                </button>
                <div x-show="buka" x-cloak x-transition.origin.top.right
                     class="absolute right-0 mt-2 w-56 overflow-hidden rounded-xl border border-slate-200 bg-white py-1.5 shadow-pop">
                    <a href="{{ route('kelompok.create') }}" class="block px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50">Buat Kelompok Tani</a>
                    <a href="{{ route('pinjaman.create') }}" class="block px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50">Buat Pinjaman</a>
                    <a href="{{ route('pembayaran.pilih') }}" class="block px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50">Buat Pembayaran Pinjaman</a>
                </div>
            </div>
        @endcan

        @can('lihat-log')
            {{-- Riwayat aktivitas --}}
            <div class="relative" x-data="{ buka: false }" x-on:click.outside="buka = false">
                <button type="button" x-on:click="buka = ! buka"
                        class="rounded-lg p-2.5 text-slate-500 transition hover:bg-slate-100 hover:text-slate-900"
                        aria-label="Riwayat aktivitas">
                    <x-icon name="riwayat-dokumen" class="h-5 w-5" />
                </button>
                <div x-show="buka" x-cloak x-transition.origin.top.right
                     class="absolute right-0 mt-2 w-72 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-pop">
                    <p class="border-b border-slate-100 px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Aktivitas Terkini
                    </p>
                    @forelse ($riwayatTerkini as $log)
                        <div class="border-b border-slate-50 px-4 py-3">
                            <p class="text-sm font-medium text-slate-800">{{ $log->user?->name ?? 'Sistem' }}</p>
                            <p class="mt-0.5 text-xs text-slate-500">{{ $log->deskripsi }}</p>
                        </div>
                    @empty
                        <p class="px-4 py-6 text-center text-sm text-slate-400">Belum ada aktivitas.</p>
                    @endforelse
                    <a href="{{ route('log.index') }}" class="block px-4 py-3 text-center text-sm font-semibold text-brand-700 hover:bg-slate-50">
                        Lihat Selengkapnya
                    </a>
                </div>
            </div>
        @endcan

        @can('lihat-pengaturan')
            <a href="{{ route('pengaturan.edit') }}"
               class="rounded-lg p-2.5 text-slate-500 transition hover:bg-slate-100 hover:text-slate-900"
               aria-label="Pengaturan sistem">
                <x-icon name="pengaturan" class="h-5 w-5" />
            </a>
        @endcan

        <span class="mx-2 h-8 w-px bg-slate-200"></span>

        {{-- Profil pengguna --}}
        <div class="relative" x-data="{ buka: false }" x-on:click.outside="buka = false">
            <button type="button" x-on:click="buka = ! buka" class="flex items-center gap-3 rounded-lg py-1 pl-2 pr-1 hover:bg-slate-50">
                <span class="hidden text-right sm:block">
                    <span class="block text-sm font-semibold leading-tight text-slate-900">{{ auth()->user()->name }}</span>
                    <span class="block text-xs text-slate-500">{{ auth()->user()->label_role }}</span>
                </span>
                <span class="flex h-9 w-9 items-center justify-center rounded-full bg-brand-700 text-sm font-semibold text-white">
                    {{ auth()->user()->inisial }}
                </span>
            </button>
            <div x-show="buka" x-cloak x-transition.origin.top.right
                 class="absolute right-0 mt-2 w-48 overflow-hidden rounded-xl border border-slate-200 bg-white py-1.5 shadow-pop">
                <a href="{{ route('profil.show') }}" class="block px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50">Data Diri</a>
                @can('lihat-pengaturan')
                    <a href="{{ route('pengaturan.edit') }}" class="block px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50">Pengaturan Sistem</a>
                @endcan
                <form method="POST" action="{{ route('logout') }}" class="border-t border-slate-100" onsubmit="return konfirmasiKeluar(this)">
                    @csrf
                    <button type="submit" class="block w-full px-4 py-2.5 text-left text-sm text-danger-600 hover:bg-danger-50">
                        Keluar
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>
