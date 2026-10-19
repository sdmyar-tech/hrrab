<?php

namespace App\Support;

use App\Models\AlurPegawai;
use App\Models\PengajuanCuti;
use App\Models\PengajuanLembur;
use App\Models\Penugasan;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/** Menentukan tingkatan pengaju & alur persetujuan berjenjang untuk pengajuan cuti/lembur. */
class PengajuanWorkflow
{
    public const LEVEL_KEPALA_DIVISI = 'kepala_divisi';
    public const LEVEL_KEPALA_SDM = 'kepala_sdm';
    public const LEVEL_GM = 'gm';

    public const LEVEL_LABELS = [
        self::LEVEL_KEPALA_DIVISI => 'Kepala Bagian',
        self::LEVEL_KEPALA_SDM => 'Kepala SDM (Approve Akhir)',
        self::LEVEL_GM => 'GM',
    ];

    /** Tentukan tingkatan pengaju berdasarkan role tertinggi ORANG-nya, bukan cuma akun yang dipakai. */
    public static function submitterLevel(User $user): string
    {
        $roles = $user->data_pegawai_id
            ? User::where('data_pegawai_id', $user->data_pegawai_id)->pluck('role')->push($user->role)
            : collect([$user->role]);

        return match (true) {
            $roles->contains(User::ROLE_GM) => self::LEVEL_GM,
            $roles->contains(User::ROLE_KEPALA_SDM) => self::LEVEL_KEPALA_SDM,
            $roles->contains(User::ROLE_KEPALA_BAGIAN) => self::LEVEL_KEPALA_DIVISI,
            default => 'staff',
        };
    }

    /** Tingkat approval pertama saat pengajuan baru dibuat di $unit (cuti: unit utama; lembur: unit yang dipilih). */
    public static function initialLevel(User $user, ?string $unit, bool $lembur = false): string
    {
        // Alur khusus (diatur Kepala SDM/Admin) selalu menang atas aturan otomatis.
        if ($alur = AlurPegawai::milik($user->data_pegawai_id)) {
            return $alur->daftarTahap()[0] ?? self::LEVEL_KEPALA_SDM;
        }

        $level = self::submitterLevel($user);

        if (in_array($level, [self::LEVEL_KEPALA_SDM, self::LEVEL_GM], true)) {
            return self::LEVEL_GM;
        }

        $pengajuMemimpin = $lembur
            ? self::memimpin($user->data_pegawai_id, $unit)
            : $level === self::LEVEL_KEPALA_DIVISI;

        if ($pengajuMemimpin || self::kepalaUnit($unit, $user->data_pegawai_id)->isEmpty()) {
            return self::LEVEL_GM;
        }

        return self::LEVEL_KEPALA_DIVISI;
    }

    /** Tahap berikutnya setelah $currentLevel disetujui, bergantung pada siapa $submitter-nya. */
    public static function nextLevel(?User $submitter, string $currentLevel): ?string
    {
        if ($alur = AlurPegawai::milik($submitter?->data_pegawai_id)) {
            return $alur->tahapSetelah($currentLevel);
        }

        return match ($currentLevel) {
            self::LEVEL_KEPALA_DIVISI => self::LEVEL_KEPALA_SDM,
            self::LEVEL_GM => ($submitter && in_array(self::submitterLevel($submitter), [self::LEVEL_KEPALA_SDM, self::LEVEL_GM], true))
                ? null
                : self::LEVEL_KEPALA_SDM,
            default => null,
        };
    }

    /** Apakah pegawai $pegawaiId sedang menjabat kepala di $unit. */
    public static function memimpin(?int $pegawaiId, ?string $unit): bool
    {
        return $pegawaiId && $unit
            && Penugasan::aktif()->kepala()->where('data_pegawai_id', $pegawaiId)->where('unit_kerja', $unit)->exists();
    }

    /**
     * Akun yang bisa approve tahap Kepala Bagian untuk $unit.
     *
     * @return Collection<int, User>
     */
    public static function kepalaUnit(?string $unit, ?int $kecualiPegawaiId = null): Collection
    {
        if (! $unit) {
            return collect();
        }

        return once(fn () => User::query()
            ->whereIn('role', [User::ROLE_KEPALA_BAGIAN, User::ROLE_KEPALA_SDM, User::ROLE_GM])
            ->where(fn ($w) => $w
                ->whereIn('data_pegawai_id', Penugasan::aktif()->kepala()->where('unit_kerja', $unit)
                    ->when($kecualiPegawaiId, fn ($q) => $q->where('data_pegawai_id', '!=', $kecualiPegawaiId))
                    ->select('data_pegawai_id'))
                // GM tanpa Data Pegawai yang memimpin unit ini (disimpan di akun).
                ->orWhereIn('id', User::gmKepalaTanpaPegawai($unit)->select('id')))
            ->orderBy('name')
            ->get()
            ->filter(fn (User $u) => $u->hasAbility(User::ABILITY_APPROVE))
            ->values());
    }

