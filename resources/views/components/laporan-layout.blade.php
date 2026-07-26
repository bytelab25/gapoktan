<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ $title ?? 'Laporan' }} — {{ config('app.name') }}</title>
    <link rel="icon" href="{{ asset('img/favicon.png') }}" sizes="any">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css'])
    <style>
        @media print {
            .tanpa-cetak { display: none !important; }
            body { background: #fff; }
            .lembar { box-shadow: none; border: 0; margin: 0; max-width: none; padding: 0; }
            thead { display: table-header-group; }
            tr { break-inside: avoid; }
        }
    </style>
</head>
<body class="font-sans">
    <div class="mx-auto max-w-5xl p-6 lg:p-10">
        <div class="lembar card p-8 lg:p-10">
            {{ $slot }}

            <div class="mt-12 flex justify-end">
                <div class="text-center">
                    <p class="text-sm text-slate-500">{{ tanggal_id(now(), 'd F Y') }}</p>
                    <p class="text-sm text-slate-500">Mengetahui,</p>
                    <div class="mt-16 w-56 border-t border-slate-300 pt-2">
                        <p class="text-sm font-semibold text-slate-900">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-slate-500">{{ auth()->user()->role }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
