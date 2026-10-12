const formatter = new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 });

export function formatNominal(nilai) {
    return formatter.format(Math.round(Number(nilai) || 0));
}

// Rupiah selalu bulat: titik hanya pemisah ribuan, tidak ada angka desimal.
export function parseNominal(teks) {
    const angka = String(teks).trim().replace(/^Rp\s*/i, '').replaceAll('.', '');
    if (!/^\d+$/.test(angka)) return null;

    const nilai = Number(angka);
    return Number.isSafeInteger(nilai) ? nilai : null;
}

// Rapikan teks yang sedang diketik: buang karakter selain angka lalu beri titik ribuan.
export function formatMasukanNominal(teks) {
    const angka = String(teks).replace(/\D/g, '').replace(/^0+(?=\d)/, '');
    return angka.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}

export function inputNominal(awal = 0) {
    return {
        nilai: Math.round(Number(awal)) || '',
        teks: Number(awal) ? formatNominal(awal) : '',
        init() {
            this.$watch('nilai', (nilai) => {
                if (this.$el.contains(document.activeElement)) return;
                this.teks = Number(nilai) ? formatNominal(nilai) : '';
            });
        },
        ubah(event) {
            const masukan = event.target;
            // Hitung posisi kursor dari jumlah digit di kirinya, karena titik ribuan bisa bergeser.
            const digitKiri = masukan.value.slice(0, masukan.selectionStart).replace(/\D/g, '').length;
            const teks = formatMasukanNominal(masukan.value);
            const nilai = parseNominal(teks);
            this.nilai = nilai ?? '';
            this.teks = teks;
            masukan.value = teks;
            masukan.setCustomValidity(teks === '' || (nilai !== null && nilai >= 1)
                ? '' : 'Masukkan nominal minimal Rp 1.');
            let kursor = 0;
            for (let digit = 0; kursor < teks.length && digit < digitKiri; kursor++) {
                if (/\d/.test(teks[kursor])) digit++;
            }
            masukan.setSelectionRange(kursor, kursor);
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