    /**
     * GM yang bisa approve pengajuan pegawai $pegawaiId (semua GM selain dirinya sendiri).
     *
     * @return Collection<int, User>
     */
    public static function gmTersedia(?int $pegawaiId): Collection
    {
        return once(fn () => User::where('role', User::ROLE_GM)
            ->when($pegawaiId, fn ($q) => $q->where(fn ($w) => $w->whereNull('data_pegawai_id')->orWhere('data_pegawai_id', '!=', $pegawaiId)))
            ->orderBy('name')
            ->get());
    }

    /**
     * Kepala SDM yang bisa approve akhir pengajuan pegawai $pegawaiId.
     *
     * @return Collection<int, User>
     */
    public static function kepalaSdmTersedia(?int $pegawaiId): Collection
    {
        return once(fn () => User::where('role', User::ROLE_KEPALA_SDM)
            ->when($pegawaiId, fn ($q) => $q->where(fn ($w) => $w->whereNull('data_pegawai_id')->orWhere('data_pegawai_id', '!=', $pegawaiId)))
            ->orderBy('name')
            ->get());
    }

    /** Pesan error bila rute approval pengajuan baru tidak punya approver (mis. */
    public static function cekApprover(User $user, ?string $unit, bool $lembur = false): ?string
    {
        foreach (self::rute($user, $unit, $lembur) as $tahap) {
            if ($tahap['approvers']->isEmpty()) {
                return "Belum ada {$tahap['label']} yang bisa menyetujui pengajuan ini. Hubungi admin/SDM.";
            }
        }

        return null;
    }

    /**
     * Rute approval lengkap untuk pengajuan baru $user di $unit, mis.
     *
     * @return list<array{level: string, label: string, approvers: Collection<int, User>, final: bool}>
     */
    public static function rute(User $user, ?string $unit, bool $lembur = false): array
    {
        $pegawaiId = $user->data_pegawai_id;
        $alur = AlurPegawai::with(['atasan', 'gm'])->where('data_pegawai_id', $pegawaiId ?? 0)->first();
        $tahap = [];
        $level = self::initialLevel($user, $unit, $lembur);

        while ($level) {
            $approvers = match ($level) {
                self::LEVEL_KEPALA_DIVISI => match (true) {
                    (bool) $alur?->atasan => collect([$alur->atasan]),
                    // Alur khusus tanpa atasan tertentu & unit tanpa kepala: GM yang menyetujui (sama seperti canAct).
                    $alur !== null && self::kepalaUnit($unit, $pegawaiId)->isEmpty() => self::gmTersedia($pegawaiId),
                    default => self::kepalaUnit($unit, $pegawaiId),
                },
                self::LEVEL_GM => $alur?->gm
                    ? collect([$alur->gm])
                    : self::gmTersedia($pegawaiId)->sortByDesc(fn (User $gm) => $gm->memegangDivisi($unit))->values(),
                default => self::kepalaSdmTersedia($pegawaiId),
            };

            $next = self::nextLevel($user, $level);
            // Atasan yang juga Kepala SDM langsung menjadi persetujuan akhir (tidak menyetujui dua kali).
            if ($level === self::LEVEL_KEPALA_DIVISI && $next === self::LEVEL_KEPALA_SDM && $approvers->isNotEmpty() && $approvers->every->isKepalaSdm()) {
                $next = null;
            }

            $tahap[] = ['level' => $level, 'label' => self::levelLabel($level), 'approvers' => $approvers, 'final' => $next === null, 'khusus' => $alur !== null];
            $level = $next;
        }

        return $tahap;
    }

    /** Cari akun User pemilik/pengaju pengajuan (lewat relasi data_pegawai_id). */
    public static function submitterUser(PengajuanCuti|PengajuanLembur $pengajuan): ?User
    {
        if (! $pengajuan->data_pegawai_id) {
            return null;
        }

        return User::where('data_pegawai_id', $pengajuan->data_pegawai_id)->first();
    }

    /** Unit pengajuan = snapshot saat diajukan (cuti. */
    public static function unitPengajuan(PengajuanCuti|PengajuanLembur $pengajuan): ?string
    {
        return $pengajuan->unit_kerja ?? $pengajuan->dataPegawai?->kepegawaian?->unit_kerja;
    }

