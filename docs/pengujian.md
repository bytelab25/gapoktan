# Pengujian dan status verifikasi

## Pemeriksaan rutin

Jalankan dari akar repository setelah dependency tersedia:

```bash
composer validate --no-check-publish
composer check-platform-reqs
php artisan route:list --except-vendor
php artisan migrate:status
npm run build
npm test
php artisan view:cache
vendor/bin/phpunit
```

`view:cache` menulis hasil kompilasi ke storage. Kembali ke pengembangan Blade langsung dengan `php artisan view:clear`. `migrate:status` memeriksa database aktif tanpa menjalankan migrasi.

`composer test` juga tersedia; script tersebut membersihkan config cache terlebih dahulu lalu menjalankan `php artisan test`. Bila hanya ingin menjalankan suite tanpa membersihkan cache aplikasi, gunakan `vendor/bin/phpunit`.

[`phpunit.xml`](../phpunit.xml) menetapkan SQLite `:memory:`, cache/session array, mail array, dan queue sync. Sediakan `pdo_sqlite` pada PHP CLI. Jangan mengarahkan tes `RefreshDatabase` ke database operasional; periksa juga konfigurasi testing dan environment eksternal sebelum menjalankannya.

Tidak terdapat script lint/typecheck JavaScript atau konfigurasi CI aplikasi pada berkas terlacak yang ditinjau. Pint tersedia sebagai dependency pengembangan; `vendor/bin/pint --test` dapat dipakai bila perubahan PHP membutuhkan pemeriksaan gaya. Jangan memakai Pint tanpa `--test` untuk audit yang hanya membaca karena akan memformat berkas.

## Hasil 8 Oktober 2026

Lingkungan: PHP CLI 8.3.35, Composer 2.8.8, Node.js 22.15.0, npm 10.9.2. Dependency terpasang: Laravel 13.22.0, dompdf bridge 3.1.2, PHPUnit 12.5.32, Vite 8.1.5, Tailwind 3.4.19, Alpine 3.15.12, SweetAlert2 11.26.25. Pada revisi, ekstensi SQLite versi PHP yang sama dimuat dari direktori sementara untuk tes dan database demo browser; konfigurasi PHP utama dan database operasional tidak diubah.

| Pemeriksaan | Hasil | Makna |
| --- | --- | --- |
| `composer validate --no-check-publish` | Lulus | Struktur manifest Composer valid |
| `composer check-platform-reqs` | Lulus | Kebutuhan paket terpasang cocok dengan PHP aktif; driver SQLite tetap tidak tersedia |
| `php artisan route:list --except-vendor --json` | Lulus | Route aplikasi dapat dibangun |
| `php artisan migrate:status --no-ansi` | Lulus | Semua 19 migrasi bertanda `Ran` pada database lokal yang dikonfigurasi |
| `npm run build` | Lulus | Aset Vite produksi berhasil dihasilkan |
| `php artisan view:cache --no-ansi` | Lulus | Seluruh template Blade berhasil dikompilasi |
| Tes `RevisiKeuanganTest` dengan SQLite | Lulus | 16 tes, 78 assertion: izin pengaturan, nominal/desimal, tanggal pilihan, setoran dini, bunga/pokok, FIFO, perpanjangan termin |
| `npm test` | Lulus | 5 tes: konversi nominal, masukan desimal, nilai tidak valid, potongan/jumlah diterima, tanggal standar |
| Pint untuk berkas PHP yang berubah | Lulus | Pemeriksaan gaya pada perubahan revisi |
| Browser desktop dan ponsel 390 px | Lulus untuk alur revisi | Kas/pinjaman disimpan dengan desimal; potongan/diterima sesuai; jatuh tempo tersimpan; setoran hari pencairan diterima; pengaturan sesuai tiga role; form tidak melebar keluar layar |
| Suite PHPUnit penuh dengan SQLite | Belum lulus | 41 tes: 18 lulus, 4 gagal, 19 error; kegagalan berasal dari tes starter |

