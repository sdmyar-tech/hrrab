<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DataPelamar extends Model
{
    use HasFactory;

    protected $table = 'data_pelamars';

    public const PLATFORM_SOSMED = ['LinkedIn', 'Instagram', 'Facebook', 'TikTok', 'X (Twitter)', 'YouTube', 'GitHub', 'Behance', 'Portofolio', 'Website'];

    protected $fillable = [
        'email',
        'nama_lengkap',
        'nik',
        'npwp',
        'no_kk',
        'no_hp',
        'tempat_lahir',
        'tanggal_lahir',
        'jenis_kelamin',
        'agama',
        'golongan_darah',
        'status_pernikahan',
        'jumlah_tanggungan',
        'nama_ibu_kandung',
        'pendidikan_terakhir',
        'alamat_ktp',
        'alamat_domisili',
        'foto_path',
        'cv_path',
        'ijazah_path',
        'sertifikat_path',
        'sosial_media',
    ];

    protected $casts = [
        'tanggal_lahir' => 'date',
        'jumlah_tanggungan' => 'integer',
        'sosial_media' => 'array',
    ];

    protected $attributes = [
        'jumlah_tanggungan' => 0,
        'status_pernikahan' => 'Belum Menikah',
    ];

    public function user(): HasOne
    {
        return $this->hasOne(User::class, 'data_pelamar_id');
    }

    public function lamarans(): HasMany
    {
        return $this->hasMany(Lamaran::class);
    }

    public function riwayatPekerjaan(): HasMany
    {
        return $this->hasMany(RiwayatPekerjaanPelamar::class);
    }

    public function riwayatPendidikan(): HasMany
    {
        return $this->hasMany(RiwayatPendidikanPelamar::class)->orderByDesc('tahun_masuk')->orderByDesc('id');
    }

    public function isLengkap(): bool
    {
        return true;
    }
}
