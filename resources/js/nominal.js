const formatter = new Intl.NumberFormat('id-ID', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
});

export function formatNominal(nilai) {
    return formatter.format(Number(nilai) || 0);
}

// Titik memisahkan ribuan, koma memisahkan desimal pada masukan rupiah.
export function parseNominal(teks) {
    const angka = String(teks).trim().replace(/^Rp\s*/i, '').replaceAll('.', '');
    if (!/^\d+(?:,\d{0,2})?$/.test(angka)) return null;

    const nilai = Number(angka.replace(',', '.'));
    return Number.isFinite(nilai) ? nilai : null;
}

export function formatMasukanNominal(teks) {
    if (parseNominal(teks) === null) return teks;

    const [utuh, pecahan] = String(teks).trim().replace(/^Rp\s*/i, '').replaceAll('.', '').split(',');
    const ribuan = utuh.replace(/^0+(?=\d)/, '').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    return ribuan + (pecahan === undefined ? '' : ',' + pecahan);
}

export function inputNominal(awal = 0) {
    return {
        nilai: Number(awal) || '',
        teks: Number(awal) ? formatNominal(awal) : '',
        init() {
            this.$watch('nilai', (nilai) => {
                if (this.$el.contains(document.activeElement)) return;
                this.teks = Number(nilai) ? formatNominal(nilai) : '';
            });
        },
        ubah(event) {
            const masukan = event.target;
            const posisi = masukan.selectionStart;
            const panjang = masukan.value.length;
            const nilai = parseNominal(masukan.value);
            this.nilai = nilai ?? '';
            this.teks = formatMasukanNominal(masukan.value);
            masukan.setCustomValidity(masukan.value === '' || (nilai !== null && nilai >= 1)
                ? '' : 'Masukkan nominal minimal Rp 1 dengan maksimal dua angka desimal, misalnya 1.000,50.');
            // Pertahankan posisi kursor ketika pemisah ribuan berubah.
            this.$nextTick(() => {
                const kursor = Math.max(0, posisi + masukan.value.length - panjang);
                masukan.setSelectionRange(kursor, kursor);
            });
        },
        rapikan() {
            if (this.nilai !== '') this.teks = formatNominal(this.nilai);
        },
    };
}

export function tambahBulan(tanggal, bulan) {
    if (!tanggal) return '';
    const tempo = new Date(tanggal + 'T00:00:00');
    tempo.setMonth(tempo.getMonth() + bulan);
    return [tempo.getFullYear(), String(tempo.getMonth() + 1).padStart(2, '0'), String(tempo.getDate()).padStart(2, '0')].join('-');
}
