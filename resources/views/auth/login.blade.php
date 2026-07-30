<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <meta name="color-scheme" content="light">
    <style>html { background-color: #F8FAFC; }</style>

    <title>Masuk — {{ config('app.name') }}</title>
    <link rel="icon" href="{{ asset('img/favicon.png') }}" sizes="any">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans">
    <div class="flex min-h-screen">
        {{-- Kiri: branding di atas foto sawah --}}
        <div class="relative hidden w-1/2 shrink-0 lg:block">
            <img src="{{ asset('img/login-img.png') }}" alt=""
                 class="absolute inset-0 h-full w-full object-cover">
            {{-- Gelapkan bagian bawah supaya teks putih tetap terbaca. --}}
            <div class="absolute inset-0 bg-gradient-to-t from-slate-950/85 via-slate-950/40 to-slate-950/20"></div>

            <div class="relative flex h-full flex-col justify-between p-10">
                <div class="flex items-center gap-3">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-white/95 p-1 shadow-pop">
                        <img src="{{ asset('img/logo-256.png') }}" alt="Logo Gapoktan Swagotra Arthagiri"
                             class="h-full w-full object-contain">
                    </div>
                    <span class="text-2xl font-bold text-white">Swagotra Arthagiri</span>
                </div>

                <div class="max-w-lg">
                    <h2 class="text-4xl font-bold leading-tight text-white">
                        Membangun Masa Depan Pertanian Indonesia yang Lebih Tangguh.
                    </h2>

                    <p class="mt-6 text-base leading-relaxed text-white/80">
                        Kami menghubungkan petani dengan teknologi dan akses finansial yang adil untuk
                        kemakmuran bersama melalui transparansi data dan tata kelola kooperatif yang modern.
                    </p>

                    <dl class="mt-8 flex gap-12">
                        <div>
                            <dt class="text-2xl font-bold text-white">{{ $jumlahKelompok }}</dt>
                            <dd class="text-sm text-white/70">Kelompok Tani</dd>
                        </div>
                        <div>
                            <dt class="text-2xl font-bold text-white">1</dt>
                            <dd class="text-sm text-white/70">Gabungan Kelompok Tani</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>

        {{-- Kanan: form masuk --}}
        <div class="flex w-full items-center justify-center bg-white px-6 py-12 lg:w-1/2">
            <div class="w-full max-w-md">
                {{-- Branding ringkas untuk layar kecil, karena panel kiri disembunyikan. --}}
                <div class="mb-10 flex items-center gap-3 lg:hidden">
                    <img src="{{ asset('img/logo-256.png') }}" alt="" class="h-12 w-12 object-contain">
                    <span class="text-xl font-bold text-slate-900">Swagotra Arthagiri</span>
                </div>

                <h1 class="text-3xl font-bold text-slate-900">Selamat Datang</h1>
                <p class="mt-2 text-slate-500">
                    Masukkan kredensial Anda untuk mengakses sistem manajemen kooperatif.
                </p>

                <x-auth-session-status class="mt-6" :status="session('status')" />

                <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-5" x-data="{ lihatSandi: false }">
                    @csrf

                    <div>
                        <label for="username" class="label-field">Username</label>
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.5 20.25a7.5 7.5 0 0 1 15 0v.75H4.5v-.75Z" />
                                </svg>
                            </span>
                            <input id="username" name="username" type="text" value="{{ old('username') }}"
                                   required autofocus autocomplete="username"
                                   placeholder="Masukkan username Anda"
                                   class="input-field py-3.5 pl-12">
                        </div>
                        <x-input-error :messages="$errors->get('username')" class="mt-2" />
                    </div>

                    <div>
                        <label for="password" class="label-field">Password</label>
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400">
                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                                </svg>
                            </span>
                            <input id="password" name="password" required autocomplete="current-password"
                                   placeholder="Masukkan password Anda"
                                   x-bind:type="lihatSandi ? 'text' : 'password'"
                                   class="input-field py-3.5 pl-12 pr-12">
                            <button type="button" x-on:click="lihatSandi = !lihatSandi"
                                    class="absolute inset-y-0 right-0 flex items-center pr-4 text-slate-400 hover:text-slate-600"
                                    x-bind:aria-label="lihatSandi ? 'Sembunyikan password' : 'Tampilkan password'">
                                <svg x-show="!lihatSandi" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                     stroke-width="1.8" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.964-7.178Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                </svg>
                                <svg x-show="lihatSandi" x-cloak class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                     stroke-width="1.8" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.774 3.162 10.066 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.244-4.244" />
                                </svg>
                            </button>
                        </div>
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>

                    <label for="remember_me" class="flex items-center gap-2.5">
                        <input id="remember_me" name="remember" type="checkbox"
                               class="rounded border-slate-300 text-brand-700 focus:ring-brand-500">
                        <span class="text-sm text-slate-600">Ingat saya di perangkat ini</span>
                    </label>

                    <button type="submit" class="btn-primary w-full py-4 text-base">
                        Masuk
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                        </svg>
                    </button>
                </form>

                <p class="mt-8 text-center text-sm text-slate-500">
                    Belum memiliki akun? <span class="font-semibold text-brand-700">Hubungi Ketua Gapoktan</span>
                </p>
            </div>
        </div>
    </div>
</body>
</html>
