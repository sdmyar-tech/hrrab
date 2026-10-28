<?php

namespace App\Models;

use App\Models\Concerns\DicatatLog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;

class DataPegawai extends Model
{
    use DicatatLog, HasFactory;

    protected $table = 'data_pegawais';

    /** Nilai golongan darah yang diterima kolom ENUM data_pegawais.golongan_darah. */
    public const GOLONGAN_DARAH = ['Belum Diketahui', 'A', 'A+', 'A-', 'B', 'B+', 'B-', 'AB', 'AB+', 'AB-', 'O', 'O+', 'O-'];

    /**
     * Pegawai aktif untuk dropdown pemilihan pegawai (urut nama); dihitung sekali per request.
     *
     * @return Collection<int, array{id: int, nama: string, nip: ?string, jabatan: ?string, unit: ?string, label: string}>
     */
    public static function opsiPilihan(): Collection
    {
        return once(fn () => self::with('kepegawaian:id,data_pegawai_id,unit_kerja,jabatan')
            ->whereHas('kepegawaian', fn ($q) => $q->where('status_rekrutmen', 'Aktif'))
            ->orderBy('nama_lengkap')->get(['id', 'nama_lengkap', 'nip'])
            ->map(fn (self $p) => [
                'id' => $p->id,
                'nama' => $p->nama_lengkap,
                'nip' => $p->nip,
                'jabatan' => $p->kepegawaian?->jabatan,
                'unit' => $p->kepegawaian?->unit_kerja,
                'label' => $p->nama_lengkap . ' — ' . ($p->kepegawaian?->jabatan ?: '-') . ' (' . ($p->kepegawaian?->unit_kerja ?: '-') . ')',
            ]));
    }

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

    // finger_id & ikut_absensi sengaja tidak di $fillable: hanya diubah lewat modul Absensi (SDM/Admin).

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

    /** Folder unggahan dokumen: per NIP, atau per ID bila NIP belum diisi (Admin boleh menyimpan data belum lengkap). */
    public function folderDokumen(): string
    {
        $nip = preg_replace('/[^A-Za-z0-9._-]/', '_', trim((string) $this->nip));

        return 'dokumen-pegawai/' . ($nip !== '' ? $nip : 'id-' . $this->getKey());
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

    public function pengajuanIzinKeluars(): HasMany
    {
        return $this->hasMany(PengajuanIzinKeluar::class);
    }
}
