<x-app-layout title="Pengguna">
    <x-page-header judul="Data Pengguna"
                   deskripsi="Akun pengurus yang bisa mengakses sistem.">
        <x-slot:aksi>
            @can('kelola-pengguna')
                <a href="{{ route('pengguna.create') }}" class="btn-primary">
                    <x-icon name="plus" class="h-4 w-4" />
                    Tambah Data
                </a>
            @endcan
        </x-slot:aksi>
    </x-page-header>

    <div class="card overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 p-5">
            <h2 class="text-lg font-semibold text-slate-900">Daftar Pengguna</h2>

            <form method="GET" class="flex flex-wrap items-center gap-3">
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                        <x-icon name="cari" class="h-4 w-4" />
                    </span>
                    <input type="search" name="cari" value="{{ request('cari') }}"
                           placeholder="Cari nama atau username" class="input-field w-64 py-2 pl-9 text-sm">
                </div>

                <select name="role" onchange="this.form.submit()" class="input-field w-44 py-2 text-sm">
                    <option value="">Semua Role</option>
                    @foreach (\App\Models\User::LABEL_ROLE as $nilai => $label)
                        <option value="{{ $nilai }}" @selected(request('role') === $nilai)>{{ $label }}</option>
                    @endforeach
                </select>
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
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($daftarPengguna as $pengguna)
                        <tr class="cursor-pointer hover:bg-slate-50/60"
                            onclick="window.location='{{ route('pengguna.show', $pengguna) }}'">
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
                                <span class="badge {{ $pengguna->isAdmin() ? 'bg-brand-50 text-brand-700' : 'bg-slate-100 text-slate-600' }}">
                                    {{ $pengguna->label_role }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <x-empty-state kolom="4" pesan="Belum ada pengguna." />
                    @endforelse
                </tbody>
            </table>
        </div>

        <x-tabel-kaki :paginator="$daftarPengguna" />
    </div>
</x-app-layout>
