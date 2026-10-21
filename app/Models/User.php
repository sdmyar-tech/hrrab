<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable
{
    use \App\Models\Concerns\DicatatLog, HasFactory, Notifiable;

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

    public const ABILITY_LIHAT_DASHBOARD = 'lihat-dashboard';

    public const ABILITY_LIHAT_TABEL_KARYAWAN = 'lihat-tabel-karyawan';

    public const ABILITY_LIHAT_LAPORAN = 'lihat-laporan';

    public const ABILITY_APPROVE = 'approve';

    public const ABILITY_MENGAJUKAN = 'mengajukan';

    public const ABILITY_KELOLA_SISTEM = 'kelola-sistem';

    public const ABILITY_KELOLA_PENUGASAN = 'kelola-penugasan';

    public const ABILITY_KELOLA_PEGAWAI = 'kelola-pegawai';

    public const ABILITY_KELOLA_PENGUMUMAN = 'kelola-pengumuman';

    public const ABILITY_KIRIM_NOTIFIKASI = 'kirim-notifikasi';

    public const ABILITY_KELOLA_ABSENSI = 'kelola-absensi'; // import, koreksi, pengaturan absensi

    public const ABILITY_LIHAT_ABSENSI = 'lihat-absensi';   // melihat absensi (Kepala Bagian: unitnya saja)

    public const ABILITY_AJUKAN_CUTI = 'ajukan-cuti';

    public const ABILITY_AJUKAN_LEMBUR = 'ajukan-lembur';

    public const ABILITY_AJUKAN_IZIN_KELUAR = 'ajukan-izin-keluar';

    public const ABILITIES = [
        self::ROLE_PEMBINA => [
            self::ABILITY_LIHAT_DASHBOARD,
            self::ABILITY_LIHAT_TABEL_KARYAWAN,
            self::ABILITY_LIHAT_LAPORAN,
        ],
        self::ROLE_GM => [
            self::ABILITY_LIHAT_DASHBOARD,
            self::ABILITY_LIHAT_TABEL_KARYAWAN,
            self::ABILITY_LIHAT_LAPORAN,
            self::ABILITY_APPROVE,
            self::ABILITY_KELOLA_PENGUMUMAN,
            self::ABILITY_KIRIM_NOTIFIKASI,
        ],
        self::ROLE_KEPALA_BAGIAN => [
            self::ABILITY_LIHAT_DASHBOARD,
            self::ABILITY_LIHAT_TABEL_KARYAWAN,
            self::ABILITY_LIHAT_LAPORAN,
            self::ABILITY_APPROVE,
            self::ABILITY_MENGAJUKAN,
            self::ABILITY_KELOLA_PENGUMUMAN,
            self::ABILITY_KIRIM_NOTIFIKASI,
            self::ABILITY_LIHAT_ABSENSI,
        ],
        self::ROLE_KEPALA_SDM => [
            self::ABILITY_LIHAT_DASHBOARD,
            self::ABILITY_LIHAT_TABEL_KARYAWAN,
            self::ABILITY_LIHAT_LAPORAN,
            self::ABILITY_APPROVE,
            self::ABILITY_MENGAJUKAN,
            self::ABILITY_KELOLA_PENUGASAN,
            self::ABILITY_KELOLA_PENGUMUMAN,
            self::ABILITY_KIRIM_NOTIFIKASI,
            self::ABILITY_KELOLA_PEGAWAI,
            self::ABILITY_KELOLA_ABSENSI,
            self::ABILITY_LIHAT_ABSENSI,
        ],
        self::ROLE_STAFF => [
            self::ABILITY_LIHAT_DASHBOARD,
            self::ABILITY_MENGAJUKAN,
        ],
        self::ROLE_ADMIN => [
            self::ABILITY_LIHAT_DASHBOARD,
            self::ABILITY_LIHAT_TABEL_KARYAWAN,
            self::ABILITY_LIHAT_LAPORAN,
            self::ABILITY_KELOLA_SISTEM,
            self::ABILITY_KELOLA_PENUGASAN,
            self::ABILITY_KELOLA_PENGUMUMAN,
            self::ABILITY_KIRIM_NOTIFIKASI,
            self::ABILITY_KELOLA_PEGAWAI,
            self::ABILITY_KELOLA_ABSENSI,
            self::ABILITY_LIHAT_ABSENSI,
        ],
    ];

    protected $fillable = [
        'name',
        'email',
        'google_id',
        'password',
        'role',
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

    public function hasAbility(string $ability): bool
    {
        return match ($ability) {
            self::ABILITY_AJUKAN_CUTI => $this->hasAbility(self::ABILITY_MENGAJUKAN)
                && ! ($this->isStaff() && in_array($this->unitKerja(), config('kepegawaian.unit_tanpa_pengajuan', []), true)),
            self::ABILITY_AJUKAN_LEMBUR => $this->hasAbility(self::ABILITY_MENGAJUKAN) && $this->unitsLembur() !== [],
            // Izin keluar saat jam kerja: siapa pun yang boleh mengajukan cuti (unit sama, alur sama).
            self::ABILITY_AJUKAN_IZIN_KELUAR => $this->hasAbility(self::ABILITY_AJUKAN_CUTI),
            self::ABILITY_KELOLA_PEGAWAI => $this->punyaDiMatriks($ability) || ($this->isStaff() && in_array('SDM', $this->unitsAktif(), true)),
            self::ABILITY_LIHAT_TABEL_KARYAWAN => $this->punyaDiMatriks($ability) || $this->hasAbility(self::ABILITY_KELOLA_PEGAWAI),
            // Staf unit SDM ikut mengelola absensi (sama seperti kelola pegawai).
            self::ABILITY_KELOLA_ABSENSI => $this->punyaDiMatriks($ability) || ($this->isStaff() && in_array('SDM', $this->unitsAktif(), true)),
            self::ABILITY_LIHAT_ABSENSI => $this->punyaDiMatriks($ability) || $this->hasAbility(self::ABILITY_KELOLA_ABSENSI),
            self::ABILITY_MENGAJUKAN, self::ABILITY_APPROVE, self::ABILITY_LIHAT_LAPORAN => $this->punyaDiMatriks($ability)
                && ! $this->diUnitTanpaPengajuan(),
            default => $this->punyaDiMatriks($ability),
        };
    }

    private function punyaDiMatriks(string $ability): bool
    {
        return in_array($ability, self::ABILITIES[$this->role] ?? [], true);
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

    public function bisaMenginterview(): bool
    {
        return in_array($this->role, [self::ROLE_KEPALA_SDM, self::ROLE_GM, self::ROLE_KEPALA_BAGIAN], true);
    }

    public function fotoUrl(): string
    {
        $path = $this->dataPegawai?->dokumen?->pas_foto ?? $this->dataPelamar?->foto_path;

        return $path && Storage::disk('public')->exists($path)
            ? asset('storage/' . $path)
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
        return self::roleLabelFor($this->role);
    }

    public static function roleLabelFor(?string $role): string
    {
        return match ($role) {
            self::ROLE_PEMBINA => 'Pembina (Owner)',
            self::ROLE_GM => 'GM',
            self::ROLE_KEPALA_BAGIAN => 'Kepala Bagian',
            self::ROLE_KEPALA_SDM => 'Kepala SDM',
            self::ROLE_STAFF => 'Staff',
            self::ROLE_ADMIN => 'Admin',
            self::ROLE_PELAMAR => 'Pelamar',
            default => ucfirst($role ?? 'User'),
        };
    }

    public function dataPegawai()
    {
        return $this->belongsTo(DataPegawai::class, 'data_pegawai_id');
    }

    public function dataPelamar()
    {
        return $this->belongsTo(DataPelamar::class, 'data_pelamar_id');
    }

    public function pengajuanFormasis()
    {
        return $this->hasMany(PengajuanFormasi::class, 'diajukan_oleh');
    }
}
