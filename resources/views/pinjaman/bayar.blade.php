<x-app-layout title="Bayar Pinjaman">
    <x-page-header judul="Bayar Pinjaman"
                   :deskripsi="$pinjaman->kelompokTani->nama_kelompok . ' — ' . $pinjaman->kode"
                   :kembali="route('pinjaman.show', $pinjaman)" />

    <div class="grid gap-6 lg:grid-cols-5">
        <div class="lg:col-span-3">
            <div class="card overflow-hidden">
                <div class="border-b border-slate-200 p-5">
                    <h2 class="text-lg font-semibold text-slate-900">Rincian Pinjaman</h2>
                </div>

                <dl class="divide-y divide-slate-100 px-5">
                    @foreach ([
                        'Tanggal Pencairan' => tanggal_id($pinjaman->tanggal_transaksi, 'd F Y'),
                        'Tanggal Jatuh Tempo' => tanggal_id($pinjaman->tanggal_jatuh_tempo, 'd F Y'),
                        'Pinjaman Pokok' => rupiah($pinjaman->jumlah_pinjaman),
                        'Sudah Dibayar (pokok)' => rupiah($pinjaman->jumlah_pinjaman - $pinjaman->sisa_pokok),
                        'Bunga Sudah Dibayar' => rupiah($pinjaman->total_bunga_dibayar),
                        'Bunga Terakhir Dihitung' => tanggal_id($pinjaman->mulai_bunga, 'd F Y'),
                    ] as $label => $nilai)
                        <div class="flex items-center justify-between gap-4 py-3.5">
                            <dt class="text-sm text-slate-500">{{ $label }}</dt>
                            <dd class="text-sm font-medium text-slate-900">{{ $nilai }}</dd>
                        </div>
                    @endforeach

                    <div class="flex items-center justify-between gap-4 py-4">
                        <dt class="text-sm font-semibold text-slate-700">Sisa Hutang Pokok</dt>
                        <dd class="text-xl font-bold text-danger-600">{{ rupiah($pinjaman->sisa_pokok) }}</dd>
                    </div>
                </dl>
            </div>
        </div>

        <form method="POST" action="{{ route('pinjaman.bayar.store', $pinjaman) }}" class="card p-6 lg:col-span-2"
              x-data="{
                  pokok: {{ (float) old('bayar_pokok', 0) }},
                  bunga: {{ $bungaBerjalan }},
                  sisa: {{ $pinjaman->sisa_pokok }},
                  get total() { return (this.pokok || 0) + this.bunga },
                  get sisaSetelah() { return this.sisa - (this.pokok || 0) },
                  rupiah(nilai) { return 'Rp ' + new Intl.NumberFormat('id-ID').format(Math.round(nilai || 0)) },
              }">
            @csrf

            <h2 class="text-lg font-semibold text-slate-900">Form Pembayaran</h2>

            <div class="mt-5 space-y-5">
                <div>
                    <label for="tanggal" class="label-field">Tanggal</label>
                    <input id="tanggal" name="tanggal" type="date" required
                           value="{{ old('tanggal', now()->format('Y-m-d')) }}" class="input-field">
                    <x-input-error :messages="$errors->get('tanggal')" class="mt-2" />
                </div>

                <div class="rounded-lg border border-warn-100 bg-warn-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-warn-700">
                        Bunga {{ (float) $pinjaman->bunga_persen }}% / bulan
                        (Akumulasi {{ $bulanBerjalan }} Bulan)
                    </p>
                    <p class="mt-1 text-xl font-bold text-warn-700">{{ rupiah($bungaBerjalan) }}</p>
                    <p class="mt-1 text-xs text-warn-700/80">
                        Dihitung dari sisa pokok {{ rupiah($pinjaman->sisa_pokok) }}. Wajib dilunasi bersama pembayaran ini.
                    </p>
                </div>

                <div>
                    <label for="bayar_pokok" class="label-field">Bayar Pinjaman Pokok</label>
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-sm font-semibold text-slate-400">Rp</span>
                        <input id="bayar_pokok" name="bayar_pokok" type="number" min="0" step="0.01" required
                               x-model.number="pokok" placeholder="Masukkan Total Bayar" class="input-field pl-11">
                    </div>
                    <button type="button" x-on:click="pokok = sisa"
                            class="mt-2 text-sm font-semibold text-brand-700 hover:underline">
                        Lunasi seluruh sisa pokok
                    </button>
                    <x-input-error :messages="$errors->get('bayar_pokok')" class="mt-2" />
                </div>

                <div>
                    <label for="deskripsi" class="label-field">Catatan</label>
                    <textarea id="deskripsi" name="deskripsi" rows="3"
                              placeholder="Catatan pembayaran (opsional)" class="input-field">{{ old('deskripsi') }}</textarea>
                </div>
            </div>

            <div class="mt-6 space-y-3 border-t border-slate-200 pt-5">
                <div class="flex items-center justify-between text-sm">
                    <span class="text-slate-500">Sisa Pinjaman Setelah Bayar</span>
                    <span class="font-semibold text-slate-900" x-text="rupiah(sisaSetelah)"></span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm font-semibold text-slate-700">Total Bayar</span>
                    <span class="text-2xl font-bold text-brand-600" x-text="rupiah(total)"></span>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <a href="{{ route('pinjaman.show', $pinjaman) }}" class="btn-danger">Batalkan</a>
                <button type="submit" class="btn-primary">Bayar Pinjaman</button>
            </div>
        </form>
    </div>
</x-app-layout>
