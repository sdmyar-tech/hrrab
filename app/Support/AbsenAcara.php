<?php

namespace App\Support;

use App\Models\Pengumuman;
use App\Models\PengumumanPeserta;
use App\Models\User;
use App\Notifications\Pemberitahuan;
use App\Support\Absensi\AksesAbsensi;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Absen acara lewat pengumuman.
 * Alur: panitia menampilkan QR (token berganti tiap 30 detik) → pegawai scan → konfirmasi
 * → hadir bila token sah dan jendela absen terbuka. Tidak ada pengecekan lokasi.
 */
class AbsenAcara
{
    /** Lama satu token QR (detik). */
    public const LANGKAH_QR = 30;

    /** Token masih diterima sampai sekian langkah ke belakang (≈ 3 menit: cukup untuk login dulu). */
    public const TOLERANSI_LANGKAH = 6;

    /** Setelah scan sah, pegawai punya waktu sekian menit untuk menekan konfirmasi hadir. */
    public const MENIT_SCAN_BERLAKU = 5;

    /** Hanya SDM (kepala SDM), Admin, dan GM yang boleh menetapkan & memantau absen acara. */
    public static function bolehKelola(User $user): bool
    {
        return $user->isAdmin() || $user->isKepalaSdm() || $user->isGm();
    }

    public static function bolehPantau(User $user, Pengumuman $pengumuman): bool
    {
        return $pengumuman->absen_aktif && self::bolehKelola($user);
    }

