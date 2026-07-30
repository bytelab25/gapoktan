@php
    // Data alokasi untuk pratinjau di sisi klien — urutannya sama dengan FIFO di server.
    $antrian = $tagihan->map(fn ($pinjaman) => [
        'kode' => $pinjaman->kode,
        'bunga' => $pinjaman->bungaTerhutang(),
        'pokok' => $pinjaman->sisa_pokok,
    ])->values();
@endphp

<x-app-layout title="Bayar Pinjaman">
    <x-page-header judul="Bayar Pinjaman"
                   :deskripsi="$kelompok->nama_kelompok . ' — ' . $kelompok->kode"
                   :kembali="route('pembayaran.pilih')" />

    <div x-data="{
             tanggal: '{{ old('tanggal', now()->format('Y-m-d')) }}',
             jumlah: {{ (float) old('jumlah', 0) }},
             antrian: @js($antrian),
             totalBunga: {{ $totalBunga }},
             totalPelunasan: {{ $totalPelunasan }},
             rupiah(nilai) { return 'Rp ' + new Intl.NumberFormat('id-ID').format(Math.round(nilai || 0)) },
             /* Cermin logika server: bunga dulu, lalu pokok, pinjaman tertua lebih dulu. */
             get alokasi() {
                 let sisa = this.jumlah || 0;
                 return this.antrian.map((item) => {
                     const bunga = Math.min(sisa, item.bunga);
                     sisa -= bunga;
                     const pokok = bunga >= item.bunga ? Math.min(sisa, item.pokok) : 0;
                     sisa -= pokok;
                     return { kode: item.kode, bunga, pokok, total: bunga + pokok };
                 });
             },
             get terpakai() { return this.alokasi.reduce((jumlah, item) => jumlah + item.total, 0) },
             get kelebihan() { return Math.max(0, (this.jumlah || 0) - this.terpakai) },
         }"
         class="space-y-6">

        {{-- Daftar tagihan dibuat selebar halaman supaya kolom bunga tidak terpotong. --}}
        <div>
            <div class="card overflow-hidden">
                <div class="flex items-center justify-between gap-3 border-b border-slate-200 p-5">
                    <h2 class="text-lg font-semibold text-slate-900">Tagihan Kelompok</h2>
                    <span class="badge bg-slate-100 text-slate-600">Urut tertua &rarr; terbaru</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="table-head">
                            <tr>
                                <th class="px-5 py-3 text-left">ID Pinjaman</th>
                                <th class="px-5 py-3 text-left">Jatuh Tempo</th>
                                <th class="px-5 py-3 text-right">Sisa Pokok</th>
                                <th class="px-5 py-3 text-right">Bunga Terhutang</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($tagihan as $urutan => $pinjaman)
                                <tr>
                                    <td class="whitespace-nowrap px-5 py-4">
                                        <span class="font-semibold text-slate-900">{{ $pinjaman->kode }}</span>
                                        <span class="ml-2 badge bg-brand-50 text-brand-700">#{{ $urutan + 1 }}</span>
                                        <span class="block text-xs text-slate-500">
                                            Cair {{ tanggal_id($pinjaman->tanggal_transaksi) }} · Termin {{ $pinjaman->termin }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4">
                                        <span class="text-slate-600">{{ tanggal_id($pinjaman->tanggal_jatuh_tempo) }}</span>
                                        <span class="block"><x-status-badge :status="$pinjaman->status" /></span>
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4 text-right font-semibold text-slate-900">
                                        {{ rupiah($pinjaman->sisa_pokok) }}
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4 text-right">
                                        <span class="font-semibold text-warn-700">{{ rupiah($pinjaman->bungaTerhutang()) }}</span>
                                        <span class="block text-xs text-slate-500">
                                            {{ (float) $pinjaman->bunga_persen }}% × {{ $pinjaman->bulanBungaBerjalan() }} bulan
                                            @if ((float) $pinjaman->tunggakan_bunga > 0)
                                                + tunggakan {{ rupiah($pinjaman->tunggakan_bunga) }}
                                            @endif
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <x-empty-state kolom="4" pesan="Kelompok ini tidak punya tagihan pinjaman." />
                            @endforelse
                        </tbody>
                        @if ($tagihan->isNotEmpty())
                            <tfoot class="border-t-2 border-slate-200 bg-slate-50/60">
                                <tr>
                                    <td colspan="2" class="px-5 py-3.5 text-right font-semibold text-slate-700">Total</td>
                                    <td class="whitespace-nowrap px-5 py-3.5 text-right font-bold text-slate-900">
                                        {{ rupiah($totalSisaPokok) }}
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-3.5 text-right font-bold text-warn-700">
                                        {{ rupiah($totalBunga) }}
                                    </td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>

        </div>

        <div class="grid gap-6 lg:grid-cols-5">
            {{-- Pratinjau pembagian setoran --}}
            <div class="card self-start p-5 lg:col-span-3" x-show="jumlah > 0" x-cloak>
                <h2 class="text-base font-semibold text-slate-900">Pembagian Setoran</h2>
                <p class="mt-1 text-xs text-slate-500">
                    Bunga dilunasi lebih dulu. Sisanya baru memotong pokok, lalu mengalir ke pinjaman berikutnya.
                </p>

                <div class="mt-4 space-y-2">
                    <template x-for="baris in alokasi" :key="baris.kode">
                        <div class="flex items-center justify-between gap-4 rounded-lg px-3 py-2.5"
                             :class="baris.total > 0 ? 'bg-brand-50' : 'bg-slate-50'">
                            <span class="text-sm font-semibold"
                                  :class="baris.total > 0 ? 'text-brand-800' : 'text-slate-400'"
                                  x-text="baris.kode"></span>
                            <span class="text-xs" :class="baris.total > 0 ? 'text-brand-800' : 'text-slate-400'">
                                bunga <span x-text="rupiah(baris.bunga)"></span>
                                · pokok <span x-text="rupiah(baris.pokok)"></span>
                            </span>
                        </div>
                    </template>
                </div>
            </div>

        <form method="POST" action="{{ route('pembayaran.store', $kelompok) }}"
              class="card self-start p-6 lg:col-span-2 lg:col-start-4">
            @csrf

            <h2 class="text-lg font-semibold text-slate-900">Form Setoran</h2>

            <div class="mt-5 space-y-5">
                <div>
                    <label for="tanggal" class="label-field">Tanggal</label>
                    <input id="tanggal" name="tanggal" type="date" required x-model="tanggal" class="input-field">
                    <p class="mt-1.5 text-xs text-slate-500">
                        Bunga dihitung sampai tanggal ini. Mengubah tanggal butuh muat ulang halaman
                        agar nominal bunga ikut menyesuaikan.
                    </p>
                    <x-input-error :messages="$errors->get('tanggal')" class="mt-2" />
                </div>

                <div>
                    <label for="jumlah" class="label-field">Jumlah Setoran</label>
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-sm font-semibold text-slate-400">Rp</span>
                        <input id="jumlah" name="jumlah" type="number" min="1" step="0.01" required
                               x-model.number="jumlah" placeholder="Masukkan Total Bayar" class="input-field pl-11">
                    </div>
                    <div class="mt-2 flex flex-wrap gap-3 text-sm font-semibold">
                        <button type="button" x-on:click="jumlah = totalBunga"
                                class="text-brand-700 hover:underline">Bayar bunga saja</button>
                        <button type="button" x-on:click="jumlah = totalPelunasan"
                                class="text-brand-700 hover:underline">Lunasi semua</button>
                    </div>
                    <x-input-error :messages="$errors->get('jumlah')" class="mt-2" />
                </div>

                <div>
                    <label for="deskripsi" class="label-field">Catatan</label>
                    <textarea id="deskripsi" name="deskripsi" rows="3"
                              placeholder="Catatan setoran (opsional)" class="input-field">{{ old('deskripsi') }}</textarea>
                </div>
            </div>

            <div class="mt-6 space-y-3 border-t border-slate-200 pt-5 text-sm">
                <div class="flex items-center justify-between">
                    <span class="text-slate-500">Bunga Terhutang</span>
                    <span class="font-semibold text-warn-700">{{ rupiah($totalBunga) }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-500">Sisa Pokok Semua Pinjaman</span>
                    <span class="font-semibold text-slate-900">{{ rupiah($totalSisaPokok) }}</span>
                </div>
                <div class="flex items-center justify-between border-t border-slate-100 pt-3">
                    <span class="font-semibold text-slate-700">Total Pelunasan</span>
                    <span class="text-xl font-bold text-brand-600">{{ rupiah($totalPelunasan) }}</span>
                </div>
                <p x-show="kelebihan > 0" x-cloak class="rounded-lg bg-danger-50 px-3 py-2 text-xs text-danger-700">
                    Setoran melebihi tagihan sebesar <span x-text="rupiah(kelebihan)"></span>.
                </p>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <a href="{{ route('pembayaran.pilih') }}" class="btn-danger">Batalkan</a>
                <button type="submit" class="btn-primary" @disabled($tagihan->isEmpty())>Catat Setoran</button>
            </div>
        </form>
        </div>
    </div>
</x-app-layout>
