<?php

namespace App\Models;

use App\Models\Concerns\DicatatLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Satu baris riwayat kontrak pegawai (input manual): Masa Training, PKWT 1…6, PKWTT, Magang, Lainnya. */
class KontrakPegawai extends Model
{
    use DicatatLog;

    public const TRAINING = 'Masa Training';

    public const PKWTT = 'PKWTT';

    /** Pilihan jenis (urut kronologis umum). */
    public const JENIS = [
        'Masa Training', 'PKWT 1', 'PKWT 2', 'PKWT 3', 'PKWT 4', 'PKWT 5', 'PKWT 6', 'PKWTT', 'Magang', 'Lainnya',
    ];

    /** Jenis yang hanya boleh satu per pegawai (Magang & Lainnya boleh berulang). */
    public const TUNGGAL = ['Masa Training', 'PKWT 1', 'PKWT 2', 'PKWT 3', 'PKWT 4', 'PKWT 5', 'PKWT 6', 'PKWTT'];

    /** Jenis yang boleh tanpa tanggal selesai. */
    public const TANPA_BATAS = ['PKWTT', 'Lainnya'];

    private static ?bool $tabelAda = null;

    /** Tabel sudah dimigrasi? (halaman lain tetap jalan sebelum `php artisan migrate`). */
    public static function tersedia(): bool
    {
        return self::$tabelAda ??= \Illuminate\Support\Facades\Schema::hasTable('kontrak_pegawais');
    }

    protected $fillable = ['data_pegawai_id', 'jenis', 'tanggal_mulai', 'tanggal_selesai', 'nomor_kontrak', 'keterangan', 'dibuat_oleh'];

    protected $casts = ['tanggal_mulai' => 'date', 'tanggal_selesai' => 'date'];

    public function dataPegawai(): BelongsTo
    {
        return $this->belongsTo(DataPegawai::class);
    }

    /** Nomor PKWT (1–6) bila jenisnya "PKWT n"; selain itu null. */
    public function nomorPkwt(): ?int
    {
        return preg_match('/^PKWT (\d+)$/', $this->jenis, $m) ? (int) $m[1] : null;
    }

    /** Ikut dihitung sebagai "berapa kali dikontrak" (hanya PKWT 1, 2, …). */
    public function dihitung(): bool
    {
        return $this->nomorPkwt() !== null;
    }

    public function warna(): string
    {
        return match (true) {
            $this->jenis === self::PKWTT => 'success',
            $this->dihitung() => 'info',
            $this->jenis === self::TRAINING => 'warning',
            default => 'neutral',
        };
    }

    /** Sedang berlaku pada tanggal tertentu (tanpa tanggal selesai = tanpa batas). */
    public function berlaku(?\DateTimeInterface $tanggal = null): bool
    {
        $t = \Carbon\Carbon::instance($tanggal ?? now())->startOfDay();

        return $this->tanggal_mulai->lte($t) && (! $this->tanggal_selesai || $this->tanggal_selesai->gte($t));
    }
}
