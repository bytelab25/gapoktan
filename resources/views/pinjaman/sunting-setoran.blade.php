<x-app-layout title="Sunting Setoran">
    <x-page-header judul="Sunting Setoran"
                   :deskripsi="($pembayaran->kode_setoran ?? $pembayaran->kode) . ' — ' . $kelompok->nama_kelompok"
                   :kembali="route('pinjaman.kelompok', $kelompok)" />

    <div class="grid gap-6 lg:grid-cols-5">
        <div class="card self-start overflow-hidden lg:col-span-3">
            <div class="border-b border-slate-200 p-5">
                <h2 class="text-lg font-semibold text-slate-900">Alokasi Saat Ini</h2>
                <p class="mt-1 text-xs text-slate-500">
                    Setelah disimpan, semua setoran {{ $kelompok->nama_kelompok }} dihitung ulang urut tanggal:
                    bunga dulu, lalu pokok, pinjaman tertua lebih dulu.
                </p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="table-head">
                        <tr>
                            <th class="px-5 py-3 text-left">ID Pinjaman</th>
                            <th class="px-5 py-3 text-right">Bunga</th>
                            <th class="px-5 py-3 text-right">Pokok</th>
                            <th class="px-5 py-3 text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach ($daftarBaris as $baris)
                            <tr>
                                <td class="whitespace-nowrap px-5 py-4 font-semibold text-slate-900">{{ $baris->pinjaman->kode }}</td>
                                <td class="whitespace-nowrap px-5 py-4 text-right text-slate-600">
                                    {{ rupiah($baris->bunga_nominal) }}
                                    <span class="block text-xs text-slate-400">{{ $baris->bulan_bunga }} bulan</span>
                                </td>
                                <td class="whitespace-nowrap px-5 py-4 text-right text-slate-600">{{ rupiah($baris->bayar_pokok) }}</td>
                                <td class="whitespace-nowrap px-5 py-4 text-right font-bold text-brand-600">{{ rupiah($baris->total_bayar) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <form method="POST" action="{{ route('pembayaran.update', $pembayaran) }}"
              x-data="{ jumlah: {{ (float) old('jumlah', $jumlah) }} }"
              class="card self-start p-6 lg:col-span-2">
            @csrf
            @method('PUT')

            <h2 class="text-lg font-semibold text-slate-900">Form Setoran</h2>

            <div class="mt-5 space-y-5">
                <div>
                    <label for="tanggal" class="label-field">Tanggal</label>
                    <input id="tanggal" name="tanggal" type="date" required class="input-field"
                           value="{{ old('tanggal', $pembayaran->tanggal->format('Y-m-d')) }}">
                    <x-input-error :messages="$errors->get('tanggal')" class="mt-2" />
                </div>

                <div>
                    <label for="jumlah" class="label-field">Jumlah Setoran</label>
                    <x-input-nominal name="jumlah" :value="old('jumlah', $jumlah)" placeholder="Masukkan Total Bayar" />
                    <x-input-error :messages="$errors->get('jumlah')" class="mt-2" />
                </div>

                <div>
                    <label for="deskripsi" class="label-field">Catatan</label>
                    <textarea id="deskripsi" name="deskripsi" rows="3" placeholder="Catatan setoran (opsional)"
                              class="input-field">{{ old('deskripsi', $pembayaran->deskripsi) }}</textarea>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <a href="{{ route('pinjaman.kelompok', $kelompok) }}" class="btn-danger">Batalkan</a>
                <button type="submit" class="btn-primary">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</x-app-layout>
