/**
 * Tooltip grafik beranda. Setiap elemen ber-atribut data-grafik membawa JSON
 * { judul, baris: [{ nama, nilai, warna }], total? }. Isi disusun dengan
 * textContent, tidak pernah innerHTML, karena nama kelompok/kategori adalah
 * data dari pengguna.
 */
export function pasangTooltipGrafik() {
    const tip = document.createElement('div');
    tip.className = 'grafik-tooltip';
    tip.setAttribute('role', 'tooltip');
    tip.hidden = true;
    document.body.append(tip);

    let aktif = null;

    const isi = (el) => {
        let data;
        try {
            data = JSON.parse(el.dataset.grafik);
        } catch {
            return false;
        }

        tip.replaceChildren();

        const judul = document.createElement('p');
        judul.className = 'grafik-tooltip__judul';
        judul.textContent = data.judul ?? '';
        tip.append(judul);

        for (const baris of data.baris ?? []) {
            const row = document.createElement('div');
            row.className = 'grafik-tooltip__baris';

            const kunci = document.createElement('span');
            kunci.className = 'grafik-tooltip__kunci';
            kunci.style.background = baris.warna ?? 'currentColor';

            const nilai = document.createElement('strong');
            nilai.textContent = baris.nilai ?? '';

            const nama = document.createElement('span');
            nama.className = 'grafik-tooltip__nama';
            nama.textContent = baris.nama ?? '';

            row.append(kunci, nilai, nama);
            tip.append(row);
        }

        if (data.total) {
            const total = document.createElement('div');
            total.className = 'grafik-tooltip__total';
            const label = document.createElement('span');
            label.textContent = 'Total';
            const nilai = document.createElement('strong');
            nilai.textContent = data.total;
            total.append(label, nilai);
            tip.append(total);
        }

        return true;
    };

    const posisikan = (x, y) => {
        const jarak = 14;
        const { offsetWidth: w, offsetHeight: h } = tip;
        let kiri = x + jarak;
        let atas = y - h - jarak;

        if (kiri + w > window.innerWidth - 8) kiri = x - w - jarak;
        if (kiri < 8) kiri = 8;
        if (atas < 8) atas = y + jarak;

        tip.style.left = `${kiri}px`;
        tip.style.top = `${atas}px`;
    };

    const tampil = (el, x, y) => {
        if (aktif !== el && !isi(el)) return;
        aktif = el;
        tip.hidden = false;
        posisikan(x, y);
    };

    const sembunyi = () => {
        aktif = null;
        tip.hidden = true;
    };

    document.addEventListener('pointermove', (e) => {
        const el = e.target.closest?.('[data-grafik]');
        if (el) tampil(el, e.clientX, e.clientY);
        else if (aktif) sembunyi();
    });

    document.addEventListener('focusin', (e) => {
        const el = e.target.closest?.('[data-grafik]');
        if (!el) return;
        const kotak = el.getBoundingClientRect();
        tampil(el, kotak.left + kotak.width / 2, kotak.top + 8);
    });

    document.addEventListener('focusout', sembunyi);
    window.addEventListener('scroll', sembunyi, { passive: true });
    document.addEventListener('keydown', (e) => e.key === 'Escape' && sembunyi());
}
