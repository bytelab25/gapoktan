{{--
    Template khusus dompdf. Tidak memakai Tailwind karena dompdf tidak mengerti
    flexbox maupun grid — semua tata letak di sini pakai tabel dan CSS dasar.
--}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $hasil['judul'] }}</title>
    <style>
        @page { margin: 28mm 12mm 20mm 12mm; }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            /* Helvetica adalah font inti PDF, tidak perlu ditanam. Memakai
               DejaVu membengkakkan berkas dari ~60 KB jadi ~930 KB, sementara
               semua karakter di laporan ini sudah tercakup WinAnsi. */
            font-family: Helvetica, sans-serif;
            font-size: 9pt;
            color: #0F172A;
        }

        /* Kop dan kaki dipasang fixed supaya ikut tercetak di tiap halaman. */
        .kop {
            position: fixed;
            top: -22mm;
            left: 0;
            right: 0;
            height: 20mm;
            border-bottom: 1.4pt solid #0F172A;
            padding-bottom: 3mm;
        }

        .kop td { vertical-align: middle; border: 0; padding: 0; }
        .kop img { height: 13mm; }
        .kop .nama { font-size: 12pt; font-weight: bold; text-transform: uppercase; letter-spacing: 0.4pt; }
        .kop .sub { font-size: 8pt; color: #475569; }
        .kop .judul { font-size: 11pt; font-weight: bold; text-transform: uppercase; text-align: right; }
        .kop .periode { font-size: 8pt; color: #475569; text-align: right; }

        .kaki {
            position: fixed;
            bottom: -14mm;
            left: 0;
            right: 0;
            font-size: 7.5pt;
            color: #475569;
            border-top: 0.5pt solid #E2E8F0;
            padding-top: 2mm;
        }

        .kaki td { border: 0; padding: 0; }
        .kaki .kanan { text-align: right; }
        /* dompdf hanya mengerti counter(page); counter(pages) selalu 0,
           jadi total halaman tidak ditampilkan. */
        .nomor:before { content: counter(page); }

        table { width: 100%; border-collapse: collapse; }

        .ringkasan { margin-bottom: 5mm; }
        .ringkasan td {
            width: 25%;
            border: 0.5pt solid #E2E8F0;
            background: #F8FAFC;
            padding: 2.5mm 3mm;
        }
        .ringkasan .label { font-size: 7.5pt; color: #475569; text-transform: uppercase; letter-spacing: 0.3pt; }
        .ringkasan .nilai { font-size: 10pt; font-weight: bold; padding-top: 1mm; }

        .data thead th {
            background: #F1F5F9;
            border-top: 0.6pt solid #CBD5E1;
            border-bottom: 0.6pt solid #CBD5E1;
            padding: 2.2mm 2.5mm;
            font-size: 7.5pt;
            text-transform: uppercase;
            letter-spacing: 0.3pt;
            color: #475569;
            text-align: left;
        }

        .data tbody td {
            border-bottom: 0.4pt solid #E2E8F0;
            padding: 2.2mm 2.5mm;
            vertical-align: top;
        }

        .data tfoot td {
            border-top: 1pt solid #0F172A;
            padding: 2.5mm;
            font-weight: bold;
        }

        thead { display: table-header-group; }
        tr { page-break-inside: avoid; }

        .kanan { text-align: right; }
        .satu-baris { white-space: nowrap; }
        .tengah { text-align: center; }
        .tebal { font-weight: bold; }
        .redup { color: #475569; font-size: 7.5pt; }
        .hijau { color: #006C49; }
        .merah { color: #F94141; }
        .kosong { padding: 12mm 0; text-align: center; color: #94A3B8; }

        .bagian { margin-bottom: 7mm; }
        .bagian h2 {
            margin: 0 0 2mm;
            padding-bottom: 1.5mm;
            border-bottom: 0.6pt solid #CBD5E1;
            font-size: 9.5pt;
            text-transform: uppercase;
            letter-spacing: 0.4pt;
        }

        .rincian td { border-bottom: 0.4pt solid #E2E8F0; padding: 2mm 0; }
        .rincian .indent { padding-left: 5mm; }
        .rincian .total td { border-top: 1pt solid #0F172A; border-bottom: 0; font-weight: bold; }
        .sisa {
            margin-top: 3mm;
            background: #ECFCF5;
            padding: 3mm 4mm;
        }
        .sisa .teks { font-size: 9pt; font-weight: bold; text-transform: uppercase; color: #065F46; }
        .sisa .angka { font-size: 14pt; font-weight: bold; text-align: right; }
        .sisa td { border: 0; padding: 0; }

        .catatan { font-size: 8pt; color: #475569; line-height: 1.5; }

        /* `page-break-inside: avoid` di level tabel membuat dompdf mendorong
           blok ini ke halaman baru walau ruangnya masih cukup; aturan global
           pada `tr` sudah cukup menjaga tanda tangan tidak terbelah. */
        /* Jarak tanda tangan dibuat dari elemen spacer bertinggi tetap, bukan
           margin, karena dompdf menghitung margin di dalam sel tabel terlalu
           besar dan mendorong blok ini ke halaman baru yang kosong. */
        .ttd { margin-top: 4mm; }
        .ttd td { border: 0; padding: 0; }
        .ttd .kotak { width: 55mm; }
        .ttd .ruang { height: 16mm; }
        .ttd .garis { border-top: 0.6pt solid #94A3B8; padding-top: 1.5mm; }
    </style>
</head>
<body>
    <table class="kop">
        <tr>
            <td style="width: 16mm;"><img src="{{ public_path('img/logo-256.png') }}" alt=""></td>
            <td>
                <div class="nama">Gapoktan Swagotra Arthagiri</div>
                <div class="sub">Sistem Manajemen Koperasi</div>
            </td>
            <td>
                <div class="judul">{{ $hasil['judul'] }}</div>
                <div class="periode">{{ $hasil['periode'] }}</div>
            </td>
        </tr>
    </table>

    <table class="kaki">
        <tr>
            <td>Dicetak {{ tanggal_id($dicetakPada, 'd F Y') }} pukul {{ $dicetakPada->format('H:i') }} oleh {{ $penandatangan->name }}</td>
            <td class="kanan">Halaman <span class="nomor"></span></td>
        </tr>
    </table>

    @if (isset($hasil['ringkasan']))
        <table class="ringkasan">
            <tr>
                @foreach ($hasil['ringkasan'] as $kartu)
                    <td>
                        <div class="label">{{ $kartu['label'] }}</div>
                        <div class="nilai">{{ $kartu['nilai'] }}</div>
                    </td>
                @endforeach
            </tr>
        </table>
    @endif

    @if ($hasil['jenis'] === 'kas')
        <table class="data">
            <thead>
                <tr>
                    <th style="width: 24mm;">Tanggal</th>
                    <th style="width: 24mm;">ID Transaksi</th>
                    <th>Nama Poktan / Deskripsi</th>
                    <th style="width: 38mm;">Kategori</th>
                    <th style="width: 30mm;" class="kanan">Jumlah</th>
                    <th style="width: 32mm;" class="kanan">Saldo</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($hasil['baris'] as $transaksi)
                    <tr>
                        <td class="satu-baris">{{ tanggal_id($transaksi->tanggal) }}</td>
                        <td>{{ $transaksi->kode }}</td>
                        <td>
                            <span class="tebal">{{ $transaksi->kelompokTani?->nama_kelompok ?? 'Umum' }}</span>
                            <div class="redup">{{ $transaksi->deskripsi ?: '-' }}</div>
                        </td>
                        <td>{{ $transaksi->kategori->nama }}</td>
                        <td class="kanan {{ $transaksi->jenis === 'masuk' ? '' : 'merah' }}">
                            {{ rupiah_bertanda($transaksi->nilai) }}
                        </td>
                        <td class="kanan tebal">{{ rupiah(\App\Models\TransaksiKas::saldoSampai($transaksi)) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="kosong">Tidak ada transaksi kas pada periode ini.</td></tr>
                @endforelse
            </tbody>
            @if ($hasil['baris']->isNotEmpty())
                <tfoot>
                    <tr>
                        <td colspan="4" class="kanan">Saldo Akhir Periode</td>
                        <td colspan="2" class="kanan hijau">{{ $hasil['ringkasan'][3]['nilai'] }}</td>
                    </tr>
                </tfoot>
            @endif
        </table>
    @endif

    @if ($hasil['jenis'] === 'pinjaman')
        <table class="data">
            <thead>
                <tr>
                    <th style="width: 24mm;">Tanggal</th>
                    <th style="width: 26mm;">ID Pinjaman</th>
                    <th>Nama Kelompok</th>
                    <th style="width: 30mm;" class="kanan">Pinjaman Pokok</th>
                    <th style="width: 28mm;" class="kanan">Potongan</th>
                    <th style="width: 30mm;" class="kanan">Sisa Pokok</th>
                    <th style="width: 24mm;">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($hasil['baris'] as $pinjaman)
                    <tr>
                        <td class="satu-baris">{{ tanggal_id($pinjaman->tanggal_transaksi) }}</td>
                        <td>{{ $pinjaman->kode }}</td>
                        <td>
                            <span class="tebal">{{ $pinjaman->kelompokTani->nama_kelompok }}</span>
                            <div class="redup">
                                Jatuh tempo {{ tanggal_id($pinjaman->tanggal_jatuh_tempo) }} · termin {{ $pinjaman->termin }}
                            </div>
                        </td>
                        <td class="kanan">{{ rupiah($pinjaman->jumlah_pinjaman) }}</td>
                        <td class="kanan">
                            {{ rupiah($pinjaman->potongan_nominal) }}
                            <div class="redup">{{ (float) $pinjaman->potongan_persen }}%</div>
                        </td>
                        <td class="kanan tebal">{{ rupiah($pinjaman->sisa_pokok) }}</td>
                        <td>{{ $pinjaman->label_status }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="kosong">Tidak ada pinjaman yang dicairkan pada periode ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    @endif

    @if ($hasil['jenis'] === 'dana_talangan')
        <table class="data">
            <thead>
                <tr>
                    <th style="width: 22mm;">Tanggal</th>
                    <th style="width: 26mm;">ID Transaksi</th>
                    <th>Nama Kelompok</th>
                    <th>Penerima / Sumber</th>
                    <th style="width: 34mm;" class="kanan">Jumlah</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($hasil['baris'] as $transaksi)
                    <tr>
                        <td class="satu-baris">{{ tanggal_id($transaksi->tanggal) }}</td>
                        <td>{{ $transaksi->kode }}</td>
                        <td class="tebal">{{ $transaksi->kelompokTani?->nama_kelompok ?? '-' }}</td>
                        <td>
                            @if ($transaksi->jenis === \App\Models\DanaTalangan::JENIS_KELUAR)
                                {{ $transaksi->penerima ?: '-' }}
                                <div class="redup">Klaim santunan</div>
                            @else
                                {{ $transaksi->pinjaman?->kode ?? 'Setoran langsung' }}
                                <div class="redup">Kontribusi masuk</div>
                            @endif
                        </td>
                        <td class="kanan tebal {{ $transaksi->jenis === 'masuk' ? 'hijau' : 'merah' }}">
                            {{ rupiah_bertanda($transaksi->nilai) }}
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="kosong">Tidak ada transaksi dana talangan pada periode ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    @endif

    @if ($hasil['jenis'] === 'akhir_tahun')
        <div class="bagian">
            <h2>Posisi Aset</h2>
            <table class="rincian">
                @foreach ($hasil['aset'] as $label => $nilai)
                    <tr>
                        <td>{{ $label }}</td>
                        <td class="kanan tebal">{{ rupiah($nilai) }}</td>
                    </tr>
                @endforeach
                <tr class="total">
                    <td>Total Aset</td>
                    <td class="kanan hijau">{{ rupiah($hasil['totalAset']) }}</td>
                </tr>
            </table>
        </div>

        <div class="bagian">
            <h2>Perhitungan Hasil Usaha</h2>

            <table class="rincian">
                <tr>
                    <td class="tebal" colspan="2">Pendapatan:</td>
                </tr>
                @foreach ($hasil['pendapatan'] as $label => $nilai)
                    <tr>
                        <td class="indent">{{ $label }}</td>
                        <td class="kanan">{{ rupiah($nilai) }}</td>
                    </tr>
                @endforeach
                <tr>
                    <td class="indent tebal">Total Pendapatan</td>
                    <td class="kanan tebal hijau">{{ rupiah($hasil['totalPendapatan']) }}</td>
                </tr>

                <tr>
                    <td class="tebal" colspan="2" style="padding-top: 4mm;">Pengeluaran:</td>
                </tr>
                @forelse ($hasil['pengeluaran'] as $label => $nilai)
                    <tr>
                        <td class="indent">Beban {{ $label }}</td>
                        <td class="kanan merah">({{ rupiah($nilai) }})</td>
                    </tr>
                @empty
                    <tr>
                        <td class="indent redup" colspan="2">Tidak ada beban tunai tercatat tahun ini.</td>
                    </tr>
                @endforelse
                <tr>
                    <td class="indent tebal">Total Pengeluaran</td>
                    <td class="kanan tebal merah">({{ rupiah($hasil['totalPengeluaran']) }})</td>
                </tr>
            </table>

            <table class="sisa">
                <tr>
                    <td class="teks">Sisa Hasil Usaha</td>
                    <td class="angka {{ $hasil['sisaHasilUsaha'] < 0 ? 'merah' : 'hijau' }}">
                        {{ rupiah_bertanda($hasil['sisaHasilUsaha']) }}
                    </td>
                </tr>
            </table>
        </div>

        <div class="bagian">
            <h2>Dana Talangan <span class="redup" style="text-transform: none; letter-spacing: 0;">— dana amanah anggota, tidak masuk perhitungan hasil usaha</span></h2>

            <table class="rincian">
                <tr>
                    <td class="indent tebal">Kontribusi Diterima {{ $hasil['tahun'] }}</td>
                    <td class="kanan tebal">{{ rupiah($hasil['kontribusiTalangan']) }}</td>
                </tr>
                <tr>
                    <td class="indent tebal">Klaim Dibayar {{ $hasil['tahun'] }}</td>
                    <td class="kanan tebal merah">({{ rupiah($hasil['klaimTalangan']) }})</td>
                </tr>
            </table>

            <table class="sisa">
                <tr>
                    <td class="teks">Saldo Dana Talangan</td>
                    <td class="angka {{ $hasil['saldoTalangan'] < 0 ? 'merah' : 'hijau' }}">
                        {{ rupiah_bertanda($hasil['saldoTalangan']) }}
                    </td>
                </tr>
            </table>
        </div>

        <div class="bagian">
            <h2>Catatan</h2>
            <p class="catatan">
                Yang diakui sebagai pendapatan hanyalah bunga pinjaman yang sudah benar-benar diterima
                dan porsi potongan operasional. Pencairan pinjaman tidak dicatat sebagai beban karena
                uangnya berubah bentuk menjadi piutang, bukan hilang.
            </p>
        </div>
    @endif

    <table class="ttd">
        <tr>
            <td></td>
            <td class="kotak">
                <div>{{ tanggal_id($dicetakPada, 'd F Y') }}</div>
                <div>Mengetahui,</div>
                <div class="ruang"></div>
                <div class="garis">
                    <span class="tebal">{{ $penandatangan->name }}</span>
                    <div class="redup">{{ $penandatangan->label_role }}</div>
                </div>
            </td>
        </tr>
    </table>
</body>
</html>
