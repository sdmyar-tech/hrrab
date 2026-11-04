<?php

namespace App\Support;

use App\Models\AlurPegawai;
use App\Models\PengajuanCuti;
use App\Models\PengajuanIzinKeluar;
use App\Models\PengajuanLembur;
use App\Models\Penugasan;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/** Menentukan tingkatan pengaju & alur persetujuan berjenjang untuk pengajuan cuti/lembur/izin keluar. */
class PengajuanWorkflow
{
    public const LEVEL_KEPALA_DIVISI = 'kepala_divisi';

    public const LEVEL_KEPALA_SDM = 'kepala_sdm';

    public const LEVEL_GM = 'gm';

    /** Pengajuan mengikuti Tangga Alur bagian (tahap berjalan = kolom tahap_ke, detail tahap = kolom tangga). */
    public const LEVEL_TANGGA = 'tangga';

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
        if (TanggaAlurWorkflow::untuk($user, $unit)) {
            return self::LEVEL_TANGGA;
        }

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

    /** Kolom tahap awal pengajuan baru: tangga alur bagian (salinan tahap + tahap pertama) atau level alur bawaan. */
    public static function kolomAwal(User $user, ?string $unit, bool $lembur = false): array
    {
        if ($tangga = TanggaAlurWorkflow::untuk($user, $unit)) {
            return ['current_level' => self::LEVEL_TANGGA, 'tangga' => TanggaAlurWorkflow::salinan($tangga, $unit), 'tahap_ke' => 0];
        }

        return ['current_level' => self::initialLevel($user, $unit, $lembur)];
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
            ->filter(fn (User $u) => $u->hasAbility(Izin::PENGAJUAN_APPROVE))
            ->values());
    }

    /**
     * GM yang bisa approve pengajuan pegawai $pegawaiId: akun ber-role GM (alur) yang memegang permission
     * pengajuan.approve_gm, selain dirinya sendiri.
     *
     * @return Collection<int, User>
     */
    public static function gmTersedia(?int $pegawaiId): Collection
    {
        return once(fn () => User::where('role', User::ROLE_GM)
            ->when($pegawaiId, fn ($q) => $q->where(fn ($w) => $w->whereNull('data_pegawai_id')->orWhere('data_pegawai_id', '!=', $pegawaiId)))
            ->orderBy('name')
            ->get()
            ->filter(fn (User $u) => $u->hasAbility(Izin::PENGAJUAN_APPROVE_GM))
            ->values());
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

    /** Pesan error bila salah satu tahap rute approval pengajuan baru tidak punya approver; null bila lengkap. */
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
        if ($tangga = TanggaAlurWorkflow::untuk($user, $unit)) {
            return TanggaAlurWorkflow::rute($tangga, $user, $unit);
        }

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

    /**
     * Rute → data komponen x-tangga-alur (nama tahap, penyetuju, tahap akhir, semua harus setuju, tanpa penyetuju).
     *
     * @return list<array{label: string, oleh: string, final: bool, semua: bool, kosong: bool}>
     */
    public static function tahapTampil(array $rute): array
    {
        return array_map(fn (array $t) => [
            'label' => str_replace(' (Approve Akhir)', '', $t['label']),
            'oleh' => $t['approvers']->take(3)->pluck('name')->implode(', ') . ($t['approvers']->count() > 3 ? ' +' . ($t['approvers']->count() - 3) . ' lainnya' : ''),
            'final' => $t['final'],
            'semua' => $t['semua'] ?? false,
            'kosong' => $t['approvers']->isEmpty(),
        ], $rute);
    }

    /** Cari akun User pemilik/pengaju pengajuan (lewat relasi data_pegawai_id). */
    public static function submitterUser(PengajuanCuti|PengajuanLembur|PengajuanIzinKeluar $pengajuan): ?User
    {
        if (! $pengajuan->data_pegawai_id) {
            return null;
        }

        return User::where('data_pegawai_id', $pengajuan->data_pegawai_id)->first();
    }

    /** Unit pengajuan = snapshot saat diajukan; data lama tanpa snapshot memakai unit pegawai sekarang. */
    public static function unitPengajuan(PengajuanCuti|PengajuanLembur|PengajuanIzinKeluar $pengajuan): ?string
    {
        return $pengajuan->unit_kerja ?? $pengajuan->dataPegawai?->kepegawaian?->unit_kerja;
    }

    /** Apakah $approver berwenang menyetujui/menolak pengajuan ini pada tahap saat ini. */
    public static function canAct(User $approver, PengajuanCuti|PengajuanLembur|PengajuanIzinKeluar $pengajuan): bool
    {
        // Mode Admin (sistem.override): boleh memproses pengajuan yang masih menunggu di tahap mana pun.
        if ($approver->can(Izin::OVERRIDE)) {
            return $pengajuan->isPending() && (bool) $pengajuan->current_level;
        }

        if (! $pengajuan->isPending() || ! $pengajuan->current_level || ! $approver->hasAbility(Izin::PENGAJUAN_APPROVE)) {
            return false;
        }

        if ($approver->data_pegawai_id && $approver->data_pegawai_id === $pengajuan->data_pegawai_id) {
            return false;
        }

        if ($pengajuan->current_level === self::LEVEL_TANGGA) {
            return TanggaAlurWorkflow::bisaSetujui($approver, $pengajuan);
        }

        $submitterUnit = self::unitPengajuan($pengajuan);
        $alur = AlurPegawai::milik($pengajuan->data_pegawai_id);

        return match ($pengajuan->current_level) {
            self::LEVEL_KEPALA_DIVISI => $alur?->atasan_user_id
                ? self::orangSama($approver, $alur->atasan_user_id)
                : $approver->memimpinUnit($submitterUnit)
                    || ($approver->isGm() && $approver->hasAbility(Izin::PENGAJUAN_APPROVE_GM) && self::kepalaUnit($submitterUnit, $pengajuan->data_pegawai_id)->isEmpty()),
            // Tahap GM: role GM (alur) + permission pengajuan.approve_gm.
            self::LEVEL_GM => $approver->hasAbility(Izin::PENGAJUAN_APPROVE_GM)
                && ($alur?->gm_user_id ? self::orangSama($approver, $alur->gm_user_id) : $approver->isGm()),
            self::LEVEL_KEPALA_SDM => $approver->isKepalaSdm(),
            default => false,
        };
    }

    /** Kolom yang di-update saat $approver menyetujui tahap saat ini (sudah lolos canAct()). */
    public static function approvalUpdates(User $approver, PengajuanCuti|PengajuanLembur|PengajuanIzinKeluar $pengajuan): array
    {
        if ($pengajuan->current_level === self::LEVEL_TANGGA) {
            return TanggaAlurWorkflow::updateSetuju($approver, $pengajuan);
        }

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
     * Setujui tahap berjalan (sudah lolos canAct) + kolom $tambahan (mis. pengganti tugas), lalu simpan.
     * Tangga alur: persetujuan dicatat per tahap sebelum pengajuan berpindah tahap.
     */
    public static function setujui(User $approver, PengajuanCuti|PengajuanLembur|PengajuanIzinKeluar $pengajuan, array $tambahan = []): array
    {
        return DB::transaction(function () use ($approver, $pengajuan, $tambahan) {
            $updates = [...self::approvalUpdates($approver, $pengajuan), ...$tambahan];
            if ($pengajuan->current_level === self::LEVEL_TANGGA) {
                TanggaAlurWorkflow::catat($approver, $pengajuan, (int) $pengajuan->tahap_ke);
            }
            $pengajuan->update($updates);

            return $updates;
        });
    }

    /** Lanjutan kalimat setelah "… disetujui dan": tahap berikutnya, atau tahap yang masih menunggu penyetuju lain. */
    public static function keteranganLanjut(PengajuanCuti|PengajuanLembur|PengajuanIzinKeluar $pengajuan): string
    {
        return $pengajuan->current_level === self::LEVEL_TANGGA && ! $pengajuan->wasChanged('tahap_ke')
            ? 'tercatat. Tahap ' . $pengajuan->currentLevelLabel() . ' masih menunggu persetujuan lain'
            : 'diteruskan ke ' . $pengajuan->currentLevelLabel();
    }

    /** @return array<string, string> pilihan tahap untuk form Ubah Langsung (Admin) */
    public static function pilihanTahapAdmin(PengajuanCuti|PengajuanLembur|PengajuanIzinKeluar $pengajuan): array
    {
        return $pengajuan->tangga ? TanggaAlurWorkflow::pilihanAdmin($pengajuan) : self::LEVEL_LABELS;
    }

    /** Nilai tahap saat ini di form Ubah Langsung (Admin): "tangga:indeks" untuk tangga alur. */
    public static function nilaiTahapAdmin(PengajuanCuti|PengajuanLembur|PengajuanIzinKeluar $pengajuan): ?string
    {
        return $pengajuan->tangga ? self::LEVEL_TANGGA . ':' . (int) $pengajuan->tahap_ke : $pengajuan->current_level;
    }

    /**
     * Kolom status yang di-set Admin secara langsung (tanpa tahapan).
     * Disetujui/ditolak = final atas nama Admin; menunggu = dikembalikan ke tahap $level
     * ("tangga:indeks" untuk tangga alur: persetujuan tahap itu & sesudahnya diulang).
     */
    public static function statusAdmin(PengajuanCuti|PengajuanLembur|PengajuanIzinKeluar $pengajuan, string $status, ?string $level, User $admin): array
    {
        $tangga = [];
        if ($level !== null && str_starts_with($level, self::LEVEL_TANGGA . ':')) {
            $ke = (int) substr($level, strlen(self::LEVEL_TANGGA) + 1);
            $level = self::LEVEL_TANGGA;
            if ($status === $pengajuan::STATUS_PENDING) {
                TanggaAlurWorkflow::ulangDari($pengajuan, $ke);
                $tangga = ['tahap_ke' => $ke];
            }
        }

        return match ($status) {
            $pengajuan::STATUS_PENDING => ['current_level' => $level, 'approved_by' => null, 'approved_at' => null] + $tangga,
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
    public static function isGmPengganti(User $gm, PengajuanCuti|PengajuanLembur|PengajuanIzinKeluar $pengajuan): bool
    {
        return ! $gm->memegangDivisi(self::unitPengajuan($pengajuan));
    }

    /** Nama GM utama untuk unit pemohon (beberapa dipisah " / "), atau "GM bidang lain" bila tidak ada. */
    public static function gmUtamaLabel(PengajuanCuti|PengajuanLembur|PengajuanIzinKeluar $pengajuan): string
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
     * Jumlah pengajuan yang menunggu approval GM per tab ("saya" = bidangnya, "lain" = di luar bidang); null bila GM tanpa bidang.
     *
     * @param  class-string<PengajuanCuti|PengajuanLembur|PengajuanIzinKeluar>  $model
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
        return $level === self::LEVEL_TANGGA ? 'Tangga Alur' : (self::LEVEL_LABELS[$level] ?? '-');
    }

    /** Nama tahap berjalan sebuah pengajuan (tangga alur: nama tahapnya, mis. "Koordinator SDM"). */
    public static function labelTahap(PengajuanCuti|PengajuanLembur|PengajuanIzinKeluar $pengajuan): string
    {
        return $pengajuan->current_level === self::LEVEL_TANGGA
            ? ($pengajuan->tangga[(int) $pengajuan->tahap_ke]['nama'] ?? self::levelLabel(self::LEVEL_TANGGA))
            : self::levelLabel($pengajuan->current_level);
    }

    /**
     * Batasi daftar pengajuan yang terlihat sesuai tingkatan/divisi $viewer,
     * ditambah pengajuan tangga alur yang menunggu / pernah disetujui $viewer.
     */
    public static function scopeVisibleTo(Builder $query, User $viewer): Builder
    {
        if (in_array($viewer->role, [User::ROLE_PEMBINA, User::ROLE_ADMIN], true)) {
            return $query;
        }
        $tangga = TanggaAlurWorkflow::idTerlihat($query->getModel(), $viewer);

        return $query->where(function ($q) use ($viewer, $tangga) {
            self::lingkupPeran($q, $viewer);
            if ($tangga) {
                $q->orWhereIn($q->qualifyColumn('id'), $tangga);
            }
        });
    }

    /** Lingkup bawaan per peran (alur Kepala Bagian → GM → Kepala SDM). */
    private static function lingkupPeran(Builder $query, User $viewer): Builder
    {
        return match ($viewer->role) {
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
                    // Tangga alur yang sudah selesai (disetujui/ditolak) tetap masuk laporan SDM walau SDM tidak di tangganya.
                    ->orWhere(fn ($w) => $w->whereNotNull($q->qualifyColumn('tangga'))->where($q->qualifyColumn('status'), '!=', $q->getModel()::STATUS_PENDING))
                    ->orWhere('data_pegawai_id', $viewer->data_pegawai_id ?? 0)
                    ->orWhereIn('unit_kerja', $viewer->unitsDipimpinUntukQuery())
                    ->orWhereIn('data_pegawai_id', self::pegawaiDenganAtasan($viewer));
            }),
            User::ROLE_KEPALA_BAGIAN => $query->where(function ($q) use ($viewer) {
                $units = $viewer->unitsDipimpinUntukQuery();
                $q->whereIn('unit_kerja', $units)
                    ->orWhere('data_pegawai_id', $viewer->data_pegawai_id ?? 0)
                    ->orWhereIn('data_pegawai_id', self::pegawaiDenganAtasan($viewer));
                if ($q->getModel() instanceof PengajuanCuti || $q->getModel() instanceof PengajuanIzinKeluar) {
                    $q->orWhereIn('data_pegawai_id', Penugasan::aktif()->whereIn('unit_kerja', $units)->select('data_pegawai_id'));
                }
            }),
            // Staff: pengajuannya sendiri; bila diberi hak lihat laporan (role kustom / hak jabatan) juga pengajuan unit tempatnya bertugas.
            default => $query->where(function ($q) use ($viewer) {
                $q->where('data_pegawai_id', $viewer->data_pegawai_id ?? 0);
                if ($viewer->hasAbility(Izin::PENGAJUAN_REPORT) && ($units = $viewer->unitsAktif())) {
                    $q->orWhereIn('unit_kerja', $units);
                }
            }),
        };
    }
}
