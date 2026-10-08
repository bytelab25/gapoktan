# Instalasi dan pengembangan lokal

## Kebutuhan

| Komponen | Kebutuhan proyek |
| --- | --- |
| PHP | 8.3 atau versi lebih baru yang cocok dengan `composer.lock` |
| Composer | Composer 2 |
| Node.js | Vite terkunci saat tinjauan membutuhkan `^20.19.0 || >=22.12.0` |
| npm | Mendukung `npm ci` dan lockfile proyek |
| Database | SQLite dengan `pdo_sqlite`, atau server database dan driver PDO yang sesuai |
| Akses tulis | `storage/` dan `bootstrap/cache/` untuk proses PHP |

Ekstensi seperti DOM, XML, XMLWriter, Fileinfo, OpenSSL, dan Mbstring dibutuhkan dependency aplikasi/pengujian. Periksa kebutuhan nyata paket dengan `composer check-platform-reqs` setelah pemasangan. Pemeriksaan Composer itu tidak menggantikan pemeriksaan driver database. Untuk PDF dengan logo PNG, sediakan GD juga.

```bash
php --version
composer --version
node --version
npm --version
php -r 'print_r(PDO::getAvailableDrivers());'
```

Pastikan PHP CLI dan PHP-FPM/web server menggunakan versi serta ekstensi yang sesuai. Di lingkungan yang ditinjau, PHP CLI 8.3.35 memiliki driver MySQL/PostgreSQL tetapi tidak memiliki SQLite; tes memerlukan lingkungan PHP dengan `pdo_sqlite`.

## 1. Pasang dependency

Jalankan dari akar repository:

```bash
composer install
npm ci --ignore-scripts
```

Gunakan lockfile yang ada untuk mempertahankan versi. `.npmrc` sudah menetapkan `ignore-scripts=true`. Tidak perlu `composer update` atau `npm update` hanya untuk menjalankan proyek.

## 2. Siapkan environment

Buat `.env` hanya jika belum ada:

```bash
php -r "file_exists('.env') || copy('.env.example', '.env');"
```

Untuk instalasi baru, sesuaikan setidaknya:

```dotenv
APP_NAME="Gapoktan Swagotra Arthagiri"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000
APP_LOCALE=id
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=id_ID

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
```

`.env.example` masih memakai nama `Laravel`, locale `en`, dan SQLite. Teks UI sudah berbahasa Indonesia, tetapi `APP_LOCALE=id` membantu format tanggal dari `tanggal_id()`. Zona waktu aplikasi ditetapkan langsung sebagai `UTC` di [`config/app.php`](../config/app.php); menambah `APP_TIMEZONE` saja tidak mengubahnya.

Jika `APP_KEY` belum terisi pada instalasi baru:

```bash
php artisan key:generate
```

Pertahankan key instalasi lama. Setelah menyunting environment pada instalasi dengan konfigurasi tersimpan, jalankan `php artisan config:clear`.

## 3. Pilih database

### SQLite

Gunakan `DB_CONNECTION=sqlite`. Biarkan `DB_DATABASE` tidak diisi di `.env` agar memakai `database/database.sqlite`, atau isi dengan path absolut berkas SQLite.

```bash
php -r "file_exists('database/database.sqlite') || touch('database/database.sqlite');"
php artisan migrate
```

Driver `pdo_sqlite` harus muncul dalam pemeriksaan PDO. Berkas `.sqlite` tidak disimpan di Git.

### MySQL

