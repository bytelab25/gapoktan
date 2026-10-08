# Panduan penggunaan

Panduan mengikuti menu dan form yang ada. Ketersediaan aksi bergantung pada [hak akses](rute-dan-akses.md). Sekretaris dan bendahara menjalankan pencatatan; ketua mengawasi data dan mengubah pengaturan; pengguna melihat data keuangan dan mengelola profil sendiri.

## Masuk dan navigasi

1. Buka `/login`, masukkan username dan sandi.
2. Centang “Ingat saya di perangkat ini” bila diperlukan pada perangkat pribadi.
3. Setelah masuk, Beranda menampilkan saldo kas, saldo talangan, dana beredar, dan tautan laporan.
4. Gunakan sidebar untuk membuka modul. Pada layar kecil, buka sidebar melalui tombol menu di topbar.

Ikon `+` untuk administrator menyediakan pintasan membuat kelompok, pinjaman, dan pembayaran. Ikon riwayat berbentuk dokumen dengan jam di pojok atas dan menampilkan tiga aktivitas terakhir serta tautan Log Aktivitas. Ikon pengaturan tersedia bagi ketua, sekretaris, dan bendahara. Menu profil memuat Data Diri dan Keluar. Halaman login tidak menampilkan jumlah kelompok tani.

Pesan berhasil/gagal tampil sebagai toast. Pesan validasi muncul dekat input. Tabel dapat digulir mendatar jika layar sempit; pagination menyediakan pilihan 10, 25, atau 50 baris.

## Pengaturan awal

Sebelum mencatat akad pertama, pengurus memeriksa Pengaturan Sistem: suku bunga pinjaman per bulan, potongan operasional, dan potongan dana talangan. Default masing-masing 2%, 0,5%, dan 1%. Jumlah dua potongan membentuk potongan muka 1,5%.

Hanya ketua dapat menyimpan perubahan; sekretaris dan bendahara melihat nilai dengan field baca saja. Perubahan berlaku untuk pinjaman baru. Persentase pinjaman lama tetap tersimpan. Jangan menyamakan bunga bulanan dengan potongan muka. Baca contoh di [aturan bisnis](aturan-bisnis.md).

## Kelompok Tani

1. Pilih **Kelompok Tani → Buat Kelompok Tani**.
2. Isi nama kelompok, nama ketua, NIK tepat 16 digit, nomor HP, dan alamat.
3. Simpan untuk membuka profil kelompok.
4. Gunakan tab **Transaksi** untuk melihat pinjaman dan bunga yang telah dibayar.

Nama kelompok/ketua dapat dicari dari daftar. Kode kelompok dihasilkan otomatis. Kelompok dengan riwayat pinjaman tidak bisa dihapus. Untuk kelompok tanpa pinjaman, penghapusan tetap dapat menghapus dana talangan terkait melalui hubungan database; periksa riwayat sebelum menghapus.

Kartu “Total Pinjaman Berjalan” di daftar kelompok saat ini dihitung dari kelompok pada halaman pagination yang terbuka. Total pada tab transaksi kelompok menjumlahkan pokok awal seluruh akad, bukan sisa pokoknya.

## Kas

1. Pilih **Kas → Buat Transaksi**.
2. Isi tanggal, jumlah berformat desimal Indonesia (contoh `1.000.000,50`), dan jenis Masuk/Keluar.
3. Pilih kategori. **Tambah Kategori** membuka form kategori baru; setelah tersimpan, gunakan kategori itu pada transaksi.
4. Pilih kelompok jika transaksi terkait kelompok, lalu isi catatan bila perlu.
5. Periksa dampak saldo sebelum menyimpan.

Kas umum boleh tanpa kelompok. Tampilan Kas tidak memuat biaya yang dihitung. Filter tanggal/jenis mengubah tabel, sementara kartu ringkasan kas tetap menggambarkan seluruh data.

Klik baris untuk membuka detail, menyunting/menghapus transaksi manual, atau membuka faktur. Transaksi pencairan pinjaman otomatis menampilkan tautan ke akad dan harus diperbaiki melalui akad tersebut. Jangan mencatat pencairan ulang secara manual karena sistem sudah menghasilkan barisnya.

## Pinjaman Kelompok

1. Pilih **Pinjaman Kelompok → Buat Pinjaman**.
2. Pilih kelompok yang memenuhi syarat. Kelompok yang belum pernah menyetor pada pinjaman aktifnya dikunci.
3. Isi tanggal pencairan, jumlah pokok berformat desimal Indonesia, dan catatan.
4. Pilih jatuh tempo (nilai awal empat bulan, bisa disunting). Periksa nominal potongan muka, jumlah diterima, dan bunga bulanan; contoh pokok `1.000.000,50` menghasilkan potongan 1,5% sebesar `15.000,01` dan diterima `985.000,49`.
5. Simpan, lalu periksa detail dan faktur.

Pencairan membuat kas keluar dan talangan masuk otomatis. Detail memuat pokok awal, sisa pokok, bunga terbayar, bunga terhutang, termin, kontribusi talangan, dan riwayat pembayaran. Filter daftar tersedia berdasarkan kelompok dan status.

Penyuntingan nominal/tanggal/kelompok masih tersedia pada kode saat ini, bahkan setelah pembayaran. Jika sudah ada pembayaran, perubahan tersebut dapat membuat riwayat tidak konsisten; lakukan rekonsiliasi sebelum mengubah akad. Pinjaman dengan riwayat pembayaran tidak dapat dihapus.

## Pembayaran pinjaman

