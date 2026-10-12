import { test } from 'node:test';
import assert from 'node:assert/strict';
import { formatNominal, formatMasukanNominal, parseNominal, tambahBulan } from '../../resources/js/nominal.js';

test('nominal rupiah dibaca sebagai bilangan bulat untuk server', () => {
    for (const [teks, angka] of [['1.000.000', 1000000], ['Rp 25.123', 25123], ['1000', 1000], ['500.000.000', 500000000]]) {
        assert.equal(parseNominal(teks), angka);
        assert.equal(parseNominal(formatNominal(angka)), angka);
    }
});

test('masukan yang sedang diketik diberi titik setiap tiga angka', () => {
    assert.equal(formatMasukanNominal('500000000'), '500.000.000');
    assert.equal(formatMasukanNominal('1.0000'), '10.000');
    assert.equal(formatMasukanNominal('0001000'), '1.000');
    assert.equal(formatMasukanNominal('12,5abc'), '125');
    assert.equal(formatMasukanNominal(''), '');
});

test('nominal kosong, negatif, desimal, dan teks ditolak', () => {
    for (const teks of ['', '-1.000', '1,5', '1.000,50', '1e6', 'NaN', '100rupiah']) {
        assert.equal(parseNominal(teks), null, teks);
    }
});

test('tampilan nominal selalu tanpa angka desimal', () => {
    assert.equal(formatNominal(15000.01), '15.000');
    assert.equal(formatNominal(985000.5), '985.001');
    assert.equal(formatNominal(0), '0');
});

test('tanggal standar empat bulan melewati tahun dan mengikuti overflow Carbon', () => {
    assert.equal(tambahBulan('2026-10-08', 4), '2027-02-08');
    assert.equal(tambahBulan('2026-10-31', 4), '2027-03-03');
    assert.equal(tambahBulan('', 4), '');
});
