<?php

namespace App\Support;

use App\Models\DataPegawai;
use App\Models\Jabatan;
use App\Models\Kepegawaian;
use App\Models\PengajuanFormasi;
use App\Models\Penugasan;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/** Hak akses modul rekrutmen: Kepala SDM, GM & Admin bisa approve/aksi di semua divisi. */
class RekrutmenAccess
{
    public static function canReview(User $user): bool
    {
        return $user->isKepalaSdm() || $user->isGm() || $user->isAdmin();
    }

    /** Admin (super admin) mengelola lowongan semua divisi bersama Kepala SDM & GM. */
    public static function canKelolaSemuaLowongan(User $user): bool
    {
        return self::canReview($user) || $user->isAdmin();
    }

    /** Hak lihat seluruh data rekrutmen (lamaran, formasi) tanpa batas divisi. */
    public static function canLihatSemua(User $user): bool
    {
        return self::canReview($user) || $user->isAdmin() || $user->isPembina();
    }

    /** Kepala Bagian mengajukan untuk unit yang dipimpin; Kepala SDM boleh mengajukan untuk unit mana pun. */
    public static function bisaAjukanFormasi(User $user): bool
    {
        return $user->isKepalaSdm() || $user->isAdmin()
            || PengajuanWorkflow::submitterLevel($user) === PengajuanWorkflow::LEVEL_KEPALA_DIVISI;
    }

    /**
     * Unit pengajuan formasi = unit yang dipimpin pengaju (mis. Nurfahmi: GA & CS), unit utama lebih dulu.
     * Tidak bisa unit lain. Tanpa unit dipimpin → unit utamanya. Admin bebas memilih unit mana pun.
     *
     * @return list<string>
     */
    public static function unitAjuanFormasi(User $user): array
    {
        if ($user->isAdmin()) {
            return config('kepegawaian.unit_kerja');
        }
        $utama = $user->unitKerja();
        $dipimpin = $user->unitsDipimpin();
        if (! $dipimpin) {
            return $utama ? [$utama] : [];
        }

        // Unit utama di urutan pertama (jadi pilihan bawaan) bila memang dipimpin.
        return array_values(array_unique(in_array($utama, $dipimpin, true) ? [$utama, ...$dipimpin] : $dipimpin));
    }

    /**
     * Data bagan unit untuk formulir formasi: kepala di atas, pegawai dikelompokkan per jabatan.
     * aktif = pegawai aktif (hijau), tidak aktif = pernah di unit ini tapi sudah nonaktif (abu).
     *
     * jk = 'L' / 'P' / null (belum diisi), dipakai untuk mengisi jumlah laki-laki/perempuan pengganti.
     *
     * @return array{kepala: list<string>, grup: array<string, list<array{id: int, nama: string, aktif: bool, jk: ?string}>>}
     */
    public static function baganUnit(string $unit): array
    {
        $kepalaIds = Penugasan::aktif()->kepala()->where('unit_kerja', $unit)->pluck('data_pegawai_id')->all();
        $orang = [];
        $jk = fn ($pegawai) => match ($pegawai->jenis_kelamin) {
            'Laki-laki' => 'L', 'Perempuan' => 'P', default => null
        };

        // Aktif: penugasan aktif di unit ini (termasuk rangkap).
        Penugasan::aktif()->where('unit_kerja', $unit)->with('dataPegawai:id,nama_lengkap,jenis_kelamin')
            ->whereHas('dataPegawai.kepegawaian', fn ($q) => $q->where('status_rekrutmen', 'Aktif'))->get()
            ->each(function ($p) use (&$orang, $kepalaIds, $jk) {
                if ($p->dataPegawai && ! in_array($p->data_pegawai_id, $kepalaIds, true)) {
                    $orang[$p->data_pegawai_id] = ['id' => $p->data_pegawai_id, 'nama' => $p->dataPegawai->nama_lengkap, 'aktif' => true, 'jk' => $jk($p->dataPegawai), 'jabatan' => mb_strtoupper((string) $p->jabatan) ?: 'LAINNYA'];
                }
            });
        // Tidak aktif: unit terakhirnya unit ini.
        Kepegawaian::where('unit_kerja', $unit)->where('status_rekrutmen', 'Tidak Aktif')->with('dataPegawai:id,nama_lengkap,jenis_kelamin')->get()
            ->each(function ($k) use (&$orang, $jk) {
                if ($k->dataPegawai && ! isset($orang[$k->data_pegawai_id])) {
                    $orang[$k->data_pegawai_id] = ['id' => $k->data_pegawai_id, 'nama' => $k->dataPegawai->nama_lengkap, 'aktif' => false, 'jk' => $jk($k->dataPegawai), 'jabatan' => mb_strtoupper((string) $k->jabatan) ?: 'LAINNYA'];
                }
            });

        $grup = collect($orang)->sortBy([['aktif', 'desc'], ['nama', 'asc']])->groupBy('jabatan')->sortKeys()
            ->map(fn ($g) => $g->map(fn ($o) => collect($o)->except('jabatan')->all())->values()->all())->all();

        return [
            'kepala' => DataPegawai::whereIn('id', $kepalaIds)->pluck('nama_lengkap')->all(),
            'grup' => $grup,
        ];
    }

