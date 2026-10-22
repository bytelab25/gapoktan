# Aturan bisnis dan perhitungan

Dokumen ini menjelaskan perilaku yang diimplementasikan dalam [`Pinjaman`](../app/Models/Pinjaman.php), [`KelompokTani`](../app/Models/KelompokTani.php), dua [service keuangan](../app/Services), dan [`LaporanController`](../app/Http/Controllers/LaporanController.php). Batasan kode dijelaskan terpisah di [catatan implementasi](catatan-implementasi.md).

## Pengaturan dan snapshot

Default koperasi adalah bunga **2% per bulan**, potongan operasional **0,5%**, dan potongan talangan **1%**. Bunga berjalan berbeda dari potongan saat pencairan. Ketiganya hanya dapat diubah oleh ketua; role lain boleh melihat dalam mode baca saja.

Pinjaman baru menyimpan snapshot `bunga_persen`, `potongan_persen` gabungan, serta `potongan_talangan_persen`. Perubahan pengaturan tidak mengubah snapshot pinjaman lama. Penyuntingan pinjaman tetap memakai persentase tersimpan, sedangkan penyuntingan kas manual menghitung ulang `biaya` menggunakan persentase operasional saat penyuntingan.

## Pencairan pinjaman

Dengan pokok `P`, persentase operasional `O`, dan persentase talangan `T`:

```text
potongan_persen  = O + T
potongan_nominal = round(P × (O + T) / 100, 2)
jumlah_diterima  = P − potongan_nominal
kontribusi      = round(P × T / 100, 2)
biaya           = round(P × O / 100, 2)
```

`PencairanPinjaman::sinkronkan()` menghasilkan:

| Pos | Jenis | Jumlah | Catatan |
| --- | --- | --- | --- |
| Kas / Pencairan Pinjaman | Keluar | `jumlah_diterima` | Menyimpan `biaya` operasional |
| Kas / Setoran Dana Talangan | Keluar | Kontribusi | Uang berpindah ke pos talangan |
| Dana Talangan | Masuk | Kontribusi | Terkait pinjaman yang sama |

Jika kontribusi nol, baris kas setoran talangan dan talangan turunan dihapus/tidak dibuat. Porsi operasional tidak dibuat menjadi kas masuk tambahan karena uangnya memang tetap berada di kas.

Contoh pokok Rp100.000.000 dengan pengaturan default:

| Komponen | Nilai |
| --- | --- |
| Potongan total 1,5% | Rp1.500.000 |
| Operasional 0,5% | Rp500.000 |
| Talangan 1% | Rp1.000.000 |
| Diterima kelompok | Rp98.500.000 |
| Total kas berkurang | Rp99.500.000 |
| Saldo talangan bertambah | Rp1.000.000 |
| Piutang pokok awal | Rp100.000.000 |

Kas/talangan pencairan ditautkan melalui `pinjaman_id`, sehingga tidak boleh disunting/dihapus manual. Penyuntingan akad menyinkronkan baris turunannya. Pinjaman hanya dapat dihapus bila tidak mempunyai pembayaran, dan baris pencairannya ikut dihapus.

## Kelayakan kelompok dan termin

Kelompok boleh memiliki beberapa pinjaman. Namun, selama masih ada **satu** pinjaman aktif tanpa pembayaran sama sekali (status `belum_bayar`), kelompok tidak bisa menambah pinjaman baru, walaupun status kelompoknya sudah `menunggak`. Pemeriksaan per pinjaman ada di `KelompokTani::bisaMengajukanPinjaman()` dan dijalankan server pada `store()` serta `update()` saat pinjaman dipindah ke kelompok lain.

Karena setoran dialokasikan FIFO, pinjaman yang lebih baru baru menerima uang setelah pinjaman yang lebih tua lunas. Akibatnya, kelompok dengan dua pinjaman berjalan baru bisa mengajukan pinjaman ketiga setelah pinjaman pertama lunas dan sebagian setoran masuk ke pinjaman kedua.

Satu termin standar adalah empat bulan. Akad baru memiliki termin 1; form menyarankan jatuh tempo `tanggal_transaksi + 4 bulan`, tetapi pengurus dapat memilih tanggal lain. Server menyimpan tanggal pilihan dan menolak jatuh tempo sebelum pencairan.

