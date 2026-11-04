<?php

namespace App\Support;

use App\Models\AlurPegawai;
use App\Models\DataPegawai;
use App\Models\Penugasan;
use App\Models\SuratPeringatan;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Hak akses surat peringatan. Pemberi SP = atasan yang menilai pegawai itu (atasan langsung; GM untuk kepala bagian
 * & Kepala SDM — sama dengan penilai evaluasi kontrak), ditambah pemegang permission sp.manage_all.
 */
class SuratPeringatanAkses
{
    /** Melihat SP seluruh pegawai. */
    public static function lihatSemua(User $user): bool
    {
        return $user->can(Izin::SP_VIEW_ALL);
    }

    public static function bolehMembuka(User $user): bool
    {
        return self::lihatSemua($user) || ! empty($user->unitsDipimpin()) || AlurPegawai::where('atasan_user_id', $user->id)->exists();
    }

    public static function bisaBeri(User $user, DataPegawai $pegawai): bool
    {
        if ($user->data_pegawai_id && $user->data_pegawai_id === $pegawai->id) {
            return false; // tidak memberi SP untuk diri sendiri
        }

        return $user->can(Izin::SP_MANAGE_ALL)
            || EvaluasiKontrakAlur::penilai($pegawai)->contains(fn (User $a) => PengajuanWorkflow::orangSama($user, $a->id));
    }

    /** SP boleh dihapus pemberinya atau pemegang sp.manage_all — hanya SP terakhir di bulannya (urutan tak bolong). */
    public static function bisaHapus(User $user, SuratPeringatan $sp): bool
    {
        return ($user->can(Izin::SP_MANAGE_ALL) || $sp->diberikan_oleh === $user->id) && $sp->terakhirDiBulannya();
    }

    /**
     * Pegawai aktif yang boleh diberi SP oleh $user.
     *
     * @return Collection<int, DataPegawai>
     */
    public static function pegawaiBoleh(User $user): Collection
    {
        $aktif = DataPegawai::with('kepegawaian')->whereHas('kepegawaian', fn ($q) => $q->where('status_rekrutmen', 'Aktif'))->orderBy('nama_lengkap');
        if ($user->can(Izin::SP_MANAGE_ALL)) {
            return $aktif->get()->reject(fn (DataPegawai $p) => $p->id === $user->data_pegawai_id)->values();
        }

        // Kandidat: pegawai di unit yang dipimpin / dipegang sebagai GM, plus bawahan di alur khusus; lalu dicek ulang.
        $unit = collect($user->unitsDipimpin())
            ->merge($user->isGm() ? collect(config('kepegawaian.unit_kerja'))->filter(fn ($u) => User::gmUtamaUntuk($u)->contains('id', $user->id)) : [])
            ->unique()->values();
        $diUnit = Penugasan::aktif()->whereIn('unit_kerja', $unit)->pluck('data_pegawai_id');
        $alur = AlurPegawai::where('atasan_user_id', $user->id)->pluck('data_pegawai_id');

        return $aktif->whereIn('id', $diUnit->merge($alur)->unique())->get()
            ->filter(fn (DataPegawai $p) => self::bisaBeri($user, $p))->values();
    }
}
