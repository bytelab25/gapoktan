# Analisis Desain Figma — Gapoktan Swagotra Arthagiri

Sumber: https://www.figma.com/design/VdEdMiY2lQzmksnLy2c9Pt/Gapoktan
File berisi 2 set desain duplikat. **Set kiri (x≈6200) yang dipakai.** Set kanan (x≈18311) diabaikan.

## Identitas

- Nama sistem: **Swagotra Arthagiri** — "Sistem Manajemen"
- Entitas: Gapoktan (Gabungan Kelompok Tani) — koperasi simpan pinjam tingkat kelompok

## Design Token

| Token | Hex | Pakai |
|---|---|---|
| primary | `#006C49` | Tombol utama, brand, sidebar aktif (teks) |
| accent | `#059669` | Nilai uang positif, ikon |
| primary-tint | `#ECFCF5` | Background sidebar aktif, badge sukses |
| bg | `#F8FAFC` | Background halaman (slate-50) |
| surface | `#FFFFFF` | Card, tabel |
| border | `#E2E8F0` | Garis (slate-200) |
| table-head | `#F1F5F9` | Header tabel (slate-100) |
| text | `#0F172A` | Teks utama (slate-900) |
| text-muted | `#475569` | Teks sekunder (slate-600) |
| danger | `#F94141` | Tombol Batalkan / Hapus |

Font: sans-serif (Inter-like). Layout: sidebar 260px fixed + konten fluid, canvas 1280px.

## Kerangka Layout

**Sidebar (7 menu):** Beranda · Pengguna · Kas · Pinjaman Kelompok · Dana Talangan · Kelompok Tani · Laporan Keuangan

**Topbar:** ikon `+` (shortcut) · ikon riwayat · ikon gear · blok profil user

- `+` → dropdown: Buat Kelompok Tani / Buat Pinjaman / Buat Pembayaran Pinjaman
- riwayat → dropdown aktivitas terakhir + "Lihat Selengkapnya" (→ Log Aktivitas)
- gear → Pengaturan Sistem
- profil → Data Diri

Catatan: **Log Aktivitas tidak ada di sidebar**, hanya lewat topbar.

## Daftar Layar (33 frame, set kiri)

### Dashboard (203:566)
| Frame | Node |
|---|---|
| Beranda Utama | 2:1039 |
| Profile (Data Diri) | 210:4617 |
| Pengaturan Sistem | 242:8805 |
| Login | 235:7642 |

Beranda = "Ringkasan Keuangan", 4 kartu: Saldo Kas, Dana Talangan, Dana Beredar, Laporan Keuangan Tahunan — masing-masing tombol "Lihat Detail".

Login: username + password + "Ingat saya di perangkat ini". Footer: "Belum memiliki akun? Hubungi Ketua Gapoktan" → **tidak ada registrasi publik**.

Pengaturan Sistem: Suku Bunga Pinjaman `2%`, Potongan Operasional `0.5%`, Potongan Dana Talangan `1%`.

### Kas (203:567)
| Frame | Node |
|---|---|
| Kas (Beranda) | 2:2 |
| Detail | 145:527 |
| Create | 24:2462 / 212:5694 |
| Faktur | 332:935 |

4 kartu statistik: Saldo Saat Ini · Suku Bunga Pinjaman (2%) · Biaya Operasional (0.5%) · Pengeluaran.
Filter: rentang tanggal + jenis transaksi. Tabel: Tanggal · ID Transaksi · Nama Poktan/Deskripsi · Kategori (badge) · Jumlah · Saldo. Pagination + baris per halaman.

Form: Tanggal Transaksi · Jenis Transaksi (toggle Masuk/Keluar) · Kategori (dropdown) · Jumlah (IDR) · Deskripsi/Catatan. Ringkasan: Biaya yang Dihitung + Dampak Pada Saldo.
Dropdown kategori (145:686): Saldo Awal · Gaji · Operasional · **Tambah Kategori** → kategori dinamis.

