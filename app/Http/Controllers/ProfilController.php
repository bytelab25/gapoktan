<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfilController extends Controller
{
    public function show(Request $request): View
    {
        return view('profil.show', [
            'pengguna' => $request->user(),
        ]);
    }

    public function edit(Request $request): View
    {
        return view('profil.edit', [
            'pengguna' => $request->user(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $pengguna = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'alpha_dash', Rule::unique('users')->ignore($pengguna)],
            'no_hp' => ['required', 'string', 'max:20'],
            'alamat' => ['required', 'string', 'max:255'],
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ], attributes: [
            'name' => 'nama pengguna',
            'no_hp' => 'nomor HP',
        ]);

        if (blank($data['password'])) {
            unset($data['password']);
        }

        $pengguna->update($data);

        return redirect()
            ->route('profil.show')
            ->with('sukses', 'Data diri berhasil diperbarui.');
    }
}