    /** Apakah $approver berwenang menyetujui/menolak pengajuan ini pada tahap saat ini. */
    public static function canAct(User $approver, PengajuanCuti|PengajuanLembur $pengajuan): bool
    {
        // Admin boleh memproses pengajuan yang masih menunggu di tahap mana pun (bertindak sebagai penyetuju tahap itu).
        if ($approver->isAdmin()) {
            return $pengajuan->isPending() && (bool) $pengajuan->current_level;
        }

        if (! $pengajuan->isPending() || ! $pengajuan->current_level || ! $approver->hasAbility(User::ABILITY_APPROVE)) {
            return false;
        }

        if ($approver->data_pegawai_id && $approver->data_pegawai_id === $pengajuan->data_pegawai_id) {
            return false;
        }

        $submitterUnit = self::unitPengajuan($pengajuan);
        $alur = AlurPegawai::milik($pengajuan->data_pegawai_id);

        return match ($pengajuan->current_level) {
            self::LEVEL_KEPALA_DIVISI => $alur?->atasan_user_id
                ? self::orangSama($approver, $alur->atasan_user_id)
                : $approver->memimpinUnit($submitterUnit)
                    || ($approver->isGm() && self::kepalaUnit($submitterUnit, $pengajuan->data_pegawai_id)->isEmpty()),
            self::LEVEL_GM => $alur?->gm_user_id ? self::orangSama($approver, $alur->gm_user_id) : $approver->isGm(),
            self::LEVEL_KEPALA_SDM => $approver->isKepalaSdm(),
            default => false,
        };
    }

    /** Kolom yang di-update saat $approver menyetujui tahap saat ini (sudah lolos canAct()). */
    public static function approvalUpdates(User $approver, PengajuanCuti|PengajuanLembur $pengajuan): array
    {
        $current = $pengajuan->current_level;
        $updates = [];

        if ($current === self::LEVEL_KEPALA_DIVISI) {
            $updates['kepala_approved_by'] = $approver->id;
            $updates['kepala_approved_at'] = now();
        }

        if ($current === self::LEVEL_GM) {
            $updates['gm_approved_by'] = $approver->id;
            $updates['gm_approved_at'] = now();
            $updates['gm_pengganti'] = self::isGmPengganti($approver, $pengajuan);
        }

        $level = self::nextLevel(self::submitterUser($pengajuan), $current);
        if ($current === self::LEVEL_KEPALA_DIVISI && $level === self::LEVEL_KEPALA_SDM && $approver->isKepalaSdm()) {
            $level = null; // Kepala SDM sebagai atasan langsung = sekaligus persetujuan akhir.
        }
        $updates['current_level'] = $level;

        if ($level === null) {
            $updates['status'] = $pengajuan::STATUS_APPROVED;
            $updates['catatan_admin'] = null;
            $updates['approved_by'] = $approver->id;
            $updates['approved_at'] = now();
        }

        return $updates;
    }

    /**
     * Kolom status yang di-set Admin secara langsung (tanpa tahapan).
     * Disetujui/ditolak = final atas nama Admin; menunggu = dikembalikan ke tahap $level.
     */
    public static function statusAdmin(PengajuanCuti|PengajuanLembur $pengajuan, string $status, ?string $level, User $admin): array
    {
        return match ($status) {
            $pengajuan::STATUS_PENDING => ['current_level' => $level, 'approved_by' => null, 'approved_at' => null],
            default => [
                'current_level' => null,
                'approved_by' => $pengajuan->status === $status ? ($pengajuan->approved_by ?? $admin->id) : $admin->id,
                'approved_at' => $pengajuan->status === $status ? ($pengajuan->approved_at ?? now()) : now(),
            ],
        };
    }

    /** $approver adalah orang yang sama dengan akun $userId (satu orang bisa punya beberapa akun). */
    public static function orangSama(User $approver, int $userId): bool
    {
        if ($approver->id === $userId) {
            return true;
        }
        $pegawaiId = User::whereKey($userId)->value('data_pegawai_id');

        return $pegawaiId && $approver->data_pegawai_id === $pegawaiId;
    }

    /** Subquery pegawai yang alur khususnya menunjuk $viewer (sebagai atasan atau GM) — agar pengajuannya terlihat. */
    private static function pegawaiDenganAtasan(User $viewer): \Illuminate\Database\Query\Builder
    {
        $akun = $viewer->data_pegawai_id
            ? User::where('data_pegawai_id', $viewer->data_pegawai_id)->pluck('id')->push($viewer->id)->unique()->all()
            : [$viewer->id];

        return AlurPegawai::query()->toBase()->select('data_pegawai_id')
            ->where(fn ($q) => $q->whereIn('atasan_user_id', $akun)->orWhereIn('gm_user_id', $akun));
    }