### Pinjaman Kelompok (203:774)
| Frame | Node |
|---|---|
| Beranda | 2:349 |
| Detail | 160:714 |
| Create | 24:2848 |
| Edit | 214:5959 |
| Bayar Pinjaman | 232:6793 |
| Faktur Bayar | 298:13045 |
| Faktur Pinjaman | 301:13367 |

Beranda: kartu Total Pinjaman Pokok. Tabel: Nama Kelompok · ID Pinjaman (`#LP-2023-001`) · Pinjaman Pokok · Status Pelunasan (LANCAR / MENUNGGAK / HAMPIR LUNAS).

Create: Pilih Kelompok Tani · Tanggal Transaksi · Tanggal Jatuh Tempo · Jumlah Pinjaman · Potongan 1.5% (terkunci, "Suku bunga default koperasi per periode") · Jumlah Diterima · Deskripsi.

Bayar Pinjaman: sisa hutang, list pinjaman kelompok, Bunga 2%/bulan (akumulasi N bulan), Bayar Pinjaman Pokok, Sisa Pinjaman Semua, Total Bayar.

### Dana Talangan (203:775)
| Frame | Node |
|---|---|
| Beranda | 2:688 |
| Create | 24:3880 |
| Faktur | 333:1139 |

Deskripsi resmi: "Pengelolaan dana darurat untuk anggota yang meninggal dan pelaporan keuangan akhir tahun."
3 kartu: Total Saldo Dana · Total Kontribusi (1%) · Klaim Terproses (N kasus).
Tabel: ID Transaksi (`#TX-9021`) · Nama Kelompok · Tanggal · Jumlah.
Form: Kelompok Tani · Tanggal · Jenis Transaksi (Masuk/Keluar) · Penerima ("Masukkan nama anggota yang meninggal") · Jumlah · Deskripsi.

### Kelompok Tani (203:776)
| Frame | Node |
|---|---|
| Beranda | 126:17 |
| Profil | 162:253 |
| Create | 207:2663 |
| Transaksi | 162:455 |

Beranda: kartu total pinjaman + tabel ID · Kelompok Tani · Total Pinjaman Kelompok · Status.
Create/Profil: Nama Kelompok Tani · Nama Ketua · NIK Ketua · Nomor HP · Alamat.
Tab Transaksi: total pinjaman + total bunga pinjaman, tabel Tanggal Transaksi · ID · Tanggal Jatuh Tempo · Deskripsi · Jumlah Pinjaman.

### Pengguna (203:777)
| Frame | Node |
|---|---|
| Beranda | 207:3625 |
| Detail | 207:3847 |
| Create | 207:4244 |
| Edit | 207:4486 |

Field: Nama Pengguna · Username · Nomor HP · Alamat · Password · Role.
Role terlihat: Ketua Gapoktan, Ketua Poktan I / II / III. Header profil: "Admin Utama — System Manager".

### Laporan Keuangan (211:4786)
| Frame | Node |
|---|---|
| Laporan (Kas) | 211:4958 |
| Laporan (Pinjaman) | 333:2141 |
| Laporan (Dana Talangan) | 333:2381 |
| Laporan (Akhir Tahun) | 372:426 / 376:1208 |
| Detail Kas / Pinjaman / Dana Talangan | 381:224 / 381:506 / 381:697 |

Alur: pilih Rentang Tanggal (atau Tahun) + Jenis Laporan → "Buat Laporan" → tampil laporan berkop "Gapoktan Swagotra Arthagiri" → "Unduh Laporan".
Laporan Akhir Tahun: **Posisi Aset** (Total Pinjaman) + **Perhitungan Hasil Usaha** (Pendapatan / Pengeluaran).

### Log (482:744)
Log Aktivitas Sistem (482:254): filter rentang tanggal + jenis, tombol Cetak Log.
Tabel: Tanggal · Nama Pengguna · ID Invoice · Jenis Tindakan (Input/Sunting/Hapus) · Deskripsi Aktivitas.

