<?php

namespace App\Models;

use App\Models\Concerns\DicatatLog;
use App\Support\EvaluasiKontrakAlur;
use App\Support\PegawaiKeluar;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Kepegawaian extends Model
{
    use DicatatLog, HasFactory;

    protected $table = 'kepegawaians';

    protected $fillable = [
        'data_pegawai_id',
        'jabatan',
        'jabatan_tambahan',
        'unit_kerja',
        'unit_organisasi',
        'lokasi_kerja',
        'tanggal_masuk',
        'tanggal_keluar',
        'jenis_keluar',
        'alasan_keluar',
        'keluar_diproses_oleh',
        'keluar_snapshot',
        'masa_kerja',
        'status_rekrutmen',
        'status_kerja',
        'tanggal_habis_kontrak',
    ];

    protected $casts = [
        'tanggal_masuk' => 'date',
        'tanggal_keluar' => 'date',
        'tanggal_habis_kontrak' => 'date',
        'keluar_snapshot' => 'array',
    ];

    /** Tanggal keluar mengikuti status: jadi "Tidak Aktif" → hari ini (bila belum diisi); aktif lagi → data keluar dikosongkan. */
    protected static function booted(): void
    {
        static::saving(function (Kepegawaian $k) {
            if ($k->status_rekrutmen === 'Aktif') {
                $k->forceFill(['tanggal_keluar' => null, 'jenis_keluar' => null, 'alasan_keluar' => null, 'keluar_diproses_oleh' => null, 'keluar_snapshot' => null]);
            } elseif ($k->status_rekrutmen === 'Tidak Aktif' && $k->exists && $k->isDirty('status_rekrutmen') && ! $k->tanggal_keluar) {
                // Hanya saat berubah dari Aktif; data baru/impor yang sudah nonaktif tidak diberi tanggal palsu.
                $k->tanggal_keluar = now()->toDateString();
            }
        });

        // Kontrak berubah: evaluasi lama yang belum diisi & tak berlaku dihapus, lalu evaluasi baru langsung dibuat
        // bila habis ≤ 30 hari lagi (tidak menunggu jadwal harian).
        static::saved(function (Kepegawaian $k) {
            if ($k->wasChanged(['tanggal_habis_kontrak', 'status_kerja', 'status_rekrutmen']) || $k->wasRecentlyCreated) {
                EvaluasiKontrakAlur::bersihkanUsang($k->data_pegawai_id);
                EvaluasiKontrakAlur::buatJatuhTempo($k->data_pegawai_id);
            }
        });
    }

    public function dataPegawai(): BelongsTo
    {
        return $this->belongsTo(DataPegawai::class);
    }

    public function pemrosesKeluar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'keluar_diproses_oleh');
    }

    public function jenisKeluarLabel(): ?string
    {
        return $this->jenis_keluar ? (PegawaiKeluar::JENIS[$this->jenis_keluar] ?? $this->jenis_keluar) : null;
    }
}
