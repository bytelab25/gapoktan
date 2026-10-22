# Catatan implementasi dan pekerjaan lanjutan

Temuan berikut berasal dari pembacaan kode pada 8 Oktober 2026. Dokumen ini membedakan perilaku yang tersedia dari bagian yang masih perlu dibenahi. Pembaruan dokumentasi tidak mengubah kode aplikasi atau menyelesaikan masalah di bawah.

## Laporan historis

`LaporanController::akhirTahun()` memfilter pendapatan dan beban menurut tahun, tetapi mengambil aset dari saldo kas/talangan dan piutang **saat ini**, tanpa batas akhir tahun. `laporanPinjaman()` memilih akad berdasarkan periode pencairan, lalu menghitung sisa pokok dari seluruh pembayarannya. Hasil tersebut bukan snapshot posisi keuangan pada tanggal akhir periode.

Jika diperlukan laporan historis, definisikan perhitungan saldo dan sisa pokok hingga tanggal tertentu lalu uji dengan pembayaran setelah batas periode. Daftar akad dalam periode juga tidak mencakup semua akad lama yang masih memiliki piutang.

## Integritas setoran dan koreksi akad

- `SetoranPinjaman::catatKasMasuk()` tidak mengisi `pinjaman_id` dan tidak mempunyai `pembayaran_id`/`kode_setoran` sebagai kolom relasi pada kas. `TransaksiKas::otomatis` hanya memeriksa `pinjaman_id`, sehingga kas setoran dapat disunting/dihapus manual tanpa mengubah pembayaran.
- Tidak ada route membatalkan, mengedit, atau menghapus pembayaran. Penanganan salah setoran memerlukan rekonsiliasi seluruh data yang terpengaruh.
- `PinjamanController::update()` tetap mengizinkan perubahan kelompok, tanggal, dan pokok setelah ada pembayaran, tanpa merekonstruksi riwayat bunga/setoran. Mengurangi pokok di bawah pokok yang sudah dibayar dapat membuat sisa pokok negatif.
- Larangan menambah akad untuk kelompok yang masih punya pinjaman belum bayar diperiksa pada `store()` dan pada `update()` ketika kelompok akad diubah.

Pekerjaan lanjutan yang relevan adalah hubungan setoran ke ledger, prosedur void/reversal, serta batas penyuntingan akad yang memiliki pembayaran. Jangan memperbaiki ketidaksesuaian dengan menghapus kas setoran saja.

## Tanggal, status, dan pratinjau

Form pembayaran disusun dari `tagihan()` dan bunga hari ini. Input tanggal hanya tersimpan pada state Alpine; tidak mengambil ulang tagihan maupun bunga untuk tanggal lain. Server memvalidasi dan menghitung ulang berdasarkan tanggal yang dikirim. Tulisan UI yang menyarankan muat ulang belum didukung filter tanggal pada `create()`.

Pembayaran tidak memvalidasi urutan terhadap pembayaran terakhir atau batas tanggal masa depan. Alokasi memakai seluruh pembayaran yang sudah ada saat itu, bukan rekonstruksi ledger pada tanggal historis. Pembayaran backdate perlu ditinjau agar perhitungan tidak memakai keadaan masa depan.

Status pinjaman tersimpan dan disegarkan saat penyuntingan akad atau pencatatan pembayaran; pembacaan daftar/detail tidak memperbaruinya berdasarkan tanggal. Tidak ada scheduler domain. Metode `segarkanStatus()` memeriksa waktu saat ini, bukan tanggal setoran historis.

Pembayaran tidak dikunci empat bulan; setoran bisa dicatat sejak pencairan. Jatuh tempo awal dapat dipilih, lalu perpanjangan menambah empat bulan dari tanggal pilihan. Label “menunggak” kelompok berarti sudah menyetor sebagian tetapi belum lunas, berbeda dari keterlambatan pinjaman.

## Pembayaran beberapa akad

FIFO membayar bunga lalu pokok akad tertua sebelum beralih ke akad selanjutnya. Tombol “Bayar bunga saja” mengisi jumlah seluruh bunga kelompok; pada beberapa akad jumlah tersebut dapat masuk ke pokok akad tertua. Perlu penyelarasan label/perilaku apabila operator menghendaki pembayaran bunga semua akad tanpa pokok.

## Validasi dan saldo

- Masing-masing persentase pengaturan divalidasi 0–100, tetapi jumlah operasional dan talangan belum dibatasi. Gabungan di atas 100% dapat membuat `jumlah_diterima` negatif.
- Kas/talangan keluar dan pencairan belum memeriksa kecukupan saldo; saldo negatif dapat tercatat.
- Dropdown kas manual menyembunyikan kategori sistem, tetapi validasi server hanya memeriksa kategori ada, tanpa mensyaratkan `sistem=false`.
- Nominal memakai decimal di database tetapi float pada perhitungan PHP. Form Kas/Pinjaman dan pratinjau potongan memakai dua desimal, sementara helper rupiah tabel/laporan menampilkan nol desimal. Rekonsiliasi perlu memakai nilai tersimpan dua desimal.
- `aktif` pada kelompok tidak tersedia di form dan tidak membatasi kelompok yang dapat menerima akad.