### Modal (212:5130)
Shortcut create · History dropdown · dropdown jenis_transaksi · dropdown jenis_pinjaman (Belum Bayar/Menunggak/Lunas) · dropdown Buat Transaksi pinjaman (Buat Pinjaman / Bayar Pinjaman).

## Skema Data Turunan

- `users` — nama, username, no_hp, alamat, password, role
- `kelompok_tani` — nama_kelompok, nama_ketua, nik_ketua, no_hp, alamat, status
- `kategori_kas` — nama (dinamis, bisa ditambah user)
- `transaksi_kas` — kode, tanggal, jenis(masuk/keluar), kategori_id, kelompok_tani_id?, jumlah, deskripsi, biaya, saldo
- `pinjaman` — kode(LP-YYYY-NNN), kelompok_tani_id, tgl_transaksi, tgl_jatuh_tempo, jumlah_pinjaman, potongan_persen, jumlah_diterima, deskripsi, status
- `pembayaran_pinjaman` — pinjaman_id, tanggal, bunga, bayar_pokok, total_bayar, sisa
- `dana_talangan` — kode(TX-NNNN), kelompok_tani_id, tanggal, jenis, penerima, jumlah, deskripsi
- `pengaturan` — suku_bunga_pinjaman, potongan_operasional, potongan_dana_talangan
- `log_aktivitas` — user_id, tanggal, id_invoice, jenis_tindakan, deskripsi

## Temuan / Lubang Desain

1. **Tidak ada tabel anggota/petani.** Semua di level kelompok. Tapi Dana Talangan bayar klaim "anggota yang meninggal" lewat field teks bebas. Klaim tak bisa divalidasi, tak bisa cegah klaim ganda.
2. **Bunga 1.5% vs 2% bentrok.** Form pinjaman: "Potongan 1.5% — suku bunga default". Pengaturan: "Suku Bunga Pinjaman 2%". Bayar: "Bunga 2%/bulan".
3. **Kolom Saldo running di tabel Kas.** Kalau transaksi bisa disunting/dihapus, saldo harus dihitung ulang. Data contoh di Figma tidak konsisten.
4. **Tiga kosakata status pinjaman.** LANCAR/MENUNGGAK/HAMPIR LUNAS vs Belum Bayar/Lunas vs Belum Bayar/Menunggak/Lunas.
5. **Role tanpa aturan akses.** Semua layar tampil sidebar penuh yang sama. Tidak jelas Ketua Poktan lihat apa.
6. **Dua konsep riwayat.** Dropdown History (aktivitas pinjaman) beda dari Log Aktivitas Sistem (audit CRUD).
7. **Kategori kas dinamis tapi warna badge statis** per kategori di desain.

## Koreksi Setelah Tinjauan Ulang (30 Juli 2026)

Dua layar Laporan diambil ulang dari Figma (node `211:4958` dan `372:426`) dan ternyata
implementasi awal salah bentuk:

- **Laporan Keuangan itu satu halaman**, bukan halaman cetak terpisah. Bar filter
  (`Rentang Tanggal` / `Tahun` + `Jenis Laporan` + tombol `Buat Laporan`) berada di atas,
  hasilnya langsung tampil di bawahnya di dalam layout aplikasi lengkap dengan sidebar.
  Tombol `Unduh Laporan` yang membuka versi berkop ada di header hasil.
- **Warna badge kategori kas mengikuti jenis transaksi**, bukan nama kategori: masuk
  memakai hijau, keluar memakai abu. Ini menutup temuan nomor 7 di atas.
- **Kolom Saldo di tabel laporan berwarna hijau untuk baris masuk** dan gelap untuk baris
  keluar.
- Tabel laporan punya pagination bernomor plus pemilih `Baris per halaman`, dan diurutkan
  terbaru lebih dulu.

Node yang belum sempat diambil karena batas kuota Figma MCP: `333:2141` (Laporan Pinjaman),
`333:2381` (Laporan Dana Talangan), `376:1208` (Akhir Tahun setelah dibuat), `381:224`,
`381:506`, `381:697` (layar detail). Ketiga laporan itu dibangun mengikuti kerangka yang
sama dengan Laporan Kas.