Empat kegagalan suite penuh berasal dari route reset sandi/registrasi yang tidak tersedia, autentikasi registrasi yang tidak tersedia, serta `ExampleTest` yang mengharapkan 200 pada `/` padahal aplikasi mengembalikan 302. Semua 19 error berasal dari `UserFactory` starter yang tidak memasok `username` wajib.

Sebelum revisi, audit dokumentasi dengan PHP CLI utama menghasilkan 25 tes: 1 lulus, 1 gagal, 23 error karena driver SQLite tidak tersedia. Hasil ini merupakan pemeriksaan awal, bukan hasil tes revisi setelah ekstensi sementara dimuat.

Seluruh 19 migrasi dan seeder berhasil dijalankan pada database SQLite demo kosong yang terpisah. Pemeriksaan revisi mencakup form Kas/Pinjaman, pembayaran dini, serta pengaturan. Render seluruh route, seluruh interaksi browser/mobile, dan hasil cetak PDF belum diverifikasi; build dan kompilasi template tidak membuktikan semua skenario tersebut lulus.

Browser menggunakan `agent-browser` pada server lokal dengan database sementara. Masukan Kas `1.234.567,89` tersimpan sebagai `1234567.89`; pinjaman `1.000.000,50` menghasilkan potongan `15.000,01`, diterima `985.000,49`, dan menyimpan jatuh tempo pilihan 15 Desember 2026. Setoran `1000.25` pada hari pencairan menghasilkan bunga nol dan pembayaran pokok `1000.25`. Sekretaris/bendahara mendapat tiga field readonly tanpa tombol simpan; ketua berhasil menyimpan bunga baru pada database demo. Form sunting mempertahankan desimal dan tanggal saat dibuka kembali di ponsel; tidak ada error JavaScript yang dilaporkan pada halaman yang diperiksa.

Untuk mengulang tes yang khusus memverifikasi revisi, dengan `pdo_sqlite` tersedia:

```bash
vendor/bin/phpunit tests/Feature/RevisiKeuanganTest.php
npm test
vendor/bin/pint --test --dirty
```

## Ketidaksesuaian tes bawaan

Pembacaan source menemukan beberapa masalah terpisah dari driver SQLite:

- `UserFactory` belum menghasilkan `username` yang wajib pada skema.
- Tes login mengirim `email` dan mengharapkan route `dashboard`, sedangkan aplikasi memakai `username` serta `beranda`.
- Tes profil mengakses `/profile` dengan PATCH/DELETE, sedangkan aplikasi memakai `/profil` dengan PUT dan tidak menyediakan hapus akun sendiri.
- Tes registrasi, reset email, verifikasi email, dan konfirmasi sandi menguji route yang tidak tersedia.
- Tes logout mengharapkan `/`, sedangkan controller mengarahkan ke `/login`.

Mengaktifkan SQLite saja belum cukup untuk menjadikan tes starter sesuai perilaku produk. Revisi menambahkan tes domain keuangan/hak akses tanpa mengubah tes starter tersebut.

## Skenario verifikasi hak akses

Gunakan database demo terpisah. Untuk setiap role, uji URL langsung selain tombol/menu; menu tersembunyi saja tidak membuktikan otorisasi.

| Skenario | Sekretaris/Bendahara | Ketua | User |
| --- | --- | --- | --- |
| Beranda, kas, pinjaman, talangan, laporan/PDF, log | Dapat dibuka | Dapat dibuka | Dapat dibuka |
| Daftar/detail Pengguna dan Kelompok | Dapat dibuka | Dapat dibuka | 403 |
| Form dan request tulis transaksi/kelompok/setoran | Diizinkan | 403 | 403 |
| Buat/sunting/hapus akun | Diizinkan | 403 | 403 |
| Baca Pengaturan | Diizinkan, field baca saja | Diizinkan | 403 |
| Ubah Pengaturan (PUT) | 403 | Diizinkan | 403 |
| Sunting profil sendiri | Diizinkan | Diizinkan | Diizinkan |

