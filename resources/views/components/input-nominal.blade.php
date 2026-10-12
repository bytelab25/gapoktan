@props(['name', 'model' => 'jumlah', 'value' => 0, 'placeholder' => '0'])

<div x-data="inputNominal(@js($value))" x-modelable="nilai" x-model="{{ $model }}" class="relative">
    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-sm font-semibold text-slate-400">Rp</span>
    <input id="{{ $name }}" type="text" inputmode="numeric" required autocomplete="off"
           value="{{ $value ? number_format(round((float) $value), 0, ',', '.') : '' }}"
           x-bind:value="teks" x-on:input="ubah($event)" x-on:blur="rapikan()"
           placeholder="{{ $placeholder }}" aria-describedby="{{ $name }}-format"
           class="input-field pl-11">
    <input type="hidden" name="{{ $name }}" value="{{ $value ? (int) round((float) $value) : '' }}"
           x-bind:value="nilai === '' ? '' : Math.round(Number(nilai))">
</div>
<p id="{{ $name }}-format" class="mt-1.5 text-xs text-slate-500">Contoh: 1.500.000</p>
