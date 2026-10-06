<?php

namespace App\Support;

use App\Models\InterviewJadwal;
use App\Models\PengajuanCuti;
use App\Models\PengajuanFormasi;
use App\Models\PengajuanLembur;
use App\Models\ScreeningOnboarding;
use App\Models\User;
use App\Notifications\Pemberitahuan;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;

/** Pengirim notifikasi tugas/kabar otomatis dari alur sistem (pengajuan, formasi, interview, onboarding, mutasi). */
class Notifikasi
{
    /**
     * @param  iterable<User>|User|null  $penerima
     */
    public static function kirim(iterable|User|null $penerima, string $judul, string $pesan, ?string $url = null,
        string $jenis = Pemberitahuan::JENIS_INFO, string $ikon = 'bi-bell', ?string $pengirim = null): int
    {
        $pelaku = Auth::id();
        $users = collect($penerima instanceof User ? [$penerima] : ($penerima ?? []))
            ->filter()->unique('id')->reject(fn (User $u) => $u->id === $pelaku)->values();

        if ($users->isNotEmpty()) {
            Notification::send($users, new Pemberitahuan($judul, $pesan, $url, $jenis, $ikon, $pengirim ?? Auth::user()?->name));
        }

        return $users->count();
    }

public static function pengajuanMenunggu(PengajuanCuti|PengajuanLembur $pengajuan): void
    {
        $pengajuan->loadMissing('dataPegawai.kepegawaian');
        [$jenis, $route] = self::jenisPengajuan($pengajuan);

        self::kirim(
            self::approver($pengajuan),
            "{$jenis} menunggu persetujuan Anda",
            "{$pengajuan->dataPegawai?->nama_lengkap} ({$pengajuan->unit_kerja}) — tahap {$pengajuan->currentLevelLabel()}.",
            route($route, $pengajuan),
            Pemberitahuan::JENIS_TUGAS,
            $pengajuan instanceof PengajuanCuti ? 'bi-calendar2-check' : 'bi-clock-history',
        );
    }

    public static function pengajuanDiproses(PengajuanCuti|PengajuanLembur $pengajuan): void
    {
        $pengajuan->refresh()->loadMissing('dataPegawai.kepegawaian');

        if ($pengajuan->isPending()) {
            self::pengajuanMenunggu($pengajuan);

            return;
        }

        [$jenis] = self::jenisPengajuan($pengajuan);
        $disetujui = $pengajuan->status === $pengajuan::STATUS_APPROVED;
        $pengaju = User::where('data_pegawai_id', $pengajuan->data_pegawai_id)->get();
        $url = route($pengajuan instanceof PengajuanCuti ? 'pegawai.pengajuan-cuti' : 'pegawai.pengajuan-lembur');

        self::kirim(
            $pengaju,
            "{$jenis} Anda " . ($disetujui ? 'disetujui' : 'ditolak'),
            $disetujui ? 'Pengajuan sudah disetujui sampai tahap akhir.' : 'Alasan: ' . ($pengajuan->catatan_admin ?: '-'),
            $url,
            Pemberitahuan::JENIS_INFO,
            $disetujui ? 'bi-check-circle' : 'bi-x-circle',
        );
    }

    public static function approver(PengajuanCuti|PengajuanLembur $pengajuan): Collection
    {
        return User::with('dataPegawai.penugasanAktif')
            ->whereIn('role', [User::ROLE_KEPALA_BAGIAN, User::ROLE_KEPALA_SDM, User::ROLE_GM])
            ->get()
            ->filter(fn (User $u) => PengajuanWorkflow::canAct($u, $pengajuan))
            ->pipe(function (Collection $users) use ($pengajuan) {
                $gm = $users->filter->isGm();
                $utama = $gm->reject(fn ($g) => PengajuanWorkflow::isGmPengganti($g, $pengajuan));

                return $gm->isNotEmpty() && $utama->isNotEmpty() ? $users->reject(fn ($u) => $u->isGm() && ! $utama->contains($u)) : $users;
            })
            ->values();
    }

    /** @return array{0: string, 1: string} label & route detail admin */
    private static function jenisPengajuan(PengajuanCuti|PengajuanLembur $pengajuan): array
    {
        return $pengajuan instanceof PengajuanCuti
            ? ['Pengajuan cuti', 'admin.pengajuan-cuti.show']
            : ['Pengajuan lembur', 'admin.pengajuan-lembur.show'];
    }

public static function formasiBaru(PengajuanFormasi $formasi): void
    {
        $reviewer = User::whereIn('role', [User::ROLE_KEPALA_SDM, User::ROLE_GM, User::ROLE_PEMBINA])->get()
            ->filter(fn (User $u) => RekrutmenAccess::canReviewFormasi($u, $formasi));

        self::kirim($reviewer, 'Pengajuan formasi baru', "{$formasi->jumlah_dibutuhkan} orang {$formasi->jabatan} untuk unit {$formasi->unit_kerja}.",
            route('admin.pengajuan-formasi.index'), Pemberitahuan::JENIS_TUGAS, 'bi-person-plus');
    }

    public static function formasiDiputuskan(PengajuanFormasi $formasi): void
    {
        $disetujui = $formasi->status === PengajuanFormasi::STATUS_APPROVED;

        self::kirim(User::find($formasi->diajukan_oleh), 'Formasi ' . ($disetujui ? 'disetujui' : 'ditolak'),
            "{$formasi->jabatan} ({$formasi->unit_kerja})" . ($disetujui ? ' — lowongan sudah dibuat.' : '. Alasan: ' . ($formasi->catatan_admin ?: '-')),
            route('admin.pengajuan-formasi.index'), Pemberitahuan::JENIS_INFO, $disetujui ? 'bi-check-circle' : 'bi-x-circle');
    }

    public static function interviewDijadwalkan(InterviewJadwal $jadwal): void
    {
        $jadwal->loadMissing('lamaran.dataPelamar', 'lamaran.dataPegawai', 'lamaran.lowongan', 'interviewer');

        self::kirim($jadwal->interviewer, "Jadwal interview: {$jadwal->lamaran?->namaKandidat()}",
            "{$jadwal->tahapLabel()} — {$jadwal->tanggal->translatedFormat('d M Y')}, " . substr($jadwal->waktu_mulai, 0, 5) . ($jadwal->lokasi ? " · {$jadwal->lokasi}" : '') . '.',
            route('admin.lamaran.show', $jadwal->lamaran_id), Pemberitahuan::JENIS_TUGAS, 'bi-chat-square-text');
    }

    public static function onboardingDimulai(ScreeningOnboarding $screening): void
    {
        $screening->loadMissing('lamaran.dataPelamar', 'lamaran.dataPegawai', 'lamaran.lowongan', 'kepala', 'pengawas');
        $penerima = collect([$screening->kepala, $screening->pengawas])->concat(User::where('role', User::ROLE_KEPALA_SDM)->get());

        self::kirim($penerima, "Onboarding dimulai: {$screening->lamaran?->namaKandidat()}",
            "{$screening->tanggal_mulai->translatedFormat('d M')} – {$screening->tanggal_selesai->translatedFormat('d M Y')}. Konfirmasi hasil setelah masa onboarding.",
            route('admin.lamaran.show', $screening->lamaran_id), Pemberitahuan::JENIS_TUGAS, 'bi-clipboard-check');
    }

public static function penugasanBerubah(int $dataPegawaiId, string $judul, string $pesan): void
    {
        self::kirim(User::where('data_pegawai_id', $dataPegawaiId)->get(), $judul, $pesan, null, Pemberitahuan::JENIS_INFO, 'bi-arrow-left-right');
    }
}