Setoran dapat dicatat sejak tanggal pencairan, tanpa kunci empat bulan. Ketika bunga tertutup dan pokok masih tersisa, `majukanTermin()` menambah empat bulan dari jatuh tempo berjalan sampai tanggal tersebut berada setelah tanggal pembayaran. Tanggal yang dipilih manual tetap menjadi dasar perpanjangan.

## Bunga dan tunggakan

```text
sisa_pokok      = round(pokok awal − total pembayaran pokok, 2)
mulai_bunga     = tanggal pembayaran terakhir, atau tanggal pencairan
bulan_berjalan  = jumlah bulan penuh sejak mulai_bunga hingga tanggal hitung
bunga_berjalan  = round(sisa_pokok × bunga_persen / 100 × bulan_berjalan, 2)
bunga_terhutang = round(tunggakan_bunga + bunga_berjalan, 2)
pelunasan       = round(sisa_pokok + bunga_terhutang, 2)
```

Bunga merupakan perhitungan sederhana atas sisa pokok, bukan bunga majemuk atau bunga harian. Bagian bulan yang belum penuh tidak dihitung. Tunggakan lama dijumlahkan dengan bunga berjalan, tanpa dikenai bunga tersendiri.

Contoh Rp100 juta, bunga 2%, pencairan 1 Januari, pembayaran pertama 1 Mei: bunga empat bulan Rp8 juta. Setoran Rp5 juta membayar bunga Rp5 juta, menyisakan tunggakan Rp3 juta dan pokok tetap Rp100 juta. Pada 1 Juni, bunga bulan baru Rp2 juta sehingga total bunga Rp5 juta. Jika menyetor Rp10 juta saat itu, bunga Rp5 juta tertutup, pokok berkurang Rp5 juta, sisa pokok Rp95 juta, dan jatuh tempo bergerak ke termin 2 pada 1 September.

Contoh tanggal memakai awal bulan untuk menghindari ambiguitas pergeseran akhir bulan pada Carbon. Semua jumlah disimpan dua desimal. Form Kas dan Pinjaman memakai pemisah ribuan titik dan desimal koma (`1.000.000,50`); data dikirim sebagai angka `1000000.50`. Potongan muka dan jumlah diterima pada form mempertahankan dua desimal. Helper rupiah untuk tabel/laporan masih membulatkan ke bilangan bulat.

## Pembagian setoran

Setoran dicatat untuk **kelompok**, bukan untuk satu akad yang dipilih bebas:

1. Ambil pinjaman belum lunas yang sudah dapat dibayar pada tanggal setoran.
2. Urutkan berdasarkan tanggal pencairan, lalu ID bila tanggal sama.
3. Bayar bunga pinjaman pertama sebatas uang tersedia.
4. Jika bunga pinjaman itu tertutup, sisa uang membayar pokoknya.
5. Uang yang masih tersisa diteruskan ke pinjaman berikutnya dengan aturan yang sama.

Prioritas bunga berlaku **di dalam setiap pinjaman**. Pokok pinjaman tertua dibayar sebelum bunga pinjaman berikutnya. Tombol UI “Bayar bunga saja” mengisi total bunga kelompok; pada beberapa pinjaman, nominal tersebut belum tentu seluruhnya dialokasikan ke bunga.

Satu setoran dapat menghasilkan beberapa baris `pembayaran_pinjaman`, disatukan `kode_setoran`. Setiap baris menyimpan bunga, pokok, bulan bunga, dan total. Kas menerima satu baris masuk sebesar jumlah yang teralokasi, dengan kategori `Pembayaran Pinjaman`.

Controller menolak setoran di atas total pelunasan dengan toleransi perbandingan Rp0,01. Tagihan mencakup pinjaman belum lunas dengan tanggal pencairan ≤ tanggal setoran, termasuk pinjaman yang belum genap empat bulan. Setoran sebelum pencairan tidak dialokasikan.

Setoran dapat disunting (tanggal, nominal, catatan) dan dihapus dari halaman detail pinjaman kelompok. Karena bunga, tunggakan, termin, dan alokasi FIFO bergantung pada setoran sebelumnya, `SetoranPinjaman::susunUlang()` memutar ulang seluruh setoran kelompok: semua baris pembayaran dibuang, pinjaman dikembalikan ke termin 1 dengan `tanggal_jatuh_tempo_awal`, lalu setoran dialokasikan ulang urut tanggal. Kode setoran, kode baris `BP-...`, dan kode kas `TXN-...` lama dipakai lagi. Bila salah satu setoran menjadi melebihi tagihan atau tidak punya tagihan, seluruh perubahan dibatalkan. Setoran baru juga melewati jalur yang sama, jadi setoran bertanggal mundur teralokasi dengan benar. Baris kas setoran ditautkan lewat `transaksi_kas.kode_setoran` dan tidak bisa disunting dari menu Kas.

