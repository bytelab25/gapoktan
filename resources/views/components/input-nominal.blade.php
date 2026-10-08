@props(['name', 'model' => 'jumlah', 'value' => 0])

<div x-data="inputNominal(@js($value))" x-modelable="nilai" x-model="{{ $model }}" class="relative">
    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-sm font-semibold text-slate-400">Rp</span>
    <input id="{{ $name }}" type="text" inputmode="decimal" required autocomplete="off"
           value="{{ $value ? number_format((float) $value, 2, ',', '.') : '' }}"
           x-bind:value="teks" x-on:input="ubah($event)" x-on:blur="rapikan()"
           placeholder="0,00" aria-describedby="{{ $name }}-format"
           class="input-field pl-11">
    <input type="hidden" name="{{ $name }}" value="{{ $value }}"
           x-bind:value="nilai === '' ? '' : Number(nilai).toFixed(2)">
</div>
<p id="{{ $name }}-format" class="mt-1.5 text-xs text-slate-500">Contoh: 1.000.000,50</p>
