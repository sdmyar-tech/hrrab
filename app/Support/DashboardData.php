<?php

namespace App\Support;

use App\Models\DataPegawai;
use App\Models\InterviewJadwal;
use App\Models\KontrakJadwal;
use App\Models\Lamaran;
use App\Models\Lowongan;
use App\Models\PengajuanCuti;
use App\Models\PengajuanFormasi;
use App\Models\PengajuanLembur;
use App\Models\Pengumuman;
use App\Models\Penugasan;
use App\Models\ScreeningOnboarding;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/** Isi dashboard panel admin, disesuaikan role & lingkup unit. */
class DashboardData
{
    public static function untuk(User $user, ?string $unit = null): array
    {
        $pilihanUnit = self::pilihanUnit($user);
        $unit = in_array($unit, $pilihanUnit, true) ? $unit : null;
        $lingkup = $unit ? [$unit] : self::lingkupDasar($user);

        $aktif = self::pegawaiAktif($lingkup);
        [$approvalCuti, $approvalLembur] = [self::menungguSaya(PengajuanCuti::class, $user, $lingkup), self::menungguSaya(PengajuanLembur::class, $user, $lingkup)];
        $interviewSaya = $user->bisaMenginterview()
            ? InterviewJadwal::where('interviewer_id', $user->id)->where('status', InterviewJadwal::STATUS_TERJADWAL)->count()
            : 0;
        $bisaLaporan = $user->can(User::ABILITY_LIHAT_LAPORAN) || $user->isAdmin();
        $kontrakHabis = self::kontrakSegeraHabis($aktif, 60);
        $cutiHariIni = $bisaLaporan ? self::cutiHariIni($user, $lingkup) : collect();

        return [
            'judul' => self::judul($user),
            'unitDipilih' => $unit,
            'pilihanUnit' => $pilihanUnit,
            'labelLingkup' => $unit ?? ($lingkup ? implode(', ', $lingkup) : 'Semua unit'),
            'kartu' => self::kartu($user, $aktif, $kontrakHabis, $approvalCuti, $approvalLembur, $interviewSaya, $cutiHariIni, $unit),
            'tugas' => self::tugas($user, $approvalCuti, $approvalLembur),
            'agenda' => self::agenda($user),
            'ringkasanUnit' => self::ringkasanUnit($user, $aktif),
            'ringkasanArea' => self::ringkasanArea($aktif),
            'keluarMasuk' => self::keluarMasuk($lingkup),
            'komposisi' => self::komposisi($aktif, $unit),
            'tren' => $bisaLaporan ? self::tren($lingkup) : null,
            'kontrakHabis' => $kontrakHabis,
            'pegawaiBaru' => $aktif->filter(fn ($p) => $p->kepegawaian?->tanggal_masuk?->gte(now()->subDays(90)))
                ->sortByDesc(fn ($p) => $p->kepegawaian->tanggal_masuk)->take(6)->values(),
            'pengumuman' => Pengumuman::untuk($user)->berlaku()->orderByDesc('penting')->latest()->take(4)->get(),
            'cutiHariIni' => $cutiHariIni,
            'anggota' => ($user->isKepalaBagian() || $unit) ? self::anggota($aktif, $cutiHariIni) : collect(),
            'pipeline' => (RekrutmenAccess::canReview($user) || $user->isPembina()) ? self::pipeline() : null,
            'akunPerRole' => $user->isAdmin() ? User::where('role', '!=', User::ROLE_PELAMAR)->selectRaw('role, count(*) n')->groupBy('role')->pluck('n', 'role') : null,
            'bisaLaporan' => $bisaLaporan,
        ];
    }

public static function pilihanUnit(User $user): array
    {
        $dasar = self::lingkupDasar($user);
        $berpegawai = Penugasan::aktif()->distinct()->pluck('unit_kerja')->all();
        $urutan = config('kepegawaian.unit_kerja');

        $units = $dasar ?? array_values(array_filter($urutan, fn ($u) => in_array($u, $berpegawai, true)));

        return array_values(array_filter($urutan, fn ($u) => in_array($u, $units, true)));
    }