Pinjaman yang sudah punya pembayaran dikunci: tidak bisa disunting atau dihapus. Hapus setorannya lebih dulu bila akad perlu dikoreksi.

## Dua arti status

| Status | Pada satu pinjaman | Pada kelompok |
| --- | --- | --- |
| `lunas` | Sisa pokok ≤ 0 | Tidak ada pinjaman berstatus selain lunas |
| `belum_bayar` | Pokok tersisa dan belum ada pembayaran | Ada pinjaman aktif dan semuanya belum memiliki pembayaran |
| `menunggak` | Pokok tersisa dan sudah ada pembayaran sebagian | Ada pinjaman aktif dan setidaknya satu sudah memiliki pembayaran |

Sejak revisi 16 Oktober 2026 kedua arti `menunggak` sama: sudah menyetor sebagian tapi belum lunas. Status tidak lagi bergantung pada tanggal jatuh tempo, sehingga tidak perlu scheduler.

## Kas manual dan talangan manual

Kas masuk menambah saldo; kas keluar mengurangi saldo. Kolom `biaya` dihitung sebagai jumlah × persentase operasional saat pencatatan/penyuntingan, tetapi tidak ditambahkan atau dikurangi lagi dari saldo. Nilai biaya tidak ditampilkan pada daftar, form, detail, atau faktur Kas. Saldo berjalan dihitung dari seluruh transaksi sampai tanggal dan ID baris tersebut, termasuk transaksi di luar filter daftar.

Dana talangan mempunyai saldo tersendiri. Dana masuk manual boleh tanpa kelompok. Dana keluar wajib kelompok dan nama penerima; nama penerima adalah teks bebas karena tidak ada tabel anggota. Pencatatan talangan manual tidak otomatis membuat baris kas pasangan.

Kartu statistik pada halaman Kas/Talangan umumnya menjumlahkan seluruh data, sedangkan filter hanya mengubah daftar. “Total Kontribusi” talangan mencakup semua dana masuk, termasuk saldo awal/hibah, bukan hanya potongan pinjaman. “Total Pinjaman Pokok” adalah seluruh pokok awal, bukan sisa piutang.

## Laporan

| Jenis | Basis periode | Ringkasan |
| --- | --- | --- |
| Kas | `transaksi_kas.tanggal` | Saldo sebelum periode, masuk, keluar, saldo akhir |
| Pinjaman | `pinjaman.tanggal_transaksi` untuk daftar | Pokok dan potongan akad dalam periode, bunga pembayaran dalam periode, sisa pokok terkini akad yang dipilih |
| Dana talangan | `dana_talangan.tanggal` | Saldo sebelum periode, masuk, klaim keluar, saldo akhir |
| Akhir tahun | Tahun untuk pendapatan dan beban | Posisi aset, hasil usaha, catatan dana amanah |

Laporan akhir tahun mengakui bunga yang diterima pada tahun terpilih dan potongan operasional akad yang dicairkan tahun itu. Beban adalah kas keluar tahun tersebut, selain kategori `Pencairan Pinjaman` dan `Setoran Dana Talangan`. Sisa hasil usaha = pendapatan − beban. Kontribusi serta klaim talangan tidak masuk perhitungan hasil usaha.

**Posisi aset akhir tahun memakai saldo kas, talangan, dan pokok beredar saat laporan dibuat, bukan snapshot 31 Desember tahun terpilih.** Sisa pokok pada laporan pinjaman juga mencakup semua pembayaran yang ada, tanpa batas tanggal akhir laporan. Perilaku ini perlu diperhatikan saat membaca laporan tahun lampau.

HTML menggunakan pagination; PDF mengambil seluruh hasil dalam periode, tanpa batas halaman tabel UI. Faktur cetak memakai dialog browser; unduhan laporan menghasilkan file PDF langsung.

[Indeks dokumentasi](README.md).
