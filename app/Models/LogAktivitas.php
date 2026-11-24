<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class LogAktivitas extends Model
{
    public const TINDAKAN_INPUT = 'input';

    public const TINDAKAN_SUNTING = 'sunting';

    public const TINDAKAN_HAPUS = 'hapus';

    protected $table = 'log_aktivitas';

    protected $fillable = [
        'user_id',
        'id_invoice',
        'jenis_tindakan',
        'deskripsi',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function catat(string $jenisTindakan, string $deskripsi, ?string $idInvoice = null): self
    {
        return static::create([
            'user_id' => Auth::id(),
            'id_invoice' => $idInvoice,
            'jenis_tindakan' => $jenisTindakan,
            'deskripsi' => $deskripsi,
        ]);
    }
}
