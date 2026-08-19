<?php

namespace App\Http\Controllers;

use App\Models\DanaTalangan;
use App\Models\KelompokTani;
use App\Models\LogAktivitas;
use App\Models\Pengaturan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DanaTalanganController extends Controller
{
    public function index(Request $request): View
    {
        $transaksi = DanaTalangan::query()
            ->with('kelompokTani')
            ->when($request->string('jenis')->trim()->value(), fn ($query, $jenis) => $query->where('jenis', $jenis))
            ->when($request->integer('kelompok'), fn ($query, $id) => $query->where('kelompok_tani_id', $id))
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->paginate($request->integer('per_halaman', 10))
            ->withQueryString();

        return view('talangan.index', [
            'daftarTransaksi' => $transaksi,
            'saldo' => DanaTalangan::saldoTotal(),
            'totalKontribusi' => DanaTalangan::where('jenis', DanaTalangan::JENIS_MASUK)->sum('jumlah'),
            'jumlahKlaim' => DanaTalangan::where('jenis', DanaTalangan::JENIS_KELUAR)->count(),
            'pengaturan' => Pengaturan::ambil(),
            'daftarKelompok' => KelompokTani::orderBy('nama_kelompok')->get(),
        ]);
    }

    public function create(): View
    {
        return view('talangan.create', $this->dataForm());
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validasi($request);
        $data['kode'] = $this->kodeBerikutnya();
        $data['user_id'] = $request->user()->id;

        $transaksi = DanaTalangan::create($data);

        LogAktivitas::catat(
            LogAktivitas::TINDAKAN_INPUT,
            'Menambahkan transaksi dana talangan '.rupiah($transaksi->jumlah),
            $transaksi->kode,
        );

        return redirect()
            ->route('talangan.show', $transaksi)
            ->with('sukses', 'Transaksi dana talangan berhasil dicatat.');
    }

    public function show(DanaTalangan $talangan): View
    {
        return view('talangan.show', [
            'transaksi' => $talangan->load('kelompokTani'),
        ]);
    }

    public function faktur(DanaTalangan $talangan): View
    {
        return view('talangan.faktur', [
            'transaksi' => $talangan->load('kelompokTani'),
        ]);
    }

    public function edit(DanaTalangan $talangan): View|RedirectResponse
    {
        if ($talangan->otomatis) {
            return redirect()->route('talangan.show', $talangan)->with('gagal', $this->pesanOtomatis());
        }

        return view('talangan.edit', [...$this->dataForm(), 'transaksi' => $talangan]);
    }

    public function update(Request $request, DanaTalangan $talangan): RedirectResponse
    {
        if ($talangan->otomatis) {
            return back()->with('gagal', $this->pesanOtomatis());
        }

        $talangan->update($this->validasi($request));

        LogAktivitas::catat(
            LogAktivitas::TINDAKAN_SUNTING,
            'Mengubah transaksi dana talangan '.rupiah($talangan->jumlah),
            $talangan->kode,
        );

        return redirect()
            ->route('talangan.show', $talangan)
            ->with('sukses', 'Transaksi dana talangan berhasil diperbarui.');
    }

    public function destroy(DanaTalangan $talangan): RedirectResponse
    {
        if ($talangan->otomatis) {
            return back()->with('gagal', $this->pesanOtomatis());
        }

        $kode = $talangan->kode;
        $jumlah = rupiah($talangan->jumlah);
        $talangan->delete();

        LogAktivitas::catat(LogAktivitas::TINDAKAN_HAPUS, "Menghapus transaksi dana talangan {$jumlah}", $kode);

        return redirect()
            ->route('talangan.index')
            ->with('sukses', 'Transaksi dana talangan berhasil dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function dataForm(): array
    {
        return [
            'daftarKelompok' => KelompokTani::orderBy('nama_kelompok')->get(),
            'pengaturan' => Pengaturan::ambil(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validasi(Request $request): array
    {
        return $request->validate([
            // Dana masuk boleh tanpa kelompok (mis. hibah atau setoran kolektif),
            // tapi klaim keluar harus jelas kelompok penerimanya.
            'kelompok_tani_id' => [
                'nullable',
                'required_if:jenis,'.DanaTalangan::JENIS_KELUAR,
                Rule::exists('kelompok_tani', 'id'),
            ],
            'tanggal' => ['required', 'date'],
            'jenis' => ['required', Rule::in([DanaTalangan::JENIS_MASUK, DanaTalangan::JENIS_KELUAR])],
            // Penerima hanya relevan untuk klaim santunan (dana keluar).
            'penerima' => ['nullable', 'required_if:jenis,'.DanaTalangan::JENIS_KELUAR, 'string', 'max:255'],
            'jumlah' => ['required', 'numeric', 'min:1'],
            'deskripsi' => ['nullable', 'string', 'max:1000'],
        ], messages: [
            'kelompok_tani_id.required_if' => 'Kelompok tani wajib dipilih untuk dana keluar.',
            'penerima.required_if' => 'Nama penerima wajib diisi untuk dana keluar.',
        ], attributes: [
            'kelompok_tani_id' => 'kelompok tani',
        ]);
    }

    private function kodeBerikutnya(): string
    {
        return sprintf('DT-%04d', (int) DanaTalangan::max('id') + 1);
    }

    private function pesanOtomatis(): string
    {
        return 'Kontribusi ini lahir otomatis dari pencairan pinjaman. Ubah data pinjamannya bila nominalnya salah.';
    }
}