Tanpa login, route domain harus mengarahkan ke `/login`. Uji sandi salah, login benar, logout, dan pembatasan percobaan login. Keberhasilan route GET tidak membuktikan request POST/PUT/DELETE aman dan benar.

## Skenario keuangan yang perlu diuji

| Kasus | Hasil yang harus diperiksa |
| --- | --- |
| Pinjaman Rp100 juta dengan default | Diterima Rp98,5 juta; kas turun Rp99,5 juta; talangan naik Rp1 juta |
| Potongan talangan nol | Tidak ada baris kontribusi talangan turunan |
| Ubah nominal pinjaman tanpa pembayaran | Kas/talangan turunan tersinkron dan kode baris tidak bertambah tanpa kebutuhan |
| Hapus pinjaman tanpa pembayaran | Akad dan baris pencairan terhapus bersama |
| Hapus pinjaman dengan pembayaran | Ditolak |
| Pinjaman baru untuk kelompok yang masih punya pinjaman belum bayar | Ditolak oleh controller, bukan hanya dropdown |
| Setoran sebelum empat bulan | Diizinkan sejak pencairan; bunga bulan penuh didahulukan lalu pokok |
| Setoran pada hari pencairan | Bunga nol; setoran langsung mengurangi pokok |
| Setoran sebelum pencairan | Tidak dialokasikan |
| Jatuh tempo pilihan | Tersimpan sesuai input; tidak boleh sebelum pencairan; perpanjangan mengikuti tanggal pilihan |
| Nominal desimal Kas/Pinjaman | Tampilan `1.000.000,50`, payload `1000000.50`, nilai tersimpan dua desimal |
| Setoran kurang dari bunga | Pokok tidak berkurang; sisa bunga menjadi tunggakan |
| Setoran menutup bunga dengan pokok tersisa | Termin/jatuh tempo maju jika batas termin telah tercapai |
| Setoran pada beberapa akad | FIFO tanggal dan ID; bunga lalu pokok setiap akad; satu kas masuk dan satu kode setoran |
| Setoran melebihi pelunasan | Validasi menolak nominal di atas toleransi |
| Pengaturan berubah | Snapshot akad lama tetap; akad baru memakai nilai baru |
| Kas manual disunting/backdate | Saldo baris berikutnya dihitung ulang menurut tanggal dan ID |
| Klaim talangan tanpa kelompok/penerima | Validasi menolak |
| Penghapusan/demotion admin terakhir | Ditolak; akses pengelolaan tetap tersedia |

Gunakan waktu tetap untuk tes bunga/status, tanggal akhir bulan untuk memeriksa pergeseran termin, serta tanggal pembayaran berbeda dari hari ini untuk memeriksa pratinjau dan server. Kas masuk setoran masih dapat diedit terpisah; dokumentasikan perilaku tersebut sebagai batasan sampai rekonsiliasi diperbaiki.

## Laporan dan tampilan

Verifikasi keempat jenis laporan dengan data kosong dan berisi, rentang tanggal batas, pagination, unduh PDF seluruh baris, logo, tanda tangan, dan orientasi halaman. Bandingkan ringkasan kas/talangan dengan jumlah ledger. Untuk laporan historis, perhatikan bahwa aset akhir tahun dan sisa pokok masih menggunakan keadaan terkini.

Uji browser desktop/mobile untuk navigasi, sidebar, dropdown, validasi, toast, konfirmasi hapus, pratinjau alokasi, tabel lebar, serta dialog cetak. Build dan kompilasi Blade tidak menggantikan pemeriksaan runtime maupun browser.

## Pemeriksaan dokumentasi

Pastikan tautan relatif mengarah ke berkas/direktori yang ada, blok kode tertutup, contoh perintah sesuai script proyek, dan role cocok dengan gate. Saat memperbarui dokumen Figma, bedakan keputusan historis dari perilaku kode yang berlaku.

[Indeks dokumentasi](README.md).
