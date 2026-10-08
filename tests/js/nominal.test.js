import { test } from 'node:test';
import assert from 'node:assert/strict';
import { formatNominal, formatMasukanNominal, parseNominal, tambahBulan } from '../../resources/js/nominal.js';

test('nominal Indonesia mempertahankan sen saat dikonversi untuk server', () => {
    for (const [teks, angka] of [['1.000.000,50', 1000000.50], ['Rp 25.123,99', 25123.99], ['1000', 1000], ['1,05', 1.05]]) {
        assert.equal(parseNominal(teks), angka);
        assert.equal(parseNominal(formatNominal(angka)), angka);
        assert.equal(Number(parseNominal(teks).toFixed(2)), angka);
    }
});

test('masukan yang sedang diketik menjaga koma dan posisi desimal', () => {
    assert.equal(formatMasukanNominal('1000000,'), '1.000.000,');
    assert.equal(formatMasukanNominal('1000000,5'), '1.000.000,5');
    assert.equal(formatNominal(parseNominal('1000000,5')), '1.000.000,50');
    assert.equal(formatMasukanNominal('0001000,50'), '1.000,50');
});

test('nominal kosong, negatif, teks, dan lebih dari dua desimal ditolak', () => {
    for (const teks of ['', '-1.000,50', '1,234', '1,2,3', '1e6', 'NaN', '100rupiah']) {
        assert.equal(parseNominal(teks), null, teks);
    }
});

test('potongan dan jumlah diterima menampilkan dua angka desimal', () => {
    const jumlah = parseNominal('1.000.000,50');
    const potongan = Math.round(jumlah * 1.5) / 100;
    assert.equal(formatNominal(potongan), '15.000,01');
    assert.equal(formatNominal(jumlah - potongan), '985.000,49');
});

test('tanggal standar empat bulan melewati tahun dan mengikuti overflow Carbon', () => {
    assert.equal(tambahBulan('2026-10-08', 4), '2027-02-08');
    assert.equal(tambahBulan('2026-10-31', 4), '2027-03-03');
    assert.equal(tambahBulan('', 4), '');
});