## Aturan Bisnis yang Ditetapkan Pemilik Produk

- **Role**: `sekretaris` dan `bendahara` identik dan merupakan administrator penuh (satu-satunya
  yang boleh mengelola akun). `ketua` mengelola seluruh data operasional plus Pengaturan Sistem,
  tapi tidak bisa mengubah akun. `user` hanya boleh melihat.
- **Pinjaman berjalan per termin 4 bulan** (4 → 8 → 12). Bunga tetap dihitung per bulan dari
  sisa pokok, tapi jatuh temponya kelipatan 4 bulan. Melunasi bunga tanpa memotong pokok
  menggeser jatuh tempo ke termin berikutnya.
- **Pembayaran memprioritaskan bunga**, sisanya baru memotong pokok, sehingga kelompok boleh
  menyetor kurang dari bunga (kekurangannya jadi `tunggakan_bunga`).
- **FIFO antar pinjaman**: satu kelompok boleh punya beberapa pinjaman berjalan; setoran masuk
  ke pinjaman dengan tanggal pencairan paling awal, sisanya mengalir ke berikutnya. Karena itu
  form bayar berada di level kelompok (`kelompok/{id}/bayar`), bukan level pinjaman.
- **Potongan muka 1,5% = 0,5% operasional + 1% dana talangan.** Pencairan pinjaman otomatis
  membuat dua baris kas keluar (dana yang diterima kelompok dan setoran ke pos dana talangan)
  serta satu baris dana talangan masuk. Porsi operasional tidak jadi baris kas karena uangnya
  tidak pernah keluar — hanya disimpan di kolom `biaya`.

## Unduh Laporan (30 Juli 2026)

Tombol `Unduh Laporan` sekarang menghasilkan PDF lewat `barryvdh/laravel-dompdf`, bukan
dialog cetak browser. Templatnya terpisah di `resources/views/laporan/pdf.blade.php` dan
memakai CSS dasar saja — dompdf tidak mengerti flexbox maupun grid, jadi tata letaknya
tabel. Catatan teknis yang perlu diingat:

- Font `Helvetica` dipakai karena termasuk font inti PDF dan tidak perlu ditanam. Memakai
  `DejaVu Sans` membengkakkan berkas dari ~73 KB jadi ~930 KB tanpa manfaat, sebab semua
  karakter di laporan ini tercakup WinAnsi.
- `counter(pages)` tidak berfungsi di dompdf (selalu 0), jadi kaki halaman hanya menulis
  nomor halaman tanpa totalnya.
- `page-break-inside: avoid` di level tabel membuat dompdf mendorong blok tanda tangan ke
  halaman baru yang kosong. Aturan global pada `tr` sudah cukup.
- Laporan tabel dicetak lanskap, laporan akhir tahun potret.

Halaman `laporan/cetak` beserta komponen `laporan-layout` dan `laporan-kop` dihapus karena
sudah tidak dipakai.

## Kedip Gelap Antar-Halaman (30 Juli 2026)

Sistem ini hanya punya tampilan terang, tapi tidak pernah menyatakannya ke browser. Di OS
mode gelap, browser melukis kanvas root dengan warna gelap bawaannya pada sela antar-navigasi
— dan di mode `npm run dev` Vite menyuntikkan CSS lewat JavaScript, sehingga selanya makin
terlihat. Perbaikannya berlapis:

1. `<meta name="color-scheme" content="light">` plus `<style>html { background-color: #F8FAFC }</style>`
   inline di `<head>` setiap layout, dibaca browser sebelum stylesheet turun.
2. `:root { color-scheme: light; }` dan latar pada `html` (bukan cuma `body`) di
   `resources/css/app.css`.

Diverifikasi dengan Chrome yang dipaksa `prefers-color-scheme: dark`: seluruh halaman tetap
`color-scheme: light` dengan latar `rgb(248, 250, 252)`.
