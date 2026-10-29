<?php

namespace App\Support;

use App\Models\AlurPegawai;
use App\Models\DataPegawai;
use App\Models\DataPelamar;
use App\Models\PengajuanCuti;
use App\Models\PengajuanIzinKeluar;
use App\Models\PengajuanLembur;
use App\Models\Penugasan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Pegawai keluar (resign/kontrak habis/PHK/...): status kepegawaian jadi Tidak Aktif, akun TIDAK dihapus tetapi
 * diubah menjadi akun pelamar (harus melamar lagi untuk kembali), data pelamar diisi dari biodata pegawai.
 * Keadaan sebelum keluar disimpan di kepegawaians.keluar_snapshot sehingga proses bisa dibatalkan bila keliru.
 */
class PegawaiKeluar
{
    public const JENIS = [
        'resign' => 'Resign / mengundurkan diri',
        'kontrak_habis' => 'Kontrak tidak diperpanjang',
        'phk' => 'Diberhentikan (PHK)',
        'pensiun' => 'Pensiun',
        'meninggal' => 'Meninggal dunia',
        'lainnya' => 'Lainnya',
    ];

    /** Penanda di catatan pengajuan yang ditutup otomatis (dipakai juga saat membatalkan). */
    private const CATATAN_TUTUP = 'Ditutup otomatis: pegawai keluar';

    private const PENGAJUAN = [PengajuanCuti::class, PengajuanLembur::class, PengajuanIzinKeluar::class];

    /**
     * Ringkasan dampak sebelum diproses (untuk dialog konfirmasi).
     *
     * @return array{akun: Collection, akunAdmin: Collection, penugasan: Collection, pengajuan: int, memimpin: list<string>, terjadwal: int, tanggalMinimal: ?string}
     */
    public static function dampak(DataPegawai $pegawai): array
    {
        $akun = User::where('data_pegawai_id', $pegawai->id)->orderBy('id')->get();
        $penugasan = $pegawai->penugasans()->aktif()->orderByDesc('is_utama')->get();

        return [
            'akun' => $akun->reject(fn (User $u) => $u->isAdmin())->values(),
            'akunAdmin' => $akun->filter(fn (User $u) => $u->isAdmin())->values(),
            'penugasan' => $penugasan,
            'pengajuan' => collect(self::PENGAJUAN)->sum(fn ($m) => $m::where('data_pegawai_id', $pegawai->id)->where('status', $m::STATUS_PENDING)->count()),
            'memimpin' => $penugasan->filter(fn (Penugasan $p) => $p->isKepala())->pluck('unit_kerja')->unique()->values()->all(),
            'terjadwal' => $pegawai->penugasans()->where('tanggal_mulai', '>', now()->toDateString())->count(),
            'tanggalMinimal' => $penugasan->max(fn (Penugasan $p) => $p->tanggal_mulai->toDateString()),
        ];
    }

