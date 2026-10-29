<?php

namespace App\Http\Controllers;

use App\Models\InterviewJadwal;
use App\Models\Lamaran;
use App\Models\PengajuanIzinKeluar;
use App\Models\User;
use App\Support\EksporPegawai;
use App\Support\ExcelExporter;
use App\Support\FilterTanggal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/** Ekspor data milik pengguna yang sedang login (jadwal, riwayat pengajuan, notifikasi, lamaran). Data terbaru di baris paling bawah. */
class EksporController extends Controller
{
    public function jadwalInterviewSaya(): BinaryFileResponse
    {
        $user = $this->user();
        abort_unless($user->bisaMenginterview(), 403);

        $rows = InterviewJadwal::with(['lamaran.dataPelamar', 'lamaran.dataPegawai', 'lamaran.lowongan'])
            ->where('interviewer_id', $user->id)
            ->orderBy('tanggal')->orderBy('waktu_mulai')->orderBy('id')->get()
            ->map(fn (InterviewJadwal $j) => [
                $j->tanggal->format('d-m-Y'),
                substr((string) $j->waktu_mulai, 0, 5) . ($j->waktu_selesai ? ' - ' . substr((string) $j->waktu_selesai, 0, 5) : ''),
                $j->lamaran?->namaKandidat(),
                $j->lamaran?->judulPosisi(),
                $j->lamaran?->unitKerjaTujuan(),
                $j->tahapLabel(),
                $j->lokasi,
                $j->statusLabel(),
                $j->hasilLabel(),
                $j->catatan_hasil,
            ]);

        return ExcelExporter::download(ExcelExporter::namaFile('jadwal-interview-saya'), [
            'Tanggal', 'Jam', 'Kandidat', 'Posisi', 'Unit', 'Tahap', 'Lokasi', 'Status', 'Hasil', 'Catatan Hasil',
        ], $rows, 'Jadwal Interview');
    }

    public function riwayatCuti(): BinaryFileResponse
    {
        $pegawai = $this->user()->dataPegawai;
        abort_unless($pegawai, 403);

        $rows = $pegawai->pengajuanCutis()->orderBy('id')->get()->map(fn ($c) => [
            optional($c->created_at)->format('d-m-Y H:i'),
            $c->jenisLabel(),
            $c->kategoriBesarLabel(),
            $c->tanggal_mulai->format('d-m-Y'),
            $c->tanggal_selesai->format('d-m-Y'),
            $c->lama_cuti,
            $c->pengganti_nama,
            $c->statusLabel(),
            $c->isPending() ? $c->currentLevelLabel() : '-',
            $c->keterangan,
            $c->catatan_admin,
        ]);

        return ExcelExporter::download(ExcelExporter::namaFile('riwayat-cuti-saya'), [
            'Diajukan', 'Jenis', 'Kategori Cuti Besar', 'Mulai', 'Selesai', 'Lama (hari)', 'Pengganti', 'Status', 'Tahap', 'Keterangan', 'Catatan',
        ], $rows, 'Riwayat Cuti');
    }

    public function riwayatLembur(): BinaryFileResponse
    {
        $pegawai = $this->user()->dataPegawai;
        abort_unless($pegawai, 403);

        $rows = $pegawai->pengajuanLemburs()->orderBy('id')->get()->map(fn ($l) => [
            optional($l->created_at)->format('d-m-Y H:i'),
            $l->unit_kerja,
            $l->tanggal->format('d-m-Y'),
            $l->hari,
            substr((string) $l->jam_mulai, 0, 5),
            substr((string) $l->jam_selesai, 0, 5),
            $l->jumlah_jam,
            $l->uraian_tugas,
            $l->statusLabel(),
            $l->isPending() ? $l->currentLevelLabel() : '-',
            $l->catatan_admin,
        ]);

        return ExcelExporter::download(ExcelExporter::namaFile('riwayat-lembur-saya'), [
            'Diajukan', 'Unit', 'Tanggal', 'Hari', 'Jam Mulai', 'Jam Selesai', 'Jumlah Jam', 'Uraian Tugas', 'Status', 'Tahap', 'Catatan',
        ], $rows, 'Riwayat Lembur');
    }

    public function riwayatIzinKeluar(): BinaryFileResponse
    {
        $pegawai = $this->user()->dataPegawai;
        abort_unless($pegawai, 403);

        $rows = $pegawai->pengajuanIzinKeluars()->reorder()->orderBy('id')->get()->map(fn (PengajuanIzinKeluar $i) => [
            optional($i->created_at)->format('d-m-Y H:i'),
            optional($i->tanggal)->format('d-m-Y'),
            substr((string) $i->jam_keluar, 0, 5),
            $i->tidakKembali() ? 'Tidak kembali' : substr((string) $i->jam_kembali, 0, 5),
            $i->keperluanLabel(),
            $i->alasan,
            $i->statusLabel(),
            $i->isPending() ? $i->currentLevelLabel() : '-',
            $i->kode_tiket,
            $i->catatan_admin,
        ]);

        return ExcelExporter::download(ExcelExporter::namaFile('riwayat-izin-keluar-saya'), [
            'Diajukan', 'Tanggal', 'Jam Keluar', 'Jam Kembali', 'Keperluan', 'Alasan', 'Status', 'Tahap', 'Kode Tiket', 'Catatan',
        ], $rows, 'Riwayat Izin Keluar');
    }

