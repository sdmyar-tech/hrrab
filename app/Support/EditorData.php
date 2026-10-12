<?php

namespace App\Support;

use App\Models;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Editor Data (khusus Admin): ubah/hapus baris data apa pun tanpa aturan alur.
 * Daftar entitas di bawah = tabel yang boleh diotak-atik. Log aktivitas sengaja TIDAK termasuk (jejak audit).
 */
class EditorData
{
    /** Kolom yang tidak pernah ditampilkan/diubah (rahasia & kunci). */
    private const KOLOM_RAHASIA = ['password', 'remember_token', 'google_id', 'absen_kunci', 'token_jadwal'];

    /** Kolom yang hanya ditampilkan. */
    private const KOLOM_BACA = ['id', 'created_at', 'updated_at'];

    /** Kolom teks yang diberi daftar saran (nilai yang sudah ada di tabel). */
    private const KOLOM_SARAN = ['status', 'tahap', 'hasil_status', 'hasil_sdm', 'hasil_kepala', 'keputusan', 'jalur', 'role', 'jenis',
        'jenis_cuti', 'kategori_cuti_besar', 'current_level', 'kategori', 'jadwal', 'tipe_kerja', 'status_manual', 'unit_kerja',
        'unit_kerja_tujuan', 'jabatan', 'jabatan_tujuan', 'lokasi_kerja', 'lokasi_kerja_tujuan', 'jabatan_tambahan', 'hari', 'jenjang', 'agama', 'pendidikan_terakhir'];

