<?php

namespace App\Support;

use App\Models\DataPegawai;
use App\Models\LogAktivitas;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Throwable;

/**
 * Pencatat log aktivitas pengguna (kecuali superadmin/role Admin).
 * Satu baris log per tindakan; perubahan kolom dari model selama request digabung ke baris itu.
 */
class PencatatLog
{
    /** Kolom yang tidak pernah dicatat nilainya. */
    private const KOLOM_RAHASIA = ['password', 'remember_token', 'google_id', 'token', 'jadwal_token'];

    /** Kolom teknis yang tidak perlu ditampilkan sebagai perubahan. */
    private const KOLOM_ABAIKAN = ['created_at', 'updated_at', 'id'];

    /** route => [aksi, modul, kata kerja deskripsi] */
    private const RUTE = [
        'admin.pegawai.store' => ['tambah', 'Data Pegawai', 'Menambah pegawai'],
        'admin.pegawai.update' => ['ubah', 'Data Pegawai', 'Mengubah data pegawai'],
        'admin.pegawai.destroy' => ['hapus', 'Data Pegawai', 'Menghapus pegawai'],
        'admin.pegawai.export' => ['ekspor', 'Data Pegawai', 'Mengekspor data pegawai'],
        'admin.pegawai.export-detail' => ['ekspor', 'Data Pegawai', 'Mengekspor detail pegawai'],
        'admin.penugasan.store' => ['tambah', 'Mutasi & Rangkap', 'Mencatat mutasi/rangkap'],
        'admin.penugasan.update' => ['ubah', 'Mutasi & Rangkap', 'Mengoreksi penugasan'],
        'admin.penugasan.akhiri' => ['ubah', 'Mutasi & Rangkap', 'Mengakhiri rangkap tugas'],
        'admin.penugasan.destroy' => ['hapus', 'Mutasi & Rangkap', 'Menghapus mutasi/rangkap'],
        'admin.penugasan.export' => ['ekspor', 'Mutasi & Rangkap', 'Mengekspor mutasi & rangkap'],
        'admin.pengajuan-cuti.approve' => ['setujui', 'Cuti', 'Menyetujui cuti'],
        'admin.pengajuan-cuti.reject' => ['tolak', 'Cuti', 'Menolak cuti'],
        'admin.pengajuan-cuti.ubah-admin' => ['ubah', 'Cuti', 'Mengubah langsung cuti'],
        'admin.pengajuan-cuti.destroy' => ['hapus', 'Cuti', 'Menghapus pengajuan cuti'],
        'admin.pengajuan-cuti.export' => ['ekspor', 'Cuti', 'Mengekspor pengajuan cuti'],
        'admin.pengajuan-lembur.approve' => ['setujui', 'Lembur', 'Menyetujui lembur'],
        'admin.pengajuan-lembur.reject' => ['tolak', 'Lembur', 'Menolak lembur'],
        'admin.pengajuan-lembur.ubah-admin' => ['ubah', 'Lembur', 'Mengubah langsung lembur'],
        'admin.pengajuan-lembur.destroy' => ['hapus', 'Lembur', 'Menghapus pengajuan lembur'],
        'admin.pengajuan-lembur.export' => ['ekspor', 'Lembur', 'Mengekspor pengajuan lembur'],
        'pegawai.pengajuan-cuti.store' => ['tambah', 'Cuti', 'Mengajukan cuti'],
        'pegawai.pengajuan-cuti.export' => ['ekspor', 'Cuti', 'Mengekspor riwayat cuti sendiri'],
        'pegawai.pengajuan-lembur.store' => ['tambah', 'Lembur', 'Mengajukan lembur'],
        'pegawai.pengajuan-lembur.export' => ['ekspor', 'Lembur', 'Mengekspor riwayat lembur sendiri'],
        'pegawai.pengkinian-data.store' => ['ubah', 'Data Pegawai', 'Memperbarui data diri (pengkinian)'],
        'admin.pengajuan-formasi.store' => ['tambah', 'Formasi', 'Mengajukan formasi'],
        'admin.pengajuan-formasi.approve' => ['setujui', 'Formasi', 'Menyetujui formasi'],
        'admin.pengajuan-formasi.reject' => ['tolak', 'Formasi', 'Menolak formasi'],
        'admin.pengajuan-formasi.batalkan' => ['hapus', 'Formasi', 'Membatalkan formasi'],
        'admin.pengajuan-formasi.destroy' => ['hapus', 'Formasi', 'Menghapus formasi'],
        'admin.pengajuan-formasi.export' => ['ekspor', 'Formasi', 'Mengekspor formasi'],
        'admin.lowongan.store' => ['tambah', 'Lowongan', 'Membuat lowongan'],
        'admin.lowongan.update' => ['ubah', 'Lowongan', 'Mengubah lowongan'],
        'admin.lowongan.publish' => ['ubah', 'Lowongan', 'Memublikasikan lowongan'],
        'admin.lowongan.close' => ['ubah', 'Lowongan', 'Menutup lowongan'],
        'admin.lowongan.reopen' => ['ubah', 'Lowongan', 'Membuka kembali lowongan'],
        'admin.lowongan.export' => ['ekspor', 'Lowongan', 'Mengekspor lowongan'],
        'admin.lamaran.internal.store' => ['tambah', 'Rekrutmen', 'Menambah kandidat jalur langsung'],
        'admin.lamaran.verifikasi.terima' => ['setujui', 'Rekrutmen', 'Memverifikasi lamaran'],
        'admin.lamaran.verifikasi.tolak' => ['tolak', 'Rekrutmen', 'Menolak lamaran saat verifikasi'],
        'admin.lamaran.interview.store' => ['tambah', 'Rekrutmen', 'Menjadwalkan interview'],
        'admin.lamaran.interview.update' => ['ubah', 'Rekrutmen', 'Mengubah jadwal interview'],
        'admin.lamaran.interview.hasil' => ['ubah', 'Rekrutmen', 'Mengisi hasil interview'],
        'admin.lamaran.interview.cancel' => ['hapus', 'Rekrutmen', 'Membatalkan interview'],
        'admin.lamaran.interview.selesai' => ['setujui', 'Rekrutmen', 'Mengonfirmasi interview selesai'],
        'admin.lamaran.screening.store' => ['tambah', 'Rekrutmen', 'Memulai onboarding'],
        'admin.lamaran.screening.hasil' => ['ubah', 'Rekrutmen', 'Mengonfirmasi hasil onboarding'],
        'admin.lamaran.kontrak.store' => ['tambah', 'Rekrutmen', 'Menjadwalkan tanda tangan kontrak'],
        'admin.lamaran.kontrak.update' => ['ubah', 'Rekrutmen', 'Mengubah jadwal kontrak'],
        'admin.lamaran.rekrut' => ['setujui', 'Rekrutmen', 'Merekrut kandidat'],
        'admin.lamaran.tolak' => ['tolak', 'Rekrutmen', 'Menolak kandidat'],
        'admin.lamaran.ubah-admin' => ['ubah', 'Rekrutmen', 'Mengubah langsung tahap lamaran'],
        'admin.lamaran.export' => ['ekspor', 'Rekrutmen', 'Mengekspor lamaran'],
        'admin.lamaran.export-jadwal' => ['ekspor', 'Rekrutmen', 'Mengekspor jadwal rekrutmen'],
        'admin.absensi.import.simpan' => ['tambah', 'Absensi', 'Mengimport data absensi mesin'],
        'admin.absensi.koreksi' => ['ubah', 'Absensi', 'Mengoreksi absensi'],
        'admin.absensi.rekap.export' => ['ekspor', 'Absensi', 'Mengekspor rekap absensi'],
        'admin.absensi.pengaturan.pegawai' => ['ubah', 'Absensi', 'Mengubah pengaturan absensi pegawai'],
        'admin.absensi.pengaturan.ikut' => ['ubah', 'Absensi', 'Mengubah status ikut absensi pegawai'],
        'admin.absensi.pengaturan.massal' => ['ubah', 'Absensi', 'Mengatur absensi pegawai secara massal'],
        'admin.absensi.pengaturan.petakan' => ['ubah', 'Absensi', 'Memetakan ID mesin ke pegawai'],
        'admin.absensi.pengaturan.aktifkan-daftar' => ['ubah', 'Absensi', 'Mengaktifkan absensi dari daftar'],
        'admin.absensi.izin.store' => ['tambah', 'Absensi', 'Memberi izin telat/pulang cepat'],
        'admin.absensi.izin.destroy' => ['hapus', 'Absensi', 'Menghapus izin absensi'],
        'admin.users.store' => ['tambah', 'Akun', 'Membuat akun'],
        'admin.users.update' => ['ubah', 'Akun', 'Mengubah akun'],
        'admin.users.destroy' => ['hapus', 'Akun', 'Menghapus akun'],
        'admin.users.export' => ['ekspor', 'Akun', 'Mengekspor akun'],
        'admin.jabatan.store' => ['tambah', 'Master Jabatan', 'Menambah jabatan'],
        'admin.jabatan.update' => ['ubah', 'Master Jabatan', 'Mengubah nama jabatan'],
        'admin.jabatan.destroy' => ['hapus', 'Master Jabatan', 'Menghapus jabatan'],
        'admin.jabatan.export' => ['ekspor', 'Master Jabatan', 'Mengekspor jabatan'],
        'pengumuman.store' => ['tambah', 'Pengumuman', 'Membuat pengumuman'],
        'pengumuman.update' => ['ubah', 'Pengumuman', 'Mengubah pengumuman'],
        'pengumuman.destroy' => ['hapus', 'Pengumuman', 'Menghapus pengumuman'],
        'pengumuman.export' => ['ekspor', 'Pengumuman', 'Mengekspor pengumuman'],
        'notifikasi.kirim.store' => ['kirim', 'Notifikasi', 'Mengirim notifikasi'],
        'notifikasi.export' => ['ekspor', 'Notifikasi', 'Mengekspor notifikasi sendiri'],
        'interview.saya.export' => ['ekspor', 'Rekrutmen', 'Mengekspor jadwal interview sendiri'],
        'pelamar.data-pelamar.update' => ['ubah', 'Pelamar', 'Memperbarui data pelamar'],
        'pelamar.lamaran.apply' => ['tambah', 'Pelamar', 'Melamar lowongan'],
        'pelamar.lamaran.tolak' => ['hapus', 'Pelamar', 'Membatalkan lamaran'],
        'pelamar.lamaran.export' => ['ekspor', 'Pelamar', 'Mengekspor lamaran sendiri'],
    ];