    private static function lingkupDasar(User $user): ?array
    {
        return match (true) {
            $user->isKepalaBagian() => $user->unitsDipimpinUntukQuery(),
            $user->isGm() && ! empty($user->divisi) => array_values($user->divisi),
            default => null,
        };
    }

    private static function pegawaiAktif(?array $lingkup): Collection
    {
        return DataPegawai::with(['kepegawaian', 'penugasanAktif', 'dokumen'])
            ->whereHas('kepegawaian', fn ($q) => $q->where('status_rekrutmen', 'Aktif'))
            ->when($lingkup, fn (Builder $q) => $q->whereHas('penugasanAktif', fn ($p) => $p->whereIn('unit_kerja', $lingkup)))
            ->orderBy('nama_lengkap')
            ->get();
    }

    private static function judul(User $user): string
    {
        return match (true) {
            $user->isAdmin() => 'Ringkasan sistem & data pegawai',
            $user->isPembina() => 'Monitoring yayasan',
            $user->isGm() => $user->divisi ? 'Bidang: ' . self::ringkasDaftar($user->divisi) : 'Semua bidang',
            $user->isKepalaSdm() => 'Approval akhir & rekrutmen',
            $user->isKepalaBagian() => 'Kepala ' . (implode(', ', $user->unitsDipimpin()) ?: '-'),
            default => '',
        };
    }

    private static function ringkasDaftar(array $daftar, int $maks = 4): string
    {
        return count($daftar) > $maks
            ? implode(', ', array_slice($daftar, 0, $maks)) . ' +' . (count($daftar) - $maks) . ' lainnya'
            : implode(', ', $daftar);
    }

private static function kartu(User $user, Collection $aktif, Collection $kontrakHabis, int $approvalCuti, int $approvalLembur, int $interviewSaya, Collection $cutiHariIni, ?string $unit = null): array
    {
        $tetap = $aktif->where('kepegawaian.status_kerja', 'Tetap')->count();
        $kartu = [[
            'label' => $user->isKepalaBagian() ? 'Anggota Unit' : 'Pegawai Aktif', 'nilai' => $aktif->count(),
            'meta' => "{$tetap} tetap · " . ($aktif->count() - $tetap) . ' kontrak/lainnya', 'ikon' => 'bi-people', 'warna' => 'primary',
            'url' => $user->can(User::ABILITY_LIHAT_TABEL_KARYAWAN) ? route('admin.pegawai.index', array_filter(['status_rekrutmen' => 'Aktif', 'unit_kerja' => $unit])) : null,
        ]];

        if ($user->isAdmin()) {
            $kartu[] = ['label' => 'Akun Login', 'nilai' => User::where('role', '!=', User::ROLE_PELAMAR)->count(), 'meta' => 'akun internal', 'ikon' => 'bi-person-lock', 'warna' => 'success', 'url' => route('admin.users.index')];
        }
        if ($user->can(User::ABILITY_APPROVE)) {
            $kartu[] = ['label' => 'Menunggu Persetujuan', 'nilai' => $approvalCuti + $approvalLembur, 'meta' => "{$approvalCuti} cuti · {$approvalLembur} lembur", 'ikon' => 'bi-inbox', 'warna' => 'warning', 'url' => route($approvalCuti || ! $approvalLembur ? 'admin.pengajuan-cuti.index' : 'admin.pengajuan-lembur.index', ['status' => 'pending'])];
        }
        $kartu[] = ['label' => 'Kontrak ≤ 60 Hari', 'nilai' => $kontrakHabis->count(), 'meta' => 'perlu perpanjangan/keputusan', 'ikon' => 'bi-hourglass-split', 'warna' => 'danger',
            'url' => $user->can(User::ABILITY_LIHAT_TABEL_KARYAWAN) ? route('admin.pegawai.index', array_filter(['status_rekrutmen' => 'Aktif', 'kontrak' => 60, 'unit_kerja' => $unit])) : null];
        if ($user->can(User::ABILITY_LIHAT_LAPORAN)) {
            $kartu[] = ['label' => 'Cuti Hari Ini', 'nilai' => $cutiHariIni->count(), 'meta' => 'pegawai tidak masuk', 'ikon' => 'bi-calendar-x', 'warna' => 'prpl', 'url' => route('admin.pengajuan-cuti.index', ['berlangsung' => 1])];
        }
        if ($user->bisaMenginterview()) {
            $kartu[] = ['label' => 'Interview Saya', 'nilai' => $interviewSaya, 'meta' => 'jadwal terjadwal', 'ikon' => 'bi-calendar2-week', 'warna' => 'prpl', 'url' => route('interview.saya')];
        }
        if ($user->isPembina() || RekrutmenAccess::canReview($user)) {
            $kartu[] = ['label' => 'Lamaran Diproses', 'nilai' => Lamaran::where('status', Lamaran::STATUS_PROSES)->count(),
                'meta' => Lowongan::where('status', Lowongan::STATUS_DIPUBLIKASIKAN)->count() . ' lowongan dibuka', 'ikon' => 'bi-person-plus', 'warna' => 'success', 'url' => route('admin.lamaran.index')];
        }

        return array_slice($kartu, 0, 4);
    }

