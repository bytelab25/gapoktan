<div class="{{ $cetak ? '' : 'p-6' }} space-y-10">
    <section>
        <h3 class="border-b border-slate-200 pb-2 text-base font-bold uppercase tracking-wide text-slate-900">
            Posisi Aset
        </h3>

        <dl class="mt-4 divide-y divide-slate-100">
            @foreach ($hasil['aset'] as $label => $nilai)
                <div class="flex items-center justify-between gap-4 py-3">
                    <dt class="text-sm text-slate-600">{{ $label }}</dt>
                    <dd class="text-sm font-semibold text-slate-900">{{ rupiah($nilai) }}</dd>
                </div>
            @endforeach

            <div class="flex items-center justify-between gap-4 border-t-2 border-slate-900 py-3">
                <dt class="text-sm font-bold text-slate-900">Total Aset</dt>
                <dd class="text-lg font-bold text-brand-700">{{ rupiah($hasil['totalAset']) }}</dd>
            </div>
        </dl>
    </section>

    <section>
        <h3 class="border-b border-slate-200 pb-2 text-base font-bold uppercase tracking-wide text-slate-900">
            Perhitungan Hasil Usaha
        </h3>

        <p class="mt-4 text-sm font-semibold text-slate-700">Pendapatan:</p>
        <dl class="mt-2 divide-y divide-slate-100">
            @foreach ($hasil['pendapatan'] as $label => $nilai)
                <div class="flex items-center justify-between gap-4 py-3 pl-4">
                    <dt class="text-sm text-slate-600">{{ $label }}</dt>
                    <dd class="text-sm text-slate-900">{{ rupiah($nilai) }}</dd>
                </div>
            @endforeach
            <div class="flex items-center justify-between gap-4 py-3 pl-4">
                <dt class="text-sm font-semibold text-slate-700">Total Pendapatan</dt>
                <dd class="text-sm font-bold text-brand-700">{{ rupiah($hasil['totalPendapatan']) }}</dd>
            </div>
        </dl>

        <p class="mt-6 text-sm font-semibold text-slate-700">Pengeluaran:</p>
        <dl class="mt-2 divide-y divide-slate-100">
            @forelse ($hasil['pengeluaran'] as $label => $nilai)
                <div class="flex items-center justify-between gap-4 py-3 pl-4">
                    <dt class="text-sm text-slate-600">Beban {{ $label }}</dt>
                    <dd class="text-sm text-danger-600">({{ rupiah($nilai) }})</dd>
                </div>
            @empty
                <div class="py-3 pl-4">
                    <dt class="text-sm text-slate-400">Tidak ada beban tunai tercatat tahun ini.</dt>
                </div>
            @endforelse
            <div class="flex items-center justify-between gap-4 py-3 pl-4">
                <dt class="text-sm font-semibold text-slate-700">Total Pengeluaran</dt>
                <dd class="text-sm font-bold text-danger-600">({{ rupiah($hasil['totalPengeluaran']) }})</dd>
            </div>
        </dl>

        <div class="mt-6 flex items-center justify-between gap-4 rounded-lg bg-brand-50 px-5 py-4">
            <span class="text-sm font-bold uppercase tracking-wide text-brand-800">Sisa Hasil Usaha</span>
            <span class="text-2xl font-bold {{ $hasil['sisaHasilUsaha'] < 0 ? 'text-danger-600' : 'text-brand-700' }}">
                {{ rupiah_bertanda($hasil['sisaHasilUsaha']) }}
            </span>
        </div>
    </section>

    <section>
        <h3 class="border-b border-slate-200 pb-2 text-base font-bold uppercase tracking-wide text-slate-900">
            Catatan
        </h3>
        <p class="mt-3 text-sm leading-relaxed text-slate-600">
            Dana talangan dikelola sebagai dana amanah anggota, terpisah dari hasil usaha koperasi.
            Sepanjang {{ $hasil['tahun'] }} pos ini menerima kontribusi
            <span class="font-semibold text-slate-900">{{ rupiah($hasil['kontribusiTalangan']) }}</span>
            dan membayar klaim
            <span class="font-semibold text-slate-900">{{ rupiah($hasil['klaimTalangan']) }}</span>,
            sehingga saldonya kini <span class="font-semibold text-slate-900">{{ rupiah($hasil['saldoTalangan']) }}</span>.
            Keduanya tidak masuk perhitungan di atas.
        </p>
        <p class="mt-3 text-sm leading-relaxed text-slate-600">
            Yang diakui sebagai pendapatan hanyalah bunga pinjaman yang sudah benar-benar diterima
            dan porsi potongan operasional. Pencairan pinjaman tidak dicatat sebagai beban karena
            uangnya berubah bentuk menjadi piutang, bukan hilang.
        </p>
    </section>
</div>
