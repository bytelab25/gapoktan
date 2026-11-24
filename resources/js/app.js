import Alpine from 'alpinejs';
import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';
import { formatNominal, inputNominal, tambahBulan } from './nominal';
import { pasangTooltipGrafik } from './grafik';

window.Alpine = Alpine;
window.Swal = Swal;
window.formatNominal = formatNominal;
window.tambahBulan = tambahBulan;
Alpine.data('inputNominal', inputNominal);

/**
 * Penanda daftar yang bisa digulir (komponen x-daftar-gulir). "sisa" adalah
 * jumlah baris yang belum terlihat di bawah area tampil.
 */
Alpine.data('daftarGulir', () => ({
    diAtas: true,
    diBawah: true,
    sisa: 0,
    init() {
        this.perbarui();
        new ResizeObserver(() => this.perbarui()).observe(this.$refs.isi);
    },
    perbarui() {
        const el = this.$refs.isi;
        const batasBawah = el.scrollTop + el.clientHeight;
        this.diAtas = el.scrollTop <= 2;
        this.diBawah = batasBawah >= el.scrollHeight - 2;

        const baris = el.querySelectorAll(':scope > ul > li, :scope > li');
        const atasKotak = el.getBoundingClientRect().top;
        this.sisa = [...baris].filter((li) => li.getBoundingClientRect().top - atasKotak >= el.clientHeight - 4).length;
    },
    gulirKeBawah() {
        this.$refs.isi.scrollBy({ top: this.$refs.isi.clientHeight * 0.8, behavior: 'smooth' });
    },
}));

Alpine.start();
pasangTooltipGrafik();

const WARNA = {
    hijau: '#006C49',
    merah: '#F94141',
    abu: '#64748B',
};

const notifikasi = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 4000,
    timerProgressBar: true,
});

/**
 * Toast untuk pesan hasil aksi (flash session).
 */
window.beritahu = (jenis, pesan) => notifikasi.fire({ icon: jenis, title: pesan });

/**
 * Dipanggil dari atribut onsubmit form hapus. Selalu mengembalikan false agar
 * form tidak langsung terkirim; pengiriman menyusul setelah pengguna setuju.
 */
window.konfirmasiHapus = (formulir, pesan) => {
    Swal.fire({
        title: 'Hapus data ini?',
        text: pesan,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, hapus',
        cancelButtonText: 'Batal',
        confirmButtonColor: WARNA.merah,
        cancelButtonColor: WARNA.abu,
        reverseButtons: true,
        focusCancel: true,
    }).then((hasil) => {
        if (hasil.isConfirmed) {
            formulir.submit();
        }
    });

    return false;
};

/**
 * Konfirmasi umum untuk aksi yang tidak menghapus, mis. simpan pengaturan.
 */
window.konfirmasiAksi = (formulir, judul, pesan, tombol = 'Lanjutkan') => {
    Swal.fire({
        title: judul,
        text: pesan,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: tombol,
        cancelButtonText: 'Batal',
        confirmButtonColor: WARNA.hijau,
        cancelButtonColor: WARNA.abu,
        reverseButtons: true,
    }).then((hasil) => {
        if (hasil.isConfirmed) {
            formulir.submit();
        }
    });

    return false;
};

/**
 * Dipanggil dari form keluar di sidebar dan menu profil.
 */
window.konfirmasiKeluar = (formulir) => window.konfirmasiAksi(
    formulir,
    'Keluar dari sistem?',
    'Sesi Anda akan diakhiri. Anda perlu login lagi untuk masuk.',
    'Ya, keluar',
);

function tampilkanFlash() {
    const { sukses, gagal } = document.body.dataset;

    if (sukses) {
        window.beritahu('success', sukses);
    }

    if (gagal) {
        window.beritahu('error', gagal);
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', tampilkanFlash);
} else {
    tampilkanFlash();
}