1. Pilih **Bayar Pinjaman**, lalu pilih kelompok yang menyetor.
2. Periksa **Tagihan Kelompok** yang urut tertua ke terbaru.
3. Setoran bisa dibuat sejak tanggal pencairan, termasuk sebelum empat bulan. Pinjaman dengan tanggal pencairan masa depan tampil pada **Belum Dicairkan**.
4. Isi tanggal, jumlah setoran, dan catatan.
5. Periksa **Pembagian Setoran**, lalu tekan **Catat Setoran**.
6. Faktur setoran menampilkan pembagian bunga/pokok ke setiap pinjaman.

Jumlah dapat lebih kecil dari bunga terhutang; selisih menjadi tunggakan bunga. Jumlah melebihi tagihan ditolak. Pembagian berlangsung otomatis: bunga dan pokok akad tertua, baru beralih ke akad berikutnya.

Tombol **Bayar bunga saja** mengisi total bunga kelompok. Jika ada beberapa akad, periksa pembagian karena uang dapat memotong pokok akad tertua sebelum mencapai bunga akad berikutnya. **Lunasi semua** memakai total tagihan yang ditampilkan ketika halaman dibuka.

**Pratinjau memakai tanggal hari ini saat halaman dimuat.** Mengubah input tanggal belum memperbarui tagihan/pratinjau; server menghitung ulang memakai tanggal yang dikirim saat menyimpan. Memuat ulang halaman juga belum menyediakan pilihan tanggal historis pada query. Untuk setoran tanggal lain, periksa perhitungan dan faktur hasil penyimpanan, jangan menganggap pratinjau telah menyesuaikan.

Tidak tersedia aksi membatalkan atau mengedit setoran. Kesalahan pembayaran memerlukan penanganan pengembang/operator database dengan rekonsiliasi seluruh pos terkait; jangan menghapus kas masuk saja.

## Dana Talangan

1. Pilih **Dana Talangan → Buat Transaksi**.
2. Pilih Masuk atau Keluar, tanggal, serta jumlah.
3. Dana masuk dapat tanpa kelompok, misalnya hibah atau setoran kolektif.
4. Dana keluar wajib menyebut kelompok dan penerima santunan.
5. Isi catatan, simpan, dan buka faktur bila diperlukan.

Baris kontribusi dari pencairan ditandai otomatis dan diperbaiki melalui akad pinjaman. Dana talangan manual tidak otomatis mengubah kas koperasi. Bila kejadian nyata juga memindahkan uang antarpos, pembukuan pasangan perlu ditentukan oleh pengurus.

“Total Kontribusi” menjumlahkan seluruh dana masuk. “Klaim Terproses” menghitung seluruh baris keluar, tanpa tahap persetujuan terpisah. Penerima merupakan teks bebas; aplikasi belum memvalidasi keanggotaan atau klaim ganda.

## Laporan Keuangan

1. Pilih jenis Kas, Pinjaman, Dana Talangan, atau Laporan Akhir Tahun.
2. Isi tanggal mulai/akhir, atau pilih tahun untuk laporan akhir tahun.
3. Tekan **Buat Laporan**; hasil tampil di bawah form pada halaman yang sama.
4. Tekan **Unduh Laporan** untuk PDF berkop dan seluruh baris hasil filter.

Pagination HTML tidak membatasi isi PDF. Laporan tabel memakai A4 lanskap; akhir tahun memakai A4 potret. Posisi aset laporan akhir tahun adalah posisi **saat ini**, sedangkan pendapatan/beban difilter menurut tahun. Sisa pokok laporan pinjaman juga merupakan sisa terkini. Penjelasan ada di [aturan bisnis](aturan-bisnis.md).

Faktur Kas, Pinjaman, Setoran, dan Talangan memakai tombol **Cetak PDF** yang membuka dialog cetak browser. Pilih simpan sebagai PDF pada dialog tersebut; mekanismenya berbeda dari Unduh Laporan.

## Pengguna, profil, dan sandi

Administrator membuat akun melalui Pengguna, mengisi nama, username unik, nomor HP, alamat, role, sandi dan konfirmasi. Ketua dapat melihat daftar/detail akun tetapi tidak mengubahnya. Role `user` tidak membuka modul ini.

Pada penyuntingan akun, kosongkan sandi bila tidak diganti. Sistem menolak penghapusan akun sendiri dan administrator terakhir, serta penurunan role administrator terakhir ke non-admin. Tidak ada registrasi atau reset email; akun yang lupa sandi ditangani sekretaris/bendahara melalui penyuntingan akun.

Semua role dapat menyunting Data Diri, termasuk sandi opsional, dengan role tetap. Form profil saat ini tidak meminta sandi lama; endpoint `/password` terpisah meminta sandi lama tetapi tidak dipakai form profil. Keluar setelah selesai memakai perangkat bersama.

## Log Aktivitas

Buka ikon riwayat → **Lihat Selengkapnya**. Filter tanggal dan jenis `input`, `sunting`, atau `hapus`, lalu gunakan **Cetak Log** untuk dialog browser. Tombol ini mencetak halaman yang sedang terbuka, bukan mengekspor seluruh halaman log.

Log mencakup aksi yang dicatat controller secara eksplisit, seperti CRUD transaksi/kelompok/pengguna dan perubahan pengaturan. Login, logout, perubahan profil sendiri, dan penambahan kategori belum dicatat. Nama pelaku menjadi “Sistem” jika relasi user null, misalnya setelah akun dihapus.

[Indeks dokumentasi](README.md).
