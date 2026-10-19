<?php

namespace App\Support;

use App\Models\AlurPegawai;
use App\Models\DataPegawai;
use App\Models\EvaluasiKontrak;
use App\Models\Kepegawaian;
use App\Models\Penugasan;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Alur evaluasi kontrak: dibuat otomatis H-30 → atasan mengisi → Kepala SDM memutuskan → data kontrak diperbarui.
 * Atasan = atasan alur khusus → kepala unit pegawai (termasuk kepala sekolah yang tidak menyetujui cuti) → GM (unit tanpa kepala).
 */
class EvaluasiKontrakAlur
{
    /** @return Collection<int, User> */
    public static function atasan(DataPegawai $pegawai, ?string $unit = null): Collection
    {
        $unit ??= $pegawai->kepegawaian?->unit_kerja;
        if (($alur = AlurPegawai::milik($pegawai->id)) && $alur->atasan) {
            return collect([$alur->atasan]);
        }
        $kepala = self::kepalaUnit($unit, $pegawai->id);
        if ($kepala->isNotEmpty()) {
            return $kepala;
        }
        $gm = User::gmUtamaUntuk($unit)->reject(fn (User $g) => $g->data_pegawai_id && $g->data_pegawai_id === $pegawai->id);

        return $gm->isNotEmpty() ? $gm->values() : PengajuanWorkflow::gmTersedia($pegawai->id);
    }

    /**
     * Kepala unit (dari penugasan kepala aktif + GM tanpa data pegawai yang memegang unit), selain pegawai itu sendiri.
     * Berbeda dengan PengajuanWorkflow::kepalaUnit: TIDAK mensyaratkan hak menyetujui cuti, karena kepala sekolah
     * (SMA/SMP/SMK) tetap atasan yang menilai kontrak walau tidak memproses cuti.
     *
     * @return Collection<int, User>
     */
    private static function kepalaUnit(?string $unit, int $kecualiPegawaiId): Collection
    {
        if (! $unit) {
            return collect();
        }

        return User::query()
            ->whereIn('role', [User::ROLE_KEPALA_BAGIAN, User::ROLE_KEPALA_SDM, User::ROLE_GM])
            ->where(fn ($w) => $w
                ->whereIn('data_pegawai_id', Penugasan::aktif()->kepala()->where('unit_kerja', $unit)
                    ->where('data_pegawai_id', '!=', $kecualiPegawaiId)->select('data_pegawai_id'))
                ->orWhereIn('id', User::gmKepalaTanpaPegawai($unit)->select('id')))
            ->orderBy('name')
            ->get();
    }

    public static function adalahAtasan(User $user, EvaluasiKontrak $e): bool
    {
        return $e->dataPegawai && self::atasan($e->dataPegawai, $e->unit_kerja)
            ->contains(fn (User $a) => PengajuanWorkflow::orangSama($user, $a->id));
    }

