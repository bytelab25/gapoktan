@php
    $pinjaman ??= null;
    // Pinjaman lama memakai persentase yang tersimpan, pinjaman baru memakai yang berlaku sekarang.
    $persen = $pinjaman ? (float) $pinjaman->potongan_persen : $persenPotongan;
@endphp

<div x-data="{
        jumlah: {{ (float) old('jumlah_pinjaman', $pinjaman?->jumlah_pinjaman ?? 0) }},
        persen: {{ $persen }},
        tanggal: '{{ old('tanggal_transaksi', optional($pinjaman?->tanggal_transaksi)->format('Y-m-d') ?? now()->format('Y-m-d')) }}',
        terminBulan: {{ \App\Models\Pinjaman::BULAN_PER_TERMIN * ($pinjaman?->termin ?? 1) }},
        get potongan() { return Math.round(this.jumlah * this.persen) / 100 },
        get diterima() { return this.jumlah - this.potongan },
        /* Jatuh tempo selalu mengikuti kelipatan 4 bulan dari tanggal pencairan. */
        get jatuhTempo() {
            if (! this.tanggal) return '';
            const mulai = new Date(this.tanggal + 'T00:00:00');
            const tempo = new Date(mulai);
            tempo.setMonth(tempo.getMonth() + this.terminBulan);
            return tempo.toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' });
        },
        rupiah(nilai) { return 'Rp ' + new Intl.NumberFormat('id-ID').format(Math.round(nilai || 0)) },
     }">

    <h2 class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-brand-700">
        <x-icon name="kelompok" class="h-4 w-4" />
        Identitas Kelompok
    </h2>

    <div class="mt-4">
        <label for="kelompok_tani_id" class="label-field">Pilih Kelompok Tani</label>
        <select id="kelompok_tani_id" name="kelompok_tani_id" required class="input-field">
            <option value="">Pilih salah satu Kelompok Tani</option>
            @foreach ($daftarKelompok as $kelompok)
                <option value="{{ $kelompok->id }}"
                    @selected(old('kelompok_tani_id', $pinjaman?->kelompok_tani_id ?? request('kelompok')) == $kelompok->id)>
                    {{ $kelompok->nama_kelompok }} — {{ $kelompok->nama_ketua }}
                </option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('kelompok_tani_id')" class="mt-2" />
    </div>

    <h2 class="mt-8 flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-brand-700">
        <x-icon name="talangan" class="h-4 w-4" />
        Detail Finansial
    </h2>

    <div class="mt-4 grid gap-5 md:grid-cols-2">
        <div>
            <label for="tanggal_transaksi" class="label-field">Tanggal Transaksi</label>
            <input id="tanggal_transaksi" name="tanggal_transaksi" type="date" required
                   x-model="tanggal" class="input-field">
            <x-input-error :messages="$errors->get('tanggal_transaksi')" class="mt-2" />
        </div>

        <div>
            <label class="label-field">Tanggal Jatuh Tempo</label>
            <div class="relative">
                <input type="text" readonly x-bind:value="jatuhTempo"
                       class="input-field cursor-not-allowed bg-slate-100 pr-11 text-slate-500">
                <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-4 text-slate-400">
                    <x-icon name="kalender" class="h-4 w-4" />
                </span>
            </div>
            <p class="mt-1.5 text-xs italic text-slate-500">
                Pinjaman kelompok berjalan per termin {{ \App\Models\Pinjaman::BULAN_PER_TERMIN }} bulan,
                jadi jatuh tempo dihitung otomatis
                @if ($pinjaman) (termin berjalan: {{ $pinjaman->termin }}) @endif.
            </p>
        </div>

        <div>
            <label for="jumlah_pinjaman" class="label-field">Jumlah Pinjaman (Rp)</label>
            <div class="relative">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-sm font-semibold text-slate-400">Rp</span>
                <input id="jumlah_pinjaman" name="jumlah_pinjaman" type="number" min="1" step="0.01" required
                       x-model.number="jumlah" placeholder="0" class="input-field pl-11">
            </div>
            <x-input-error :messages="$errors->get('jumlah_pinjaman')" class="mt-2" />
        </div>

        <div>
            <label class="label-field">Potongan {{ $persen }}%</label>
            <div class="relative">
                <input type="text" value="{{ $persen }}%" readonly
                       class="input-field cursor-not-allowed bg-slate-100 pr-11 text-slate-500">
                <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-4 text-slate-400">
                    <x-icon name="pengaturan" class="h-4 w-4" />
                </span>
            </div>
            <p class="mt-1.5 text-xs italic text-slate-500">
                Gabungan biaya operasional {{ (float) $pengaturan->potongan_operasional }}%
                dan kontribusi dana talangan {{ (float) $pengaturan->potongan_dana_talangan }}%.
                Diubah lewat Pengaturan Sistem.
            </p>
        </div>

        <div>
            <label class="label-field">Jumlah Diterima</label>
            <div class="relative">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-sm font-semibold text-slate-400">Rp</span>
                <input type="text" readonly x-bind:value="new Intl.NumberFormat('id-ID').format(Math.round(diterima))"
                       class="input-field cursor-not-allowed bg-slate-100 pl-11 font-semibold text-slate-700">
            </div>
            <p class="mt-1.5 text-xs text-slate-500">
                Potongan <span class="font-semibold" x-text="rupiah(potongan)">Rp 0</span> dipotong di muka.
            </p>
        </div>

        <div class="md:col-span-2">
            <label for="deskripsi" class="label-field">Deskripsi</label>
            <textarea id="deskripsi" name="deskripsi" rows="4" placeholder="Masukkan deskripsi anda"
                      class="input-field">{{ old('deskripsi', $pinjaman?->deskripsi) }}</textarea>
            <x-input-error :messages="$errors->get('deskripsi')" class="mt-2" />
        </div>
    </div>

    <div class="mt-6 rounded-lg bg-slate-50 p-4 text-sm text-slate-600">
        Bunga <span class="font-semibold text-slate-900">{{ $pinjaman ? (float) $pinjaman->bunga_persen : (float) $pengaturan->suku_bunga_pinjaman }}% per bulan</span>
        dihitung dari sisa pokok yang belum lunas dan diakumulasi per
        {{ \App\Models\Pinjaman::BULAN_PER_TERMIN }} bulan. Saat menyetor, bunga dilunasi lebih dulu
        sebelum pokok terpotong.
    </div>
</div>
