<?php

namespace App\Support;

use App\Models\InterviewJadwal;
use App\Models\Lamaran;
use App\Models\PengajuanCuti;
use App\Models\PengajuanFormasi;
use App\Models\PengajuanLembur;
use App\Models\Pengumuman;
use App\Models\User;

/** Angka badge di sidebar: pekerjaan yang menunggu user (approval, review, interview) & yang belum dibaca. */
class SidebarHitung
{
    /** @return array<string, int> */
    public static function untuk(User $user): array
    {
        $hitung = [
            'pengumuman' => Pengumuman::untuk($user)->berlaku()->belumDibaca($user)->count(),
            'notifikasi' => $user->unreadNotifications()->count(),
        ];

        if ($user->can(User::ABILITY_APPROVE)) {
            $hitung['cuti'] = DashboardData::menungguSaya(PengajuanCuti::class, $user);
            $hitung['lembur'] = DashboardData::menungguSaya(PengajuanLembur::class, $user);
        }
        if ($user->bisaMenginterview()) {
            $hitung['interview'] = InterviewJadwal::where('interviewer_id', $user->id)->where('status', InterviewJadwal::STATUS_TERJADWAL)->count();
        }
        if (RekrutmenAccess::canReview($user) || $user->isPembina()) {
            $hitung['formasi'] = PengajuanFormasi::where('status', PengajuanFormasi::STATUS_PENDING)->get()
                ->filter(fn ($f) => RekrutmenAccess::canReviewFormasi($user, $f))->count();
        }
        if (RekrutmenAccess::canReview($user)) {
            $hitung['lamaran'] = Lamaran::where('status', Lamaran::STATUS_MENUNGGU_VERIFIKASI)->count();
        }
        if ($user->isStaff()) {
            $hitung['data_kurang'] = count(KelengkapanData::kurang($user->dataPegawai?->loadMissing('kepegawaian', 'kontakDarurat', 'dokumen')));
        }

        return $hitung;
    }
}
