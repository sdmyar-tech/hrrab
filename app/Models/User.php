<?php

namespace App\Models;

use App\Models\Concerns\DicatatLog;
use App\Support\Berkas;
use App\Support\Izin;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable
{
    use DicatatLog, HasFactory, Notifiable;

    public const ROLE_PEMBINA = 'pembina';

    public const ROLE_GM = 'gm';

    public const ROLE_KEPALA_BAGIAN = 'kepala_bagian';

    public const ROLE_KEPALA_SDM = 'kepala_sdm';

    public const ROLE_STAFF = 'staff';

    public const ROLE_ADMIN = 'admin';

    public const ROLE_PELAMAR = 'guest';

    public const INTERNAL_ROLES = [
        self::ROLE_PEMBINA,
        self::ROLE_GM,
        self::ROLE_KEPALA_BAGIAN,
        self::ROLE_KEPALA_SDM,
        self::ROLE_STAFF,
        self::ROLE_ADMIN,
    ];

    // Gate turunan (bukan permission yang dicentang): pengajuan.create + aturan unit/data pegawai.
    public const ABILITY_AJUKAN_CUTI = 'ajukan-cuti';

    public const ABILITY_AJUKAN_LEMBUR = 'ajukan-lembur';

    public const ABILITY_AJUKAN_IZIN_KELUAR = 'ajukan-izin-keluar';

    protected $fillable = [
        'name',
        'email',
        'google_id',
        'password',
        'role',
        'role_id',
        'divisi',
        'kepala_unit_gm',
        'nip',
        'data_pegawai_id',
        'data_pelamar_id',
        'wajib_ganti_password',
        'password_diganti_pada',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected static function booted(): void
    {
        // Role kustom hanya berlaku selama jenis dasarnya sama (mis. Staff dinaikkan jadi Kepala Bagian → role kustom berbasis Staff dilepas).
        static::saving(function (self $user) {
            if ($user->role_id && $user->isDirty('role') && $user->roleKustom()?->jenis_dasar !== $user->role) {
                $user->role_id = null;
            }
            // Role bawaan yang sudah dihapus di Master Role (mis. dipasang otomatis: pegawai baru = Staff) → pakai role penggantinya.
            if (! $user->role_id && ($user->isDirty('role') || $user->isDirty('role_id')) && ($tujuan = Role::penggantiUntuk((string) $user->role))) {
                $user->role = $tujuan['role'];
                $user->role_id = $tujuan['role_id'];
            }
        });
    }

    /** Role kustom akun ini (Master Role), atau null bila memakai role bawaan. */
    public function roleKustom(): ?Role
    {
        return $this->role_id ? Role::semua()->firstWhere('id', $this->role_id) : null;
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'divisi' => 'array',
            'kepala_unit_gm' => 'array',
            'wajib_ganti_password' => 'boolean',
            'password_diganti_pada' => 'datetime',
        ];
    }

    /**
     * Apakah akun memegang permission $ability (lihat App\Support\Izin) atau lolos gate turunan pengajuan.
     * Sumber permission HANYA centang di database: role akun (role_permission) + centang tambahan akun (user_permission).
     * Aturan bisnis yang tetap berlaku (mengurangi, tidak menambah): unit tanpa pengajuan (mis. sekolah) tidak ikut
     * mengajukan/menyetujui/melihat data pengajuan, dan pengajuan butuh data pegawai.
     */
    public function hasAbility(string $ability): bool
    {
        return match ($ability) {
            self::ABILITY_AJUKAN_CUTI => $this->hasAbility(Izin::PENGAJUAN_CREATE) && $this->data_pegawai_id
                && ! ($this->isStaff() && in_array($this->unitKerja(), config('kepegawaian.unit_tanpa_pengajuan', []), true)),
            self::ABILITY_AJUKAN_LEMBUR => $this->hasAbility(Izin::PENGAJUAN_CREATE) && $this->data_pegawai_id && $this->unitsLembur() !== [],
            // Izin keluar saat jam kerja: siapa pun yang boleh mengajukan cuti (unit sama, alur sama).
            self::ABILITY_AJUKAN_IZIN_KELUAR => $this->hasAbility(self::ABILITY_AJUKAN_CUTI),
            Izin::PENGAJUAN_CREATE, Izin::PENGAJUAN_APPROVE, Izin::PENGAJUAN_REPORT => $this->punyaIzin($ability) && ! $this->diUnitTanpaPengajuan(),
            default => $this->punyaIzin($ability),
        };
    }

    /** @var list<string>|null hasil izin() untuk instance ini */
    private ?array $izinMemo = null;

    /**
     * Permission yang dipegang akun: dari role (role kustom bila ada, selain itu role sistem) + centang tambahan akun.
     *
     * @return list<string>
     */
    public function izin(): array
    {
        if ($this->izinMemo !== null) {
            return $this->izinMemo;
        }
        if (! in_array($this->role, self::INTERNAL_ROLES, true)) {
            return $this->izinMemo = [];
        }
        $kustom = $this->roleKustom();

        return $this->izinMemo = array_values(array_unique([
            ...($kustom ? $kustom->hakAksesEfektif() : Role::hakAksesBawaan((string) $this->role)),
            ...$this->izinTambahan(),
        ]));
    }

    /** @return list<string> permission yang dicentang langsung di akun ini (di luar role) */
    public function izinTambahan(): array
    {
        return $this->id ? Permission::tambahanUntuk($this->id) : [];
    }

    private function punyaIzin(string $kode): bool
    {
        return in_array($kode, $this->izin(), true);
    }

    /** Lupakan memo permission (setelah role / centang akun berubah dalam proses yang sama). */
    public function segarkanIzin(): void
    {
        $this->izinMemo = null;
        Permission::lupakan();
    }

    /**
     * Ikut dalam rantai persetujuan (antrean & daftar calon penyetuju): memegang pengajuan.approve dan bukan role penuh.
     * Admin memegang semua permission tetapi memproses pengajuan lewat Mode Admin (sistem.override), bukan sebagai
     * penyetuju di alur.
     */
    public function adalahPenyetuju(): bool
    {
        return $this->role !== Izin::ROLE_PENUH && $this->hasAbility(Izin::PENGAJUAN_APPROVE);
    }

    public function diUnitTanpaPengajuan(): bool
    {
        if (! $this->isStaff() && ! $this->isKepalaBagian()) {
            return false;
        }

        $units = $this->isKepalaBagian() ? $this->unitsDipimpin() : $this->unitsAktif();

        return $units !== [] && array_diff($units, config('kepegawaian.unit_tanpa_pengajuan', [])) === [];
    }

    public function isPendidik(): bool
    {
        $kep = $this->dataPegawai?->kepegawaian;

        return self::jabatanPendidik([$kep?->jabatan, $kep?->jabatan_tambahan]);
    }

    /**
     * @param  array<int, string|null>  $daftar  jabatan (boleh berisi daftar dipisah koma, mis. jabatan tambahan)
     */
    private static function jabatanPendidik(array $daftar): bool
    {
        $jabatan = array_filter(array_merge(...array_map(fn ($j) => explode(',', (string) $j), $daftar)));

        foreach ($jabatan as $j) {
            foreach (config('kepegawaian.jabatan_tanpa_lembur', []) as $pola) {
                if (preg_match($pola, mb_strtoupper(trim($j)))) {
                    return true;
                }
            }
        }

        return false;
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isGm(): bool
    {
        return $this->role === self::ROLE_GM;
    }

    public function isKepalaBagian(): bool
    {
        return $this->role === self::ROLE_KEPALA_BAGIAN;
    }

    public function isKepalaSdm(): bool
    {
        return $this->role === self::ROLE_KEPALA_SDM;
    }

    public function isPembina(): bool
    {
        return $this->role === self::ROLE_PEMBINA;
    }

    public function isStaff(): bool
    {
        return $this->role === self::ROLE_STAFF;
    }

    public function isPelamar(): bool
    {
        return $this->role === self::ROLE_PELAMAR;
    }

    /**
     * Menu "Data Saya" (Data Diri & Pengkinian Data): semua staf, plus akun internal lain (GM, kepala, dst.)
     * yang tertaut ke data pegawai. Akun tanpa data pegawai (mis. akun Admin murni) tidak dibuatkan otomatis.
     */
    public function punyaDataSaya(): bool
    {
        return $this->isStaff()
            || (in_array($this->role, self::INTERNAL_ROLES, true) && $this->data_pegawai_id !== null);
    }

    /** Menu Jadwal Interview Saya. Penunjukan interviewer tetap mengikuti alur rekrutmen (InterviewFlow). */
    public function bisaMenginterview(): bool
    {
        return $this->hasAbility(Izin::INTERVIEW);
    }

    public function fotoUrl(): string
    {
        $path = $this->dataPegawai?->dokumen?->pas_foto ?? $this->dataPelamar?->foto_path;

        return $path && Storage::disk('public')->exists($path)
            ? Berkas::url($path)
            : asset('assets-admin/images/avatar/avatar-fallback.jpg');
    }

    public function panelLayout(): string
    {
        return match (true) {
            $this->isPelamar() => 'layouts.pelamar.app',
            $this->isStaff() => 'layouts.pegawai.app',
            default => 'layouts.admin.app',
        };
    }

    public function dashboardRoute(): string
    {
        return match (true) {
            $this->isPelamar() => 'pelamar.lamaran.index',
            $this->isStaff() => 'pegawai.dashboard',
            default => 'admin.dashboard',
        };
    }

    public function unitKerja(): ?string
    {
        return $this->dataPegawai?->kepegawaian?->unit_kerja;
    }

    /**
     * @return Collection<int, Penugasan>
     */
    public function penugasanAktif(): Collection
    {
        return $this->dataPegawai?->penugasanAktif ?? collect();
    }

    /**
     * @return list<string>
     */
    public function unitsAktif(): array
    {
        $units = $this->penugasanAktif()->pluck('unit_kerja')->unique()->values()->all();

        return $units ?: array_values(array_filter([$this->unitKerja()]));
    }

    /**
     * @return list<string>
     */
    public function unitsDipimpin(): array
    {
        if (! $this->isKepalaBagian() && ! $this->isKepalaSdm() && ! $this->isGm()) {
            return [];
        }

        // GM tanpa Data Pegawai: unit dipimpin disimpan langsung di akun.
        if ($this->isGm() && ! $this->data_pegawai_id) {
            return array_values(array_unique((array) $this->kepala_unit_gm));
        }

        $penugasan = $this->penugasanAktif();

        if ($penugasan->isEmpty()) {
            return ($this->isGm() || ! $this->unitKerja()) ? [] : [$this->unitKerja()];
        }

        return $penugasan->where('peran', Penugasan::PERAN_KEPALA)->pluck('unit_kerja')->unique()->values()->all();
    }

    /**
     * @return list<string>
     */
    public function unitsLembur(): array
    {
        $kep = $this->dataPegawai?->kepegawaian;
        $penugasan = $this->penugasanAktif();

        $baris = $penugasan->isNotEmpty()
            ? $penugasan->map(fn (Penugasan $p) => [$p->unit_kerja, $p->is_utama ? [$p->jabatan, $kep?->jabatan_tambahan] : [$p->jabatan]])
            : collect([[$kep?->unit_kerja, [$kep?->jabatan, $kep?->jabatan_tambahan]]]);

        return $baris
            ->reject(fn ($b) => ! $b[0] || in_array($b[0], config('kepegawaian.unit_tanpa_pengajuan', []), true))
            ->reject(fn ($b) => self::jabatanPendidik($b[1]))
            ->pluck(0)->unique()->values()->all();
    }

    public function unitDipimpin(): ?string
    {
        return $this->unitsDipimpin()[0] ?? null;
    }

    public function memimpinUnit(?string $unit): bool
    {
        return $unit !== null && in_array($unit, $this->unitsDipimpin(), true);
    }

    /**
     * @return list<string>
     */
    public function unitsDipimpinUntukQuery(): array
    {
        return $this->unitsDipimpin() ?: ['__none__'];
    }

    /**
     * Akun GM tanpa Data Pegawai yang memimpin $unit (atau semua unit yang dipimpin akun seperti itu bila $unit null).
     */
    public static function gmKepalaTanpaPegawai(?string $unit = null): Builder
    {
        return self::query()->where('role', self::ROLE_GM)->whereNull('data_pegawai_id')->whereNotNull('kepala_unit_gm')
            ->when($unit !== null, fn ($q) => $q->whereJsonContains('kepala_unit_gm', $unit));
    }

    /** @return list<string> unit yang dipimpin GM tanpa Data Pegawai */
    public static function unitsKepalaGmTanpaPegawai(): array
    {
        return self::gmKepalaTanpaPegawai()->pluck('kepala_unit_gm')->flatten()->unique()->values()->all();
    }

    public static function gmUtamaUntuk(?string $unit): Collection
    {
        static $gms = null;
        $gms ??= self::where('role', self::ROLE_GM)->orderBy('name')->get();

        return $gms->filter(fn (User $gm) => ! empty($gm->divisi) && in_array($unit, $gm->divisi, true))->values();
    }

    public function memegangDivisi(?string $unit): bool
    {
        if (! $this->isGm()) {
            return false;
        }

        return empty($this->divisi) || in_array($unit, $this->divisi, true);
    }

    public function roleLabel(): string
    {
        return $this->roleKustom()?->nama ?? self::roleLabelFor($this->role);
    }

    /** Nama role bawaan menurut kode (sebelum disesuaikan di Master Role). */
    public const LABEL_ROLE = [
        self::ROLE_PEMBINA => 'Pembina (Owner)',
        self::ROLE_GM => 'GM',
        self::ROLE_KEPALA_BAGIAN => 'Kepala Bagian',
        self::ROLE_KEPALA_SDM => 'Kepala SDM',
        self::ROLE_STAFF => 'Staff',
        self::ROLE_ADMIN => 'Admin',
        self::ROLE_PELAMAR => 'Pelamar',
    ];

    public static function roleLabelFor(?string $role): string
    {
        return Role::penyesuaian($role)?->nama ?? self::LABEL_ROLE[$role] ?? ucfirst($role ?? 'User');
    }

    public function dataPegawai()
    {
        return $this->belongsTo(DataPegawai::class, 'data_pegawai_id');
    }

    public function dataPelamar()
    {
        return $this->belongsTo(DataPelamar::class, 'data_pelamar_id');
    }

    /** Permission yang dicentang langsung di akun ini (di luar role), beserta keterangan asal-usulnya. */
    public function permissionTambahan(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'user_permission')->withPivot(['keterangan', 'diberikan_oleh'])->withTimestamps();
    }

    public function pengajuanFormasis()
    {
        return $this->hasMany(PengajuanFormasi::class, 'diajukan_oleh');
    }
}
