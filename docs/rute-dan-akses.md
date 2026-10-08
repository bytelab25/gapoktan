# Rute, filter, dan hak akses

Sumber acuan: [`routes/web.php`](../routes/web.php), [`routes/auth.php`](../routes/auth.php), [`User`](../app/Models/User.php), dan [`AppServiceProvider`](../app/Providers/AppServiceProvider.php).

## Gate

| Gate | Role yang diperbolehkan | Penggunaan |
| --- | --- | --- |
| `kelola` | `sekretaris`, `bendahara` | Tulis kas, pinjaman, setoran, talangan, kelompok, kategori |
| `kelola-pengguna` | `sekretaris`, `bendahara` | Membuat/mengubah/menghapus akun |
| `lihat-pengaturan` | `sekretaris`, `bendahara`, `ketua` | Baca persentase |
| `kelola-pengaturan` | `ketua` | Ubah persentase |
| `lihat-pengurus` | Semua role valid selain `user` | Daftar/detail Pengguna, Kelompok, transaksi kelompok |

Semua route domain berada dalam middleware `auth`. Role `user` tetap dapat menyunting profil dan sandinya sendiri. Akses data keuangan yang diizinkan bersifat global, tanpa pembatasan kepemilikan kelompok. Profil sendiri memakai `$request->user()`, tanpa parameter ID pengguna lain.

## Rute umum dan autentikasi

| Metode | URI | Nama | Akses / perilaku |
| --- | --- | --- | --- |
| Semua metode redirect | `/` | Tidak bernama | Redirect ke `/beranda` |
| GET | `/up` | Health bawaan | Bootstrap Laravel; tidak memeriksa seluruh transaksi/PDF |
| GET | `/login` | `login` | Guest, halaman login |
| POST | `/login` | Tidak bernama | Guest, login username/password dan `remember` |
| POST | `/logout` | `logout` | Auth |
| PUT | `/password` | `password.update` | Auth; membutuhkan sandi saat ini dan konfirmasi sandi baru |
| GET | `/beranda` | `beranda` | Semua role terautentikasi |
| GET | `/profil` | `profil.show` | Akun sendiri |
| GET | `/profil/sunting` | `profil.edit` | Akun sendiri |
| PUT | `/profil` | `profil.update` | Akun sendiri; sandi opsional, tanpa mengubah role |

GET juga menerima HEAD. Tidak ada `/register`, `/forgot-password`, `/reset-password`, `/verify-email`, `/confirm-password`, `/dashboard`, atau `/profile` dalam route aplikasi saat ini.

## Resource

Resource memakai ID database dalam URL; kode `LP-...` atau `TXN-...` merupakan identitas yang ditampilkan, bukan parameter binding.

| Prefix | Nama route | Baca index/show | Tulis create/store/edit/update/destroy |
| --- | --- | --- | --- |
| `/pengguna` | `pengguna.*` | `lihat-pengurus` | `kelola-pengguna` |
| `/kelompok` | `kelompok.*` | `lihat-pengurus` | `kelola` |
| `/kas` | `kas.*` | Auth | `kelola` |
| `/pinjaman` | `pinjaman.*` | Auth | `kelola` |
| `/talangan` | `talangan.*` | Auth | `kelola` |

Pola untuk setiap prefix:

| Metode | Sufiks URI | Sufiks nama | Aksi |
| --- | --- | --- | --- |
| GET | Kosong | `.index` | Daftar |
| GET | `/create` | `.create` | Form baru |
| POST | Kosong | `.store` | Simpan baru |
| GET | `/{id}` | `.show` | Detail |
| GET | `/{id}/edit` | `.edit` | Form sunting |
| PUT/PATCH | `/{id}` | `.update` | Simpan perubahan |
| DELETE | `/{id}` | `.destroy` | Hapus jika aturan domain mengizinkan |

## Rute tambahan

| Metode | URI | Nama | Akses |
| --- | --- | --- | --- |
| GET | `/kelompok/{kelompok}/transaksi` | `kelompok.transaksi` | `lihat-pengurus` |
| GET | `/pembayaran` | `pembayaran.pilih` | `kelola` |
| GET | `/kelompok/{kelompok}/bayar` | `pembayaran.create` | `kelola` |
| POST | `/kelompok/{kelompok}/bayar` | `pembayaran.store` | `kelola` |
| GET | `/pembayaran/{pembayaran}/faktur` | `pembayaran.faktur` | Auth |
| GET | `/kas/{kas}/faktur` | `kas.faktur` | Auth |
| GET | `/pinjaman/{pinjaman}/faktur` | `pinjaman.faktur` | Auth |
| GET | `/talangan/{talangan}/faktur` | `talangan.faktur` | Auth |
| POST | `/kategori-kas` | `kategori-kas.store` | `kelola` |
| GET | `/pengaturan` | `pengaturan.edit` | `lihat-pengaturan` |
| PUT | `/pengaturan` | `pengaturan.update` | `kelola-pengaturan` |
| GET | `/laporan` | `laporan.index` | Auth |
| GET | `/laporan/unduh` | `laporan.unduh` | Auth |
| GET | `/log` | `log.index` | Auth |

## Filter query string

| Halaman | Parameter |
| --- | --- |
| Pengguna | `cari` nama/username, `role` |
| Kelompok | `cari` nama kelompok/ketua |
| Kas | `dari`, `sampai`, `jenis=masuk\|keluar` |
| Pinjaman | `kelompok` ID, `status=belum_bayar\|menunggak\|lunas` |
| Talangan | `kelompok` ID, `jenis=masuk\|keluar` |
| Log | `dari`, `sampai`, `tindakan=input\|sunting\|hapus` |
| Laporan dan unduh | `jenis=kas\|pinjaman\|dana_talangan\|akhir_tahun`, `dari`, `sampai`, `tahun` |

Daftar berpagination menerima `page` dan `per_halaman` dengan default 10; UI menawarkan 10, 25, 50 dan mempertahankan filter ketika berpindah halaman. Controller belum membatasi `per_halaman` ke daftar nilai tersebut. PDF tidak menggunakan pagination.

Laporan non-tahunan membutuhkan tanggal mulai/akhir, dengan akhir ≥ mulai. Laporan akhir tahun membutuhkan `tahun` 2000–2100; dropdown UI menawarkan tahun sekarang dan lima tahun sebelumnya. Membuka `/laporan` tanpa `jenis` hanya menampilkan form.

Contoh URL setelah login:

```text
/kas?jenis=masuk&per_halaman=25
/pinjaman?kelompok=1&status=belum_bayar
/laporan?jenis=kas&dari=2026-01-01&sampai=2026-12-31
/laporan/unduh?jenis=akhir_tahun&tahun=2026
```

## Memeriksa perubahan route

```bash
php artisan route:list --except-vendor
php artisan route:list --path=kelompok -v
```

Middleware menghasilkan penolakan 403 untuk akun yang tidak memenuhi gate. Pengunjung tanpa session diarahkan ke login. Form web memakai CSRF dan method spoofing `PUT`/`DELETE` pada Blade. Lihat [pengujian](pengujian.md) untuk matriks smoke test.

[Indeks dokumentasi](README.md).
