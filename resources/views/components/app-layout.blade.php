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
<body class="font-sans">
<div x-data="{ sidebarTerbuka: false }" class="min-h-screen">
    @include('layouts.partials.sidebar')

    {{-- Latar gelap saat sidebar dibuka di layar kecil. --}}
    <div x-show="sidebarTerbuka" x-cloak x-on:click="sidebarTerbuka = false"
         class="fixed inset-0 z-30 bg-slate-900/40 lg:hidden"></div>

    <div class="lg:pl-sidebar">
        @include('layouts.partials.topbar')

        <main class="p-6 lg:p-8">
            @if (session('sukses'))
                <div class="mb-6 flex items-start gap-3 rounded-lg border border-brand-200 bg-brand-50 p-4 text-sm text-brand-800">
                    <x-icon name="centang" class="mt-0.5 h-5 w-5 shrink-0 text-brand-600" />
                    <span>{{ session('sukses') }}</span>
                </div>
            @endif

            @if (session('gagal'))
                <div class="mb-6 flex items-start gap-3 rounded-lg border border-danger-100 bg-danger-50 p-4 text-sm text-danger-700">
                    <x-icon name="peringatan" class="mt-0.5 h-5 w-5 shrink-0 text-danger-500" />
                    <span>{{ session('gagal') }}</span>
                </div>
            @endif

            {{ $slot }}
        </main>
    </div>
</div>
</body>
</html>