    /** $gm menangani pengajuan di luar bidang utamanya (menggantikan GM lain). */
    public static function isGmPengganti(User $gm, PengajuanCuti|PengajuanLembur $pengajuan): bool
    {
        return ! $gm->memegangDivisi(self::unitPengajuan($pengajuan));
    }

    /** Nama GM utama untuk unit pemohon, mis. */
    public static function gmUtamaLabel(PengajuanCuti|PengajuanLembur $pengajuan): string
    {
        $names = User::gmUtamaUntuk(self::unitPengajuan($pengajuan))->pluck('name');

        return $names->isNotEmpty() ? $names->implode(' / ') : 'GM bidang lain';
    }

    /** Tab daftar approval GM: 'saya' = unit di bidang $gm, 'lain' = di luar bidangnya. */
    public static function scopeBidangGm(Builder $query, User $gm, string $tab): Builder
    {
        if (! $gm->isGm() || empty($gm->divisi)) {
            return $query;
        }

        return $tab === 'lain'
            ? $query->where(fn ($q) => $q->whereNotIn('unit_kerja', $gm->divisi)->orWhereNull('unit_kerja'))
            : $query->whereIn('unit_kerja', $gm->divisi);
    }

    /**
     * Jumlah pengajuan yang menunggu approval GM per tab, mis.
     *
     * @param  class-string<PengajuanCuti|PengajuanLembur>  $model
     */
    public static function gmTabCounts(string $model, User $viewer): ?array
    {
        if (! $viewer->isGm() || empty($viewer->divisi)) {
            return null;
        }

        return collect(['saya', 'lain'])->mapWithKeys(function ($tab) use ($model, $viewer) {
            $query = $model::query()->where('status', $model::STATUS_PENDING)->where('current_level', self::LEVEL_GM);
            self::scopeVisibleTo($query, $viewer);
            self::scopeBidangGm($query, $viewer, $tab);

            return [$tab => $query->count()];
        })->all();
    }

    public static function levelLabel(?string $level): string
    {
        return self::LEVEL_LABELS[$level] ?? '-';
    }

    /** Batasi daftar pengajuan yang terlihat sesuai tingkatan/divisi $viewer. */
    public static function scopeVisibleTo(Builder $query, User $viewer): Builder
    {
        return match ($viewer->role) {
            User::ROLE_PEMBINA, User::ROLE_ADMIN => $query,
            User::ROLE_GM => $query->where(function ($q) use ($viewer) {
                $q->whereIn('data_pegawai_id', User::whereIn('role', [User::ROLE_KEPALA_BAGIAN, User::ROLE_KEPALA_SDM])
                    ->whereNotNull('data_pegawai_id')->select('data_pegawai_id'))
                    ->orWhere('current_level', self::LEVEL_GM)
                    ->orWhereNotNull('gm_approved_by');
                if ($tanpaKepala = PenugasanService::unitsTanpaKepala()) {
                    $q->orWhere(fn ($w) => $w->where('current_level', self::LEVEL_KEPALA_DIVISI)->whereIn('unit_kerja', $tanpaKepala));
                }
                if ($units = $viewer->unitsDipimpin()) {
                    $q->orWhereIn('unit_kerja', $units);
                }
                $q->orWhereIn('data_pegawai_id', self::pegawaiDenganAtasan($viewer));
            }),
            User::ROLE_KEPALA_SDM => $query->where(function ($q) use ($viewer) {
                $q->whereNotNull('kepala_approved_by')
                    ->orWhereNotNull('gm_approved_by')
                    ->orWhere('current_level', self::LEVEL_KEPALA_SDM)
                    ->orWhere('data_pegawai_id', $viewer->data_pegawai_id ?? 0)
                    ->orWhereIn('unit_kerja', $viewer->unitsDipimpinUntukQuery())
                    ->orWhereIn('data_pegawai_id', self::pegawaiDenganAtasan($viewer));
            }),
            User::ROLE_KEPALA_BAGIAN => $query->where(function ($q) use ($viewer) {
                $units = $viewer->unitsDipimpinUntukQuery();
                $q->whereIn('unit_kerja', $units)
                    ->orWhere('data_pegawai_id', $viewer->data_pegawai_id ?? 0)
                    ->orWhereIn('data_pegawai_id', self::pegawaiDenganAtasan($viewer));
                if ($q->getModel() instanceof PengajuanCuti) {
                    $q->orWhereIn('data_pegawai_id', Penugasan::aktif()->whereIn('unit_kerja', $units)->select('data_pegawai_id'));
                }
            }),
            default => $query->where('data_pegawai_id', $viewer->data_pegawai_id ?? 0),
        };
    }
}