    /** Rute yang tidak dicatat (bukan tindakan bermakna). */
    private const DIABAIKAN = ['login.submit', 'pelamar.login.submit', 'logout', 'register.submit', 'notifikasi.baca-semua', 'pengumuman.baca-semua'];

    /** @var list<array{model: string, id: mixed, jenis: string, kolom: array<string, array{0: mixed, 1: mixed}>}> */
    private static array $perubahan = [];

    public static function mulaiRequest(): void
    {
        self::$perubahan = [];
    }

    public static function dicatat(?User $user): bool
    {
        return $user !== null && ! $user->isAdmin();
    }

    public static function routeDicatat(Request $request): bool
    {
        $nama = $request->route()?->getName();

        return $nama !== null && ! in_array($nama, self::DIABAIKAN, true)
            && ($request->method() !== 'GET' || isset(self::RUTE[$nama]));
    }

    /** Dipanggil observer model: kumpulkan perubahan kolom untuk digabung ke log request ini. */
    public static function rekamModel(Model $model, string $jenis): void
    {
        if (! self::dicatat(Auth::user())) {
            return;
        }

        $kolom = [];
        $sumber = $jenis === 'ubah' ? array_keys($model->getChanges()) : array_keys($model->getAttributes());
        foreach ($sumber as $k) {
            if (in_array($k, self::KOLOM_ABAIKAN, true)) {
                continue;
            }
            $rahasia = in_array($k, self::KOLOM_RAHASIA, true);
            $lama = $jenis === 'tambah' ? null : $model->getOriginal($k);
            $baru = $jenis === 'hapus' ? null : $model->getAttribute($k);
            if ($jenis === 'ubah' && self::sama($lama, $baru)) {
                continue;
            }
            $kolom[$k] = $rahasia ? ['(disembunyikan)', '(diubah)'] : [self::ringkas($lama), self::ringkas($baru)];
        }

        if ($kolom || $jenis !== 'ubah') {
            self::$perubahan[] = ['model' => self::labelModel($model), 'id' => $model->getKey(), 'jenis' => $jenis, 'kolom' => $kolom];
        }
    }

