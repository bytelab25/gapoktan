@props(['aksi', 'pesan' => 'Data yang sudah dihapus tidak bisa dikembalikan.', 'label' => 'Hapus'])

{{-- Konfirmasi lewat SweetAlert, bukan dialog bawaan browser. --}}
<form method="POST" action="{{ $aksi }}" class="inline"
      onsubmit="return konfirmasiHapus(this, @js($pesan))">
    @csrf
    @method('DELETE')
    <button type="submit" {{ $attributes->merge(['class' => 'btn-danger']) }}>
        <x-icon name="hapus" class="h-4 w-4" />
        {{ $label }}
    </button>
</form>
