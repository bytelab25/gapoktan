<x-laporan-layout :title="$judul">
    <x-laporan-kop :judul="$judul" :periode="'Tahun Buku ' . $tahun" />

    <section>
        <h2 class="border-b border-slate-200 pb-2 text-base font-bold uppercase tracking-wide text-slate-900">
            Posisi Aset
        </h2>

        <dl class="mt-4 divide-y divide-slate-100">
            @foreach ([
                'Saldo Kas' => $saldoKas,
                'Saldo Dana Talangan' => $saldoTalangan,
                'Total Pinjaman Beredar' => $totalPinjamanBeredar,
            ] as $label => $nilai)
                <div class="flex items-center justify-between gap-4 py-3">
                    <dt class="text-sm text-slate-600">{{ $label }}</dt>
                    <dd class="text-sm font-semibold text-slate-900">{{ rupiah($nilai) }}</dd>
                </div>
            @endforeach

            <div class="flex items-center justify-between gap-4 border-t-2 border-slate-900 py-3">
                <dt class="text-sm font-bold text-slate-900">Total Aset</dt>
                <dd class="text-lg font-bold text-brand-700">
                    {{ rupiah($saldoKas + $saldoTalangan + $totalPinjamanBeredar) }}
                </dd>
            </div>
        </dl>
    </section>

    <section class="mt-10">
        <h2 class="border-b border-slate-200 pb-2 text-base font-bold uppercase tracking-wide text-slate-900">
            Perhitungan Hasil Usaha
        </h2>

        <p class="mt-4 text-sm font-semibold text-slate-700">Pendapatan:</p>
        <dl class="mt-2 divide-y divide-slate-100">
            @foreach ([
                'Pendapatan Bunga Pinjaman' => $pendapatanBunga,
                'Pendapatan Potongan Muka' => $pendapatanPotongan,
            ] as $label => $nilai)
                <div class="flex items-center justify-between gap-4 py-3 pl-4">
                    <dt class="text-sm text-slate-600">{{ $label }}</dt>
                    <dd class="text-sm text-slate-900">{{ rupiah($nilai) }}</dd>
                </div>
            @endforeach
            <div class="flex items-center justify-between gap-4 py-3 pl-4">
                <dt class="text-sm font-semibold text-slate-700">Total Pendapatan</dt>
                <dd class="text-sm font-bold text-brand-700">{{ rupiah($totalPendapatan) }}</dd>
            </div>
        </dl>

        <p class="mt-6 text-sm font-semibold text-slate-700">Pengeluaran:</p>
        <dl class="mt-2 divide-y divide-slate-100">
            @foreach ([
                'Klaim Dana Talangan' => $klaimTalangan,
                'Biaya Operasional' => $biayaOperasional,
            ] as $label => $nilai)
                <div class="flex items-center justify-between gap-4 py-3 pl-4">
                    <dt class="text-sm text-slate-600">{{ $label }}</dt>
                    <dd class="text-sm text-danger-600">({{ rupiah($nilai) }})</dd>
                </div>
            @endforeach
            <div class="flex items-center justify-between gap-4 py-3 pl-4">
                <dt class="text-sm font-semibold text-slate-700">Total Pengeluaran</dt>
                <dd class="text-sm font-bold text-danger-600">({{ rupiah($totalPengeluaran) }})</dd>
            </div>
        </dl>

        <div class="mt-6 flex items-center justify-between gap-4 rounded-lg bg-brand-50 px-5 py-4">
            <span class="text-sm font-bold uppercase tracking-wide text-brand-800">Sisa Hasil Usaha</span>
            <span class="text-2xl font-bold {{ $sisaHasilUsaha < 0 ? 'text-danger-600' : 'text-brand-700' }}">
                {{ rupiah_bertanda($sisaHasilUsaha) }}
            </span>
        </div>
    </section>

    <section class="mt-10">
        <h2 class="border-b border-slate-200 pb-2 text-base font-bold uppercase tracking-wide text-slate-900">
            Catatan
        </h2>
        <p class="mt-3 text-sm leading-relaxed text-slate-600">
            Kontribusi dana talangan yang terkumpul sepanjang {{ $tahun }} sebesar
            <span class="font-semibold text-slate-900">{{ rupiah($kontribusiTalangan) }}</span>
            dicatat sebagai dana amanah anggota, bukan pendapatan koperasi, sehingga tidak
            masuk dalam perhitungan hasil usaha di atas.
        </p>
    </section>
</x-laporan-layout>