    public function notifikasi(Request $request): BinaryFileResponse
    {
        // Ikut rentang tanggal yang sedang disaring di halaman Notifikasi.
        $rows = FilterTanggal::terapkan($this->user()->notifications(), 'notifications.created_at', $request)->reorder()->orderBy('created_at')->get()->map(fn ($n) => [
            $n->created_at->format('d-m-Y H:i'),
            $n->data['judul'] ?? '',
            $n->data['pesan'] ?? '',
            $n->data['pengirim'] ?? 'Sistem',
            $n->read_at ? 'Sudah dibaca' : 'Belum dibaca',
        ]);

        return ExcelExporter::download(ExcelExporter::namaFile('notifikasi-saya'), ['Waktu', 'Judul', 'Pesan', 'Pengirim', 'Status'], $rows, 'Notifikasi');
    }

    /** Pelamar: daftar lamaran + seluruh jadwal (interview, onboarding, kontrak) dalam satu file. */
    public function lamaranSaya(): BinaryFileResponse
    {
        $user = $this->user();
        abort_unless($user->data_pelamar_id, 403);

        $lamarans = Lamaran::with(['lowongan', 'interviewJadwals', 'screeningOnboardings', 'kontrakJadwals'])
            ->where('data_pelamar_id', $user->data_pelamar_id)->orderBy('id')->get();

        return ExcelExporter::downloadMultiSheet(ExcelExporter::namaFile('lamaran-saya'), [
            [
                'name' => 'Lamaran',
                'headers' => ['Tanggal Melamar', 'Posisi', 'Unit', 'Tahap', 'Status'],
                'rows' => $lamarans->map(fn (Lamaran $l) => [optional($l->created_at)->format('d-m-Y'), $l->judulPosisi(), $l->unitKerjaTujuan(), $l->tahapLabel(), $l->statusLabel()]),
            ],
            ...self::sheetJadwal($lamarans),
        ]);
    }

    /** Data diri pegawai yang login (Data Diri Saya / Profil): biodata, penugasan, cuti, lembur. */
    public function dataSaya(): BinaryFileResponse
    {
        $user = $this->user();
        $pegawai = $user->dataPegawai;
        if (! $pegawai) {
            // Akun tanpa data pegawai (mis. Admin/GM): ekspor data akunnya saja.
            return ExcelExporter::download(ExcelExporter::namaFile('akun-saya'), ['Data', 'Isi'], [
                ['Nama', $user->name], ['Email', $user->email], ['Role', $user->roleLabel()], ['Dibuat', optional($user->created_at)->format('d-m-Y H:i')],
            ], 'Akun');
        }

        return ExcelExporter::downloadMultiSheet(ExcelExporter::namaFile('data-saya-' . str($pegawai->nama_lengkap)->slug()), EksporPegawai::lembar($pegawai));
    }

