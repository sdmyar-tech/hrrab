<?php

namespace App\Models;

use App\Support\EvaluasiKontrakAlur;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Kepegawaian extends Model
{
    use \App\Models\Concerns\DicatatLog, HasFactory;

    protected $table = 'kepegawaians';

    protected $fillable = [
        'data_pegawai_id',
        'jabatan',
        'jabatan_tambahan',
        'unit_kerja',
        'lokasi_kerja',
        'tanggal_masuk',
        'tanggal_keluar',
        'masa_kerja',
        'status_rekrutmen',
        'status_kerja',
        'tanggal_habis_kontrak',
    ];

    protected $casts = [
        'tanggal_masuk' => 'date',
        'tanggal_keluar' => 'date',
        'tanggal_habis_kontrak' => 'date',
    ];

    /** Tanggal keluar mengikuti status: jadi "Tidak Aktif" → hari ini (bila belum diisi); aktif lagi → dikosongkan. */
    protected static function booted(): void
    {
        static::saving(function (Kepegawaian $k) {
            if ($k->status_rekrutmen === 'Aktif') {
                $k->tanggal_keluar = null;
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
}
