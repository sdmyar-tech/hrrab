<?php

namespace App\Support;

use App\Models\EvaluasiKontrak;
use App\Models\InterviewJadwal;
use App\Models\KontrakJadwal;
use App\Models\PengajuanCuti;
use App\Models\PengajuanFormasi;
use App\Models\PengajuanIzinKeluar;
use App\Models\PengajuanLembur;
use App\Models\Pengumuman;
use App\Models\PengumumanPeserta;
use App\Models\ScreeningOnboarding;
use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;

/**
 * Notifikasi tugas (absen acara, persetujuan, interview, onboarding, kontrak, formasi) membawa penanda
 * data['tugas'] = ['tipe' => ..., 'id' => ...]. Tandanya TIDAK hilang saat dibuka / "baca semua",
 * dan baru dianggap terbaca otomatis setelah tugasnya selesai.
 */
class TugasNotifikasi
{
    public const ABSEN_ACARA = 'absen_acara';

    public const CUTI = 'cuti';

    public const LEMBUR = 'lembur';

    public const IZIN_KELUAR = 'izin_keluar';

    public const FORMASI = 'formasi';

    public const INTERVIEW = 'interview';

    public const ONBOARDING = 'onboarding';

    public const KONTRAK = 'kontrak';

    public const EVALUASI_KONTRAK = 'evaluasi_kontrak';

    /** @var array<string, true> "user:request" yang sudah dirapikan (cukup sekali per request) */
    private static array $sudahDirapikan = [];

    public static function penanda(string $tipe, int $id): array
    {
        return ['tipe' => $tipe, 'id' => $id];
    }

    /** Notifikasi ini tugas yang masih harus dikerjakan $user? */
    public static function tertunda(DatabaseNotification $n, User $user): bool
    {
        $tugas = $n->data['tugas'] ?? null;

        return is_array($tugas) && ! self::selesai($user, $tugas);
    }

    /** Tandai terbaca semua notifikasi tugas milik $user yang tugasnya sudah selesai (sekali per request). */
    public static function rapikan(User $user): void
    {
        $kunci = $user->id . ':' . spl_object_id(request());
        if (isset(self::$sudahDirapikan[$kunci])) {
            return;
        }
        self::$sudahDirapikan[$kunci] = true;

        $user->unreadNotifications()->whereNotNull('data->tugas')->get()
            ->filter(fn (DatabaseNotification $n) => ! self::tertunda($n, $user))
            ->each->markAsRead();
    }

    public static function selesai(User $user, array $tugas): bool
    {
        $id = (int) ($tugas['id'] ?? 0);

        return match ($tugas['tipe'] ?? null) {
            self::ABSEN_ACARA => self::absenSelesai($user, $id),
            self::CUTI => ! ($p = PengajuanCuti::find($id)) || ! PengajuanWorkflow::canAct($user, $p),
            self::LEMBUR => ! ($p = PengajuanLembur::find($id)) || ! PengajuanWorkflow::canAct($user, $p),
            self::IZIN_KELUAR => ! ($p = PengajuanIzinKeluar::find($id)) || ! PengajuanWorkflow::canAct($user, $p),
            self::FORMASI => ! ($f = PengajuanFormasi::find($id)) || $f->status !== PengajuanFormasi::STATUS_PENDING
                || ! RekrutmenAccess::canReviewFormasi($user, $f),
            self::INTERVIEW => ! ($j = InterviewJadwal::find($id)) || $j->status !== InterviewJadwal::STATUS_TERJADWAL || $j->hasil_status !== null,
            self::ONBOARDING => self::onboardingSelesai($user, $id),
            self::KONTRAK => ! ($k = KontrakJadwal::find($id)) || $k->status !== KontrakJadwal::STATUS_TERJADWAL,
            // Atasan: selesai setelah mengisi; Kepala SDM: selesai setelah memutuskan.
            self::EVALUASI_KONTRAK => ! ($e = EvaluasiKontrak::find($id)) || ! EvaluasiKontrakAlur::tugasUntuk($user, $e),
            default => true, // tipe tak dikenal: perlakukan seperti notifikasi biasa
        };
    }

    /** Absen acara selesai bila sudah hadir / berhalangan, atau absennya sudah tidak bisa dilakukan lagi. */
    private static function absenSelesai(User $user, int $id): bool
    {
        $p = Pengumuman::find($id);
        if (! $p || ! $p->absen_aktif || $p->absenFase() === Pengumuman::ABSEN_TUTUP) {
            return true;
        }
        AbsenAcara::sinkronBerhalangan($p); // cuti / izin di absensi harian juga menyelesaikan tugas ini
        $peserta = $p->peserta()->where('user_id', $user->id)->first();

        return ! $peserta || $peserta->status !== PengumumanPeserta::STATUS_BELUM;
    }

    private static function onboardingSelesai(User $user, int $id): bool
    {
        $s = ScreeningOnboarding::find($id);
        if (! $s || $s->status !== ScreeningOnboarding::STATUS_BERJALAN) {
            return true;
        }
        // Tiap penerima hanya menunggu konfirmasi dari sisinya sendiri.
        $menungguSaya = ($user->isKepalaSdm() && $s->hasil_sdm === null) || ($s->kepala_id === $user->id && $s->hasil_kepala === null);

        return ! $menungguSaya;
    }
}