    /**
     * Konfigurasi entitas.
     * judul: label baris · kolom: kolom ringkas di daftar · cari: kolom teks yang dicari · relasi: [relasi => kolom] yang ikut dicari.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function entitas(): array
    {
        $kandidat = fn ($l) => $l?->dataPelamar?->nama_lengkap ?? $l?->dataPegawai?->nama_lengkap ?? 'Kandidat';
        $pegawai = fn ($m) => $m->dataPegawai?->nama_lengkap ?? 'Pegawai #' . $m->data_pegawai_id;
        $lamaranRelasi = ['lamaran.dataPelamar' => 'nama_lengkap', 'lamaran.dataPegawai' => 'nama_lengkap'];
        $lamaranWith = ['lamaran.dataPelamar:id,nama_lengkap', 'lamaran.dataPegawai:id,nama_lengkap'];

        return [
            // ---------------- Rekrutmen
            'lamaran' => ['grup' => 'Rekrutmen', 'label' => 'Lamaran', 'ikon' => 'bi-inbox', 'model' => Models\Lamaran::class,
                'with' => ['dataPelamar:id,nama_lengkap', 'dataPegawai:id,nama_lengkap', 'lowongan:id,judul'],
                'judul' => fn ($m) => $kandidat($m) . ' — ' . ($m->lowongan?->judul ?? $m->jabatan_tujuan ?? '-'),
                'kolom' => ['status', 'tahap', 'jalur', 'created_at'], 'cari' => ['jabatan_tujuan', 'unit_kerja_tujuan', 'status', 'tahap'],
                'relasi' => ['dataPelamar' => 'nama_lengkap', 'dataPegawai' => 'nama_lengkap', 'lowongan' => 'judul'],
                'tautan' => fn ($m) => self::rute('admin.lamaran.show', $m)],
            'interview' => ['grup' => 'Rekrutmen', 'label' => 'Jadwal Interview', 'ikon' => 'bi-chat-square-text', 'model' => Models\InterviewJadwal::class,
                'with' => [...$lamaranWith, 'interviewer:id,name'],
                'judul' => fn ($m) => $kandidat($m->lamaran) . ' — ' . $m->tahapLabel() . ' (' . ($m->interviewer?->name ?? '-') . ')',
                'kolom' => ['tanggal', 'waktu_mulai', 'status', 'hasil_status'], 'cari' => ['status', 'hasil_status', 'lokasi'], 'relasi' => $lamaranRelasi,
                'tautan' => fn ($m) => self::rute('admin.lamaran.show', $m->lamaran_id)],
            'onboarding' => ['grup' => 'Rekrutmen', 'label' => 'Onboarding', 'ikon' => 'bi-clipboard-check', 'model' => Models\ScreeningOnboarding::class,
                'with' => $lamaranWith, 'judul' => fn ($m) => $kandidat($m->lamaran) . ' — onboarding ' . $m->durasi_hari . ' hari',
                'kolom' => ['tanggal_mulai', 'tanggal_selesai', 'status', 'hasil_status'], 'cari' => ['status', 'hasil_status', 'lokasi'], 'relasi' => $lamaranRelasi,
                'tautan' => fn ($m) => self::rute('admin.lamaran.show', $m->lamaran_id)],
            'kontrak' => ['grup' => 'Rekrutmen', 'label' => 'Jadwal Kontrak', 'ikon' => 'bi-pen', 'model' => Models\KontrakJadwal::class,
                'with' => $lamaranWith, 'judul' => fn ($m) => $kandidat($m->lamaran) . ' — tanda tangan kontrak',
                'kolom' => ['tanggal', 'waktu', 'status'], 'cari' => ['status', 'lokasi'], 'relasi' => $lamaranRelasi,
                'tautan' => fn ($m) => self::rute('admin.lamaran.show', $m->lamaran_id)],
            'lowongan' => ['grup' => 'Rekrutmen', 'label' => 'Lowongan', 'ikon' => 'bi-briefcase', 'model' => Models\Lowongan::class,
                'judul' => fn ($m) => $m->judul . ' (' . $m->unit_kerja . ')', 'kolom' => ['status', 'jumlah_dibutuhkan', 'jumlah_terisi', 'tanggal_tutup'],
                'cari' => ['judul', 'unit_kerja', 'jabatan', 'status'], 'tautan' => fn ($m) => self::rute('admin.lowongan.edit', $m)],
            'formasi' => ['grup' => 'Rekrutmen', 'label' => 'Pengajuan Formasi', 'ikon' => 'bi-person-plus', 'model' => Models\PengajuanFormasi::class,
                'judul' => fn ($m) => $m->jabatan . ' — ' . $m->unit_kerja, 'kolom' => ['status', 'jumlah_dibutuhkan', 'target_waktu'],
                'cari' => ['jabatan', 'unit_kerja', 'status']],
            'pelamar' => ['grup' => 'Rekrutmen', 'label' => 'Biodata Pelamar', 'ikon' => 'bi-person-vcard', 'model' => Models\DataPelamar::class,
                'judul' => fn ($m) => $m->nama_lengkap ?: $m->email, 'kolom' => ['email', 'no_hp', 'nik'], 'cari' => ['nama_lengkap', 'email', 'nik', 'no_hp']],
            'riwayat_pendidikan' => ['grup' => 'Rekrutmen', 'label' => 'Riwayat Pendidikan Pelamar', 'ikon' => 'bi-mortarboard', 'model' => Models\RiwayatPendidikanPelamar::class,
                'with' => ['dataPelamar:id,nama_lengkap'], 'judul' => fn ($m) => ($m->dataPelamar?->nama_lengkap ?? '-') . ' — ' . $m->jenjang . ' ' . $m->nama_institusi,
                'kolom' => ['jenjang', 'tahun_lulus'], 'cari' => ['nama_institusi', 'jurusan', 'jenjang'], 'relasi' => ['dataPelamar' => 'nama_lengkap']],
            'riwayat_pekerjaan' => ['grup' => 'Rekrutmen', 'label' => 'Riwayat Pekerjaan Pelamar', 'ikon' => 'bi-briefcase-fill', 'model' => Models\RiwayatPekerjaanPelamar::class,
                'with' => ['dataPelamar:id,nama_lengkap'], 'judul' => fn ($m) => ($m->dataPelamar?->nama_lengkap ?? '-') . ' — ' . $m->posisi . ' di ' . $m->nama_perusahaan,
                'kolom' => ['tanggal_mulai', 'tanggal_selesai'], 'cari' => ['nama_perusahaan', 'posisi'], 'relasi' => ['dataPelamar' => 'nama_lengkap']],

            // ---------------- Pegawai
            'pegawai' => ['grup' => 'Pegawai', 'label' => 'Data Pribadi Pegawai', 'ikon' => 'bi-person', 'model' => Models\DataPegawai::class,
                'judul' => fn ($m) => $m->nama_lengkap, 'kolom' => ['nip', 'finger_id', 'ikut_absensi'], 'cari' => ['nama_lengkap', 'nip', 'nik', 'email', 'finger_id'],
                'tautan' => fn ($m) => self::rute('admin.pegawai.show', $m)],
            'kepegawaian' => ['grup' => 'Pegawai', 'label' => 'Kepegawaian', 'ikon' => 'bi-person-badge', 'model' => Models\Kepegawaian::class,
                'with' => ['dataPegawai:id,nama_lengkap'], 'judul' => $pegawai, 'kolom' => ['jabatan', 'unit_kerja', 'status_kerja', 'status_rekrutmen'],
                'cari' => ['jabatan', 'unit_kerja', 'status_kerja'], 'relasi' => ['dataPegawai' => 'nama_lengkap']],
            'penugasan' => ['grup' => 'Pegawai', 'label' => 'Penugasan / Mutasi', 'ikon' => 'bi-arrow-left-right', 'model' => Models\Penugasan::class,
                'with' => ['dataPegawai:id,nama_lengkap'], 'judul' => fn ($m) => $pegawai($m) . ' — ' . $m->jabatan . ' (' . $m->unit_kerja . ')',
                'kolom' => ['jenis', 'peran', 'tanggal_mulai', 'tanggal_selesai'], 'cari' => ['unit_kerja', 'jabatan', 'jenis', 'no_sk'], 'relasi' => ['dataPegawai' => 'nama_lengkap']],
            'bpjs' => ['grup' => 'Pegawai', 'label' => 'BPJS Pegawai', 'ikon' => 'bi-shield-plus', 'model' => Models\BpjsPegawai::class,
                'with' => ['dataPegawai:id,nama_lengkap'], 'judul' => $pegawai, 'kolom' => ['no_bpjs_ketenagakerjaan', 'no_bpjs_kesehatan'], 'cari' => ['no_bpjs_ketenagakerjaan', 'no_bpjs_kesehatan'], 'relasi' => ['dataPegawai' => 'nama_lengkap']],
            'kontak_darurat' => ['grup' => 'Pegawai', 'label' => 'Kontak Darurat', 'ikon' => 'bi-telephone', 'model' => Models\KontakDarurat::class,
                'with' => ['dataPegawai:id,nama_lengkap'], 'judul' => $pegawai, 'kolom' => ['nama_kerabat', 'hubungan_kerabat'], 'cari' => ['nama_kerabat', 'no_hp_kerabat'], 'relasi' => ['dataPegawai' => 'nama_lengkap']],
            'dokumen' => ['grup' => 'Pegawai', 'label' => 'Dokumen Pegawai (path file)', 'ikon' => 'bi-folder2', 'model' => Models\DokumenPegawai::class,
                'with' => ['dataPegawai:id,nama_lengkap'], 'judul' => $pegawai, 'kolom' => ['updated_at'], 'cari' => [], 'relasi' => ['dataPegawai' => 'nama_lengkap']],

            // ---------------- Cuti & lembur
            'cuti' => ['grup' => 'Cuti & Lembur', 'label' => 'Pengajuan Cuti', 'ikon' => 'bi-calendar2-check', 'model' => Models\PengajuanCuti::class,
                'with' => ['dataPegawai:id,nama_lengkap'], 'judul' => fn ($m) => $pegawai($m) . ' — cuti ' . $m->jenis_cuti,
                'kolom' => ['tanggal_mulai', 'tanggal_selesai', 'status', 'current_level'], 'cari' => ['jenis_cuti', 'status', 'unit_kerja'], 'relasi' => ['dataPegawai' => 'nama_lengkap'],
                'tautan' => fn ($m) => self::rute('admin.pengajuan-cuti.show', $m)],
            'lembur' => ['grup' => 'Cuti & Lembur', 'label' => 'Pengajuan Lembur', 'ikon' => 'bi-clock-history', 'model' => Models\PengajuanLembur::class,
                'with' => ['dataPegawai:id,nama_lengkap'], 'judul' => fn ($m) => $pegawai($m) . ' — lembur ' . $m->jumlah_jam . ' jam',
                'kolom' => ['tanggal', 'jam_mulai', 'jam_selesai', 'status'], 'cari' => ['status', 'unit_kerja', 'uraian_tugas'], 'relasi' => ['dataPegawai' => 'nama_lengkap'],
                'tautan' => fn ($m) => self::rute('admin.pengajuan-lembur.show', $m)],

            // ---------------- Absensi
            'absensi' => ['grup' => 'Absensi', 'label' => 'Absensi Harian', 'ikon' => 'bi-fingerprint', 'model' => Models\Absensi::class,
                'with' => ['dataPegawai:id,nama_lengkap'], 'judul' => fn ($m) => $pegawai($m) . ' — ' . $m->tanggal?->format('d-m-Y'),
                'kolom' => ['scan_masuk', 'scan_keluar', 'jam_masuk', 'jam_keluar', 'status_manual'], 'cari' => ['finger_id', 'status_manual'], 'relasi' => ['dataPegawai' => 'nama_lengkap']],
            'import_absensi' => ['grup' => 'Absensi', 'label' => 'Riwayat Import Absensi', 'ikon' => 'bi-upload', 'model' => Models\AbsensiImport::class,
                'judul' => fn ($m) => $m->nama_file, 'kolom' => ['periode_dari', 'periode_sampai', 'jumlah_baris'], 'cari' => ['nama_file']],
            'izin_absensi' => ['grup' => 'Absensi', 'label' => 'Izin Telat / Pulang Cepat', 'ikon' => 'bi-clock', 'model' => Models\IzinAbsensi::class,
                'with' => ['dataPegawai:id,nama_lengkap'], 'judul' => fn ($m) => ($m->data_pegawai_id ? $pegawai($m) : 'Unit ' . ($m->unit_kerja ?? 'semua')) . ' — ' . $m->jenis,
                'kolom' => ['tanggal', 'menit', 'alasan'], 'cari' => ['jenis', 'alasan', 'unit_kerja'], 'relasi' => ['dataPegawai' => 'nama_lengkap']],
            'jadwal_kerja' => ['grup' => 'Absensi', 'label' => 'Jam Kerja Pegawai', 'ikon' => 'bi-alarm', 'model' => Models\JadwalKerjaPegawai::class,
                'with' => ['dataPegawai:id,nama_lengkap'], 'judul' => $pegawai, 'kolom' => ['jam_masuk', 'jam_pulang', 'toleransi_menit'], 'cari' => [], 'relasi' => ['dataPegawai' => 'nama_lengkap']],

            // ---------------- Informasi
            'pengumuman' => ['grup' => 'Informasi', 'label' => 'Pengumuman', 'ikon' => 'bi-megaphone', 'model' => Models\Pengumuman::class,
                'judul' => fn ($m) => $m->judul, 'kolom' => ['kategori', 'tanggal', 'absen_aktif'], 'cari' => ['judul', 'isi', 'lokasi'],
                'tautan' => fn ($m) => self::rute('pengumuman.edit', $m)],
            'peserta_acara' => ['grup' => 'Informasi', 'label' => 'Peserta Absen Acara', 'ikon' => 'bi-qr-code-scan', 'model' => Models\PengumumanPeserta::class,
                'with' => ['pengumuman:id,judul', 'dataPegawai:id,nama_lengkap', 'user:id,name'],
                'judul' => fn ($m) => ($m->dataPegawai?->nama_lengkap ?? $m->user?->name ?? '-') . ' — ' . ($m->pengumuman?->judul ?? '-'),
                'kolom' => ['status', 'waktu_absen', 'jarak'], 'cari' => ['status', 'catatan'], 'relasi' => ['pengumuman' => 'judul', 'dataPegawai' => 'nama_lengkap'],
                'tautan' => fn ($m) => self::rute('pengumuman.absen', $m->pengumuman_id)],
            'notifikasi' => ['grup' => 'Informasi', 'label' => 'Notifikasi Terkirim', 'ikon' => 'bi-bell', 'model' => DatabaseNotification::class,
                'with' => ['notifiable'], 'judul' => fn ($m) => ($m->data['judul'] ?? 'Notifikasi') . ' → ' . ($m->notifiable?->name ?? '-'),
                'kolom' => ['read_at', 'created_at'], 'cari' => ['data']],

            // ---------------- Sistem
            'akun' => ['grup' => 'Sistem', 'label' => 'Akun Pengguna', 'ikon' => 'bi-person-lock', 'model' => Models\User::class,
                'judul' => fn ($m) => $m->name . ' <' . $m->email . '>', 'kolom' => ['role', 'nip'], 'cari' => ['name', 'email', 'nip', 'role'],
                'tautan' => fn ($m) => self::rute('admin.users.edit', $m)],
            'jabatan' => ['grup' => 'Sistem', 'label' => 'Master Jabatan', 'ikon' => 'bi-diagram-3', 'model' => Models\Jabatan::class,
                'judul' => fn ($m) => $m->nama, 'kolom' => ['updated_at'], 'cari' => ['nama']],
        ];
    }

    /** @return array<string, mixed> */
    public static function konfig(string $kunci): array
    {
        return self::entitas()[$kunci] ?? abort(404, 'Jenis data tidak dikenal.');
    }