Buat database kosong terlebih dahulu dengan akun yang memiliki izin migrasi. Contoh konfigurasi; ganti kredensial dengan milik lingkungan lokal:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=gapoktan
DB_USERNAME=gapoktan
DB_PASSWORD="isi-sandi-database-lokal"
```

Lalu jalankan `php artisan migrate`. Koneksi PostgreSQL, MariaDB, dan SQL Server tersedia di konfigurasi bawaan, tetapi instalasi baru pada semua mesin tersebut belum diverifikasi dalam tinjauan ini. Jika `DB_URL` diatur, periksa juga karena URL koneksi dapat memengaruhi pilihan database.

```bash
php artisan migrate:status
```

Terdapat 19 migrasi, termasuk tabel session/cache/queue dan perubahan termin serta hubungan transaksi otomatis.

## 4. Buat akun dan data awal

### Demo lokal

Hanya pada database demo baru yang masih kosong setelah migrasi:

```bash
php artisan db:seed
```

| Username | Role | Sandi demo |
| --- | --- | --- |
| `admin` | `sekretaris` | `password` |
| `bendahara` | `bendahara` | `password` |
| `ketua` | `ketua` | `password` |
| `user` | `user` | `password` |

Seeder memasukkan enam kelompok, kategori, pengaturan, saldo awal kas Rp500 juta, saldo awal talangan Rp25 juta, pinjaman, contoh pembayaran, klaim, dan beban. Tanggal sebagian besar relatif terhadap hari seeder dijalankan. Data ini untuk demonstrasi.

Seeder memakai `create()` untuk banyak entitas; menjalankannya lagi dapat gagal pada kolom unik. NIK contoh dari seeder saat ini berjumlah 15 digit, sementara form kelompok meminta 16 digit; perbaiki nilai demo sebelum menyunting kelompok lewat UI.

### Database operasional kosong

Gunakan Tinker pada database yang sudah dimigrasi, tanpa menjalankan seeder demo:

```bash
php artisan tinker
```

Isi nama, username, nomor HP, alamat, dan sandi administrator milik operator:

```php
\App\Models\User::create([
    'name' => 'Nama Administrator',
    'username' => 'admin-operasional',
    'no_hp' => 'nomor-hp-operator',
    'alamat' => 'alamat-operator',
    'role' => \App\Models\User::ROLE_SEKRETARIS,
    'password' => 'ganti-dengan-sandi-kuat',
]);

\App\Models\Pengaturan::ambil();

foreach (\App\Models\KategoriKas::BAWAAN as $nama) {
    \App\Models\KategoriKas::firstOrCreate(['nama' => $nama], ['bawaan' => true]);
}

foreach (\App\Models\KategoriKas::SISTEM as $nama) {
    \App\Models\KategoriKas::sistem($nama);
}
```

Contoh tersebut menulis database; jalankan hanya pada database tujuan yang sudah dipastikan. `User` meng-hash sandi melalui cast `hashed`; email opsional. Setelah login, akun lain dibuat melalui modul Pengguna. Saldo awal nyata dicatat melalui Kas dan Dana Talangan sesuai pembukuan Gapoktan.

## 5. Jalankan aplikasi

```bash
composer dev
```

Perintah ini menjalankan server Laravel, `queue:listen`, Pail, dan Vite melalui `concurrently`. Buka `http://localhost:8000/login`. Jika satu proses berhenti, `--kill-others` menghentikan proses lainnya juga.

Untuk port lain, jalankan dua terminal:

```bash
php artisan serve --host=127.0.0.1 --port=8123
```

```bash
npm run dev
```

Sesuaikan `APP_URL` dengan URL yang dipakai. Alur keuangan dan PDF bekerja secara sinkron; tidak terdapat job aplikasi khusus yang wajib diproses worker.

Untuk mencoba aset produksi tanpa Vite dev server:

```bash
npm run build
php artisan serve
```

Hentikan Vite dev server agar aplikasi tidak masih mengacu ke `public/hot`. Penanganan error ada di [pengujian](pengujian.md) serta [operasional](operasional.md).

## Script `composer setup`

Script ini memasang dependency, menyalin `.env` bila perlu, membuat key, menjalankan migrasi dengan `--force`, memasang paket npm, dan build. Ia tidak menjalankan seeder dan tidak secara eksplisit membuat berkas SQLite. Karena key selalu dibuat ulang, gunakan langkah manual di atas untuk instalasi yang sudah memiliki konfigurasi atau data.

[Indeks dokumentasi](README.md).