## Penghapusan dan nomor transaksi

Kelompok tanpa riwayat pinjaman dapat dihapus, tetapi foreign key talangan ke kelompok masih `cascadeOnDelete()`. Ini dapat menghapus talangan manual kelompok tersebut. Kas terkait kelompok menjadi null. Proteksi controller belum memeriksa riwayat talangan.

Kode memakai `max(id)+1` atau `count()+1`, tanpa penguncian/sequence. Request bersamaan dapat menghasilkan kode sama; setelah penghapusan, urutan berbasis jumlah baris juga dapat bertabrakan dengan kode yang masih ada. `DB::transaction()` saja tidak menjamin alokasi kode unik atau mengunci pembacaan tagihan terhadap setoran bersamaan.

## Akun dan akses

Role `user` bukan akun yang dibatasi ke satu kelompok: tidak ada hubungan user→kelompok. Modul keuangan dan log yang dapat diakses memuat data global. Belum ada tabel anggota, validasi penerima santunan terhadap anggota, atau pencegahan klaim ganda.

Deskripsi role di form/detail Pengguna mengikuti pembatasan pengaturan: hanya ketua boleh menyunting. Beberapa komentar migrasi dan aturan historis dokumen Figma masih menyebut ketua mengelola transaksi; gate saat ini hanya memperbolehkan sekretaris/bendahara menulis transaksi.

Penyuntingan profil sendiri dapat mengganti sandi tanpa sandi lama; endpoint `/password` memiliki validasi sandi lama tetapi tidak dipakai oleh form profil. Migrasi role hanya mengubah default, tanpa memetakan label role dari data lama. Role yang tidak dikenal perlu ditinjau pada data lama karena `lihat-pengurus` hanya memeriksa role bukan `user`.

## Audit dan ringkasan daftar

Aktivitas dicatat eksplisit setelah operasi controller. Login/logout, profil, dan kategori tidak dicatat, dan log bukan snapshot nilai sebelum/sesudah. Beberapa pencatatan log juga berada di luar transaksi database operasi utama.

Kartu total pinjaman daftar kelompok menjumlahkan kelompok pada halaman yang sedang ditampilkan. Kartu statistik Kas/Talangan dan total pokok Pinjaman memakai total global, bukan hasil filter. Filter pencarian Pengguna memakai `orWhere` tanpa pengelompokan sebelum filter role, sehingga kombinasi pencarian nama dan role perlu diuji ulang. `per_halaman` belum divalidasi terhadap pilihan UI.

## Environment, seeder, dan tes

`.env.example` masih memakai nama Laravel dan locale English; timezone kode UTC. Dependency Tailwind 4 Vite tersedia tetapi pipeline aktif Tailwind 3 PostCSS. Belum ada konfigurasi CI aplikasi atau script lint frontend.

Seeder bukan idempotent, menggunakan akun/sandi demo, tanggal relatif, dan NIK contoh 15 digit yang tidak memenuhi validasi form 16 digit. Migrasi kolom baru tidak melakukan backfill hubungan pencairan, snapshot talangan, atau kode setoran pada data historis.

Tes starter Breeze masih tidak mengikuti username dan route aplikasi. Tes revisi keuangan/hak akses kini tersedia dan lulus pada SQLite sementara; pengujian JavaScript memeriksa format nominal. PHP CLI utama tetap memerlukan driver SQLite untuk menjalankan tes database tanpa konfigurasi tambahan. Hasil tepat dan skenario uji ada di [pengujian](pengujian.md).

## Urutan perbaikan yang disarankan

1. Sesuaikan factory/tes dengan username, route aktual, dan matriks gate agar pemeriksaan domain dapat berjalan.
2. Perkuat hubungan pembayaran dengan kas, koreksi pembayaran, serta penyuntingan akad setelah pembayaran.
3. Perjelas laporan historis, tanggal setoran, penyegaran status, dan pratinjau bunga.
4. Tambahkan validasi gabungan potongan, aturan saldo sesuai keputusan produk, serta penomoran tahan request bersamaan.
5. Selaraskan teks role, statistik/filter, seeder, dan kebijakan penghapusan kelompok dengan riwayat talangan.

Urutan ini merupakan usulan tindak lanjut dari tinjauan, bukan perubahan yang sudah diterapkan.

[Indeks dokumentasi](README.md).