    public static function model(array $konfig): Model
    {
        return new $konfig['model'];
    }

    /** Query daftar: pencarian teks + relasi + ID persis. */
    public static function query(array $konfig, ?string $cari): Builder
    {
        $model = self::model($konfig);
        $q = $model->newQuery()->with($konfig['with'] ?? []);
        $cari = trim((string) $cari);

        if ($cari !== '') {
            $q->where(function ($w) use ($konfig, $cari, $model) {
                if (ctype_digit($cari) || Str::isUuid($cari)) {
                    $w->orWhere($model->getKeyName(), $cari);
                }
                foreach ($konfig['cari'] ?? [] as $kolom) {
                    $w->orWhere($kolom, 'like', "%{$cari}%");
                }
                foreach ($konfig['relasi'] ?? [] as $relasi => $kolom) {
                    $w->orWhereHas($relasi, fn ($r) => $r->where($kolom, 'like', "%{$cari}%"));
                }
            });
        }

        return $q->orderByDesc($model->getQualifiedKeyName());
    }

    /**
     * Metadata kolom tabel untuk form.
     *
     * @return list<array{nama: string, jenis: string, tipe: string, wajib: bool, baca: bool, opsi: ?array, saran: ?array, fk: ?array}>
     */
    public static function kolom(Model $model): array
    {
        $casts = $model->getCasts();

        return collect(Schema::getColumns($model->getTable()))
            ->reject(fn ($c) => in_array($c['name'], self::KOLOM_RAHASIA, true))
            ->map(function ($c) use ($casts, $model) {
                $nama = $c['name'];
                $tipe = strtolower($c['type_name']);
                $jenis = match (true) {
                    $tipe === 'tinyint' && (str_contains($c['type'], '(1)') || in_array($casts[$nama] ?? null, ['bool', 'boolean'], true)) => 'boolean',
                    $tipe === 'enum' => 'enum',
                    $tipe === 'date' => 'date',
                    in_array($tipe, ['datetime', 'timestamp'], true) => 'datetime',
                    $tipe === 'time' => 'time',
                    in_array($tipe, ['tinyint', 'smallint', 'mediumint', 'int', 'integer', 'bigint', 'decimal', 'float', 'double'], true) => 'angka',
                    $tipe === 'json' || in_array($casts[$nama] ?? null, ['array', 'json', 'collection', 'object'], true) => 'json',
                    in_array($tipe, ['text', 'mediumtext', 'longtext'], true) => 'teks_panjang',
                    default => 'teks',
                };
                $fk = self::fk($nama, $model);
                $pilihan = $fk ? [] : self::pilihan($model, $nama);

                return [
                    'nama' => $nama,
                    'label' => ucfirst(str_replace('_', ' ', $nama)),
                    'jenis' => $fk ? 'fk' : ($pilihan ? 'pilihan' : $jenis),
                    'pilihan' => $pilihan,
                    'tipe' => $c['type'],
                    'wajib' => ! $c['nullable'] && $c['default'] === null && ! in_array($nama, self::KOLOM_BACA, true),
                    'baca' => in_array($nama, self::KOLOM_BACA, true) || ($nama === $model->getKeyName()),
                    'opsi' => $jenis === 'enum' ? self::nilaiEnum($c['type']) : null,
                    'saran' => $jenis === 'teks' && in_array($nama, self::KOLOM_SARAN, true) ? self::saran($model, $nama) : null,
                    'fk' => $fk,
                ];
            })->values()->all();
    }

