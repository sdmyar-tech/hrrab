<?php

namespace App\Models;

use App\Models\Concerns\DicatatLog;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Surat peringatan: setiap SP berlaku 6 bulan sejak tanggalnya. Pelanggaran saat SP terakhir masih berlaku menaikkan
 * tingkat (SP1 → SP2 → SP3, maksimal 3); setelah SP terakhir kedaluwarsa, SP berikutnya mulai lagi dari SP1.
 */
class SuratPeringatan extends Model
{
    use DicatatLog;

    public const MAKS_TINGKAT = 3;

    public const MASA_BERLAKU_BULAN = 6;

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

    /** SP terbaru pegawai (tanggal, lalu urutan input). */
    public static function terakhirUntuk(int $pegawaiId): ?self
    {
        return self::where('data_pegawai_id', $pegawaiId)->orderByDesc('tanggal')->orderByDesc('id')->first();
    }

    /** Hari terakhir SP ini berlaku (tanggal + 6 bulan − 1 hari). */
    public function berlakuSampai(): CarbonInterface
    {
        return $this->tanggal->copy()->addMonthsNoOverflow(self::MASA_BERLAKU_BULAN)->subDay();
    }

    public function berlakuPada(CarbonInterface $tanggal): bool
    {
        return $tanggal->toDateString() <= $this->berlakuSampai()->toDateString();
    }

    /** Tingkat SP berikutnya bila diberikan pada $tanggal (1–3), atau null bila SP3 masih berlaku. */
    public static function tingkatBerikut(int $pegawaiId, CarbonInterface $tanggal): ?int
    {
        $terakhir = self::terakhirUntuk($pegawaiId);
        if (! $terakhir || ! $terakhir->berlakuPada($tanggal)) {
            return 1;
        }

        return $terakhir->tingkat >= self::MAKS_TINGKAT ? null : $terakhir->tingkat + 1;
    }

    /** Hanya SP terbaru pegawai yang boleh dihapus (agar urutan SP1→SP3 tidak bolong). */
    public function terbaru(): bool
    {
        return self::terakhirUntuk($this->data_pegawai_id)?->is($this) ?? false;
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