    public static function catatRequest(Request $request, int $kodeStatus): void
    {
        $user = Auth::user();
        $nama = $request->route()?->getName();

        if (! self::dicatat($user) || $nama === null) {
            return;
        }

        [$aksi, $modul, $kerja] = self::RUTE[$nama] ?? self::tebak($nama);
        $gagal = $kodeStatus >= 400 || session()->has('errors') || session()->has('error');
        $subjek = self::labelSubjek($request);

        self::tulis($user, $aksi, $modul, trim($kerja . ($subjek ? ": {$subjek}" : '')), $gagal ? 'gagal' : 'berhasil', [
            'route' => $nama,
            'perubahan' => self::$perubahan ?: null,
            'pesan' => $gagal ? (session('error') ?? collect(session('errors')?->all() ?? [])->first() ?? "HTTP {$kodeStatus}") : null,
        ]);

        self::$perubahan = [];
    }

    public static function tulis(?User $user, string $aksi, string $modul, string $deskripsi, string $status = 'berhasil', array $ekstra = []): void
    {
        if (! self::dicatat($user)) {
            return;
        }

        try {
            $perubahan = $ekstra['perubahan'] ?? null;
            if (! empty($ekstra['pesan'])) {
                $perubahan = [...($perubahan ?? []), ['model' => 'Pesan sistem', 'id' => null, 'jenis' => 'info', 'kolom' => ['pesan' => [null, $ekstra['pesan']]]]];
            }

            LogAktivitas::create([
                'user_id' => $user->id,
                'nama_pengguna' => $user->name,
                'role' => $user->role,
                'aksi' => $aksi,
                'modul' => $modul,
                'deskripsi' => Str::limit($deskripsi, 495),
                'status' => $status,
                'perubahan' => $perubahan,
                'route' => $ekstra['route'] ?? null,
                'ip' => request()->ip(),
                'perangkat' => Str::limit((string) request()->userAgent(), 250),
            ]);
        } catch (Throwable $e) {
            // Pencatatan log tidak boleh menggagalkan tindakan pengguna.
            report($e);
        }
    }

