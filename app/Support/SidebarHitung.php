<?php

namespace App\Support;

use App\Models\InterviewJadwal;
use App\Models\Lamaran;
use App\Models\PengajuanCuti;
use App\Models\PengajuanFormasi;
use App\Models\PengajuanIzinKeluar;
use App\Models\PengajuanLembur;
use App\Models\Pengumuman;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

/** Angka badge di sidebar: pekerjaan yang menunggu user (approval, review, interview) & yang belum dibaca. */
class SidebarHitung
{
    /** @return array<string, int> */
    public static function untuk(User $user): array
    {
        TugasNotifikasi::rapikan($user); // tugas yang sudah selesai tidak ikut dihitung
        $hitung = [
            'pengumuman' => Pengumuman::untuk($user)->berlaku()->belumDibaca($user)->count(),
            'notifikasi' => $user->unreadNotifications()->count(),
        ];

        if ($user->can(User::ABILITY_APPROVE)) {
            $hitung['cuti'] = DashboardData::menungguSaya(PengajuanCuti::class, $user);
            $hitung['lembur'] = DashboardData::menungguSaya(PengajuanLembur::class, $user);
            $hitung['izin_keluar'] = DashboardData::menungguSaya(PengajuanIzinKeluar::class, $user);
        }
        if ($user->bisaMenginterview()) {
            $hitung['interview'] = InterviewJadwal::where('interviewer_id', $user->id)->where('status', InterviewJadwal::STATUS_TERJADWAL)->count();
        }
        if (RekrutmenAccess::canReview($user) || $user->isPembina()) {
            $hitung['formasi'] = PengajuanFormasi::with('diajukanOleh')->where('status', PengajuanFormasi::STATUS_PENDING)->get()
                ->filter(fn ($f) => RekrutmenAccess::canReviewFormasi($user, $f))->count();
        }
        if (RekrutmenAccess::canReview($user)) {
            $hitung['lamaran'] = Lamaran::where('status', Lamaran::STATUS_MENUNGGU_VERIFIKASI)->count();
        }
        // Sekali sehari (siapa pun yang membuka aplikasi): buat evaluasi yang baru masuk H-30 — tidak bergantung cron.
        // (kunci v2: ikut memastikan penilai/pemutus yang berlaku sudah menerima notifikasi tugas)
        if (Cache::add('evaluasi-kontrak:harian:v2:' . now()->toDateString(), true, now()->endOfDay())) {
            EvaluasiKontrakAlur::buatJatuhTempo();
            EvaluasiKontrakAlur::kirimTugasTertunda();
        }
        if (EvaluasiKontrakAlur::bolehMembuka($user)) {
            $hitung['evaluasi_kontrak'] = EvaluasiKontrakAlur::jumlahTugas($user);
        }
        if ($user->isStaff()) {
            $hitung['data_kurang'] = count(KelengkapanData::kurang($user->dataPegawai?->loadMissing('kepegawaian', 'kontakDarurat', 'dokumen')));
        }

        return $hitung;
    }
}