    /**
     * Jabatan yang boleh diajukan untuk $unit: jabatan yang di Master Jabatan ditautkan ke unit itu
     * (diatur Admin di Master Jabatan). Mencegah Kepala Keuangan memilih mis. "STAFF STCH".
     *
     * @return list<string>
     */
    public static function jabatanUnit(string $unit): array
    {
        return Jabatan::perUnit()[$unit] ?? [];
    }

    /** GM unit: GM yang memegang divisi itu; bila tak ada, GM tanpa batas divisi. Bisa kosong. */
    public static function gmFormasi(PengajuanFormasi $formasi): Collection
    {
        $utama = User::gmUtamaUntuk($formasi->unit_kerja);

        return $utama->isNotEmpty() ? $utama : User::where('role', User::ROLE_GM)->get()->filter(fn (User $g) => empty($g->divisi))->values();
    }

    /**
     * Tahap persetujuan formasi "penambahan" yang sedang berjalan: 'gm' → 'owner' (bertahap), null bila selesai.
     * Unit tanpa GM langsung ke tahap Owner.
     */
    public static function tahapPersetujuan(PengajuanFormasi $formasi): ?string
    {
        if (! $formasi->butuhDuaPersetujuan()) {
            return null;
        }
        if (! $formasi->gm_disetujui_oleh && self::perluGm($formasi)) {
            return 'gm';
        }

        return $formasi->owner_disetujui_oleh ? null : 'owner';
    }

    /** Sisi persetujuan yang bisa dilakukan $user sekarang: hanya pemegang tahap berjalan ('gm' / 'owner'), selain itu null. */
    public static function sisiPersetujuan(User $user, PengajuanFormasi $formasi): ?string
    {
        return match (self::tahapPersetujuan($formasi)) {
            'gm' => $user->isGm() && self::gmFormasi($formasi)->contains('id', $user->id) ? 'gm' : null,
            'owner' => $user->isPembina() ? 'owner' : null,
            default => null,
        };
    }

    /** Penambahan: GM unit tidak ada → cukup persetujuan Owner. */
    public static function perluGm(PengajuanFormasi $formasi): bool
    {
        return self::gmFormasi($formasi)->isNotEmpty();
    }

    public static function canReviewFormasi(User $user, PengajuanFormasi $formasi): bool
    {
        // Admin bebas mereview formasi apa pun (termasuk unit langsung Pembina).
        if ($user->isAdmin()) {
            return true;
        }

        // Pengaju (termasuk akun lain milik orang yang sama) tidak boleh mereview formasinya sendiri.
        $pengaju = $formasi->diajukanOleh;
        if ($formasi->diajukan_oleh === $user->id
            || ($user->data_pegawai_id && $pengaju?->data_pegawai_id === $user->data_pegawai_id)) {
            return false;
        }

        // Penambahan karyawan: hanya GM unit & Owner, masing-masing untuk sisinya sendiri.
        if ($formasi->butuhDuaPersetujuan()) {
            return self::sisiPersetujuan($user, $formasi) !== null;
        }

        if (in_array($formasi->unit_kerja, config('kepegawaian.unit_langsung_pembina', []), true)) {
            return $user->isPembina();
        }

        return self::canReview($user);
    }

    public static function canLihatSemuaFormasi(User $user): bool
    {
        return self::canLihatSemua($user);
    }

    public static function scopeUnitKerja(Builder $query, User $user, string $column = 'unit_kerja'): Builder
    {
        if (self::canReview($user) || $user->isAdmin() || $user->isPembina()) {
            return $query;
        }

        return $query->whereIn($column, $user->unitsDipimpinUntukQuery());
    }
}
