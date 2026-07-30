<div class="grid gap-4 border-b border-slate-200 bg-slate-50/60 p-5 sm:grid-cols-2 lg:grid-cols-4">
    @foreach ($ringkasan as $kartu)
        <div class="rounded-lg border border-slate-200 bg-white p-4">
            <p class="text-xs text-slate-500">{{ $kartu['label'] }}</p>
            <p class="mt-1 text-base font-bold {{ $kartu['warna'] ?? 'text-slate-900' }}">{{ $kartu['nilai'] }}</p>
        </div>
    @endforeach
</div>
