<x-app-layout title="Dana Talangan">
    <x-page-header judul="Dana Talangan"
                   deskripsi="Pengelolaan dana darurat untuk anggota yang meninggal dan pelaporan keuangan akhir tahun.">
        <x-slot:aksi>
            @can('kelola')
                <a href="{{ route('talangan.create') }}" class="btn-primary">
                    <x-icon name="plus" class="h-4 w-4" />
                    Buat Transaksi
                </a>
            @endcan
        </x-slot:aksi>
    </x-page-header>

    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <x-stat-card label="Total Saldo Dana" :nilai="rupiah($saldo)" ikon="bank" />
        <x-stat-card label="Total Kontribusi ({{ (float) $pengaturan->potongan_dana_talangan }}%)"
                     :nilai="rupiah($totalKontribusi)" ikon="talangan" warna="biru" />
        <x-stat-card label="Klaim Terproses" :nilai="$jumlahKlaim . ' Kasus'" ikon="nota" warna="kuning" />
    </div>

    <div class="card overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 p-5">
            <h2 class="flex items-center gap-2 text-lg font-semibold text-slate-900">
                <x-icon name="riwayat" class="h-5 w-5 text-slate-400" />
                Transaksi Dana Terkini
            </h2>

            <form method="GET" class="flex flex-wrap items-center gap-3">
                <select name="kelompok" onchange="this.form.submit()" class="input-field py-2 text-sm">
                    <option value="">Semua Kelompok</option>
                    @foreach ($daftarKelompok as $kelompok)
                        <option value="{{ $kelompok->id }}" @selected(request('kelompok') == $kelompok->id)>
                            {{ $kelompok->nama_kelompok }}
                        </option>
                    @endforeach
                </select>

                <select name="jenis" onchange="this.form.submit()" class="input-field py-2 text-sm">
                    <option value="">Semua Transaksi</option>
                    <option value="masuk" @selected(request('jenis') === 'masuk')>Dana Masuk</option>
                    <option value="keluar" @selected(request('jenis') === 'keluar')>Dana Keluar</option>
                </select>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="table-head">
                    <tr>
                        <th class="px-6 py-3.5 text-left">ID Transaksi</th>
                        <th class="px-6 py-3.5 text-left">Nama Kelompok</th>
                        <th class="px-6 py-3.5 text-left">Penerima</th>
                        <th class="px-6 py-3.5 text-left">Tanggal</th>
                        <th class="px-6 py-3.5 text-right">Jumlah</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($daftarTransaksi as $transaksi)
                        <tr class="cursor-pointer hover:bg-slate-50/60"
                            onclick="window.location='{{ route('talangan.show', $transaksi) }}'">
                            <td class="whitespace-nowrap px-6 py-4 font-medium text-slate-700">{{ $transaksi->kode }}</td>
                            <td class="px-6 py-4 text-slate-600">{{ $transaksi->kelompokTani->nama_kelompok }}</td>
                            <td class="px-6 py-4 text-slate-600">
                                @if ($transaksi->otomatis)
                                    <span class="badge bg-brand-50 text-brand-700">Otomatis · {{ $transaksi->pinjaman->kode }}</span>
                                @else
                                    {{ $transaksi->penerima ?: '-' }}
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-slate-500">{{ tanggal_id($transaksi->tanggal) }}</td>
                            <td class="whitespace-nowrap px-6 py-4 text-right font-semibold {{ $transaksi->jenis === 'masuk' ? 'text-brand-600' : 'text-danger-600' }}">
                                {{ $transaksi->jenis === 'masuk' ? '+ ' : '- ' }}{{ rupiah($transaksi->jumlah) }}
                            </td>
                        </tr>
                    @empty
                        <x-empty-state kolom="5" pesan="Belum ada transaksi dana talangan." />
                    @endforelse
                </tbody>
            </table>
        </div>

        <x-tabel-kaki :paginator="$daftarTransaksi" />
    </div>
</x-app-layout>
