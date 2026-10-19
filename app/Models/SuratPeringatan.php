<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Surat peringatan bulanan: tingkat 1–3 bertahap dalam satu bulan kalender; bulan berikutnya mulai lagi dari SP1. */
class SuratPeringatan extends Model
{
    use \App\Models\Concerns\DicatatLog;

    public const MAKS_PER_BULAN = 3;

    protected $fillable = ['data_pegawai_id', 'unit_kerja', 'tanggal', 'tingkat', 'alasan', 'keterangan', 'diberikan_oleh'];

    protected $casts = ['tanggal' => 'date', 'tingkat' => 'integer'];

    public function dataPegawai(): BelongsTo
    {
        return $this->belongsTo(DataPegawai::class);
    }

    public function pemberi(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diberikan_oleh');
    }

    /** SP pegawai di bulan kalender $tanggal. */
    public function scopeBulanDari(Builder $q, int $pegawaiId, CarbonInterface $tanggal): Builder
    {
        return $q->where('data_pegawai_id', $pegawaiId)
            ->whereBetween('tanggal', [$tanggal->copy()->startOfMonth()->toDateString(), $tanggal->copy()->endOfMonth()->toDateString()]);
    }

    /** Tingkat SP berikutnya untuk pegawai di bulan $tanggal (1–3), atau null bila sudah SP3 bulan itu. */
    public static function tingkatBerikut(int $pegawaiId, CarbonInterface $tanggal): ?int
    {
        $ada = (int) self::query()->bulanDari($pegawaiId, $tanggal)->max('tingkat');

        return $ada >= self::MAKS_PER_BULAN ? null : $ada + 1;
    }

    /** Hanya SP terakhir di bulannya yang boleh dihapus (agar urutan SP1→SP3 tidak bolong). */
    public function terakhirDiBulannya(): bool
    {
        return (int) self::query()->bulanDari($this->data_pegawai_id, $this->tanggal)->max('tingkat') === $this->tingkat;
    }

    public function label(): string
    {
        return 'SP' . $this->tingkat;
    }

    /** Warna makin tegas dari SP1 ke SP3 (kelas di ui.css). */
    public function badgeClass(): string
    {
        return 'sp-badge sp-' . $this->tingkat;
    }
}