    /**
     * Akun pegawai aktif yang bisa diundang absen, beserta unit & jabatan aktifnya.
     * GM dibatasi ke divisi yang dipegangnya (bila diisi), sama seperti kirim notifikasi.
     *
     * @return Collection<int, array{id: int, nama: string, pegawai_id: int, unit: list<string>, jabatan: list<string>}>
     */
    public static function kandidat(User $pembuat): Collection
    {
        $divisi = $pembuat->isGm() && ! empty($pembuat->divisi) ? array_values($pembuat->divisi) : null;

        return User::query()
            ->whereIn('role', User::INTERNAL_ROLES)
            ->whereNotNull('data_pegawai_id')
            ->whereHas('dataPegawai', fn ($q) => AksesAbsensi::aktif($q))
            ->with(['dataPegawai:id,nama_lengkap', 'dataPegawai.kepegawaian:id,data_pegawai_id,unit_kerja,jabatan', 'dataPegawai.penugasanAktif'])
            ->get()
            ->map(function (User $u) {
                $tugas = $u->dataPegawai->penugasanAktif;
                $kp = $u->dataPegawai->kepegawaian;

                return [
                    'id' => $u->id,
                    'nama' => $u->dataPegawai->nama_lengkap ?: $u->name,
                    'pegawai_id' => $u->data_pegawai_id,
                    'unit' => $tugas->pluck('unit_kerja')->filter()->unique()->values()->all() ?: array_values(array_filter([$kp?->unit_kerja])),
                    'jabatan' => $tugas->pluck('jabatan')->filter()->unique()->values()->all() ?: array_values(array_filter([$kp?->jabatan])),
                ];
            })
            ->when($divisi, fn ($c) => $c->filter(fn ($k) => array_intersect($k['unit'], $divisi)))
            ->sortBy('nama', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
    }

    /**
     * Pilihan untuk form: unit & jabatan yang benar-benar dimiliki kandidat (dengan jumlah orang).
     *
     * @return array{kandidat: Collection, unit: array<string, int>, jabatan: array<string, int>}
     */
    public static function pilihan(User $pembuat): array
    {
        $kandidat = self::kandidat($pembuat);
        $hitung = fn (string $kolom) => $kandidat->flatMap(fn ($k) => $k[$kolom])->countBy()->sortKeys(SORT_NATURAL | SORT_FLAG_CASE)->all();

        return ['kandidat' => $kandidat, 'unit' => $hitung('unit'), 'jabatan' => $hitung('jabatan')];
    }

    /**
     * Kriteria → daftar peserta (gabungan: semua ATAU unit ATAU jabatan ATAU dipilih manual).
     *
     * @param  array{semua?: bool, unit?: list<string>, jabatan?: list<string>, user?: list<int>}  $kriteria
     * @return Collection<int, array{id: int, nama: string, pegawai_id: int, unit: list<string>, jabatan: list<string>}>
     */
    public static function terapkanKriteria(array $kriteria, Collection $kandidat): Collection
    {
        if (! empty($kriteria['semua'])) {
            return $kandidat->values();
        }

        $unit = $kriteria['unit'] ?? [];
        $jabatan = $kriteria['jabatan'] ?? [];
        $user = array_map('intval', $kriteria['user'] ?? []);

        return $kandidat->filter(fn ($k) => array_intersect($k['unit'], $unit)
            || array_intersect($k['jabatan'], $jabatan)
            || in_array($k['id'], $user, true))->values();
    }

    /**
     * Samakan daftar peserta dengan kriteria. Peserta yang sudah absen tidak pernah dihapus.
     *
     * @return Collection<int, User> peserta yang baru ditambahkan (untuk dikirimi notifikasi)
     */
    public static function sinkronPeserta(Pengumuman $pengumuman, User $pembuat): Collection
    {
        if (! $pengumuman->absen_aktif) {
            return collect();
        }

        $target = self::terapkanKriteria($pengumuman->absen_kriteria ?? [], self::kandidat($pembuat))->keyBy('id');
        $ada = $pengumuman->peserta()->get(['id', 'user_id', 'status'])->keyBy('user_id');

        // Hapus yang tidak lagi termasuk & belum absen.
        $hapus = $ada->reject(fn ($p, $uid) => $target->has($uid) || $p->status !== PengumumanPeserta::STATUS_BELUM)->pluck('id');
        if ($hapus->isNotEmpty()) {
            PengumumanPeserta::whereIn('id', $hapus)->delete();
        }

        $baru = $target->reject(fn ($k, $uid) => $ada->has($uid));
        if ($baru->isNotEmpty()) {
            $sekarang = now();
            PengumumanPeserta::insert($baru->map(fn ($k) => [
                'pengumuman_id' => $pengumuman->id, 'user_id' => $k['id'], 'data_pegawai_id' => $k['pegawai_id'],
                'status' => PengumumanPeserta::STATUS_BELUM, 'created_at' => $sekarang, 'updated_at' => $sekarang,
            ])->values()->all());
        }

        return User::whereIn('id', $baru->keys())->get();
    }

    public static function undang(Pengumuman $pengumuman, Collection $users): int
    {
        if ($users->isEmpty() || $pengumuman->absenFase() === Pengumuman::ABSEN_TUTUP) {
            return 0;
        }

        return Notifikasi::kirim($users, "Absen acara: {$pengumuman->judul}",
            "Anda diminta absen di lokasi acara. Waktu absen: {$pengumuman->absenJendelaLabel()}. Scan QR yang ditampilkan panitia di lokasi acara.",
            route('pengumuman.index') . '#pengumuman-' . $pengumuman->id, Pemberitahuan::JENIS_TUGAS, 'bi-qr-code-scan');
    }

    // ------------------------------------------------------------------ QR

    public static function pastikanKunci(Pengumuman $pengumuman): void
    {
        if (! $pengumuman->absen_kunci) {
            $pengumuman->forceFill(['absen_kunci' => Str::random(48)])->saveQuietly();
        }
    }

    public static function langkahSekarang(): int
    {
        return intdiv(now()->getTimestamp(), self::LANGKAH_QR);
    }

    public static function token(Pengumuman $pengumuman, ?int $langkah = null): string
    {
        $langkah ??= self::langkahSekarang();

        return substr(hash_hmac('sha256', $pengumuman->id . '|' . $langkah, $pengumuman->absen_kunci . config('app.key')), 0, 16);
    }

    public static function tokenSah(Pengumuman $pengumuman, ?string $token): bool
    {
        if (! $pengumuman->absen_kunci || ! is_string($token) || strlen($token) !== 16) {
            return false;
        }

        $sekarang = self::langkahSekarang();
        for ($i = 0; $i <= self::TOLERANSI_LANGKAH; $i++) {
            if (hash_equals(self::token($pengumuman, $sekarang - $i), $token)) {
                return true;
            }
        }

        return false;
    }

    public static function urlScan(Pengumuman $pengumuman): string
    {
        return route('acara.absen', ['pengumuman' => $pengumuman->id, 't' => self::token($pengumuman)]);
    }

    /** Detik tersisa sebelum QR berganti. */
    public static function sisaDetik(): int
    {
        return self::LANGKAH_QR - (now()->getTimestamp() % self::LANGKAH_QR);
    }

    // ------------------------------------------------------------------ Ringkasan

    /** @return array{total: int, sudah: int, belum: int, manual: int} */
    public static function ringkasan(Pengumuman $pengumuman): array
    {
        $hitung = $pengumuman->peserta()->toBase()->selectRaw('status, count(*) as n')->groupBy('status')->pluck('n', 'status');
        $total = (int) $hitung->sum();
        $belum = (int) ($hitung[PengumumanPeserta::STATUS_BELUM] ?? 0);

        return [
            'total' => $total,
            'sudah' => $total - $belum,
            'belum' => $belum,
            'manual' => (int) ($hitung[PengumumanPeserta::STATUS_MANUAL] ?? 0),
        ];
    }
}
