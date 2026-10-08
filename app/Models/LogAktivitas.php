<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogAktivitas extends Model
{
    protected $table = 'log_aktivitas';

    public const UPDATED_AT = null;

    public const AKSI = [
        'login' => ['Login', 'text-bg-success', 'bi-box-arrow-in-right'],
        'logout' => ['Logout', 'text-bg-secondary', 'bi-box-arrow-right'],
        'tambah' => ['Tambah', 'text-bg-primary', 'bi-plus-circle'],
        'ubah' => ['Ubah', 'text-bg-info', 'bi-pencil'],
        'hapus' => ['Hapus', 'text-bg-danger', 'bi-trash'],
        'setujui' => ['Setujui', 'text-bg-success', 'bi-check-circle'],
        'tolak' => ['Tolak', 'text-bg-danger', 'bi-x-circle'],
        'ekspor' => ['Ekspor', 'text-bg-light border', 'bi-file-earmark-excel'],
        'kirim' => ['Kirim', 'text-bg-primary', 'bi-send'],
        'lainnya' => ['Aksi', 'text-bg-light border', 'bi-activity'],
    ];

    protected $fillable = [
        'user_id', 'nama_pengguna', 'role', 'aksi', 'modul', 'deskripsi', 'status', 'perubahan', 'route', 'ip', 'perangkat',
    ];

    protected $casts = [
        'perubahan' => 'array',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return array{0: string, 1: string, 2: string} label, kelas badge, ikon */
    public function aksiInfo(): array
    {
        return self::AKSI[$this->aksi] ?? self::AKSI['lainnya'];
    }
}
