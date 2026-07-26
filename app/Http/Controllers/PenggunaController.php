<?php

namespace App\Http\Controllers;

use App\Models\LogAktivitas;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class PenggunaController extends Controller
{
    public function index(Request $request): View
    {
        $pengguna = User::query()
            ->when($request->string('cari')->trim()->value(), fn ($query, $cari) => $query
                ->where('name', 'like', "%{$cari}%")
                ->orWhere('username', 'like', "%{$cari}%"))
            ->latest('id')
            ->paginate(10)
            ->withQueryString();

        return view('pengguna.index', [
            'daftarPengguna' => $pengguna,
        ]);
    }

    public function create(): View
    {
        return view('pengguna.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'alpha_dash', Rule::unique('users')],
            'no_hp' => ['required', 'string', 'max:20'],
            'alamat' => ['required', 'string', 'max:255'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'role' => ['required', Rule::in(User::ROLES)],
        ], attributes: $this->namaAtribut());

        $pengguna = User::create($data);

        LogAktivitas::catat(LogAktivitas::TINDAKAN_INPUT, "Menambahkan pengguna {$pengguna->name}");

        return redirect()
            ->route('pengguna.show', $pengguna)
            ->with('sukses', 'Pengguna berhasil dibuat.');
    }

    public function show(User $pengguna): View
    {
        return view('pengguna.show', [
            'pengguna' => $pengguna,
        ]);
    }

    public function edit(User $pengguna): View
    {
        return view('pengguna.edit', [
            'pengguna' => $pengguna,
        ]);
    }

    public function update(Request $request, User $pengguna): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'alpha_dash', Rule::unique('users')->ignore($pengguna)],
            'no_hp' => ['required', 'string', 'max:20'],
            'alamat' => ['required', 'string', 'max:255'],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'role' => ['required', Rule::in(User::ROLES)],
        ], attributes: $this->namaAtribut());

        // Password hanya diganti kalau kolomnya benar-benar diisi.
        if (blank($data['password'])) {
            unset($data['password']);
        }

        $pengguna->update($data);

        LogAktivitas::catat(LogAktivitas::TINDAKAN_SUNTING, "Mengubah data pengguna {$pengguna->name}");

        return redirect()
            ->route('pengguna.show', $pengguna)
            ->with('sukses', 'Data pengguna berhasil diperbarui.');
    }

    public function destroy(Request $request, User $pengguna): RedirectResponse
    {
        if ($pengguna->is($request->user())) {
            return back()->with('gagal', 'Anda tidak bisa menghapus akun yang sedang dipakai.');
        }

        if (User::count() <= 1) {
            return back()->with('gagal', 'Pengguna terakhir tidak bisa dihapus.');
        }

        $nama = $pengguna->name;
        $pengguna->delete();

        LogAktivitas::catat(LogAktivitas::TINDAKAN_HAPUS, "Menghapus pengguna {$nama}");

        return redirect()
            ->route('pengguna.index')
            ->with('sukses', 'Pengguna berhasil dihapus.');
    }

    /**
     * @return array<string, string>
     */
    private function namaAtribut(): array
    {
        return [
            'name' => 'nama pengguna',
            'no_hp' => 'nomor HP',
        ];
    }
}
