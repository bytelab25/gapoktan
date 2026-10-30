@props(['nilai' => false])

{{-- Butuh state Alpine "hanyaBunga" pada induknya. --}}
<div>
    <span class="label-field">Jenis Setoran</span>
    <input type="hidden" name="hanya_bunga" value="{{ $nilai ? 1 : 0 }}" x-bind:value="hanyaBunga ? 1 : 0">
    <div class="grid grid-cols-2 gap-1 rounded-lg bg-slate-100 p-1" role="radiogroup" aria-label="Jenis setoran">
        @foreach ([false => 'Bunga & Pokok', true => 'Bunga Saja'] as $pilihan => $label)
            <button type="button" role="radio"
                    x-on:click="hanyaBunga = @js((bool) $pilihan)"
                    x-bind:aria-checked="(hanyaBunga === @js((bool) $pilihan)).toString()"
                    x-bind:class="hanyaBunga === @js((bool) $pilihan) ? 'bg-white text-brand-700 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                    class="rounded-md px-3 py-2 text-sm font-semibold transition">
                {{ $label }}
            </button>
        @endforeach
    </div>
    <p class="mt-1.5 text-xs text-slate-500"
       x-text="hanyaBunga
           ? 'Hanya menutup bunga semua pinjaman, urut tertua dulu. Pokok tidak terpotong.'
           : 'Bunga dilunasi dulu, sisanya memotong pokok pinjaman tertua.'"></p>
</div>
