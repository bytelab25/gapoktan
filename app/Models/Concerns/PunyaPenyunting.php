<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Transaksi menyimpan pencatat awal (user_id) dan orang yang terakhir
 * mengubahnya (diubah_oleh_id). Faktur ditandatangani yang terakhir.
 */
trait PunyaPenyunting
{
    public function diubahOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diubah_oleh_id');
    }

    public function getPenandaTanganAttribute(): ?User
    {
        return $this->diubahOleh ?? $this->user;
    }
}