    /** Atasan mengisi/mengubah rekomendasi selama Kepala SDM belum memutuskan; Admin selalu bisa. */
    public static function bisaIsi(User $user, EvaluasiKontrak $e): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $e->status !== EvaluasiKontrak::STATUS_SELESAI && self::adalahAtasan($user, $e);
    }

    /** Kepala SDM memutuskan setelah atasan mengisi (bukan evaluasi dirinya sendiri); Admin kapan saja. */
    public static function bisaPutuskan(User $user, EvaluasiKontrak $e): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->isKepalaSdm() && $e->status === EvaluasiKontrak::STATUS_MENUNGGU_SDM
            && ! ($user->data_pegawai_id && $user->data_pegawai_id === $e->data_pegawai_id);
    }

    /** Pimpinan (Kepala SDM, GM, Owner, Admin) melihat semua; selain itu hanya bawahannya. */
    public static function lihatSemua(User $user): bool
    {
        return $user->isAdmin() || $user->isKepalaSdm() || $user->isGm() || $user->isPembina();
    }

    public static function bisaLihat(User $user, EvaluasiKontrak $e): bool
    {
        return self::lihatSemua($user) || self::adalahAtasan($user, $e);
    }

    /** Pimpinan, penyetuju, kepala unit (termasuk kepala sekolah), atau atasan di alur khusus seseorang. */
    public static function bolehMembuka(User $user): bool
    {
        return self::lihatSemua($user) || $user->can(User::ABILITY_APPROVE) || ! empty($user->unitsDipimpin())
            || AlurPegawai::where('atasan_user_id', $user->id)->exists();
    }

    /** Masih ada tugas untuk $user: isi (atasan, menunggu atasan) atau putuskan (Kepala SDM, menunggu SDM). */
    public static function tugasUntuk(User $user, EvaluasiKontrak $e): bool
    {
        return match ($e->status) {
            EvaluasiKontrak::STATUS_MENUNGGU_ATASAN => self::adalahAtasan($user, $e),
            EvaluasiKontrak::STATUS_MENUNGGU_SDM => ! $user->isAdmin() && self::bisaPutuskan($user, $e),
            default => false,
        };
    }

    public static function jumlahTugas(User $user): int
    {
        if (! self::bolehMembuka($user)) {
            return 0;
        }

        return EvaluasiKontrak::with('dataPegawai.kepegawaian')->where('status', '!=', EvaluasiKontrak::STATUS_SELESAI)->get()
            ->filter(fn (EvaluasiKontrak $e) => self::tugasUntuk($user, $e))->count();
    }

    /**
     * Buat evaluasi untuk pegawai aktif yang kontraknya habis dalam HARI_SEBELUM hari (belum punya evaluasi
     * untuk tanggal itu), lalu beri tugas ke atasannya. Aman dijalankan berulang.
     *
     * @return int jumlah evaluasi baru
     */
    public static function buatJatuhTempo(?int $hanyaPegawaiId = null): int
    {
        $baru = 0;
        Kepegawaian::with('dataPegawai')
            ->when($hanyaPegawaiId, fn ($q) => $q->where('data_pegawai_id', $hanyaPegawaiId))
            ->where('status_rekrutmen', 'Aktif')
            ->where(fn ($q) => $q->whereNull('status_kerja')->orWhere('status_kerja', '!=', 'Tetap'))
            ->whereNotNull('tanggal_habis_kontrak')
            ->whereDate('tanggal_habis_kontrak', '>=', now()->toDateString())
            ->whereDate('tanggal_habis_kontrak', '<=', now()->addDays(EvaluasiKontrak::HARI_SEBELUM)->toDateString())
            ->get()
            ->each(function (Kepegawaian $k) use (&$baru) {
                if (! $k->dataPegawai) {
                    return;
                }
                $e = EvaluasiKontrak::firstOrCreate(
                    ['data_pegawai_id' => $k->data_pegawai_id, 'tanggal_habis_kontrak' => $k->tanggal_habis_kontrak->toDateString()],
                    ['unit_kerja' => $k->unit_kerja, 'status_kerja' => $k->status_kerja, 'status' => EvaluasiKontrak::STATUS_MENUNGGU_ATASAN],
                );
                if ($e->wasRecentlyCreated) {
                    $baru++;
                    Notifikasi::evaluasiKontrakBaru($e);
                }
            });

        return $baru;
    }

    /**
     * Hapus evaluasi yang belum diisi atasan bila kontraknya sudah tidak berlaku lagi (tanggal habis diubah,
     * pegawai jadi Tetap/nonaktif). Yang sudah diisi/diputuskan tetap disimpan sebagai riwayat.
     */
    public static function bersihkanUsang(int $pegawaiId): int
    {
        $k = Kepegawaian::where('data_pegawai_id', $pegawaiId)->first();
        $berlaku = $k && $k->status_rekrutmen === 'Aktif' && $k->status_kerja !== 'Tetap' ? $k->tanggal_habis_kontrak?->toDateString() : null;

        return EvaluasiKontrak::where('data_pegawai_id', $pegawaiId)
            ->where('status', EvaluasiKontrak::STATUS_MENUNGGU_ATASAN)
            ->when($berlaku, fn ($q) => $q->whereDate('tanggal_habis_kontrak', '!=', $berlaku))
            ->delete();
    }

    /**
     * Terapkan keputusan ke data kepegawaian — hanya bila kontrak yang dievaluasi masih kontrak yang berlaku
     * (mencegah perpanjangan ganda bila data sudah diubah manual).
     *
     * @return string ringkasan perubahan untuk pesan sukses
     */
    public static function terapkan(EvaluasiKontrak $e): string
    {
        $k = $e->dataPegawai?->kepegawaian;
        if (! $k || ! $k->tanggal_habis_kontrak || ! $k->tanggal_habis_kontrak->isSameDay($e->tanggal_habis_kontrak)) {
            return 'Data kontrak pegawai sudah berbeda dari yang dievaluasi, jadi tidak diubah otomatis.';
        }

        if ($e->keputusan === EvaluasiKontrak::PERPANJANG) {
            // Kontrak baru mulai sehari setelah kontrak lama habis.
            $habisBaru = Kalender::habisKontrak($e->tanggal_habis_kontrak->copy()->addDay(), $e->keputusan_lama_bulan);
            $k->update(['tanggal_habis_kontrak' => $habisBaru->toDateString()]);

            return 'Kontrak diperpanjang sampai ' . $habisBaru->translatedFormat('d M Y') . '.';
        }
        if ($e->keputusan === EvaluasiKontrak::TETAP) {
            $k->update(['status_kerja' => 'Tetap', 'tanggal_habis_kontrak' => null]);

            return 'Status kerja diubah menjadi Tetap (tanpa tanggal habis kontrak).';
        }

        return 'Kontrak tidak diperpanjang dan berakhir ' . $e->tanggal_habis_kontrak->translatedFormat('d M Y') . '.';
    }
}
