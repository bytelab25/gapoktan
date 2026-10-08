@php
    $pinjaman ??= null;
    // Pinjaman lama memakai persentase yang tersimpan, pinjaman baru memakai yang berlaku sekarang.
    $persen = $pinjaman ? (float) $pinjaman->potongan_persen : $persenPotongan;
    $tanggalDefault = optional($pinjaman?->tanggal_transaksi)->format('Y-m-d') ?? now()->format('Y-m-d');
    $tanggal = old('tanggal_transaksi', $tanggalDefault);
    $jatuhTempo = old('tanggal_jatuh_tempo', optional($pinjaman?->tanggal_jatuh_tempo)->format('Y-m-d')
        ?? \Illuminate\Support\Carbon::parse($tanggalDefault)->addMonths(\App\Models\Pinjaman::BULAN_PER_TERMIN)->format('Y-m-d'));
@endphp

<div x-data="{
        jumlah: {{ (float) old('jumlah_pinjaman', $pinjaman?->jumlah_pinjaman ?? 0) }},
        persen: {{ $persen }},
        tanggal: @js($tanggal),
        jatuhTempo: @js($jatuhTempo),
        tempoDiubah: @js($pinjaman !== null || old('tanggal_jatuh_tempo') !== null),
        terminBulan: {{ \App\Models\Pinjaman::BULAN_PER_TERMIN * ($pinjaman?->termin ?? 1) }},
        get potongan() { return Math.round(this.jumlah * this.persen) / 100 },
        get diterima() { return this.jumlah - this.potongan },
        rupiah(nilai) { return 'Rp ' + formatNominal(nilai) },
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
                @php
                    // Kelompok yang belum menyetor apa pun dikunci, kecuali kalau
                    // pinjaman ini memang miliknya (sedang disunting).
                    $terkunci = ! $kelompok->bisaMengajukanPinjaman()
                        && $pinjaman?->kelompok_tani_id !== $kelompok->id;
                @endphp
                <option value="{{ $kelompok->id }}" @disabled($terkunci)
                    @selected(old('kelompok_tani_id', $pinjaman?->kelompok_tani_id ?? request('kelompok')) == $kelompok->id)>
                    {{ $kelompok->nama_kelompok }} — {{ $kelompok->nama_ketua }}
                    @if ($terkunci) (belum menyetor, tidak bisa pinjam lagi) @endif
                </option>
            @endforeach
        </select>
        <p class="mt-1.5 text-xs text-slate-500">
            Kelompok yang belum menyetor sepeser pun untuk pinjaman berjalannya tidak bisa
            mengajukan pinjaman baru.
        </p>
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
                   x-model="tanggal" x-on:change="if (!tempoDiubah) jatuhTempo = tambahBulan(tanggal, terminBulan)" class="input-field">
            <x-input-error :messages="$errors->get('tanggal_transaksi')" class="mt-2" />
        </div>

        <div>
            <label for="tanggal_jatuh_tempo" class="label-field">Tanggal Jatuh Tempo</label>
            <input id="tanggal_jatuh_tempo" name="tanggal_jatuh_tempo" type="date" required
                   x-model="jatuhTempo" x-bind:min="tanggal" x-on:input="tempoDiubah = true" class="input-field">
            <p class="mt-1.5 text-xs italic text-slate-500">
                Tanggal awal disarankan {{ \App\Models\Pinjaman::BULAN_PER_TERMIN }} bulan setelah pencairan dan dapat disesuaikan.
                @if ($pinjaman) Termin berjalan: {{ $pinjaman->termin }}. @endif
            </p>
            <x-input-error :messages="$errors->get('tanggal_jatuh_tempo')" class="mt-2" />
        </div>

        <div>
            <label for="jumlah_pinjaman" class="label-field">Jumlah Pinjaman (Rp)</label>
            <x-input-nominal name="jumlah_pinjaman" :value="old('jumlah_pinjaman', $pinjaman?->jumlah_pinjaman ?? 0)" />
            <x-input-error :messages="$errors->get('jumlah_pinjaman')" class="mt-2" />
        </div>

        <div>
            <label for="potongan_nominal" class="label-field">Potongan {{ $persen }}%</label>
            <div class="relative">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-sm font-semibold text-slate-400">Rp</span>
                <input id="potongan_nominal" type="text" x-bind:value="formatNominal(potongan)" readonly
                       class="input-field bg-slate-100 pl-11 text-slate-600">
            </div>
            <p class="mt-1.5 text-xs italic text-slate-500">
                Gabungan biaya operasional {{ (float) $pengaturan->potongan_operasional }}%
                dan kontribusi dana talangan {{ (float) $pengaturan->potongan_dana_talangan }}%.
                Diubah lewat Pengaturan Sistem.
            </p>
        </div>

        <div>
            <label for="jumlah_diterima" class="label-field">Jumlah Diterima</label>
            <div class="relative">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-sm font-semibold text-slate-400">Rp</span>
                <input id="jumlah_diterima" type="text" readonly x-bind:value="formatNominal(diterima)"
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
        dihitung dari sisa pokok yang belum lunas. Setoran dapat dicatat sejak pencairan.
        Saat menyetor, bunga dilunasi lebih dulu
        sebelum pokok terpotong.
    </div>
</div>
