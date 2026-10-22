<?php

namespace App\Models;

use App\Support\PengajuanWorkflow;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Alur persetujuan cuti/lembur khusus untuk satu pegawai (diatur Kepala SDM/Admin). */
class AlurPegawai extends Model
{
    /** Urutan baku tahap; pegawai boleh melewati tahap mana pun, tetapi urutannya tetap. */
    public const URUTAN = [
        PengajuanWorkflow::LEVEL_KEPALA_DIVISI,
        PengajuanWorkflow::LEVEL_GM,
        PengajuanWorkflow::LEVEL_KEPALA_SDM,
    ];

    protected $fillable = ['data_pegawai_id', 'tahap', 'atasan_user_id', 'gm_user_id', 'catatan', 'diubah_oleh'];

    protected $casts = ['tahap' => 'array'];

    public function dataPegawai(): BelongsTo
    {
        return $this->belongsTo(DataPegawai::class);
    }

    public function atasan(): BelongsTo
    {
        return $this->belongsTo(User::class, 'atasan_user_id');
    }

    public function gm(): BelongsTo
    {
        return $this->belongsTo(User::class, 'gm_user_id');
    }

    public function pengubah(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diubah_oleh');
    }

    /** Tahap yang valid, sudah diurutkan sesuai URUTAN. */
    public function daftarTahap(): array
    {
        return array_values(array_intersect(self::URUTAN, (array) $this->tahap));
    }

    /**
     * Tahap sesudah $level menurut URUTAN baku. Tetap benar walau $level sendiri tidak ada di alur
     * (mis. pengajuan lama yang sedang menunggu di tahap yang baru saja dihapus dari alur).
     */
    public function tahapSetelah(string $level): ?string
    {
        $posisi = array_search($level, self::URUTAN, true);

        foreach ($this->daftarTahap() as $tahap) {
            if (array_search($tahap, self::URUTAN, true) > $posisi) {
                return $tahap;
            }
        }

        return null;
    }

    /** Alur khusus milik pegawai, atau null bila mengikuti aturan otomatis. */
    public static function milik(?int $dataPegawaiId): ?self
    {
        return $dataPegawaiId ? self::where('data_pegawai_id', $dataPegawaiId)->first() : null;
    }
}
