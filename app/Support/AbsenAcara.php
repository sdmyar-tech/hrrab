<?php

namespace App\Support;

use App\Models\Absensi;
use App\Models\PengajuanCuti;
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
     * Akun GM & Pembina (Owner) yang tidak tertaut Data Pegawai juga bisa diundang (jabatan = nama role-nya).
     * GM dibatasi ke divisi yang dipegangnya (bila diisi), sama seperti kirim notifikasi.
     *
     * @return Collection<int, array{id: int, nama: string, pegawai_id: ?int, unit: list<string>, jabatan: list<string>}>
     */
    public static function kandidat(User $pembuat): Collection
    {
        $divisi = $pembuat->isGm() && ! empty($pembuat->divisi) ? array_values($pembuat->divisi) : null;

        $pimpinan = User::query()
            ->whereIn('role', [User::ROLE_GM, User::ROLE_PEMBINA])
            ->whereNull('data_pegawai_id')
            ->get()
            ->map(fn (User $u) => [
                'id' => $u->id,
                'nama' => $u->name,
                'pegawai_id' => null,
                // Tanpa unit: dipilih lewat "semua", per jabatan (General Manager / Pembina), atau nama.
                'unit' => [],
                'jabatan' => [$u->roleLabel()],
            ]);

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
            ->concat($pimpinan)
            ->when($divisi, fn ($c) => $c->filter(fn ($k) => array_intersect($k['unit'], $divisi) || $k['pegawai_id'] === null))
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
        $ada = $pengumuman->peserta()->get(['id', 'user_id', 'status', 'sumber'])->keyBy('user_id');

        // Yang dihapus hanya yang belum absen atau berhalangan otomatis (bukan yang hadir / ditetapkan panitia).
        $hapus = $ada->reject(fn ($p, $uid) => $target->has($uid)
            || ! ($p->status === PengumumanPeserta::STATUS_BELUM || $p->otomatis()))->pluck('id');
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
            route('pengumuman.index') . '#pengumuman-' . $pengumuman->id, Pemberitahuan::JENIS_TUGAS, 'bi-qr-code-scan',
            null, TugasNotifikasi::penanda(TugasNotifikasi::ABSEN_ACARA, $pengumuman->id));
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

    /**
     * @return array{total: int, sudah: int, belum: int, manual: int, berhalangan: int, izin: int, sakit: int, dinas: int, cuti: int}
     */
    public static function ringkasan(Pengumuman $pengumuman): array
    {
        $hitung = $pengumuman->peserta()->toBase()->selectRaw('status, count(*) as n')->groupBy('status')->pluck('n', 'status');
        $n = fn (string $status) => (int) ($hitung[$status] ?? 0);
        $berhalangan = collect(PengumumanPeserta::BERHALANGAN)->sum($n);

        return [
            'total' => (int) $hitung->sum(),
            'sudah' => $n(PengumumanPeserta::STATUS_HADIR) + $n(PengumumanPeserta::STATUS_MANUAL),
            'belum' => $n(PengumumanPeserta::STATUS_BELUM),
            'manual' => $n(PengumumanPeserta::STATUS_MANUAL),
            'berhalangan' => $berhalangan,
            'izin' => $n(PengumumanPeserta::STATUS_IZIN),
            'sakit' => $n(PengumumanPeserta::STATUS_SAKIT),
            'dinas' => $n(PengumumanPeserta::STATUS_DINAS),
            'cuti' => $n(PengumumanPeserta::STATUS_CUTI),
        ];
    }

    // ------------------------------------------------------------------ Berhalangan otomatis

    /**
     * Samakan status berhalangan dengan data lain pada tanggal acara:
     *  - pengajuan cuti yang DISETUJUI → "Cuti";
     *  - koreksi absensi harian Izin / Sakit / Dinas Luar → status yang sama.
     * Hanya menyentuh peserta yang belum absen atau yang statusnya memang berasal dari sinkron ini;
     * yang sudah hadir atau ditetapkan panitia tidak pernah ditimpa. Mengembalikan jumlah baris yang berubah.
     */
    public static function sinkronBerhalangan(Pengumuman $pengumuman): int
    {
        if (! $pengumuman->absen_aktif || ! $pengumuman->absen_buka || ! $pengumuman->absen_tutup) {
            return 0;
        }

        $dari = $pengumuman->absen_buka->toDateString();
        $sampai = $pengumuman->absen_tutup->toDateString();
        $peserta = $pengumuman->peserta()
            ->where(fn ($q) => $q->where('status', PengumumanPeserta::STATUS_BELUM)
                ->orWhere(fn ($o) => $o->whereIn('sumber', [PengumumanPeserta::SUMBER_CUTI, PengumumanPeserta::SUMBER_ABSENSI])
                    ->whereIn('status', PengumumanPeserta::BERHALANGAN)))
            ->whereNotNull('data_pegawai_id')->get();
        if ($peserta->isEmpty()) {
            return 0;
        }
        $ids = $peserta->pluck('data_pegawai_id')->unique()->all();

        $cuti = PengajuanCuti::where('status', PengajuanCuti::STATUS_APPROVED)->whereIn('data_pegawai_id', $ids)
            ->whereDate('tanggal_mulai', '<=', $sampai)->whereDate('tanggal_selesai', '>=', $dari)
            ->orderBy('id')->get()->keyBy('data_pegawai_id');
        $petaAbsensi = [
            Absensi::MANUAL_IZIN => PengumumanPeserta::STATUS_IZIN,
            Absensi::MANUAL_SAKIT => PengumumanPeserta::STATUS_SAKIT,
            Absensi::MANUAL_DINAS_LUAR => PengumumanPeserta::STATUS_DINAS,
        ];
        $absensi = Absensi::whereIn('data_pegawai_id', $ids)->whereIn('status_manual', array_keys($petaAbsensi))
            ->whereBetween('tanggal', [$dari, $sampai])->orderBy('tanggal')->get()->keyBy('data_pegawai_id');

        $berubah = 0;
        foreach ($peserta as $p) {
            $c = $cuti->get($p->data_pegawai_id);
            $a = $absensi->get($p->data_pegawai_id);
            $baru = match (true) {
                (bool) $c => ['status' => PengumumanPeserta::STATUS_CUTI, 'sumber' => PengumumanPeserta::SUMBER_CUTI, 'sumber_id' => $c->id,
                    'catatan' => $c->jenisLabel() . ' ' . $c->tanggal_mulai->translatedFormat('d M') . ' – ' . $c->tanggal_selesai->translatedFormat('d M Y')],
                (bool) $a => ['status' => $petaAbsensi[$a->status_manual], 'sumber' => PengumumanPeserta::SUMBER_ABSENSI, 'sumber_id' => $a->id,
                    'catatan' => trim(Absensi::STATUS_MANUAL[$a->status_manual] . ' di absensi harian ' . $a->tanggal->translatedFormat('d M') . ($a->alasan_koreksi ? " — {$a->alasan_koreksi}" : ''))],
                // Sumbernya hilang (cuti dibatalkan / koreksi dihapus) → kembali belum absen.
                default => ['status' => PengumumanPeserta::STATUS_BELUM, 'sumber' => null, 'sumber_id' => null, 'catatan' => null],
            };
            $p->fill($baru + ['ditandai_oleh' => null]);
            if ($p->isDirty()) {
                $p->save();
                $berubah++;
            }
        }

        return $berubah;
    }
}