    /** Kolom kunci asing → [tabel, kolom label, opsi id => label]. */
    private static function fk(string $nama, Model $model): ?array
    {
        $peta = [
            'data_pegawai_id' => ['data_pegawais', 'nama_lengkap'],
            'data_pelamar_id' => ['data_pelamars', 'nama_lengkap'],
            'lowongan_id' => ['lowongans', 'judul'],
            'pengumuman_id' => ['pengumumans', 'judul'],
            'pengajuan_formasi_id' => ['pengajuan_formasis', 'jabatan'],
            'absensi_import_id' => ['absensi_imports', 'nama_file'],
            'lamaran_id' => ['lamarans', null],
        ];
        $keUser = str_ends_with($nama, '_oleh') || str_ends_with($nama, '_by')
            || in_array($nama, ['user_id', 'interviewer_id', 'pengawas_id', 'kepala_id', 'notifiable_id'], true);

        [$tabel, $label] = $peta[$nama] ?? ($keUser ? ['users', 'name'] : [null, null]);
        if (! $tabel || ($model->getTable() === $tabel)) {
            return null;
        }
        if ($label === null || DB::table($tabel)->count() > 1500) {
            return ['tabel' => $tabel, 'opsi' => null]; // terlalu banyak: isi ID manual
        }

        return ['tabel' => $tabel, 'opsi' => DB::table($tabel)->orderBy($label)->pluck($label, 'id')->map(fn ($v, $id) => "{$v} (#{$id})")->all()];
    }

