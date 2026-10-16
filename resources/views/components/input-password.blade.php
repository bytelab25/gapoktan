@props(['name', 'placeholder' => null, 'required' => false])

{{-- Kolom sandi dengan tombol untuk menampilkan atau menyembunyikan isinya. --}}
<div x-data="{ terlihat: false }" class="relative">
    <input id="{{ $name }}" name="{{ $name }}" x-bind:type="terlihat ? 'text' : 'password'" type="password"
           autocomplete="new-password" @if ($placeholder) placeholder="{{ $placeholder }}" @endif
           @required($required) {{ $attributes->merge(['class' => 'input-field pr-12']) }}>
    <button type="button" x-on:click="terlihat = ! terlihat"
            class="absolute inset-y-0 right-0 flex items-center px-3.5 text-slate-400 transition hover:text-slate-700"
            x-bind:aria-label="terlihat ? 'Sembunyikan password' : 'Tampilkan password'"
            x-bind:aria-pressed="terlihat.toString()" aria-label="Tampilkan password">
        <x-icon name="mata" class="h-5 w-5" x-show="! terlihat" />
        <x-icon name="mata-coret" class="h-5 w-5" x-show="terlihat" x-cloak />
    </button>
</div>
