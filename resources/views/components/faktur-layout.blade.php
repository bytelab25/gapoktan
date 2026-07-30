<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <meta name="color-scheme" content="light">
    <style>html { background-color: #F8FAFC; }</style>

    <title>{{ $title ?? 'Faktur' }} — {{ config('app.name') }}</title>
    <link rel="icon" href="{{ asset('img/favicon.png') }}" sizes="any">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css'])
    <style>
        /* Sembunyikan kontrol layar saat dokumen dicetak. */
        @media print {
            .tanpa-cetak { display: none !important; }
            body { background: #fff; }
            .lembar { box-shadow: none; border: 0; margin: 0; max-width: none; }
        }
    </style>
</head>
<body class="font-sans">
    <div class="mx-auto max-w-3xl p-6 lg:p-10">
        <div class="tanpa-cetak mb-6 flex flex-wrap items-center justify-between gap-3">
            <a href="{{ $kembali ?? url()->previous() }}"
               class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-900">
                <x-icon name="kembali" class="h-4 w-4" />
                Kembali
            </a>

            <button type="button" onclick="window.print()" class="btn-primary">
                <x-icon name="cetak" class="h-4 w-4" />
                Cetak PDF
            </button>
        </div>

        <div class="lembar card p-8 lg:p-10">
            <div class="flex items-start justify-between gap-4 border-b border-slate-200 pb-6">
                <div class="flex items-center gap-3">
                    <img src="{{ asset('img/logo-256.png') }}" alt="" class="h-14 w-14 object-contain">
                    <div>
                        <p class="text-lg font-bold text-slate-900">Gapoktan Swagotra Arthagiri</p>
                        <p class="text-sm text-slate-500">Sistem Manajemen Koperasi</p>
                    </div>
                </div>

                <div class="text-right">
                    <p class="text-sm font-semibold uppercase tracking-wide text-brand-700">{{ $title ?? 'Faktur' }}</p>
                    <p class="mt-1 text-sm text-slate-500">Dicetak {{ tanggal_id(now(), 'd F Y') }}</p>
                </div>
            </div>

            {{ $slot }}
        </div>
    </div>
</body>
</html>