    /**
     * Nilai sah dari konstanta model (mis. STATUS_*, HASIL_*, TAHAP_*) → kolom jadi dropdown, bukan ketik bebas.
     *
     * @return array<string, string> nilai => label
     */
    private static function pilihan(Model $model, string $kolom): array
    {
        $awalan = [
            'status' => 'STATUS_', 'hasil_status' => 'HASIL_', 'hasil_sdm' => 'HASIL_', 'hasil_kepala' => 'HASIL_',
            'tahap' => 'TAHAP_', 'tahap_interview' => 'TAHAP_', 'keputusan' => 'KEPUTUSAN_', 'jalur' => 'JALUR_',
            'role' => 'ROLE_', 'peran' => 'PERAN_', 'jadwal' => 'JADWAL_', 'status_manual' => 'MANUAL_', 'jenis' => 'JENIS_', 'jenis_cuti' => 'JENIS_',
        ][$kolom] ?? null;
        $daftarKonstanta = ['kategori' => 'KATEGORI', 'kategori_cuti_besar' => 'KATEGORI_BESAR_OPTIONS'][$kolom] ?? null;
        $konstanta = (new \ReflectionClass($model))->getConstants();
        $label = fn ($v) => ucfirst(str_replace('_', ' ', (string) $v));

        if ($daftarKonstanta && is_array($konstanta[$daftarKonstanta] ?? null)) {
            return collect($konstanta[$daftarKonstanta])->mapWithKeys(fn ($v, $k) => [(string) $k => is_array($v) ? ($v['label'] ?? $label($k)) : (string) $v])->all();
        }
        if (! $awalan) {
            return [];
        }

        return collect($konstanta)
            ->filter(fn ($v, $n) => str_starts_with($n, $awalan) && (is_string($v) || is_int($v)))
            ->mapWithKeys(fn ($v, $n) => [(string) $v => $label($v) . (is_int($v) ? ' — ' . $label(strtolower(substr($n, strlen($awalan)))) : '')])
            ->all();
    }

