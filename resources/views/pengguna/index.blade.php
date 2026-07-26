<x-app-layout title="Pengguna">
    <x-page-header judul="Data Pengguna"
                   deskripsi="Akun pengurus yang bisa mengakses sistem.">
        <x-slot:aksi>
            <a href="{{ route('pengguna.create') }}" class="btn-primary">
                <x-icon name="plus" class="h-4 w-4" />
                Tambah Data
            </a>
        </x-slot:aksi>
    </x-page-header>

    <div class="card overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 p-5">
            <h2 class="text-lg font-semibold text-slate-900">Daftar Pengguna</h2>

            <form method="GET" class="relative">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                    <x-icon name="cari" class="h-4 w-4" />
                </span>
                <input type="search" name="cari" value="{{ request('cari') }}"
                       placeholder="Cari nama atau username" class="input-field w-64 py-2 pl-9 text-sm">
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="table-head">
                    <tr>
                        <th class="px-6 py-3.5 text-left">Nama</th>
                        <th class="px-6 py-3.5 text-left">Username</th>
                        <th class="px-6 py-3.5 text-left">Nomor HP</th>
                        <th class="px-6 py-3.5 text-left">Role</th>
                        <th class="px-6 py-3.5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($daftarPengguna as $pengguna)
                        <tr class="hover:bg-slate-50/60">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-50 text-xs font-bold text-brand-700">
                                        {{ $pengguna->inisial }}
                                    </span>
                                    <span class="font-semibold text-slate-900">{{ $pengguna->name }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-slate-600">{{ $pengguna->username }}</td>
                            <td class="px-6 py-4 text-slate-600">{{ $pengguna->no_hp ?: '-' }}</td>
                            <td class="px-6 py-4">
                                <span class="badge bg-slate-100 text-slate-600">{{ $pengguna->role }}</span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('pengguna.show', $pengguna) }}"
                                   class="font-semibold text-brand-700 hover:underline">Lihat</a>
                            </td>
                        </tr>
                    @empty
                        <x-empty-state kolom="5" pesan="Belum ada pengguna." />
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($daftarPengguna->hasPages())
            <div class="border-t border-slate-200 px-6 py-4">{{ $daftarPengguna->links() }}</div>
        @endif
    </div>
</x-app-layout>
