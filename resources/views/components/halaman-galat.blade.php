@props(['kode', 'judul', 'pesan'])

{{-- Halaman galat mandiri: tidak memakai layout aplikasi karena bisa tampil
     untuk pengunjung yang belum login atau saat sesi sudah habis. --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <style>html { background-color: #F8FAFC; }</style>

    <title>{{ $judul }} — {{ config('app.name') }}</title>
    <link rel="icon" href="{{ asset('img/favicon.png') }}" sizes="any">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css'])
</head>
<body class="font-sans">
    <main class="flex min-h-screen items-center justify-center p-6">
        <div class="card w-full max-w-md p-8 text-center">
            <img src="{{ asset('img/logo-256.png') }}" alt="" class="mx-auto h-14 w-14 object-contain">

            <p class="mt-6 text-5xl font-bold tracking-tight text-brand-700">{{ $kode }}</p>
            <h1 class="mt-3 text-xl font-bold text-slate-900">{{ $judul }}</h1>
            <p class="mt-2 text-sm leading-relaxed text-slate-500">{{ $pesan }}</p>

            <div class="mt-8 flex flex-wrap justify-center gap-3">
                @auth
                    <a href="{{ route('beranda') }}" class="btn-primary">Ke Beranda</a>
                @else
                    <a href="{{ route('login') }}" class="btn-primary">Masuk</a>
                @endauth
                <button type="button" onclick="history.back()" class="btn-ghost">Kembali</button>
            </div>
        </div>
    </main>
</body>
</html>
