<?php

namespace App\Support;

use App\Models\KontrakJadwal;
use App\Models\Lamaran;
use App\Models\ScreeningOnboarding;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/** Masa screening/onboarding. */
class OnboardingFlow
{
    public const SISI_SDM = 'sdm';
    public const SISI_KEPALA = 'kepala';

    public const DURASI_MIN = 1;
    public const DURASI_MAX = 30;

    /** Kepala Bagian yang wajib ikut konfirmasi (null = cukup sisi SDM). */
    public static function kepalaTerkait(Lamaran $lamaran): ?User
    {
        $kepala = InterviewFlow::kepalaUnit($lamaran->unitKerjaTujuan());

        return ($kepala && ! $kepala->isKepalaSdm()) ? $kepala : null;
    }

    /** Hari terakhir onboarding: durasi dihitung termasuk hari pertama (3 hari mulai Senin = Senin-Rabu). */
    public static function tanggalSelesai(Carbon $mulai, int $durasiHari): Carbon
    {
        return $mulai->copy()->addDays(max(1, $durasiHari) - 1);
    }

    /** Tanggal kontrak paling awal: sehari setelah hari terakhir onboarding (tidak boleh di masa lalu). */
    public static function tanggalKontrakMinimal(ScreeningOnboarding $screening): Carbon
    {
        return $screening->tanggal_selesai->copy()->addDay()->max(now()->startOfDay());
    }

    public static function onboardingLulus(Lamaran $lamaran): ?ScreeningOnboarding
    {
        return $lamaran->screeningOnboardings
            ->where('hasil_status', ScreeningOnboarding::HASIL_LULUS)
            ->sortByDesc('id')->first();
    }

    public static function bisaKonfirmasi(User $user, ScreeningOnboarding $screening, string $sisi): bool
    {
        $lamaran = $screening->lamaran;
        $kandidatSendiri = $user->data_pegawai_id && $user->data_pegawai_id === $lamaran?->data_pegawai_id;

        if ($kandidatSendiri || $screening->status !== ScreeningOnboarding::STATUS_BERJALAN || ! $lamaran?->isProses()) {
            return false;
        }

        return match ($sisi) {
            self::SISI_SDM => $screening->hasil_sdm === null && RekrutmenAccess::canReview($user),
            self::SISI_KEPALA => $screening->hasil_kepala === null && ($user->isAdmin() || ($screening->kepala_id !== null && $screening->kepala_id === $user->id)),
            default => false,
        };
    }

    /**
     * Simpan konfirmasi satu pihak, lalu tentukan hasil akhir bila sudah lengkap.
     *
     * @return string pesan untuk ditampilkan
     */
    public static function konfirmasi(ScreeningOnboarding $screening, string $sisi, string $hasil, ?string $catatan, User $oleh): string
    {
        return DB::transaction(function () use ($screening, $sisi, $hasil, $catatan, $oleh) {
            $screening->update([
                "hasil_{$sisi}" => $hasil,
                "catatan_{$sisi}" => $catatan,
                "konfirmasi_{$sisi}_oleh" => $oleh->id,
                "konfirmasi_{$sisi}_pada" => now(),
            ]);

            $tidakLulus = in_array(ScreeningOnboarding::HASIL_TIDAK_LULUS, [$screening->hasil_sdm, $screening->hasil_kepala], true);
            $lengkap = $screening->hasil_sdm !== null && ($screening->kepala_id === null || $screening->hasil_kepala !== null);

            if ($tidakLulus) {
                self::tutup($screening, ScreeningOnboarding::HASIL_TIDAK_LULUS, $oleh);

                return 'Onboarding dinyatakan Tidak Lulus. Tolak pelamar lewat tombol Tolak bila tidak dilanjutkan.';
            }

            if (! $lengkap) {
                return 'Konfirmasi disimpan. Menunggu konfirmasi ' . ($screening->hasil_sdm === null ? 'Kepala SDM' : 'Kepala Bagian') . '.';
            }

            self::tutup($screening, ScreeningOnboarding::HASIL_LULUS, $oleh);

            $lamaran = $screening->lamaran;
            $lamaran->update(['tahap' => Lamaran::TAHAP_KONTRAK]);

            $tanggal = self::tanggalKontrakMinimal($screening);
            $lamaran->kontrakJadwals()->where('status', KontrakJadwal::STATUS_TERJADWAL)->exists()
                || $lamaran->kontrakJadwals()->create([
                    'tanggal' => $tanggal,
                    'lokasi' => $screening->lokasi,
                    'catatan' => 'Dijadwalkan otomatis sehari setelah onboarding selesai.',
                    'status' => KontrakJadwal::STATUS_TERJADWAL,
                    'dijadwalkan_oleh' => $oleh->id,
                ]);

            return 'Onboarding Lulus. Jadwal tanda tangan kontrak otomatis dibuat ' . $tanggal->translatedFormat('d M Y') . ' (bisa diubah, tidak boleh lebih awal).';
        });
    }

    /** Hasil akhir onboarding (kolom hasil_status lama tetap dipakai sebagai hasil final). */
    private static function tutup(ScreeningOnboarding $screening, string $hasil, User $oleh): void
    {
        $catatan = collect([
            $screening->catatan_sdm ? 'SDM: ' . $screening->catatan_sdm : null,
            $screening->catatan_kepala ? 'Kepala Bagian: ' . $screening->catatan_kepala : null,
        ])->filter()->implode(' | ');

        $screening->update([
            'hasil_status' => $hasil,
            'catatan_hasil' => $catatan ?: null,
            'status' => ScreeningOnboarding::STATUS_SELESAI,
            'diisi_oleh' => $oleh->id,
            'diisi_pada' => now(),
        ]);
    }
}