    private static function nilaiEnum(string $tipe): array
    {
        preg_match_all("/'((?:[^']|'')*)'/", $tipe, $m);

        return array_map(fn ($v) => str_replace("''", "'", $v), $m[1]);
    }

    private static function saran(Model $model, string $kolom): array
    {
        $ada = DB::table($model->getTable())->whereNotNull($kolom)->distinct()->limit(60)->pluck($kolom)->all();
        $tambahan = match (true) {
            str_starts_with($kolom, 'unit_kerja') => config('kepegawaian.unit_kerja', []),
            str_starts_with($kolom, 'jabatan') => Models\Jabatan::orderBy('nama')->pluck('nama')->all(),
            default => [],
        };

        return array_values(array_unique(array_merge($ada, $tambahan)));
    }

    /** Nilai kolom untuk ditampilkan di input form. */
    public static function nilaiInput(Model $model, array $k): string
    {
        $mentah = $model->getRawOriginal($k['nama']);
        if ($mentah === null) {
            return '';
        }

        return match ($k['jenis']) {
            'datetime' => \Carbon\Carbon::parse($mentah)->format('Y-m-d\TH:i:s'),
            'date' => substr((string) $mentah, 0, 10),
            'time' => substr((string) $mentah, 0, 8),
            'json' => json_encode(json_decode((string) $mentah, true), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: (string) $mentah,
            default => (string) $mentah,
        };
    }

    /** Nilai ringkas untuk tabel daftar. */
    public static function nilaiRingkas(Model $model, string $kolom): string
    {
        $v = $model->getRawOriginal($kolom);

        return match (true) {
            $v === null => '–',
            is_string($v) && preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $v) === 1 => \Carbon\Carbon::parse($v)->format('d-m-Y H:i'),
            is_string($v) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) === 1 => \Carbon\Carbon::parse($v)->format('d-m-Y'),
            default => Str::limit((string) $v, 40),
        };
    }

    /**
     * Dampak bila baris ini dihapus, dibaca dari foreign key database:
     * ikut terhapus (CASCADE), dikosongkan (SET NULL), atau menghalangi penghapusan (RESTRICT).
     *
     * @return list<array{tabel: string, kolom: string, aturan: string, jumlah: int}>
     */
    public static function dampakHapus(Model $model): array
    {
        $fk = DB::select('SELECT k.TABLE_NAME tabel, k.COLUMN_NAME kolom, r.DELETE_RULE aturan
            FROM information_schema.KEY_COLUMN_USAGE k
            JOIN information_schema.REFERENTIAL_CONSTRAINTS r ON r.CONSTRAINT_NAME = k.CONSTRAINT_NAME AND r.CONSTRAINT_SCHEMA = k.CONSTRAINT_SCHEMA
            WHERE k.REFERENCED_TABLE_SCHEMA = DATABASE() AND k.REFERENCED_TABLE_NAME = ?', [$model->getTable()]);

        return collect($fk)
            ->map(fn ($f) => ['tabel' => $f->tabel, 'kolom' => $f->kolom, 'aturan' => $f->aturan,
                'jumlah' => DB::table($f->tabel)->where($f->kolom, $model->getKey())->count()])
            ->filter(fn ($d) => $d['jumlah'] > 0)
            ->values()->all();
    }

    public static function rute(string $nama, mixed $param): ?string
    {
        return Route::has($nama) ? route($nama, $param) : null;
    }

    /** Hanya izinkan kembali ke alamat di aplikasi ini. */
    public static function kembaliAman(?string $url): ?string
    {
        if (! $url) {
            return null;
        }
        $host = parse_url($url, PHP_URL_HOST);

        return (str_starts_with($url, '/') && ! str_starts_with($url, '//')) || $host === request()->getHost() ? $url : null;
    }
}