    private static function tugas(User $user, int $approvalCuti, int $approvalLembur): array
    {
        $tugas = [];
        if ($user->can(User::ABILITY_APPROVE)) {
            $tugas[] = ['label' => 'Approve pengajuan cuti', 'jumlah' => $approvalCuti, 'url' => route('admin.pengajuan-cuti.index'), 'ikon' => 'bi-calendar2-check'];
            $tugas[] = ['label' => 'Approve pengajuan lembur', 'jumlah' => $approvalLembur, 'url' => route('admin.pengajuan-lembur.index'), 'ikon' => 'bi-clock-history'];
        }
        if (RekrutmenAccess::canReview($user) || $user->isPembina()) {
            $formasi = PengajuanFormasi::where('status', PengajuanFormasi::STATUS_PENDING)->get()->filter(fn ($f) => RekrutmenAccess::canReviewFormasi($user, $f))->count();
            $tugas[] = ['label' => $user->isPembina() ? 'Review formasi SMK' : 'Review pengajuan formasi', 'jumlah' => $formasi, 'url' => route('admin.pengajuan-formasi.index'), 'ikon' => 'bi-person-plus'];
        }
        if (RekrutmenAccess::canReview($user)) {
            $tugas[] = ['label' => 'Verifikasi lamaran baru', 'jumlah' => Lamaran::where('status', Lamaran::STATUS_MENUNGGU_VERIFIKASI)->count(), 'url' => route('admin.lamaran.index'), 'ikon' => 'bi-file-earmark-check'];
        }
        if ($user->bisaMenginterview()) {
            $tugas[] = ['label' => 'Isi hasil interview', 'jumlah' => InterviewJadwal::where('interviewer_id', $user->id)->where('status', InterviewJadwal::STATUS_TERJADWAL)
                ->whereDate('tanggal', '<=', now())->count(), 'url' => route('interview.saya'), 'ikon' => 'bi-chat-square-text'];
            $tugas[] = ['label' => 'Konfirmasi onboarding', 'jumlah' => self::onboardingMenunggu($user), 'url' => route('admin.lamaran.index'), 'ikon' => 'bi-clipboard-check'];
        }
        if (EvaluasiKontrakAlur::bolehMembuka($user)) {
            $tugas[] = ['label' => $user->isKepalaSdm() || $user->isGm() ? 'Nilai / putuskan evaluasi kontrak' : 'Beri nilai evaluasi kontrak (H-30)', 'jumlah' => EvaluasiKontrakAlur::jumlahTugas($user),
                'url' => route('admin.evaluasi-kontrak.index'), 'ikon' => 'bi-clipboard-check'];
        }
        if ($user->isKepalaBagian()) {
            $tugas[] = ['label' => 'Formasi saya menunggu review', 'jumlah' => PengajuanFormasi::where('diajukan_oleh', $user->id)->where('status', PengajuanFormasi::STATUS_PENDING)->count(),
                'url' => route('admin.pengajuan-formasi.index'), 'ikon' => 'bi-hourglass'];
        }
        if ($user->can(User::ABILITY_KELOLA_PENUGASAN)) {
            $tugas[] = ['label' => 'Unit tanpa kepala', 'jumlah' => count(PenugasanService::unitsTanpaKepala()), 'url' => route('admin.penugasan.index'), 'ikon' => 'bi-diagram-3'];
            $tugas[] = ['label' => 'Mutasi terjadwal', 'jumlah' => Penugasan::where('tanggal_mulai', '>', now()->toDateString())->count(), 'url' => route('admin.penugasan.index', ['status' => 'terjadwal']), 'ikon' => 'bi-arrow-left-right'];
        }
        if ($user->isAdmin()) {
            $tugas[] = ['label' => 'Pegawai aktif tanpa dokumen KTP', 'jumlah' => DataPegawai::whereHas('kepegawaian', fn ($q) => $q->where('status_rekrutmen', 'Aktif'))
                ->whereDoesntHave('dokumen', fn ($q) => $q->whereNotNull('upload_ktp'))->count(), 'url' => route('admin.pegawai.index'), 'ikon' => 'bi-file-earmark-x'];
        }

        return $tugas;
    }

