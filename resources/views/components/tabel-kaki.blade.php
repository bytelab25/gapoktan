@props(['paginator'])

{{-- Pagination bernomor + pemilih baris per halaman, sesuai desain Figma. --}}
<div class="flex flex-wrap items-center justify-between gap-4 border-t border-slate-200 px-6 py-4">
    <div>{{ $paginator->links() }}</div>

    <form method="GET" class="flex items-center gap-2 text-sm text-slate-500">
        @foreach (request()->except(['per_halaman', 'page']) as $kunci => $nilai)
            <input type="hidden" name="{{ $kunci }}" value="{{ $nilai }}">
        @endforeach
        <label for="per_halaman">Baris per halaman</label>
        <select id="per_halaman" name="per_halaman" onchange="this.form.submit()"
                class="input-field w-20 py-1.5 text-sm">
            @foreach ([10, 25, 50] as $jumlah)
                <option value="{{ $jumlah }}" @selected(request('per_halaman', 10) == $jumlah)>{{ $jumlah }}</option>
            @endforeach
        </select>
    </form>
</div>
