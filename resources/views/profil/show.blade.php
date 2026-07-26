<x-app-layout title="Data Diri">
    <x-page-header judul="Data Diri" deskripsi="Informasi akun Anda di sistem." />

    <div class="card max-w-3xl p-6">
        <div class="flex items-center gap-4 border-b border-slate-200 pb-6">
            <span class="flex h-16 w-16 items-center justify-center rounded-full bg-brand-700 text-xl font-bold text-white">
                {{ $pengguna->inisial }}
            </span>
            <div>
                <p class="text-lg font-bold text-slate-900">{{ $pengguna->name }}</p>
                <p class="text-sm text-slate-500">{{ $pengguna->role }}</p>
            </div>
        </div>

        <h2 class="mt-6 text-xs font-bold uppercase tracking-wider text-brand-700">Identitas</h2>

        <dl class="mt-4 divide-y divide-slate-100">
            @foreach ([
                'Nama Pengguna' => $pengguna->name,
                'Username' => $pengguna->username,
                'Nomor HP' => $pengguna->no_hp ?: '-',
                'Alamat' => $pengguna->alamat ?: '-',
                'Role' => $pengguna->role,
            ] as $label => $nilai)
                <div class="grid grid-cols-1 gap-1 py-3.5 sm:grid-cols-3">
                    <dt class="text-sm text-slate-500">{{ $label }}</dt>
                    <dd class="text-sm font-medium text-slate-900 sm:col-span-2">{{ $nilai }}</dd>
                </div>
            @endforeach
        </dl>

        <div class="mt-8 flex justify-end">
            <a href="{{ route('profil.edit') }}" class="btn-primary">Sunting Data</a>
        </div>
    </div>
</x-app-layout>