    /**
     * @param  class-string<PengajuanCuti|PengajuanLembur>  $model
     */
    public static function menungguSaya(string $model, User $user, ?array $lingkup = null): int
    {
        if (! $user->can(User::ABILITY_APPROVE)) {
            return 0;
        }

        $query = $model::with('dataPegawai.kepegawaian')->where('status', $model::STATUS_PENDING)
            ->when($lingkup, fn ($q) => $q->whereIn('unit_kerja', $lingkup));
        PengajuanWorkflow::scopeVisibleTo($query, $user);

        return $query->get()->filter(fn ($p) => PengajuanWorkflow::canAct($user, $p))->count();
    }

    private static function onboardingMenunggu(User $user): int
    {
        return ScreeningOnboarding::with('lamaran')->where('status', ScreeningOnboarding::STATUS_BERJALAN)->get()
            ->filter(fn ($s) => OnboardingFlow::bisaKonfirmasi($user, $s, OnboardingFlow::SISI_SDM) || OnboardingFlow::bisaKonfirmasi($user, $s, OnboardingFlow::SISI_KEPALA))
            ->count();
    }

private static function agenda(User $user): Collection
    {
        $sampai = now()->addDays(14)->endOfDay();
        $agenda = collect();

        if ($user->bisaMenginterview()) {
            InterviewJadwal::with('lamaran.dataPelamar', 'lamaran.dataPegawai', 'lamaran.lowongan')
                ->where('interviewer_id', $user->id)->where('status', InterviewJadwal::STATUS_TERJADWAL)
                ->whereDate('tanggal', '<=', $sampai)->get()
                ->each(fn ($j) => $agenda->push(['tanggal' => $j->tanggal, 'jam' => substr($j->waktu_mulai, 0, 5), 'judul' => $j->tahapLabel() . ': ' . $j->lamaran?->namaKandidat(),
                    'sub' => $j->lamaran?->judulPosisi(), 'ikon' => 'bi-chat-square-text', 'url' => $j->lamaran ? route('admin.lamaran.show', $j->lamaran) : null]));

            ScreeningOnboarding::with('lamaran.dataPelamar', 'lamaran.dataPegawai', 'lamaran.lowongan')->where('status', ScreeningOnboarding::STATUS_BERJALAN)
                ->when(! RekrutmenAccess::canReview($user), fn ($q) => $q->where('kepala_id', $user->id))
                ->get()
                ->each(fn ($s) => $agenda->push(['tanggal' => $s->tanggal_selesai, 'jam' => null, 'judul' => 'Onboarding selesai: ' . $s->lamaran?->namaKandidat(),
                    'sub' => 'mulai ' . $s->tanggal_mulai->translatedFormat('d M'), 'ikon' => 'bi-clipboard-check', 'url' => $s->lamaran ? route('admin.lamaran.show', $s->lamaran) : null]));
        }

        if (RekrutmenAccess::canReview($user)) {
            KontrakJadwal::with('lamaran.dataPelamar', 'lamaran.dataPegawai')->where('status', KontrakJadwal::STATUS_TERJADWAL)
                ->whereDate('tanggal', '<=', $sampai)->get()
                ->each(fn ($k) => $agenda->push(['tanggal' => $k->tanggal, 'jam' => $k->waktu ? substr($k->waktu, 0, 5) : null, 'judul' => 'Tanda tangan kontrak: ' . $k->lamaran?->namaKandidat(),
                    'sub' => $k->lokasi, 'ikon' => 'bi-pen', 'url' => $k->lamaran ? route('admin.lamaran.show', $k->lamaran) : null]));
        }

        return $agenda->sortBy(fn ($a) => $a['tanggal']->format('Ymd') . ($a['jam'] ?? '99'))->values()->take(8);
    }