    /**
     * Proses keluar. Akun Admin yang tertaut tidak diubah (agar sistem tidak kehilangan admin) — dilaporkan sebagai peringatan.
     *
     * @return list<string> ringkasan & peringatan untuk ditampilkan
     */
    public static function proses(DataPegawai $pegawai, string $tanggal, string $jenis, ?string $alasan, ?User $oleh): array
    {
        $kep = $pegawai->kepegawaian;
        $d = self::dampak($pegawai);
        $tanggal = Carbon::parse($tanggal)->toDateString();

        if (! $kep || $kep->status_rekrutmen !== 'Aktif') {
            throw ValidationException::withMessages(['tanggal_keluar' => 'Pegawai ini sudah tidak aktif.']);
        }
        if ($d['terjadwal']) {
            throw ValidationException::withMessages(['tanggal_keluar' => 'Pegawai ini masih punya mutasi/rangkap terjadwal. Batalkan dulu di Mutasi & Rangkap.']);
        }
        if ($d['tanggalMinimal'] && $tanggal < $d['tanggalMinimal']) {
            throw ValidationException::withMessages(['tanggal_keluar' => 'Tanggal keluar tidak boleh sebelum mulai penugasan sekarang (' . Carbon::parse($d['tanggalMinimal'])->format('d-m-Y') . ').']);
        }

        return DB::transaction(function () use ($pegawai, $kep, $d, $tanggal, $jenis, $alasan, $oleh) {
            $snapshot = ['akun' => [], 'penugasan' => [], 'pengajuan' => [], 'ikut_absensi' => (bool) $pegawai->ikut_absensi];
            $hasil = [];

            // 1. Akun → pelamar (tidak dihapus), data pelamar disiapkan dari biodata pegawai.
            foreach ($d['akun'] as $akun) {
                $snapshot['akun'][] = ['id' => $akun->id, 'role' => $akun->role, 'role_id' => $akun->role_id, 'data_pelamar_id' => $akun->data_pelamar_id];
                $pelamar = self::siapkanDataPelamar($pegawai, $akun);
                $akun->forceFill(['role' => User::ROLE_PELAMAR, 'role_id' => null, 'data_pegawai_id' => null, 'data_pelamar_id' => $pelamar?->id ?? $akun->data_pelamar_id])->save();
                $hasil[] = "Akun {$akun->email} kini akun pelamar (bisa login & melamar lagi)"
                    . ($pelamar ? ', data pelamar sudah terisi dari biodata pegawai.' : '; biodata belum lengkap, pelamar melengkapinya sendiri.');
            }
            foreach ($d['akunAdmin'] as $admin) {
                $hasil[] = "Akun Admin {$admin->email} tidak diubah — ubah role-nya manual di Kelola Akun bila perlu.";
            }

            // 2. Penugasan aktif diakhiri pada tanggal keluar.
            foreach ($d['penugasan'] as $p) {
                $snapshot['penugasan'][] = ['id' => $p->id, 'tanggal_selesai' => $p->tanggal_selesai?->toDateString()];
                $p->update(['tanggal_selesai' => $tanggal]);
            }

            // 3. Pengajuan yang masih menunggu ditutup agar tidak menggantung di daftar persetujuan.
            foreach (self::PENGAJUAN as $model) {
                foreach ($model::where('data_pegawai_id', $pegawai->id)->where('status', $model::STATUS_PENDING)->get() as $pj) {
                    $snapshot['pengajuan'][] = ['model' => $model, 'id' => $pj->id, 'current_level' => $pj->current_level, 'catatan_admin' => $pj->catatan_admin];
                    $pj->update(['status' => $model::STATUS_REJECTED, 'current_level' => null,
                        'catatan_admin' => self::CATATAN_TUTUP . ' per ' . Carbon::parse($tanggal)->format('d-m-Y') . '.']);
                }
            }
            if ($snapshot['pengajuan']) {
                $hasil[] = count($snapshot['pengajuan']) . ' pengajuan yang masih menunggu ditutup.';
            }

            // 4. Tidak diabsen lagi; status kepegawaian Tidak Aktif + catatan keluar.
            $pegawai->forceFill(['ikut_absensi' => false])->save();
            $kep->update([
                'status_rekrutmen' => 'Tidak Aktif', 'tanggal_keluar' => $tanggal, 'jenis_keluar' => $jenis, 'alasan_keluar' => $alasan,
                'keluar_diproses_oleh' => $oleh?->id, 'keluar_snapshot' => $snapshot,
            ]);

            // 5. Dampak ke orang lain: jadwal interview tahap 2, unit tanpa kepala, alur khusus yang menunjuk akunnya.
            PenugasanService::sinkronInterviewer();
            foreach ($d['memimpin'] as $unit) {
                $hasil[] = "Bagian {$unit} kini tanpa koordinator — tetapkan penggantinya di Master Bagian.";
            }
            $ids = collect($snapshot['akun'])->pluck('id');
            if ($ids->isNotEmpty() && ($n = AlurPegawai::whereIn('atasan_user_id', $ids)->orWhereIn('gm_user_id', $ids)->count())) {
                $hasil[] = "{$n} pegawai memakai alur persetujuan khusus yang menunjuk pegawai ini — perbarui alurnya.";
            }

            Notifikasi::kirim(User::whereIn('id', $ids)->get(), 'Status kepegawaian Anda berakhir',
                'Akun Anda kini akun pelamar. Anda tetap bisa login, melihat lowongan, dan melamar kembali.', route('lowongan.index'));

            return $hasil;
        });
    }

