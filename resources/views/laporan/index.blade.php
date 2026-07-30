<x-app-layout title="Laporan Keuangan">
    <h1 class="text-3xl font-bold text-slate-900">Laporan Keuangan</h1>

    {{-- Bar filter: rentang tanggal / tahun, jenis laporan, lalu tombol buat. --}}
    <form method="GET" action="{{ route('laporan.index') }}" class="card mt-6 p-5"
          x-data="{ jenis: '{{ $filter['jenis'] }}' }">
        <div class="flex flex-wrap items-center gap-x-5 gap-y-4">
            <div x-show="jenis !== 'akhir_tahun'" class="flex flex-wrap items-center gap-2">
                <span class="mr-1 text-sm font-semibold text-slate-500">Rentang Tanggal:</span>
                <input type="date" name="dari" value="{{ $filter['dari'] }}" class="input-field w-36 py-2 text-sm">
                <span class="text-slate-400">—</span>
                <input type="date" name="sampai" value="{{ $filter['sampai'] }}" class="input-field w-36 py-2 text-sm">
            </div>

            <div x-show="jenis === 'akhir_tahun'" x-cloak class="flex items-center gap-2">
                <label for="tahun" class="mr-1 text-sm font-semibold text-slate-500">Tahun:</label>
                <select id="tahun" name="tahun" class="input-field w-28 py-2 text-sm">
                    @foreach ($daftarTahun as $tahun)
                        <option value="{{ $tahun }}" @selected($filter['tahun'] == $tahun)>{{ $tahun }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-center gap-2">
                <label for="jenis" class="mr-1 text-sm font-semibold text-slate-500">Jenis Laporan:</label>
                <select id="jenis" name="jenis" x-model="jenis" class="input-field w-44 py-2 text-sm">
                    @foreach ($daftarJenis as $nilai => $label)
                        <option value="{{ $nilai }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <button type="submit" class="btn-primary ml-auto shrink-0">
                <x-icon name="laporan" class="h-4 w-4" />
                Buat Laporan
            </button>
        </div>

        @if ($errors->any())
            <div class="mt-4 space-y-1">
                @foreach ($errors->all() as $pesan)
                    <p class="text-sm text-danger-600">{{ $pesan }}</p>
                @endforeach
            </div>
        @endif
    </form>

    @if ($hasil)
        <div class="card mt-6 overflow-hidden">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 p-5">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">{{ $hasil['judul'] }}</h2>
                    <p class="text-sm text-slate-500">{{ $hasil['periode'] }}</p>
                </div>

                <a href="{{ route('laporan.unduh', request()->query()) }}" class="btn-ghost">
                    <x-icon name="unduh" class="h-4 w-4" />
                    Unduh Laporan
                </a>
            </div>

            @if (isset($hasil['ringkasan']))
                @include('laporan.hasil.ringkasan', ['ringkasan' => $hasil['ringkasan']])
            @endif

            @include('laporan.hasil.'.str_replace('_', '-', $hasil['jenis']), ['hasil' => $hasil])
        </div>
    @endif
</x-app-layout>