    /** Data pelamar yang login: biodata, riwayat pendidikan & pekerjaan, media sosial. */
    public function dataPelamarSaya(): BinaryFileResponse
    {
        $p = $this->user()->dataPelamar?->load('riwayatPekerjaan', 'riwayatPendidikan');
        abort_unless($p, 404, 'Data pelamar belum diisi.');

        $biodata = [
            'Nama Lengkap' => $p->nama_lengkap, 'Email' => $p->email, 'NIK' => $p->nik, 'NPWP' => $p->npwp, 'No. KK' => $p->no_kk, 'No. HP' => $p->no_hp,
            'Tempat Lahir' => $p->tempat_lahir, 'Tanggal Lahir' => optional($p->tanggal_lahir)->format('d-m-Y'), 'Jenis Kelamin' => $p->jenis_kelamin,
            'Agama' => $p->agama, 'Golongan Darah' => $p->golongan_darah, 'Status Pernikahan' => $p->status_pernikahan, 'Jumlah Tanggungan' => $p->jumlah_tanggungan,
            'Nama Ibu Kandung' => $p->nama_ibu_kandung, 'Pendidikan Terakhir' => $p->pendidikan_terakhir, 'Alamat KTP' => $p->alamat_ktp, 'Alamat Domisili' => $p->alamat_domisili,
        ];

        return ExcelExporter::downloadMultiSheet(ExcelExporter::namaFile('data-pelamar-saya'), [
            ['name' => 'Biodata', 'headers' => ['Data', 'Isi'], 'rows' => collect($biodata)->map(fn ($v, $k) => [$k, $v])->values()],
            ['name' => 'Riwayat Pendidikan', 'headers' => ['Jenjang', 'Institusi', 'Jurusan', 'Tahun Masuk', 'Tahun Lulus', 'Nilai'],
                'rows' => $p->riwayatPendidikan->map(fn ($r) => [$r->jenjang, $r->nama_institusi, $r->jurusan, $r->tahun_masuk, $r->tahun_lulus, $r->nilai])],
            ['name' => 'Riwayat Pekerjaan', 'headers' => ['Perusahaan', 'Posisi', 'Mulai', 'Selesai', 'Deskripsi'],
                'rows' => $p->riwayatPekerjaan->map(fn ($r) => [$r->nama_perusahaan, $r->posisi, optional($r->tanggal_mulai)->format('d-m-Y'),
                    optional($r->tanggal_selesai)->format('d-m-Y') ?? 'Sekarang', $r->deskripsi])],
            ['name' => 'Media Sosial', 'headers' => ['Platform', 'Tautan'],
                'rows' => collect($p->sosial_media ?? [])->map(fn ($s, $k) => is_array($s) ? [$s['platform'] ?? $k, $s['url'] ?? null] : [$k, $s])->values()],
        ]);
    }

    /**
     * Tiga sheet jadwal rekrutmen (interview, onboarding, kontrak), diurutkan dari yang paling awal.
     *
     * @param  iterable<Lamaran>  $lamarans
     * @return list<array{name: string, headers: list<string>, rows: iterable}>
     */
    public static function sheetJadwal(iterable $lamarans): array
    {
        $lamarans = collect($lamarans);

        $interview = $lamarans->flatMap(fn (Lamaran $l) => $l->interviewJadwals->map(fn ($j) => [$j, $l]))
            ->sortBy(fn ($p) => $p[0]->tanggal->format('Y-m-d') . ' ' . $p[0]->waktu_mulai . ' ' . str_pad((string) $p[0]->id, 9, '0', STR_PAD_LEFT))
            ->map(fn ($p) => [
                $p[0]->tanggal->format('d-m-Y'),
                substr((string) $p[0]->waktu_mulai, 0, 5),
                $p[1]->namaKandidat(),
                $p[1]->judulPosisi(),
                $p[0]->tahapLabel(),
                $p[0]->interviewer?->name,
                $p[0]->lokasi,
                $p[0]->statusLabel(),
                $p[0]->hasilLabel(),
            ])->values();

        $onboarding = $lamarans->flatMap(fn (Lamaran $l) => $l->screeningOnboardings->map(fn ($s) => [$s, $l]))
            ->sortBy(fn ($p) => $p[0]->tanggal_mulai->format('Y-m-d') . str_pad((string) $p[0]->id, 9, '0', STR_PAD_LEFT))
            ->map(fn ($p) => [
                $p[0]->tanggal_mulai->format('d-m-Y'),
                $p[0]->tanggal_selesai->format('d-m-Y'),
                $p[1]->namaKandidat(),
                $p[1]->judulPosisi(),
                $p[0]->lokasi,
                $p[0]->statusLabel(),
                $p[0]->hasilLabel(),
            ])->values();

        $kontrak = $lamarans->flatMap(fn (Lamaran $l) => $l->kontrakJadwals->map(fn ($k) => [$k, $l]))
            ->sortBy(fn ($p) => $p[0]->tanggal->format('Y-m-d') . str_pad((string) $p[0]->id, 9, '0', STR_PAD_LEFT))
            ->map(fn ($p) => [
                $p[0]->tanggal->format('d-m-Y'),
                $p[0]->waktu ? substr((string) $p[0]->waktu, 0, 5) : null,
                $p[1]->namaKandidat(),
                $p[1]->judulPosisi(),
                $p[0]->lokasi,
                $p[0]->statusLabel(),
            ])->values();

        return [
            ['name' => 'Jadwal Interview', 'headers' => ['Tanggal', 'Jam', 'Kandidat', 'Posisi', 'Tahap', 'Interviewer', 'Lokasi', 'Status', 'Hasil'], 'rows' => $interview],
            ['name' => 'Jadwal Onboarding', 'headers' => ['Mulai', 'Selesai', 'Kandidat', 'Posisi', 'Lokasi', 'Status', 'Hasil'], 'rows' => $onboarding],
            ['name' => 'Jadwal Kontrak', 'headers' => ['Tanggal', 'Jam', 'Kandidat', 'Posisi', 'Lokasi', 'Status'], 'rows' => $kontrak],
        ];
    }

    private function user(): User
    {
        /** @var User */
        return Auth::user();
    }
}