    /** Batalkan proses keluar (mis. salah input): kembalikan akun, penugasan, pengajuan, dan status absensi. */
    public static function batalkan(DataPegawai $pegawai): array
    {
        $kep = $pegawai->kepegawaian;
        $snap = $kep?->keluar_snapshot;
        if (! $kep || $kep->status_rekrutmen !== 'Tidak Aktif' || ! is_array($snap)) {
            throw ValidationException::withMessages(['keluar' => 'Tidak ada proses keluar yang bisa dibatalkan untuk pegawai ini.']);
        }

        return DB::transaction(function () use ($pegawai, $kep, $snap) {
            $hasil = [];
            $tanggal = $kep->tanggal_keluar?->toDateString();

            foreach ($snap['akun'] ?? [] as $a) {
                $akun = User::find($a['id']);
                // Hanya bila akun masih pelamar tanpa data pegawai lain (belum direkrut ulang / dipakai untuk hal lain).
                if ($akun && $akun->isPelamar() && ! $akun->data_pegawai_id) {
                    $akun->forceFill(['role' => $a['role'], 'role_id' => $a['role_id'], 'data_pegawai_id' => $pegawai->id, 'data_pelamar_id' => $a['data_pelamar_id']])->save();
                    $hasil[] = "Akun {$akun->email} dikembalikan menjadi " . $akun->roleLabel() . '.';
                } elseif ($akun) {
                    $hasil[] = "Akun {$akun->email} sudah berubah sejak keluar — tidak dikembalikan otomatis.";
                }
            }
            foreach ($snap['penugasan'] ?? [] as $p) {
                Penugasan::whereKey($p['id'])->whereDate('tanggal_selesai', $tanggal)->update(['tanggal_selesai' => $p['tanggal_selesai']]);
            }
            foreach ($snap['pengajuan'] ?? [] as $pj) {
                $pj['model']::whereKey($pj['id'])->where('status', $pj['model']::STATUS_REJECTED)->where('catatan_admin', 'like', self::CATATAN_TUTUP . '%')
                    ->update(['status' => $pj['model']::STATUS_PENDING, 'current_level' => $pj['current_level'], 'catatan_admin' => $pj['catatan_admin']]);
            }

            $pegawai->forceFill(['ikut_absensi' => (bool) ($snap['ikut_absensi'] ?? false)])->save();
            $kep->update(['status_rekrutmen' => 'Aktif']); // hook model mengosongkan tanggal & catatan keluar
            array_push($hasil, ...PenugasanService::sinkron($pegawai));

            return $hasil;
        });
    }

    /** Kolom data pelamar yang wajib terisi (NOT NULL di tabel); tanpa ini data pelamar baru tidak dibuat. */
    private const WAJIB_PELAMAR = ['nama_lengkap', 'no_hp', 'tempat_lahir', 'tanggal_lahir', 'jenis_kelamin', 'agama', 'nama_ibu_kandung', 'pendidikan_terakhir', 'alamat_ktp'];

    /**
     * Data pelamar untuk akun yang keluar: pakai yang sudah tertaut / ber-NIK / ber-email sama (diperbarui dari biodata pegawai),
     * atau dibuat baru bila biodata wajibnya lengkap. Null = pelamar melengkapi sendiri.
     */
    private static function siapkanDataPelamar(DataPegawai $p, User $akun): ?DataPelamar
    {
        $biodata = [
            'email' => $akun->email, 'nama_lengkap' => $p->nama_lengkap, 'nik' => $p->nik, 'npwp' => $p->npwp, 'no_kk' => $p->no_kk, 'no_hp' => $p->no_hp,
            'tempat_lahir' => $p->tempat_lahir, 'tanggal_lahir' => $p->tanggal_lahir, 'jenis_kelamin' => $p->jenis_kelamin, 'agama' => $p->agama,
            'golongan_darah' => $p->golongan_darah, 'status_pernikahan' => $p->status_pernikahan, 'jumlah_tanggungan' => $p->jumlah_tanggungan,
            'nama_ibu_kandung' => $p->nama_ibu_kandung, 'pendidikan_terakhir' => $p->pendidikan_terakhir,
            'alamat_ktp' => $p->alamat_ktp, 'alamat_domisili' => $p->alamat_domisili,
        ];

        $pelamar = ($akun->data_pelamar_id ? DataPelamar::find($akun->data_pelamar_id) : null)
            ?? ($p->nik ? DataPelamar::where('nik', $p->nik)->first() : null)
            ?? DataPelamar::where('email', $akun->email)->first();
        $isi = array_filter($biodata, fn ($v) => $v !== null && $v !== '');

        if ($pelamar) {
            // NIK milik data pelamar lain tidak ditimpa (kolom unik).
            if (isset($isi['nik']) && DataPelamar::where('nik', $isi['nik'])->whereKeyNot($pelamar->id)->exists()) {
                unset($isi['nik']);
            }
            unset($isi['email']);

            return tap($pelamar)->update($isi);
        }

        return collect(self::WAJIB_PELAMAR)->every(fn ($k) => isset($isi[$k])) ? DataPelamar::create($isi) : null;
    }
}
