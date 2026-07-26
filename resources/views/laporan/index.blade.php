<x-app-layout title="Laporan Keuangan">
    <x-page-header judul="Laporan Keuangan"
                   deskripsi="Pilih jenis laporan dan periode, lalu cetak atau unduh hasilnya." />

    <form method="GET" action="{{ route('laporan.hasil') }}" class="card max-w-3xl p-6"
          x-data="{ jenis: '{{ old('jenis', 'kas') }}' }">

        <div class="space-y-5">
            <div>
                <label for="jenis" class="label-field">Jenis Laporan</label>
                <select id="jenis" name="jenis" x-model="jenis" required class="input-field">
                    @foreach ($daftarJenis as $nilai => $label)
                        <option value="{{ $nilai }}">{{ $label }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('jenis')" class="mt-2" />
            </div>

            {{-- Laporan akhir tahun dipilih per tahun, sisanya per rentang tanggal. --}}
            <div x-show="jenis !== 'akhir_tahun'">
                <span class="label-field">Rentang Tanggal</span>
                <div class="flex flex-wrap items-center gap-3">
                    <input type="date" name="dari" value="{{ old('dari', now()->startOfMonth()->format('Y-m-d')) }}"
                           class="input-field w-48">
                    <span class="text-slate-400">—</span>
                    <input type="date" name="sampai" value="{{ old('sampai', now()->format('Y-m-d')) }}"
                           class="input-field w-48">
                </div>
                <x-input-error :messages="$errors->get('dari')" class="mt-2" />
                <x-input-error :messages="$errors->get('sampai')" class="mt-2" />
            </div>

            <div x-show="jenis === 'akhir_tahun'" x-cloak>
                <label for="tahun" class="label-field">Tahun</label>
                <select id="tahun" name="tahun" class="input-field w-48">
                    @foreach ($daftarTahun as $tahun)
                        <option value="{{ $tahun }}">{{ $tahun }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('tahun')" class="mt-2" />
            </div>
        </div>

        <div class="mt-8 flex justify-end">
            <button type="submit" class="btn-primary">
                <x-icon name="laporan" class="h-4 w-4" />
                Buat Laporan
            </button>
        </div>
    </form>
</x-app-layout>
