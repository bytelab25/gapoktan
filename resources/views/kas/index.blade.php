<x-app-layout title="Kas">
    <x-page-header judul="Kas"
                   deskripsi="Pelacakan arus masuk dan keluar keuangan koperasi secara real-time.">
        <x-slot:aksi>
            @can('kelola')
                <a href="{{ route('kas.create') }}" class="btn-primary">
                    <x-icon name="plus" class="h-4 w-4" />
                    Buat Transaksi
                </a>
            @endcan
        </x-slot:aksi>
    </x-page-header>

    <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card label="Saldo Saat Ini" :nilai="rupiah($saldo)" ikon="bank" />
        <x-stat-card label="Suku Bunga Pinjaman ({{ (float) $pengaturan->suku_bunga_pinjaman }}%)"
                     :nilai="(float) $pengaturan->suku_bunga_pinjaman . '% / bulan'" ikon="persen" warna="biru" />
        <x-stat-card label="Biaya Operasional ({{ (float) $pengaturan->potongan_operasional }}%)"
                     :nilai="rupiah($totalBiaya)" ikon="nota" warna="kuning" />
        <x-stat-card label="Pengeluaran" :nilai="rupiah($totalPengeluaran)" ikon="keluar-uang" warna="merah" />
    </div>

    <div class="card overflow-hidden">
        <form method="GET" class="flex flex-wrap items-end gap-4 border-b border-slate-200 p-5">
            <div>
                <label for="dari" class="label-field">Rentang Tanggal</label>
                <div class="flex items-center gap-2">
                    <input id="dari" type="date" name="dari" value="{{ request('dari') }}" class="input-field py-2 text-sm">
                    <span class="text-slate-400">—</span>
                    <input type="date" name="sampai" value="{{ request('sampai') }}" class="input-field py-2 text-sm">
                </div>
            </div>

            <div>
                <label for="jenis" class="label-field">Jenis Transaksi</label>
                <select id="jenis" name="jenis" class="input-field py-2 text-sm">
                    <option value="">Semua Transaksi</option>
                    <option value="masuk" @selected(request('jenis') === 'masuk')>Masuk</option>
                    <option value="keluar" @selected(request('jenis') === 'keluar')>Keluar</option>
                </select>
            </div>

            <button type="submit" class="btn-primary py-2.5">Terapkan</button>

            @if (request()->hasAny(['dari', 'sampai', 'jenis']))
                <a href="{{ route('kas.index') }}" class="btn-ghost py-2.5">Reset</a>
            @endif
        </form>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="table-head">
                    <tr>
                        <th class="px-6 py-3.5 text-left">Tanggal</th>
                        <th class="px-6 py-3.5 text-left">ID Transaksi</th>
                        <th class="px-6 py-3.5 text-left">Nama Poktan / Deskripsi</th>
                        <th class="px-6 py-3.5 text-left">Kategori</th>
                        <th class="px-6 py-3.5 text-right">Jumlah</th>
                        <th class="px-6 py-3.5 text-right">Saldo</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($daftarTransaksi as $transaksi)
                        <tr class="cursor-pointer hover:bg-slate-50/60"
                            onclick="window.location='{{ route('kas.show', $transaksi) }}'">
                            <td class="whitespace-nowrap px-6 py-4 text-slate-600">{{ tanggal_id($transaksi->tanggal) }}</td>
                            <td class="whitespace-nowrap px-6 py-4 font-medium text-slate-700">{{ $transaksi->kode }}</td>
                            <td class="max-w-sm px-6 py-4">
                                <p class="font-semibold text-slate-900">
                                    {{ $transaksi->kelompokTani?->nama_kelompok ?? 'Umum' }}
                                </p>
                                <p class="text-xs text-slate-500">{{ Str::limit($transaksi->deskripsi, 60) ?: '-' }}</p>
                            </td>
                            <td class="px-6 py-4">
                                <span class="badge {{ $transaksi->jenis === 'masuk' ? 'bg-brand-50 text-brand-700' : 'bg-slate-100 text-slate-600' }}">
                                    {{ $transaksi->kategori->nama }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right font-medium {{ $transaksi->jenis === 'masuk' ? 'text-slate-900' : 'text-danger-600' }}">
                                {{ rupiah_bertanda($transaksi->nilai) }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right font-bold text-brand-600">
                                {{ rupiah(\App\Models\TransaksiKas::saldoSampai($transaksi)) }}
                            </td>
                        </tr>
                    @empty
                        <x-empty-state kolom="6" pesan="Belum ada transaksi kas pada rentang ini." />
                    @endforelse
                </tbody>
            </table>
        </div>

        <x-tabel-kaki :paginator="$daftarTransaksi" />
    </div>
</x-app-layout>
