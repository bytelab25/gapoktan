<x-app-layout title="Detail Pengguna">
    <x-page-header judul="Detail Pengguna"
                   :deskripsi="$pengguna->name"
                   :kembali="route('pengguna.index')" />

    <div class="card max-w-3xl p-6">
        <h2 class="text-xs font-bold uppercase tracking-wider text-brand-700">Identitas</h2>

        <dl class="mt-5 divide-y divide-slate-100">
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

        <div class="mt-8 flex flex-wrap justify-end gap-3">
            <form method="POST" action="{{ route('pengguna.destroy', $pengguna) }}"
                  onsubmit="return confirm('Hapus pengguna {{ $pengguna->name }}?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn-danger">Hapus Pengguna</button>
            </form>
            <a href="{{ route('pengguna.edit', $pengguna) }}" class="btn-primary">Sunting Data</a>
        </div>
    </div>
</x-app-layout>
