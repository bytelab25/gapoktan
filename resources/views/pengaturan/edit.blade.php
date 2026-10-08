<x-app-layout title="Pengaturan Sistem">
    <x-page-header judul="Pengaturan Sistem"
                   deskripsi="Persentase yang dipakai koperasi. Perubahan hanya berlaku untuk transaksi berikutnya." />

    @php($bolehSunting = auth()->user()->bisaKelolaPengaturan())

    @unless($bolehSunting)
        <p class="mb-5 text-sm text-slate-600">Pengaturan hanya dapat disunting oleh Ketua Gapoktan.</p>
    @endunless

    <form method="POST" action="{{ route('pengaturan.update') }}" class="card max-w-3xl p-6"
          @unless($bolehSunting) onsubmit="return false" @endunless
          x-data="{
              bunga: {{ (float) old('suku_bunga_pinjaman', $pengaturan->suku_bunga_pinjaman) }},
              operasional: {{ (float) old('potongan_operasional', $pengaturan->potongan_operasional) }},
              talangan: {{ (float) old('potongan_dana_talangan', $pengaturan->potongan_dana_talangan) }},
              get potonganMuka() { return Math.round((this.operasional + this.talangan) * 100) / 100 },
          }">
        @csrf
        @method('PUT')

        <div class="space-y-5">
            @foreach ([
                ['kunci' => 'suku_bunga_pinjaman', 'model' => 'bunga', 'label' => 'Suku Bunga Pinjaman',
                 'catatan' => 'Dihitung per bulan dari sisa pokok saat kelompok membayar.'],
                ['kunci' => 'potongan_operasional', 'model' => 'operasional', 'label' => 'Potongan Operasional',
                 'catatan' => 'Dipotong di muka saat pinjaman dicairkan, jadi pendapatan koperasi.'],
                ['kunci' => 'potongan_dana_talangan', 'model' => 'talangan', 'label' => 'Potongan Dana Talangan',
                 'catatan' => 'Dipotong di muka saat pencairan, masuk ke kas dana talangan.'],
            ] as $item)
                <div>
                    <label for="{{ $item['kunci'] }}" class="label-field">{{ $item['label'] }}</label>
                    <div class="relative max-w-xs">
                        <input id="{{ $item['kunci'] }}" name="{{ $item['kunci'] }}" type="number"
                               min="0" max="100" step="0.01" required @readonly(!$bolehSunting)
                               x-model.number="{{ $item['model'] }}" class="input-field pr-10">
                        <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-4 text-sm font-semibold text-slate-400">%</span>
                    </div>
                    <p class="mt-1.5 text-xs text-slate-500">{{ $item['catatan'] }}</p>
                    <x-input-error :messages="$errors->get($item['kunci'])" class="mt-2" />
                </div>
            @endforeach
        </div>

        <div class="mt-8 rounded-lg bg-brand-50 p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-brand-800">Total Potongan Muka Pinjaman</p>
            <p class="mt-1 text-2xl font-bold text-brand-700"><span x-text="potonganMuka">0</span>%</p>
            <p class="mt-1 text-xs text-brand-800/80">
                Gabungan potongan operasional dan dana talangan. Nominalnya dihitung otomatis
                di formulir pengajuan pinjaman.
            </p>
        </div>

        @can('kelola-pengaturan')
            <div class="mt-8 flex justify-end">
                <button type="submit" class="btn-primary">Simpan Pengaturan</button>
            </div>
        @endcan
    </form>
</x-app-layout>
