<?php

namespace App\Models;

use App\Models\Concerns\DicatatLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tangga alur persetujuan cuti/lembur/izin keluar milik satu bagian, untuk pegawai biasa atau koordinatornya.
 * Urutan tahap bebas (boleh bolak-balik antarbagian), tidak ada tahap yang dilewati otomatis.
 * Tiap tahap: nama, daftar sumber penyetuju, dan apakah semua penyetuju wajib setuju (bukan salah satu).
 */
class TanggaAlur extends Model
{
    use DicatatLog;

    public const SASARAN_STAF = 'staf';

    public const SASARAN_KOORDINATOR = 'koordinator';

    public const SASARAN_LABEL = [
        self::SASARAN_STAF => 'Pegawai biasa',
        self::SASARAN_KOORDINATOR => 'Koordinator',
    ];

    /** Jenis sumber penyetuju dalam satu tahap. */
    public const TIPE_KOORDINATOR = 'koordinator';

    public const TIPE_GM = 'gm';

    public const TIPE_KEPALA_SDM = 'kepala_sdm';

    public const TIPE_JABATAN = 'jabatan';

    public const TIPE_AKUN = 'akun';

    public const TIPE_LABEL = [
        self::TIPE_KOORDINATOR => 'Koordinator',
        self::TIPE_GM => 'GM',
        self::TIPE_KEPALA_SDM => 'Kepala SDM',
        self::TIPE_JABATAN => 'Jabatan',
        self::TIPE_AKUN => 'Orang tertentu',
    ];

    /** Nilai "unit" sumber penyetuju selain id bagian: bagian pengaju sendiri, atau semua bagian. */
    public const UNIT_PENGAJU = 'pengaju';

    public const UNIT_SEMUA = 'semua';

    public const MAKS_TAHAP = 10;

    protected $fillable = ['unit_id', 'sasaran', 'tahap', 'catatan', 'diubah_oleh'];

    protected $casts = ['tahap' => 'array'];

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function pengubah(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diubah_oleh');
    }

    /** @return list<array{nama: ?string, semua: bool, penyetuju: list<array<string, mixed>>}> */
    public function daftarTahap(): array
    {
        return array_values((array) $this->tahap);
    }
}
