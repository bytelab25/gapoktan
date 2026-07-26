@php $transaksi ??= null; @endphp

<div x-data="{ jenis: '{{ old('jenis', $transaksi?->jenis ?? 'masuk') }}' }" class="grid gap-5 md:grid-cols-2">
    <div class="md:col-span-2">
        <label for="kelompok_tani_id" class="label-field">Kelompok Tani</label>
        <select id="kelompok_tani_id" name="kelompok_tani_id" required class="input-field">
            <option value="">Pilih Nama Kelompok Tani</option>
            @foreach ($daftarKelompok as $kelompok)
                <option value="{{ $kelompok->id }}"
                    @selected(old('kelompok_tani_id', $transaksi?->kelompok_tani_id) == $kelompok->id)>
                    {{ $kelompok->nama_kelompok }}
                </option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('kelompok_tani_id')" class="mt-2" />
    </div>

    <div>
        <label for="tanggal" class="label-field">Tanggal</label>
        <input id="tanggal" name="tanggal" type="date" required
               value="{{ old('tanggal', optional($transaksi?->tanggal)->format('Y-m-d') ?? now()->format('Y-m-d')) }}"
               class="input-field">
        <x-input-error :messages="$errors->get('tanggal')" class="mt-2" />
    </div>

    <div>
        <label for="jumlah" class="label-field">Jumlah (Rp)</label>
        <div class="relative">
            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-sm font-semibold text-slate-400">Rp</span>
            <input id="jumlah" name="jumlah" type="number" min="1" step="0.01" required
                   value="{{ old('jumlah', $transaksi?->jumlah) }}" placeholder="0" class="input-field pl-11">
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

    {{-- Penerima hanya relevan untuk klaim santunan. --}}
    <div x-show="jenis === 'keluar'" x-cloak>
        <label for="penerima" class="label-field">Penerima</label>
        <input id="penerima" name="penerima" type="text" value="{{ old('penerima', $transaksi?->penerima) }}"
               placeholder="Masukkan nama anggota yang meninggal" class="input-field">
        <x-input-error :messages="$errors->get('penerima')" class="mt-2" />
    </div>

    <div class="md:col-span-2">
        <label for="deskripsi" class="label-field">Deskripsi</label>
        <textarea id="deskripsi" name="deskripsi" rows="4" placeholder="Masukkan deskripsi anda"
                  class="input-field">{{ old('deskripsi', $transaksi?->deskripsi) }}</textarea>
        <x-input-error :messages="$errors->get('deskripsi')" class="mt-2" />
    </div>
</div>
