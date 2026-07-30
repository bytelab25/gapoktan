@php
    $pengguna ??= null;
    $sedangSunting = (bool) $pengguna;
@endphp

<div class="grid gap-5 md:grid-cols-2">
    <div>
        <label for="name" class="label-field">Nama Pengguna</label>
        <input id="name" name="name" type="text" value="{{ old('name', $pengguna?->name) }}"
               placeholder="Masukkan Nama Pengguna" required class="input-field">
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>

    <div>
        <label for="username" class="label-field">Username</label>
        <input id="username" name="username" type="text" value="{{ old('username', $pengguna?->username) }}"
               placeholder="Masukkan Username" required class="input-field">
        <x-input-error :messages="$errors->get('username')" class="mt-2" />
    </div>

    <div>
        <label for="no_hp" class="label-field">Nomor HP</label>
        <input id="no_hp" name="no_hp" type="tel" value="{{ old('no_hp', $pengguna?->no_hp) }}"
               placeholder="cth: 0851xxxxxx" required class="input-field">
        <x-input-error :messages="$errors->get('no_hp')" class="mt-2" />
    </div>

    <div>
        <label for="role" class="label-field">Role</label>
        <select id="role" name="role" required class="input-field">
            <option value="">Pilih Role</option>
            @foreach (\App\Models\User::LABEL_ROLE as $nilai => $label)
                <option value="{{ $nilai }}" @selected(old('role', $pengguna?->role) === $nilai)>{{ $label }}</option>
            @endforeach
        </select>
        <p class="mt-1.5 text-xs text-slate-500">
            Sekretaris dan bendahara punya wewenang sama: akses penuh termasuk mengelola akun.
            Ketua mengelola data operasional dan pengaturan suku bunga. Pengguna hanya bisa melihat.
        </p>
        <x-input-error :messages="$errors->get('role')" class="mt-2" />
    </div>

    <div class="md:col-span-2">
        <label for="alamat" class="label-field">Alamat</label>
        <input id="alamat" name="alamat" type="text" value="{{ old('alamat', $pengguna?->alamat) }}"
               placeholder="Masukkan Alamat Pengguna" required class="input-field">
        <x-input-error :messages="$errors->get('alamat')" class="mt-2" />
    </div>

    <div>
        <label for="password" class="label-field">
            Password
            @if ($sedangSunting)
                <span class="font-normal text-slate-400">(kosongkan bila tidak diganti)</span>
            @endif
        </label>
        <input id="password" name="password" type="password" autocomplete="new-password"
               placeholder="Masukkan Password" @required(! $sedangSunting) class="input-field">
        <x-input-error :messages="$errors->get('password')" class="mt-2" />
    </div>

    <div>
        <label for="password_confirmation" class="label-field">Konfirmasi Password</label>
        <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password"
               placeholder="Ulangi Password" @required(! $sedangSunting) class="input-field">
    </div>
</div>
