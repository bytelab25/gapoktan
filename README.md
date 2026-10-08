# Gapoktan Swagotra Arthagiri

Aplikasi web untuk mengelola keuangan Gabungan Kelompok Tani: kas, pinjaman kelompok, setoran pembayaran, dana talangan, laporan keuangan, dan akun pengurus. Antarmuka menggunakan bahasa Indonesia.

## Fitur

- Ringkasan saldo kas, saldo dana talangan, dan sisa pokok pinjaman beredar.
- Data kelompok tani, identitas ketua, dan riwayat pinjamannya.
- Kas masuk/keluar, kategori dinamis, saldo berjalan, dan faktur cetak.
- Pinjaman dengan potongan muka, bunga bulanan, jatuh tempo yang dapat dipilih, dan perpanjangan termin empat bulan.
- Setoran di tingkat kelompok, dibagi ke pinjaman tertua dengan bunga dibayar sebelum pokok.
- Kontribusi talangan otomatis dari pencairan, dana masuk manual, dan klaim santunan.
- Laporan kas, pinjaman, dana talangan, dan akhir tahun; unduhan PDF melalui dompdf.
- Empat role, pengaturan persentase, profil sendiri, dan log aktivitas.

## Teknologi dan kebutuhan

| Bagian | Teknologi |
| --- | --- |
| Backend | PHP 8.3+, Laravel 13, Eloquent, Blade |
| Antarmuka | Tailwind CSS 3, Alpine.js 3, SweetAlert2 |
| Build | Vite 8, npm dan `package-lock.json` |
| Database | SQLite sebagai default contoh; koneksi lain di `config/database.php` |
| PDF | `barryvdh/laravel-dompdf` 3 |
| Pengujian | PHPUnit 12 |

Gunakan Composer 2 dan Node.js yang memenuhi kebutuhan Vite: `^20.19.0 || >=22.12.0`. PHP harus memiliki driver PDO untuk database yang dipilih; tes bawaan membutuhkan `pdo_sqlite`.

## Mulai menggunakan

Ikuti [panduan instalasi](docs/instalasi.md) untuk menyiapkan `.env`, database, akun, dan aset. Setelah persiapan selesai:

```bash
composer dev
```

Server bawaan tersedia di `http://localhost:8000`; login memakai **username**, bukan email. Seeder demo menyediakan username `admin`, `bendahara`, `ketua`, dan `user`, dengan sandi `password`. Seeder hanya untuk database demo kosong dan tidak dapat dijalankan berulang tanpa konflik data.

Untuk database operasional, buat administrator sendiri sesuai panduan instalasi. Tidak ada pendaftaran publik atau reset sandi melalui email.

## Hak akses

| Role | Data keuangan dan laporan | Kelola transaksi dan kelompok | Kelola akun | Pengaturan |
| --- | --- | --- | --- | --- |
| `sekretaris` | Lihat | Ya | Ya | Lihat |
| `bendahara` | Lihat | Ya | Ya | Lihat |
| `ketua` | Lihat | Tidak | Tidak | Lihat dan sunting |
| `user` | Lihat | Tidak | Tidak | Tidak |

Semua role dapat menyunting profil sendiri. Role `user` tidak dapat membuka modul Pengguna dan Kelompok Tani, tetapi data kas, pinjaman, talangan, laporan, dan log yang dapat dibuka tetap bersifat global. Detail ada di [rute dan hak akses](docs/rute-dan-akses.md).

## Dokumentasi

Mulai dari [indeks dokumentasi](docs/README.md).

| Dokumen | Isi |
| --- | --- |
| [Instalasi](docs/instalasi.md) | Kebutuhan, konfigurasi, database, akun demo/operasional, server lokal |
| [Arsitektur](docs/arsitektur.md) | Struktur proyek, alur request, service keuangan, komponen UI |
| [Database](docs/database.md) | Tabel, relasi, tipe nilai, migrasi, kode transaksi |
| [Aturan bisnis](docs/aturan-bisnis.md) | Perhitungan pencairan, bunga, setoran FIFO, status, dan laporan |
| [Panduan pengguna](docs/panduan-pengguna.md) | Langkah kerja setiap modul |
| [Rute dan hak akses](docs/rute-dan-akses.md) | Endpoint, gate, filter, dan batas akses |
| [Pengujian](docs/pengujian.md) | Perintah pemeriksaan, hasil verifikasi, skenario uji bisnis |
| [Operasional](docs/operasional.md) | Deployment, pencadangan, pemulihan, dan penanganan masalah |
| [Catatan implementasi](docs/catatan-implementasi.md) | Batasan dan ketidaksesuaian pada kode |
| [Analisis Figma](docs/figma-analysis.md) | Referensi desain dan sejarah keputusan awal |

## Status verifikasi

Pemeriksaan pada **8 Oktober 2026**: build frontend, kompilasi Blade, daftar rute, validasi Composer, serta status 19 migrasi pada database lokal berhasil. **16 tes revisi keuangan/hak akses dan 5 tes JavaScript lulus**. Suite penuh masih memiliki kegagalan bawaan: **18 lulus, 4 gagal, 19 error**, karena tes starter belum mengikuti username dan route aplikasi. Rincian dan batas pemeriksaan ada di [pengujian](docs/pengujian.md).

Dokumentasi menjelaskan implementasi yang ada, termasuk batasan laporan historis dan rekonsiliasi setoran. Sebelum mengubah perhitungan uang, baca [aturan bisnis](docs/aturan-bisnis.md) dan [catatan implementasi](docs/catatan-implementasi.md).
