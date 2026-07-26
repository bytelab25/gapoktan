<x-app-layout title="Sunting Data Diri">
    <x-page-header judul="Sunting Data Diri"
                   deskripsi="Perbarui informasi akun Anda."
                   :kembali="route('profil.show')" />

    <form method="POST" action="{{ route('profil.update') }}" class="card max-w-4xl p-6">
        @csrf
        @method('PUT')

        <div class="grid gap-5 md:grid-cols-2">
            <div>
                <label for="name" class="label-field">Nama Pengguna</label>
                <input id="name" name="name" type="text" value="{{ old('name', $pengguna->name) }}"
                       required class="input-field">
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>

            <div>
                <label for="username" class="label-field">Username</label>
                <input id="username" name="username" type="text" value="{{ old('username', $pengguna->username) }}"
                       required class="input-field">
                <x-input-error :messages="$errors->get('username')" class="mt-2" />
            </div>

            <div>
                <label for="no_hp" class="label-field">Nomor HP</label>
                <input id="no_hp" name="no_hp" type="tel" value="{{ old('no_hp', $pengguna->no_hp) }}"
                       required class="input-field">
                <x-input-error :messages="$errors->get('no_hp')" class="mt-2" />
            </div>

            <div>
                <label class="label-field">Role</label>
                <input type="text" value="{{ $pengguna->role }}" readonly
                       class="input-field cursor-not-allowed bg-slate-100 text-slate-500">
                <p class="mt-1.5 text-xs text-slate-500">Role hanya bisa diubah lewat modul Pengguna.</p>
            </div>

            <div class="md:col-span-2">
                <label for="alamat" class="label-field">Alamat</label>
                <input id="alamat" name="alamat" type="text" value="{{ old('alamat', $pengguna->alamat) }}"
                       required class="input-field">
                <x-input-error :messages="$errors->get('alamat')" class="mt-2" />
            </div>

            <div>
                <label for="password" class="label-field">
                    Password <span class="font-normal text-slate-400">(kosongkan bila tidak diganti)</span>
                </label>
                <input id="password" name="password" type="password" autocomplete="new-password" class="input-field">
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <div>
                <label for="password_confirmation" class="label-field">Konfirmasi Password</label>
                <input id="password_confirmation" name="password_confirmation" type="password"
                       autocomplete="new-password" class="input-field">
            </div>
        </div>

        <div class="mt-8 flex justify-end gap-3">
            <a href="{{ route('profil.show') }}" class="btn-danger">Batalkan</a>
            <button type="submit" class="btn-primary">Simpan Perubahan</button>
        </div>
    </form>
</x-app-layout>
