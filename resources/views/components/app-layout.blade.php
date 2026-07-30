<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Beranda' }} — {{ config('app.name') }}</title>
    <link rel="icon" href="{{ asset('img/favicon.png') }}" sizes="any">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>[x-cloak] { display: none !important; }</style>
</head>
<body class="font-sans"
      @if (session('sukses')) data-sukses="{{ session('sukses') }}" @endif
      @if (session('gagal')) data-gagal="{{ session('gagal') }}" @endif>
<div x-data="{ sidebarTerbuka: false }" class="min-h-screen">
    @include('layouts.partials.sidebar')

    {{-- Latar gelap saat sidebar dibuka di layar kecil. --}}
    <div x-show="sidebarTerbuka" x-cloak x-on:click="sidebarTerbuka = false"
         class="fixed inset-0 z-30 bg-slate-900/40 lg:hidden"></div>

    <div class="lg:pl-sidebar">
        @include('layouts.partials.topbar')

        <main class="p-6 lg:p-8">
            {{-- Pesan hasil aksi tampil sebagai toast SweetAlert, lihat resources/js/app.js --}}
            {{ $slot }}
        </main>
    </div>
</div>
</body>
</html>
