@props([
    // Tinggi maksimum sebelum daftar bisa digulir.
    'tinggi' => '18rem',
    // Nama wilayah untuk pembaca layar, mis. "Daftar pinjaman lewat jatuh tempo".
    'label' => 'Daftar',
    // Warna latar kartu, dipakai bayangan pudar di tepi atas dan bawah.
    'latar' => 'from-white',
])

{{--
    Daftar yang bisa digulir dengan penanda jelas: bayangan pudar di tepi
    yang masih menyimpan isi, dan tombol "Gulir" selama belum sampai bawah.
    Tanpa JavaScript, daftar tetap bisa digulir; hanya penandanya yang hilang.
--}}
<div x-data="daftarGulir" class="relative">
    <div x-ref="isi"
         x-on:scroll.passive="perbarui"
         tabindex="0"
         role="region"
         aria-label="{{ $label }}"
         class="daftar-gulir overflow-y-auto overscroll-contain focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-brand-500"
         style="max-height: {{ $tinggi }}">
        {{ $slot }}
    </div>

    <div x-show="!diAtas" x-cloak x-transition.opacity
         class="pointer-events-none absolute inset-x-0 top-0 h-6 bg-gradient-to-b {{ $latar }} to-transparent"></div>

    <div x-show="!diBawah" x-cloak x-transition.opacity
         class="pointer-events-none absolute inset-x-0 bottom-0 flex h-16 items-end justify-center bg-gradient-to-t {{ $latar }} via-white/80 to-transparent pb-2.5">
        <button type="button"
                x-on:click="gulirKeBawah"
                tabindex="-1"
                class="pointer-events-auto inline-flex items-center gap-1 rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-600 shadow-pop ring-1 ring-slate-200 transition hover:text-brand-700">
            <x-icon name="chevron-bawah" class="h-3.5 w-3.5 animate-bounce" />
            <span x-text="sisa > 0 ? 'Gulir, ' + sisa + ' lagi' : 'Gulir ke bawah'">Gulir ke bawah</span>
        </button>
    </div>
</div>
