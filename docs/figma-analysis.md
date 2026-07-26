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
