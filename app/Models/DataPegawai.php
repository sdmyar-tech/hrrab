<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DataPegawai extends Model
{
    use HasFactory, \App\Models\Concerns\DicatatLog;

    protected $table = 'data_pegawais';

    protected $fillable = [
        'email',
        'nip',
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
    ];

    protected $casts = [
        'tanggal_lahir' => 'date',
        'jumlah_tanggungan' => 'integer',
        'ikut_absensi' => 'boolean',
    ];

    // finger_id & ikut_absensi sengaja tidak di illable: hanya diubah lewat modul Absensi (SDM/Admin).

    public function jadwalKerja(): HasOne
    {
        return $this->hasOne(JadwalKerjaPegawai::class);
    }

    public function absensis(): HasMany
    {
        return $this->hasMany(Absensi::class);
    }

    public function kepegawaian(): HasOne
    {
        return $this->hasOne(Kepegawaian::class);
    }

    public function kontakDarurat(): HasOne
    {
        return $this->hasOne(KontakDarurat::class);
    }

    public function bpjs(): HasOne
    {
        return $this->hasOne(BpjsPegawai::class);
    }

    public function dokumen(): HasOne
    {
        return $this->hasOne(DokumenPegawai::class);
    }

    public function penugasans(): HasMany
    {
        return $this->hasMany(Penugasan::class)->orderByDesc('tanggal_mulai')->orderByDesc('id');
    }

    public function penugasanAktif(): HasMany
    {
        return $this->hasMany(Penugasan::class)->aktif()->orderByDesc('is_utama')->orderBy('id');
    }

    public function pengajuanLemburs(): HasMany
    {
        return $this->hasMany(PengajuanLembur::class);
    }

    public function pengajuanCutis(): HasMany
    {
        return $this->hasMany(PengajuanCuti::class);
    }
}
