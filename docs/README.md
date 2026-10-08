# Dokumentasi Gapoktan

Dokumentasi ini dipelajari dari 142 berkas sumber milik proyek: controller, request, model, service, konfigurasi, 19 migrasi, seeder, factory, 60 view Blade, JavaScript, CSS, dan tes. Dependency pihak ketiga dan berkas hasil build tidak termasuk. Tinjauan dilakukan pada 8 Oktober 2026, berdasarkan kode aplikasi di commit `cd61d41` sebelum pembaruan dokumentasi.

## Pilih jalur baca

| Kebutuhan | Urutan baca |
| --- | --- |
| Menjalankan proyek pertama kali | [Instalasi](instalasi.md), lalu [panduan pengguna](panduan-pengguna.md) |
| Memahami perhitungan keuangan | [Aturan bisnis](aturan-bisnis.md), [database](database.md), [catatan implementasi](catatan-implementasi.md) |
| Mengembangkan fitur | [Arsitektur](arsitektur.md), [rute dan akses](rute-dan-akses.md), [pengujian](pengujian.md) |
| Memelihara server | [Operasional](operasional.md), [pengujian](pengujian.md) |
| Membandingkan dengan desain awal | [Analisis Figma](figma-analysis.md), lalu [catatan implementasi](catatan-implementasi.md) |

## Cakupan aplikasi

Sistem mengelola satu Gapoktan dan transaksi pada tingkat kelompok. Tidak ada tabel anggota individu, relasi akun ke kelompok, registrasi mandiri, API publik, ataupun proses persetujuan pinjaman berjenjang. Akun yang memiliki akses baca dapat melihat data keuangan seluruh kelompok melalui modul yang diizinkan.

Kas koperasi dan dana talangan merupakan dua pos terpisah. Dana beredar adalah sisa pokok pinjaman aktif. Potongan muka dan bunga bulanan memiliki tujuan serta perhitungan berbeda; keduanya dijelaskan dengan contoh di panduan aturan bisnis.

## Sumber acuan

| Topik | Sumber kode utama |
| --- | --- |
| Endpoint dan middleware | [`routes/web.php`](../routes/web.php), [`routes/auth.php`](../routes/auth.php) |
| Role dan gate | [`User.php`](../app/Models/User.php), [`AppServiceProvider.php`](../app/Providers/AppServiceProvider.php) |
| Perhitungan pinjaman | [`Pinjaman.php`](../app/Models/Pinjaman.php) |
| Pencairan dan setoran | [`PencairanPinjaman.php`](../app/Services/PencairanPinjaman.php), [`SetoranPinjaman.php`](../app/Services/SetoranPinjaman.php) |
| Laporan | [`LaporanController.php`](../app/Http/Controllers/LaporanController.php) |
| Skema dan contoh data | [`database/migrations`](../database/migrations), [`DatabaseSeeder.php`](../database/seeders/DatabaseSeeder.php) |
| Instalasi dan pemeriksaan | [`composer.json`](../composer.json), [`package.json`](../package.json), [`phpunit.xml`](../phpunit.xml) |

Dokumen Figma menyimpan sejarah rancangan. Untuk perilaku saat ini, utamakan kode dan panduan implementasi di folder ini. Hasil pemeriksaan hanya berlaku untuk lingkungan dan tanggal yang tercatat; build berhasil tidak berarti seluruh tes bisnis atau tampilan browser sudah teruji.

## Memperbarui dokumentasi

Perbarui dokumen yang terkait dalam perubahan kode yang sama. Perubahan route/role masuk ke panduan akses; kolom masuk ke database; bunga, termin, atau pencatatan uang masuk ke aturan bisnis dan pengujian. Cantumkan tanggal serta perintah yang benar-benar dijalankan saat memperbarui status verifikasi.

[Kembali ke README proyek](../README.md).