    private static function cutiHariIni(User $user, ?array $lingkup): Collection
    {
        $query = PengajuanCuti::with('dataPegawai.kepegawaian')->where('status', PengajuanCuti::STATUS_APPROVED)
            ->whereDate('tanggal_mulai', '<=', now())->whereDate('tanggal_selesai', '>=', now())
            ->when($lingkup, fn ($q) => $q->whereIn('unit_kerja', $lingkup));

        if (! $user->isAdmin()) {
            PengajuanWorkflow::scopeVisibleTo($query, $user);
        }

        return $query->orderBy('tanggal_selesai')->get();
    }

    private static function kontrakSegeraHabis(Collection $aktif, int $hari): Collection
    {
        $batas = now()->addDays($hari)->endOfDay();

        return $aktif->filter(fn ($p) => ($h = $p->kepegawaian?->tanggal_habis_kontrak) && $h->gte(now()->startOfDay()) && $h->lte($batas))
            ->sortBy(fn ($p) => $p->kepegawaian->tanggal_habis_kontrak)->values();
    }

private static function anggota(Collection $aktif, Collection $cutiHariIni): Collection
    {
        $sedangCuti = $cutiHariIni->pluck('data_pegawai_id')->all();

        return $aktif->map(fn ($p) => [
            'id' => $p->id,
            'nama' => $p->nama_lengkap,
            'foto' => $p->dokumen?->pas_foto,
            'jabatan' => $p->kepegawaian?->jabatan,
            'unit' => $p->kepegawaian?->unit_kerja,
            'rangkap' => $p->penugasanAktif->where('is_utama', false)->pluck('unit_kerja')->all(),
            'kepala' => $p->penugasanAktif->contains('peran', Penugasan::PERAN_KEPALA),
            'status_kerja' => $p->kepegawaian?->status_kerja,
            'masa_kerja' => self::masaKerja($p->kepegawaian?->tanggal_masuk),
            'habis_kontrak' => $p->kepegawaian?->tanggal_habis_kontrak,
            'cuti' => in_array($p->id, $sedangCuti, true),
            'no_hp' => $p->no_hp,
        ])->sortBy([['kepala', 'desc'], ['nama', 'asc']])->values();
    }

