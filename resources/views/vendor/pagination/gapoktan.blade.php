@php
    $kotak = 'flex h-9 min-w-9 items-center justify-center rounded-lg border px-3 text-sm transition';
@endphp

@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Navigasi halaman" class="flex flex-wrap items-center gap-2">
        @if ($paginator->onFirstPage())
            <span class="{{ $kotak }} border-slate-200 text-slate-300" aria-disabled="true">
                <x-icon name="chevron-kiri" class="h-4 w-4" />
            </span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Halaman sebelumnya"
               class="{{ $kotak }} border-slate-200 text-slate-500 hover:bg-slate-50 hover:text-slate-900">
                <x-icon name="chevron-kiri" class="h-4 w-4" />
            </a>
        @endif

        @foreach ($elements as $elemen)
            @if (is_string($elemen))
                <span class="{{ $kotak }} border-transparent text-slate-400">{{ $elemen }}</span>
            @endif

            @if (is_array($elemen))
                @foreach ($elemen as $halaman => $url)
                    @if ($halaman == $paginator->currentPage())
                        <span aria-current="page"
                              class="{{ $kotak }} border-brand-700 bg-brand-700 font-semibold text-white">{{ $halaman }}</span>
                    @else
                        <a href="{{ $url }}"
                           class="{{ $kotak }} border-slate-200 text-slate-600 hover:bg-slate-50 hover:text-slate-900">{{ $halaman }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Halaman berikutnya"
               class="{{ $kotak }} border-slate-200 text-slate-500 hover:bg-slate-50 hover:text-slate-900">
                <x-icon name="chevron-kanan" class="h-4 w-4" />
            </a>
        @else
            <span class="{{ $kotak }} border-slate-200 text-slate-300" aria-disabled="true">
                <x-icon name="chevron-kanan" class="h-4 w-4" />
            </span>
        @endif
    </nav>
@endif
