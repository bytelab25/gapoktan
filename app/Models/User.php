<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'username', 'email', 'no_hp', 'alamat', 'role', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_SEKRETARIS = 'sekretaris';

    public const ROLE_BENDAHARA = 'bendahara';

    public const ROLE_KETUA = 'ketua';

    public const ROLE_USER = 'user';

    public const LABEL_ROLE = [
        self::ROLE_SEKRETARIS => 'Sekretaris',
        self::ROLE_BENDAHARA => 'Bendahara',
        self::ROLE_KETUA => 'Ketua Gapoktan',
        self::ROLE_USER => 'Pengguna',
    ];

    /**
     * Sekretaris dan bendahara mengelola data operasional dan akun.
     */
    public const ROLE_ADMIN = [self::ROLE_SEKRETARIS, self::ROLE_BENDAHARA];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function getLabelRoleAttribute(): string
    {
        return self::LABEL_ROLE[$this->role] ?? $this->role;
    }

    public function getInisialAttribute(): string
    {
        return collect(explode(' ', $this->name))
            ->take(2)
            ->map(fn (string $bagian) => mb_substr($bagian, 0, 1))
            ->implode('');
    }

    /**
     * Sekretaris dan bendahara mengubah data operasional; ketua mengawasi
     * transaksi dan memegang pengaturan sistem.
     */
    public function isAdmin(): bool
    {
        return in_array($this->role, self::ROLE_ADMIN, true);
    }

    /**
     * Boleh menambah, menyunting, dan menghapus data operasional.
     */
    public function bisaKelola(): bool
    {
        return $this->isAdmin();
    }

    /**
     * Pengurus dapat melihat pengaturan suku bunga dan potongan.
     */
    public function bisaLihatPengaturan(): bool
    {
        return $this->isAdmin() || $this->role === self::ROLE_KETUA;
    }

    public function bisaKelolaPengaturan(): bool
    {
        return $this->role === self::ROLE_KETUA;
    }

    public function bisaKelolaPengguna(): bool
    {
        return $this->isAdmin();
    }

    /**
     * Menu pengurus (Pengguna dan Kelompok Tani). Role "user" adalah ketua
     * kelompok tani yang membuka dari HP, jadi ia hanya diberi menu yang
     * tampil di beranda: kas, pinjaman, dana talangan, dan laporan.
     */
    public function bisaLihatMenuPengurus(): bool
    {
        return $this->role !== self::ROLE_USER;
    }
}