    public static function masaKerja(?Carbon $masuk): string
    {
        if (! $masuk) {
            return '-';
        }

        $selisih = $masuk->diff(now());

        return $selisih->y ? "{$selisih->y} th {$selisih->m} bln" : "{$selisih->m} bln";
    }

private static function ringkasanUnit(User $user, Collection $aktif): Collection
    {
        $bulanIni = [now()->startOfMonth(), now()->endOfMonth()];
        $lembur = PengajuanLembur::where('status', PengajuanLembur::STATUS_APPROVED)->whereBetween('tanggal', $bulanIni)
            ->selectRaw('unit_kerja, sum(jumlah_jam) jam')->groupBy('unit_kerja')->pluck('jam', 'unit_kerja');
        $cutiBulan = PengajuanCuti::where('status', PengajuanCuti::STATUS_APPROVED)->whereBetween('tanggal_mulai', $bulanIni)
            ->selectRaw('unit_kerja, sum(lama_cuti) hari')->groupBy('unit_kerja')->pluck('hari', 'unit_kerja');
        $pending = [];
        foreach ([PengajuanCuti::class, PengajuanLembur::class] as $model) {
            foreach ($model::where('status', 'pending')->selectRaw('unit_kerja, count(*) n')->groupBy('unit_kerja')->pluck('n', 'unit_kerja') as $u => $n) {
                $pending[$u] = ($pending[$u] ?? 0) + (int) $n;
            }
        }
        $cutiHariIni = PengajuanCuti::where('status', PengajuanCuti::STATUS_APPROVED)->whereDate('tanggal_mulai', '<=', now())->whereDate('tanggal_selesai', '>=', now())
            ->selectRaw('unit_kerja, count(*) n')->groupBy('unit_kerja')->pluck('n', 'unit_kerja');
        $kepala = Penugasan::aktif()->kepala()->with('dataPegawai:id,nama_lengkap')->get()->groupBy('unit_kerja');
        // GM tanpa Data Pegawai yang memimpin unit (disimpan di akun).
        $kepalaGm = User::gmKepalaTanpaPegawai()->get(['id', 'name', 'kepala_unit_gm'])
            ->flatMap(fn (User $u) => collect((array) $u->kepala_unit_gm)->map(fn ($unit) => [$unit, $u->name]))
            ->groupBy(0)->map(fn ($baris) => $baris->pluck(1));

        $perUnit = [];
        foreach ($aktif as $p) {
            foreach ($p->penugasanAktif as $tugas) {
                $perUnit[$tugas->unit_kerja][] = $p;
            }
        }

        $units = array_values(array_filter(self::pilihanUnit($user), fn ($u) => isset($perUnit[$u])));

        return collect($units)->map(function ($unit) use ($perUnit, $lembur, $cutiBulan, $pending, $cutiHariIni, $kepala, $kepalaGm) {
            $anggota = collect($perUnit[$unit]);
            $batas = now()->addDays(60);

            return [
                'unit' => $unit,
                'kepala' => collect($kepala->get($unit)?->map(fn ($k) => $k->dataPegawai?->nama_lengkap) ?? [])
                    ->merge($kepalaGm->get($unit) ?? [])->filter()->unique()->implode(', ') ?: null,
                'jumlah' => $anggota->count(),
                'laki' => $anggota->where('jenis_kelamin', 'Laki-laki')->count(),
                'perempuan' => $anggota->where('jenis_kelamin', 'Perempuan')->count(),
                'status' => self::hitung($anggota->map(fn ($p) => $p->kepegawaian?->status_kerja ?: 'Belum diisi'), ['Tetap', 'Kontrak', 'Harian Lepas', 'Probation', 'Magang', 'Belum diisi']),
                'kontrak_habis' => $anggota->filter(fn ($p) => ($h = $p->kepegawaian?->tanggal_habis_kontrak) && $h->isFuture() && $h->lte($batas))->count(),
                'cuti_hari_ini' => (int) ($cutiHariIni[$unit] ?? 0),
                'cuti_bulan_ini' => (int) ($cutiBulan[$unit] ?? 0),
                'lembur_bulan_ini' => (float) ($lembur[$unit] ?? 0),
                'pending' => (int) ($pending[$unit] ?? 0),
            ];
        });
    }

