@php $kelompok ??= null; @endphp

<div class="grid gap-5 md:grid-cols-2">
    <div class="md:col-span-2">
        <label for="nama_kelompok" class="label-field">Nama Kelompok Tani</label>
        <input id="nama_kelompok" name="nama_kelompok" type="text"
               value="{{ old('nama_kelompok', $kelompok?->nama_kelompok) }}"
               placeholder="Masukkan Nama Kelompok Tani" required class="input-field">
        <x-input-error :messages="$errors->get('nama_kelompok')" class="mt-2" />
    </div>

    <div>
        <label for="nama_ketua" class="label-field">Nama Ketua Kelompok Tani</label>
        <input id="nama_ketua" name="nama_ketua" type="text"
               value="{{ old('nama_ketua', $kelompok?->nama_ketua) }}"
               placeholder="Masukkan Nama Ketua Kelompok Tani" required class="input-field">
        <x-input-error :messages="$errors->get('nama_ketua')" class="mt-2" />
    </div>

    <div>
        <label for="nik_ketua" class="label-field">NIK Ketua Kelompok Tani</label>
        <input id="nik_ketua" name="nik_ketua" type="text" inputmode="numeric" maxlength="16"
               value="{{ old('nik_ketua', $kelompok?->nik_ketua) }}"
               placeholder="16 digit NIK" required class="input-field">
        <x-input-error :messages="$errors->get('nik_ketua')" class="mt-2" />
    </div>

    <div>
        <label for="no_hp" class="label-field">Nomor HP</label>
        <input id="no_hp" name="no_hp" type="tel"
               value="{{ old('no_hp', $kelompok?->no_hp) }}"
               placeholder="cth: 0851xxxxxxx" required class="input-field">
        <x-input-error :messages="$errors->get('no_hp')" class="mt-2" />
    </div>

    <div>
        <label for="alamat" class="label-field">Alamat</label>
        <input id="alamat" name="alamat" type="text"
               value="{{ old('alamat', $kelompok?->alamat) }}"
               placeholder="Masukkan Alamat Ketua Kelompok Tani" required class="input-field">
        <x-input-error :messages="$errors->get('alamat')" class="mt-2" />
    </div>
</div>
