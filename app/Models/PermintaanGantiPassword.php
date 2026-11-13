<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Permintaan ganti kata sandi oleh pemilik akun; disetujui Admin/SDM lalu dipakai sekali. */
class PermintaanGantiPassword extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_USED = 'used';

    public const STATUS = [
        self::STATUS_PENDING => ['Menunggu persetujuan', 'warning'],
        self::STATUS_APPROVED => ['Disetujui', 'success'],
        self::STATUS_REJECTED => ['Ditolak', 'danger'],
        self::STATUS_USED => ['Sudah dipakai', 'neutral'],
    ];

    protected $fillable = ['user_id', 'alasan', 'status', 'diproses_oleh', 'diproses_pada', 'catatan', 'dipakai_pada'];

    protected $casts = ['diproses_pada' => 'datetime', 'dipakai_pada' => 'datetime'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function pemroses(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diproses_oleh');
    }

    /** Permintaan terbaru akun ini (apa pun statusnya), untuk ditampilkan di profil. */
    public static function terakhir(User $user): ?self
    {
        return self::aman(fn () => self::where('user_id', $user->id)->latest('id')->first());
    }

    /** Persetujuan yang masih bisa dipakai untuk mengganti kata sandi. */
    public static function siapDipakai(User $user): ?self
    {
        return self::aman(fn () => self::where('user_id', $user->id)->where('status', self::STATUS_APPROVED)->latest('id')->first());
    }

    /** Dipanggil di setiap halaman (dialog sandi): jangan sampai halaman gagal bila migrasi tabel belum dijalankan. */
    private static function aman(\Closure $cari): ?self
    {
        try {
            return $cari();
        } catch (\Illuminate\Database\QueryException) {
            return null;
        }
    }

    public function label(): string
    {
        return self::STATUS[$this->status][0] ?? $this->status;
    }

    public function nada(): string
    {
        return self::STATUS[$this->status][1] ?? 'neutral';
    }
}