    /**
     * Pegawai aktif per area kerja (kolom lokasi_kerja), terbanyak di atas.
     *
     * @return Collection<int, array{area: string, jumlah: int, status: array, laki: int, perempuan: int, unit: array<string, int>, kontrak_habis: int}>
     */
    private static function ringkasanArea(Collection $aktif): Collection
    {
        $batas = now()->addDays(60);

        return $aktif->groupBy(fn ($p) => $p->kepegawaian?->lokasi_kerja ?: 'Belum diisi')
            ->map(fn (Collection $anggota, string $area) => [
                'area' => $area,
                'jumlah' => $anggota->count(),
                'status' => self::hitung($anggota->map(fn ($p) => $p->kepegawaian?->status_kerja ?: 'Belum diisi'), ['Tetap', 'Kontrak', 'Harian Lepas', 'Probation', 'Magang', 'Belum diisi']),
                'laki' => $anggota->where('jenis_kelamin', 'Laki-laki')->count(),
                'perempuan' => $anggota->where('jenis_kelamin', 'Perempuan')->count(),
                // Unit yang bekerja di area ini (pegawai rangkap dihitung di setiap unitnya).
                'unit' => $anggota->flatMap(fn ($p) => $p->penugasanAktif->pluck('unit_kerja'))->filter()->countBy()->sortDesc()->all(),
                'kontrak_habis' => $anggota->filter(fn ($p) => ($h = $p->kepegawaian?->tanggal_habis_kontrak) && $h->isFuture() && $h->lte($batas))->count(),
            ])
            ->sortByDesc('jumlah')
            ->values();
    }

    /**
     * Pegawai masuk (tanggal_masuk) & keluar (tanggal_keluar) per bulan selama 12 bulan, plus daftar 30 hari terakhir.
     *
     * @return array{bulan: list<array{label: string, judul: string, masuk: int, keluar: int}>, masuk30: Collection, keluar30: Collection, tanpaTanggal: int}
     */
    private static function keluarMasuk(?array $lingkup): array
    {
        $bulan = collect(range(11, 0))->map(fn ($i) => now()->startOfMonth()->subMonths($i));
        $dari = $bulan->first()->copy();
        $dasar = fn () => \App\Models\Kepegawaian::query()->when($lingkup, fn ($q) => $q->whereIn('unit_kerja', $lingkup));
        $perBulan = fn (string $kolom) => $dasar()->whereBetween($kolom, [$dari, now()->endOfDay()])
            ->selectRaw("DATE_FORMAT({$kolom}, '%Y-%m') b, count(*) n")->groupBy('b')->pluck('n', 'b');
        $masuk = $perBulan('tanggal_masuk');
        $keluar = $perBulan('tanggal_keluar');
        $sejak = now()->subDays(30)->startOfDay();
        $daftar = fn (string $kolom) => $dasar()->with('dataPegawai:id,nama_lengkap,nip')
            ->whereBetween($kolom, [$sejak, now()->endOfDay()])->orderByDesc($kolom)->get()
            ->map(fn ($k) => ['id' => $k->data_pegawai_id, 'nama' => $k->dataPegawai?->nama_lengkap ?? '-', 'unit' => $k->unit_kerja, 'jabatan' => $k->jabatan, 'tanggal' => $k->{$kolom}]);

        return [
            'bulan' => $bulan->map(fn ($b) => [
                'kunci' => $b->format('Y-m'),
                'label' => $b->translatedFormat('M'),
                'judul' => $b->translatedFormat('F Y'),
                'masuk' => (int) ($masuk[$b->format('Y-m')] ?? 0),
                'keluar' => (int) ($keluar[$b->format('Y-m')] ?? 0),
            ])->all(),
            'masuk30' => $daftar('tanggal_masuk'),
            'keluar30' => $daftar('tanggal_keluar'),
            'tanpaTanggal' => $dasar()->where('status_rekrutmen', 'Tidak Aktif')->whereNull('tanggal_keluar')->count(),
        ];
    }

private static function komposisi(Collection $aktif, ?string $unit = null): array
    {
        // Setiap batang/potongan bisa diklik → Data Pegawai dengan filter yang sama (lihat KelompokPegawai::terapkan).
        $tautan = fn (string $kunci) => fn (array $item) => $item + ['url' => route('admin.pegawai.index', array_filter([
            'status_rekrutmen' => 'Aktif', 'unit_kerja' => $unit, $kunci => $item['label'],
        ]))];

        return [
            'status' => array_map($tautan('status_kerja'), self::hitung($aktif->map(fn ($p) => $p->kepegawaian?->status_kerja ?: 'Belum diisi'), KelompokPegawai::STATUS_KERJA)),
            'gender' => array_map($tautan('jk'), self::hitung($aktif->map(fn ($p) => $p->jenis_kelamin ?: 'Belum diisi'), KelompokPegawai::GENDER)),
            'masa' => array_map($tautan('masa'), self::hitung($aktif->map(fn ($p) => KelompokPegawai::masa($p)), KelompokPegawai::MASA)),
            'usia' => array_map($tautan('usia'), self::hitung($aktif->map(fn ($p) => KelompokPegawai::usia($p)), KelompokPegawai::USIA)),
            'pendidikan' => array_map($tautan('pendidikan'), self::hitung($aktif->map(fn ($p) => KelompokPegawai::jenjang($p->pendidikan_terakhir)), KelompokPegawai::PENDIDIKAN)),
        ];
    }
    private static function hitung(Collection $nilai, array $urutan): array
    {
        $jumlah = $nilai->countBy();

        return collect($urutan)->map(fn ($l) => ['label' => $l, 'jumlah' => (int) ($jumlah[$l] ?? 0)])
            ->filter(fn ($i) => $i['jumlah'] > 0 || ! in_array($i['label'], ['Belum diisi', 'Probation', 'Magang', 'Profesi', 'S3'], true))
            ->values()->all();
    }

