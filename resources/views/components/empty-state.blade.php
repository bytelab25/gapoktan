@props(['pesan' => 'Belum ada data.', 'kolom' => 1])

<tr>
    <td colspan="{{ $kolom }}" class="px-6 py-16 text-center">
        <p class="text-sm text-slate-400">{{ $pesan }}</p>
        @isset($aksi)
            <div class="mt-4 flex justify-center">{{ $aksi }}</div>
        @endisset
    </td>
</tr>