    /** @return array{0: string, 1: string, 2: string} */
    private static function tebak(string $route): array
    {
        $bagian = explode('.', $route);
        $akhir = end($bagian);
        $aksi = match (true) {
            str_contains($akhir, 'store') => 'tambah',
            str_contains($akhir, 'update') => 'ubah',
            str_contains($akhir, 'destroy') => 'hapus',
            str_contains($akhir, 'export') => 'ekspor',
            default => 'lainnya',
        };
        $modul = Str::headline($bagian[count($bagian) - 2] ?? $bagian[0]);

        return [$aksi, $modul, Str::headline($akhir) . ' ' . $modul];
    }

    /** Nama subjek dari parameter route, mis. "Armen Syahroni" atau "Lowongan Staff GA". */
    private static function labelSubjek(Request $request): ?string
    {
        $label = [];
        foreach ($request->route()?->parameters() ?? [] as $kunci => $nilai) {
            if ($kunci === 'pegawai' && ! $nilai instanceof Model) {
                $nilai = DataPegawai::find($nilai);
            }
            if ($nilai instanceof Model) {
                $label[] = self::namaModel($nilai);
            }
        }

        if (! $label && $request->filled('nama')) {
            $label[] = (string) $request->input('nama');
        }
        if (! $label && $request->filled('nama_lengkap')) {
            $label[] = (string) $request->input('nama_lengkap');
        }

        return $label ? implode(' · ', array_unique(array_filter($label))) : null;
    }

    private static function namaModel(Model $m): string
    {
        $nama = match (true) {
            method_exists($m, 'namaKandidat') => $m->namaKandidat(),
            isset($m->dataPegawai) && $m->dataPegawai?->nama_lengkap => $m->dataPegawai->nama_lengkap . (isset($m->unit_kerja) ? " ({$m->unit_kerja})" : ''),
            default => $m->nama_lengkap ?? $m->name ?? $m->nama ?? $m->judul ?? $m->jabatan ?? null,
        };

        return $nama ?: self::labelModel($m) . ' #' . $m->getKey();
    }

    private static function labelModel(Model $m): string
    {
        return Str::headline(class_basename($m));
    }

    private static function ringkas(mixed $nilai): mixed
    {
        if ($nilai instanceof \DateTimeInterface) {
            return $nilai->format('Y-m-d H:i');
        }
        if (is_array($nilai)) {
            $nilai = json_encode($nilai, JSON_UNESCAPED_UNICODE);
        }

        return is_string($nilai) ? Str::limit($nilai, 200) : $nilai;
    }

    private static function sama(mixed $a, mixed $b): bool
    {
        return (string) self::ringkas($a) === (string) self::ringkas($b);
    }
}
