# Operasional dan penanganan masalah

Bagian deployment merupakan panduan untuk operator, belum bukti deployment produksi pada tinjauan ini. Sebelum perubahan server, tentukan target database dan simpan cadangan yang dapat dipulihkan.

## Konfigurasi server

Document root mengarah ke `public/`, bukan akar repository. Gunakan PHP yang memenuhi dependency, driver database yang benar, serta izin tulis proses PHP pada `storage/` dan `bootstrap/cache/`. Apache dapat menggunakan `public/.htaccess`; server lain perlu meneruskan request non-berkas ke `public/index.php`.

Untuk produksi, sesuaikan `.env`:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://domain-gapoktan.example
APP_LOCALE=id
SESSION_SECURE_COOKIE=true
LOG_LEVEL=warning
```

Contoh domain di atas adalah placeholder. Sesuaikan database/session/cache dan HTTPS dengan server. Pertahankan `APP_KEY` yang sama untuk instalasi yang berlanjut, termasuk saat restore. `.env` dan cadangannya berada di luar Git; dokumentasi tidak menyimpan kredensial operasional.

`SESSION_DRIVER=database`, `CACHE_STORE=database`, dan `QUEUE_CONNECTION=database` memerlukan tabel infrastruktur dari migrasi. Mail default `log`; aplikasi tidak memakai reset sandi email. Zona waktu saat ini tetap UTC di `config/app.php`; perubahan zona waktu harus ditinjau bersama perhitungan tanggal dan laporan.

## Urutan deployment

Jalankan dalam direktori release/checkout tujuan yang sudah disiapkan:

```bash
composer install --no-dev --optimize-autoloader
npm ci --ignore-scripts
npm run build
```

Setelah environment, database, cadangan, dan akses server dipastikan, lakukan langkah yang memang menulis konfigurasi/skema:

```bash
php artisan down
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan up
```

Jika langkah gagal, periksa error dan status release sebelum membuka kembali aplikasi. Migrasi menambah/mengubah skema; jangan menggantinya dengan `migrate:fresh`, `migrate:refresh`, atau `db:wipe` pada data operasional. Jangan menjalankan seeder demo untuk menginisialisasi pembukuan nyata.

Pastikan `public/build/manifest.json` serta asetnya tersedia. `public/hot` hanya untuk Vite development; apabila tersisa pada release yang tidak memiliki Vite server, periksa isinya lalu bersihkan berkas penunjuk dev tersebut. Proses produksi menggunakan web server/PHP-FPM, bukan `composer dev` atau server PHP development.

Belum ada job aplikasi atau scheduler penyegaran status pinjaman. Jika menambahkan pekerjaan queue kemudian, gunakan pengelola proses worker dan restart worker saat deployment. Jangan menganggap status pinjaman otomatis diperbarui setiap hari hanya karena cron Laravel terpasang.

## Pemeriksaan setelah deployment

- Pastikan `/up` merespons, lalu buka `/login`; health endpoint sendiri belum memeriksa akses seluruh tabel.
- Login akun yang sesuai dan periksa Beranda, satu detail kas/pinjaman/talangan, serta laporan PDF.
- Periksa role ketua/user tidak dapat mengirim aksi tulis yang dilarang.
- Pastikan CSS/JavaScript termuat, toast bekerja, serta logo dan cetak tersedia.
- Cocokkan saldo dan jumlah data dengan keadaan sebelum deployment.

Daftar ini adalah langkah yang perlu dilakukan operator, bukan hasil runtime yang sudah diperiksa pada pembaruan docs.

## Pencadangan

Cadangkan seluruh database domain dan infrastruktur yang diperlukan, `.env`/key secara terpisah dengan akses terbatas, berkas `storage/app` yang digunakan, serta catatan commit/release. Logo dan gambar saat ini berada di repository `public/img`; PDF dihasilkan saat diminta dan tidak disimpan sebagai arsip otomatis.

Untuk SQLite, gunakan mekanisme backup database konsisten. Contoh dengan CLI SQLite dan direktori cadangan yang sudah dibuat:

```bash
sqlite3 database/database.sqlite ".backup '/lokasi/cadangan/gapoktan.sqlite'"
```

Ganti kedua path dengan lokasi nyata, terutama bila `DB_DATABASE` memakai lokasi lain. Menyalin berkas SQLite yang sedang ditulis tanpa mempertimbangkan WAL/journal dapat menghasilkan cadangan tidak konsisten.

Untuk MySQL, contoh berikut meminta sandi secara interaktif:

```bash
mysqldump --single-transaction --user=gapoktan --password gapoktan > /lokasi/cadangan/gapoktan.sql
```

Sesuaikan host, user, dan database. Simpan beberapa generasi cadangan dan uji restore ke database terpisah. Tidak ada fitur backup otomatis di source aplikasi saat ini.

## Pemulihan

1. Pilih cadangan serta release kode yang sesuai; catat waktu terakhir data tercakup.
2. Pulihkan ke database terpisah terlebih dahulu, menggunakan driver dan key yang sesuai.
3. Cocokkan jumlah baris, saldo kas/talangan, sisa pokok, pembayaran, dan kontribusi turunan.
4. Uji login, hak akses, faktur, dan PDF pada lingkungan pemulihan.
5. Hanya setelah hasil diperiksa, arahkan instalasi tujuan ke data hasil pemulihan.

Rollback kode tidak otomatis memulihkan data. Migrasi `down()` dapat menjatuhkan kolom/tabel atau gagal karena nullable yang sudah terisi null. Jangan menjalankan rollback skema sebagai pengganti restore cadangan tanpa menilai migrasi serta data.

## Diagnosis masalah

| Gejala | Periksa / tindakan |
| --- | --- |
| `could not find driver` | `php -r 'print_r(PDO::getAvailableDrivers());'`; cocokkan driver CLI/FPM dengan `DB_CONNECTION`. PHPUnit tetap membutuhkan SQLite |
| Database/tabel belum ada | Buat database tujuan, cek environment, lalu migrasi pada target yang benar. Session database juga membutuhkan migrasi sebelum login |
| `Vite manifest not found` | Pasang paket npm dari lockfile dan jalankan `npm run build`, atau jalankan Vite untuk development |
| Aset mengarah ke Vite yang sudah berhenti | Periksa `public/hot`, status dev server, dan manifest build |
| Perubahan `.env` belum berlaku | `php artisan config:clear` untuk dev; bangun ulang `config:cache` untuk release setelah perubahan |
| 419 saat kirim form | Periksa session, cookie domain/HTTPS, key, waktu session, dan token CSRF; muat ulang form |
| 403 | Periksa role dan gate di panduan akses; ketua tidak berhak mengubah transaksi |
| Kategori/kode unik bentrok saat seed | Seeder demo tidak idempotent; jangan mengulangnya pada database berisi data |
| Saldo tidak cocok | Rekonsiliasi jenis/jumlah/tanggal/ID ledger, transaksi pencairan, dan kas masuk setoran; `biaya` tidak dipotong lagi |
| Status lewat jatuh tempo masih lama | Belum ada penyegaran harian; status tersimpan hanya disegarkan pada jalur tertentu |
| Bunga pratinjau berbeda setelah ganti tanggal | Form memakai data hari ini, server memakai tanggal kiriman; lihat faktur dan aturan hitung |
| Gagal mengubah baris pencairan | Edit akad sumber; kas/talangan yang memiliki `pinjaman_id` dilindungi |
| PDF gagal atau logo tidak ada | Periksa error server, izin storage/temp, dependency dompdf/GD, serta `public/img/logo-256.png` |
| `composer dev` langsung berhenti | Periksa proses pertama yang gagal; `--kill-others` menghentikan proses lain. Cek bentrok port |

Log teknis default berada di `storage/logs/laravel.log`. Gunakan `tail -n 100 storage/logs/laravel.log` pada lingkungan sendiri untuk diagnosis. Jangan menyalin isi log atau environment yang memuat data privat ke dokumentasi publik.

## Rekonsiliasi sebelum koreksi data

Satu pencairan terkait akad, kas keluar, dan talangan masuk. Satu setoran terkait pembayaran, bunga/pokok, tunggakan, termin, status, dan kas masuk. Koreksi salah satu baris saja belum tentu memperbaiki keseluruhan transaksi. Kas masuk pembayaran belum mempunyai relasi langsung ke pembayaran, sehingga tetap dapat diedit dari Kas tanpa mengubah riwayat setoran.

Tidak ada proses void pembayaran atau arsip faktur tetap. Untuk koreksi transaksi yang sudah memiliki pembayaran, gunakan salinan database dan rencana rekonsiliasi yang ditinjau sebelum mengubah data tujuan.

[Indeks dokumentasi](README.md) · [Catatan implementasi](catatan-implementasi.md).
