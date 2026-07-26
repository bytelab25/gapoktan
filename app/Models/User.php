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

    public const ROLE_SYSTEM_MANAGER = 'System Manager';
    public const ROLE_KETUA_GAPOKTAN = 'Ketua Gapoktan';
    public const ROLE_KETUA_POKTAN = 'Ketua Poktan';

    /**
     * Role hanya label kepengurusan — sistem tidak membatasi akses per role.
     */
    public const ROLES = [
        self::ROLE_SYSTEM_MANAGER,
        self::ROLE_KETUA_GAPOKTAN,
        self::ROLE_KETUA_POKTAN,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function getInisialAttribute(): string
    {
        return collect(explode(' ', $this->name))
            ->take(2)
            ->map(fn (string $bagian) => mb_substr($bagian, 0, 1))
            ->implode('');
    }
}