    private static function tren(?array $lingkup): array
    {
        $bulan = collect(range(5, 0))->map(fn ($i) => now()->startOfMonth()->subMonths($i));
        $dari = $bulan->first()->copy();

        $cuti = PengajuanCuti::where('status', PengajuanCuti::STATUS_APPROVED)->where('tanggal_mulai', '>=', $dari)
            ->when($lingkup, fn ($q) => $q->whereIn('unit_kerja', $lingkup))
            ->selectRaw("DATE_FORMAT(tanggal_mulai, '%Y-%m') b, sum(lama_cuti) n")->groupBy('b')->pluck('n', 'b');
        $lembur = PengajuanLembur::where('status', PengajuanLembur::STATUS_APPROVED)->where('tanggal', '>=', $dari)
            ->when($lingkup, fn ($q) => $q->whereIn('unit_kerja', $lingkup))
            ->selectRaw("DATE_FORMAT(tanggal, '%Y-%m') b, sum(jumlah_jam) n")->groupBy('b')->pluck('n', 'b');

        // Klik batang bulan → daftar pengajuan yang disetujui pada bulan itu.
        $seri = fn ($data, string $rute) => $bulan->map(fn ($b) => [
            'label' => $b->translatedFormat('M'), 'judul' => $b->translatedFormat('F Y'), 'jumlah' => round((float) ($data[$b->format('Y-m')] ?? 0), 1),
            'url' => route($rute, ['status' => 'approved', 'tanggal_dari' => $b->toDateString(), 'tanggal_sampai' => $b->copy()->endOfMonth()->toDateString()]),
        ])->all();

        return ['cuti' => $seri($cuti, 'admin.pengajuan-cuti.index'), 'lembur' => $seri($lembur, 'admin.pengajuan-lembur.index')];
    }

    private static function pipeline(): array
    {
        $jumlah = Lamaran::whereIn('status', [Lamaran::STATUS_MENUNGGU_VERIFIKASI, Lamaran::STATUS_PROSES])
            ->selectRaw('tahap, count(*) n')->groupBy('tahap')->pluck('n', 'tahap');

        return collect([
            Lamaran::TAHAP_VERIFIKASI => 'Verifikasi',
            Lamaran::TAHAP_INTERVIEW => 'Interview',
            Lamaran::TAHAP_SCREENING => 'Onboarding',
            Lamaran::TAHAP_KONTRAK => 'Kontrak',
        ])->map(fn ($label, $tahap) => ['label' => $label, 'jumlah' => (int) ($jumlah[$tahap] ?? 0)])->values()->all();
    }
}
