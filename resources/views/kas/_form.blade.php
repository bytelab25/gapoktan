@php
    $transaksi ??= null;
    $persenBiaya = (float) $pengaturan->potongan_operasional;
@endphp

<div x-data="{
        jenis: '{{ old('jenis', $transaksi?->jenis ?? 'masuk') }}',
        jumlah: {{ (float) old('jumlah', $transaksi?->jumlah ?? 0) }},
        persenBiaya: {{ $persenBiaya }},
        get biaya() { return Math.round(this.jumlah * this.persenBiaya) / 100 },
        get dampak() { return this.jenis === 'masuk' ? this.jumlah : -this.jumlah },
        rupiah(nilai) {
            const angka = new Intl.NumberFormat('id-ID').format(Math.abs(Math.round(nilai)));
            return (nilai < 0 ? '-Rp ' : 'Rp ') + angka;
        },
     }">

    <div class="grid gap-5 md:grid-cols-2">
        <div>
            <label for="tanggal" class="label-field">Tanggal Transaksi</label>
            <input id="tanggal" name="tanggal" type="date" required
                   value="{{ old('tanggal', optional($transaksi?->tanggal)->format('Y-m-d') ?? now()->format('Y-m-d')) }}"
                   class="input-field">
            <x-input-error :messages="$errors->get('tanggal')" class="mt-2" />
        </div>

        <div>
            <label for="jumlah" class="label-field">Jumlah (IDR)</label>
            <div class="relative">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-sm font-semibold text-slate-400">Rp</span>
                <input id="jumlah" name="jumlah" type="number" min="1" step="0.01" required
                       x-model.number="jumlah" placeholder="50.000.000" class="input-field pl-11">
            </div>
            <x-input-error :messages="$errors->get('jumlah')" class="mt-2" />
        </div>

        <div>
            <span class="label-field">Jenis Transaksi</span>
            <div class="grid grid-cols-2 gap-3">
                @foreach ([
                    ['nilai' => 'masuk', 'label' => 'Masuk', 'ikon' => 'naik', 'warna' => 'text-brand-600'],
                    ['nilai' => 'keluar', 'label' => 'Keluar', 'ikon' => 'turun', 'warna' => 'text-danger-500'],
                ] as $opsi)
                    <label class="cursor-pointer">
                        <input type="radio" name="jenis" value="{{ $opsi['nilai'] }}" x-model="jenis" class="peer sr-only" required>
                        <span class="flex flex-col items-center gap-1 rounded-lg border-2 border-slate-200 py-3.5 text-sm font-semibold text-slate-600 transition peer-checked:border-brand-500 peer-checked:bg-brand-50 peer-checked:text-brand-700">
                            <x-icon :name="$opsi['ikon']" class="h-5 w-5 {{ $opsi['warna'] }}" />
                            {{ $opsi['label'] }}
                        </span>
                    </label>
                @endforeach
            </div>
            <x-input-error :messages="$errors->get('jenis')" class="mt-2" />
        </div>

        <div class="md:row-span-2">
            <label for="deskripsi" class="label-field">Deskripsi / Catatan</label>
            <textarea id="deskripsi" name="deskripsi" rows="6"
                      placeholder="Tambahkan deskripsi lengkap tentang transaksi..."
                      class="input-field">{{ old('deskripsi', $transaksi?->deskripsi) }}</textarea>
            <x-input-error :messages="$errors->get('deskripsi')" class="mt-2" />
        </div>

        <div>
            <label for="kategori_kas_id" class="label-field">Kategori</label>
            <select id="kategori_kas_id" name="kategori_kas_id" required class="input-field">
                <option value="">Pilih Kategori</option>
                @foreach ($daftarKategori as $kategori)
                    <option value="{{ $kategori->id }}"
                        @selected(old('kategori_kas_id', $transaksi?->kategori_kas_id) == $kategori->id)>
                        {{ $kategori->nama }}
                    </option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('kategori_kas_id')" class="mt-2" />

            <button type="button" x-data x-on:click="$dispatch('buka-kategori')" class="chip mt-2.5">
                <x-icon name="plus" class="h-3.5 w-3.5" />
                Tambah Kategori
            </button>
        </div>

        <div>
            <label for="kelompok_tani_id" class="label-field">
                Kelompok Tani <span class="font-normal text-slate-400">(opsional)</span>
            </label>
            <select id="kelompok_tani_id" name="kelompok_tani_id" class="input-field">
                <option value="">Tidak terkait kelompok</option>
                @foreach ($daftarKelompok as $kelompok)
                    <option value="{{ $kelompok->id }}"
                        @selected(old('kelompok_tani_id', $transaksi?->kelompok_tani_id) == $kelompok->id)>
                        {{ $kelompok->nama_kelompok }}
                    </option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('kelompok_tani_id')" class="mt-2" />
        </div>
    </div>

    {{-- Ringkasan yang ikut berubah saat nilai form diubah. --}}
    <div class="mt-8 grid gap-4 border-t border-slate-200 pt-6 sm:grid-cols-2 lg:max-w-2xl">
        <div class="rounded-lg bg-slate-50 p-4">
            <p class="text-xs text-slate-500">Biaya yang Dihitung ({{ $persenBiaya }}%)</p>
            <p class="mt-1 text-xl font-bold text-brand-600" x-text="rupiah(biaya)">Rp 0</p>
        </div>
        <div class="rounded-lg bg-slate-50 p-4">
            <p class="text-xs text-slate-500">Dampak Pada Saldo</p>
            <p class="mt-1 text-xl font-bold" x-text="rupiah(dampak)"
               x-bind:class="dampak < 0 ? 'text-danger-600' : 'text-slate-900'">Rp 0</p>
        </div>
    </div>
</div>

{{-- Modal tambah kategori --}}
<div x-data="{ buka: false }" x-on:buka-kategori.window="buka = true" x-cloak>
    <div x-show="buka" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-900/50" x-on:click="buka = false"></div>

        <div class="relative w-full max-w-md rounded-xl bg-white p-6 shadow-pop" x-show="buka" x-transition>
            <h3 class="text-lg font-semibold text-slate-900">Tambah Kategori Kas</h3>
            <p class="mt-1 text-sm text-slate-500">Kategori baru langsung bisa dipakai di transaksi berikutnya.</p>

            <div class="mt-5">
                <label for="nama_kategori_baru" class="label-field">Nama Kategori</label>
                <input id="nama_kategori_baru" type="text" form="form-kategori" name="nama" required
                       placeholder="cth: Hibah Pemerintah" class="input-field">
                <x-input-error :messages="$errors->get('nama')" class="mt-2" />
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <button type="button" x-on:click="buka = false" class="btn-ghost">Batalkan</button>
                <button type="submit" form="form-kategori" class="btn-primary">Simpan Kategori</button>
            </div>
        </div>
    </div>
</div>
