@props(['judul', 'deskripsi' => null, 'kembali' => null])

<div class="mb-6 flex flex-wrap items-start justify-between gap-4">
    <div>
        @if ($kembali)
            <a href="{{ $kembali }}" class="mb-2 inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-900">
                <x-icon name="kembali" class="h-4 w-4" />
                Kembali
            </a>
        @endif

        <h1 class="text-3xl font-bold tracking-tight text-slate-900">{{ $judul }}</h1>

        @if ($deskripsi)
            <p class="mt-1 text-slate-500">{{ $deskripsi }}</p>
        @endif
    </div>

    @isset($aksi)
        <div class="flex items-center gap-2">{{ $aksi }}</div>
    @endisset
</div>
