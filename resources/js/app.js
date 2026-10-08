import Alpine from 'alpinejs';
import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';
import { formatNominal, inputNominal, tambahBulan } from './nominal';

window.Alpine = Alpine;
window.Swal = Swal;
window.formatNominal = formatNominal;
window.tambahBulan = tambahBulan;
Alpine.data('inputNominal', inputNominal);

Alpine.start();

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
