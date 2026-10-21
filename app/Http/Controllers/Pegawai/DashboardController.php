<?php

namespace App\Http\Controllers\Pegawai;

use App\Http\Controllers\Controller;
use App\Models\DataPegawai;
use App\Models\Lamaran;
use App\Models\PengajuanCuti;
use App\Models\PengajuanLembur;
use App\Models\Pengumuman;
use App\Models\User;
use App\Support\DashboardData;
use App\Support\InterviewFlow;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        /** @var User $user */
        $user = Auth::user();
        $user->load(['dataPegawai.kepegawaian', 'dataPegawai.kontakDarurat', 'dataPegawai.dokumen', 'dataPegawai.penugasanAktif']);

        $pegawai = $user->dataPegawai;
        $kep = $pegawai?->kepegawaian;

        $wajib = [
            'Nama lengkap' => $pegawai?->nama_lengkap, 'NIK' => $pegawai?->nik, 'No. KK' => $pegawai?->no_kk, 'No. HP' => $pegawai?->no_hp,
            'Tempat lahir' => $pegawai?->tempat_lahir, 'Tanggal lahir' => $pegawai?->tanggal_lahir, 'Jenis kelamin' => $pegawai?->jenis_kelamin,
            'Agama' => $pegawai?->agama, 'Alamat KTP' => $pegawai?->alamat_ktp, 'Status pernikahan' => $pegawai?->status_pernikahan,
            'Nama ibu kandung' => $pegawai?->nama_ibu_kandung, 'Pendidikan terakhir' => $pegawai?->pendidikan_terakhir,
            'Jabatan' => $kep?->jabatan, 'Unit kerja' => $kep?->unit_kerja, 'Lokasi kerja' => $kep?->lokasi_kerja, 'Tanggal masuk' => $kep?->tanggal_masuk,
            'No. HP kerabat' => $pegawai?->kontakDarurat?->no_hp_kerabat, 'Nama kerabat' => $pegawai?->kontakDarurat?->nama_kerabat,
            'Hubungan kerabat' => $pegawai?->kontakDarurat?->hubungan_kerabat,
            'File KTP' => $pegawai?->dokumen?->upload_ktp, 'File KK' => $pegawai?->dokumen?->upload_kk, 'Pas foto' => $pegawai?->dokumen?->pas_foto,
        ];
        $kurang = array_keys(array_filter($wajib, fn ($v) => blank($v)));
        $kelengkapan = (int) round((count($wajib) - count($kurang)) / count($wajib) * 100);

        $bisaCuti = $user->can('ajukan-cuti');
        $bisaLembur = $user->can('ajukan-lembur');
        $masaKerjaCukup = $kep?->tanggal_masuk && Carbon::parse($kep->tanggal_masuk)->diffInYears(now()) >= 1;

        return view('pegawai.dashboard', [
            'user' => $user,
            'pegawai' => $pegawai,
            'kelengkapan' => $kelengkapan,
            'dataKurang' => $kurang,
            'isIncomplete' => $kelengkapan < 100,
            'bisaCuti' => $bisaCuti,
            'bisaLembur' => $bisaLembur,
            'masaKerjaCukup' => (bool) $masaKerjaCukup,
            'masaKerja' => DashboardData::masaKerja($kep?->tanggal_masuk),
            'kontrak' => $this->kontrak($kep),
            'sisaCuti' => PengajuanCuti::sisaCutiTahunan($pegawai),
            'kuotaCuti' => PengajuanCuti::ANNUAL_QUOTA,
            'pengajuanCutiTahunIni' => PengajuanCuti::jumlahPengajuanTahunIni($pegawai),
            'maxPengajuanCuti' => PengajuanCuti::MAX_PENGAJUAN_PER_TAHUN,
            'lemburBulanIni' => $pegawai ? (float) $pegawai->pengajuanLemburs()->where('status', PengajuanLembur::STATUS_APPROVED)
                ->whereYear('tanggal', now()->year)->whereMonth('tanggal', now()->month)->sum('jumlah_jam') : 0.0,
            'trenLembur' => $bisaLembur ? $this->trenLembur($pegawai) : null,
            'pending' => $pegawai
                ? $pegawai->pengajuanCutis()->where('status', PengajuanCuti::STATUS_PENDING)->count() + $pegawai->pengajuanLemburs()->where('status', PengajuanLembur::STATUS_PENDING)->count()
                : 0,
            'penugasan' => $pegawai?->penugasanAktif ?? collect(),
            'unitSaya' => $this->unitSaya($pegawai),
            'pengumuman' => Pengumuman::untuk($user)->berlaku()->orderByDesc('penting')->latest()->take(3)->get(),
            'terbaru' => $this->pengajuanTerbaru($pegawai),
            'seleksiSaya' => $user->data_pegawai_id
                ? Lamaran::with(['lowongan', 'interviewJadwals' => fn ($q) => $q->where('status', 'terjadwal')->orderBy('tanggal')])
                    ->where('data_pegawai_id', $user->data_pegawai_id)->where('status', Lamaran::STATUS_PROSES)->get()
                : collect(),
        ]);
    }

    private function kontrak($kep): ?array
    {
        if (! $kep?->tanggal_habis_kontrak) {
            return null;
        }

        $mulai = $kep->tanggal_masuk ?? $kep->tanggal_habis_kontrak->copy()->subYear();
        $total = max(1, $mulai->diffInDays($kep->tanggal_habis_kontrak));
        $berjalan = min($total, max(0, $mulai->diffInDays(now())));

        return [
            'habis' => $kep->tanggal_habis_kontrak,
            'sisa' => (int) now()->startOfDay()->diffInDays($kep->tanggal_habis_kontrak, false),
            'persen' => (int) round($berjalan / $total * 100),
        ];
    }

    private function trenLembur(?DataPegawai $pegawai): array
    {
        $bulan = collect(range(5, 0))->map(fn ($i) => now()->startOfMonth()->subMonths($i));
        $data = $pegawai
            ? $pegawai->pengajuanLemburs()->where('status', PengajuanLembur::STATUS_APPROVED)->where('tanggal', '>=', $bulan->first())
                ->selectRaw("DATE_FORMAT(tanggal, '%Y-%m') b, sum(jumlah_jam) n")->groupBy('b')->pluck('n', 'b')
            : collect();

        return $bulan->map(fn ($b) => ['label' => $b->translatedFormat('M'), 'judul' => $b->translatedFormat('F Y'), 'jumlah' => round((float) ($data[$b->format('Y-m')] ?? 0), 1)])->all();
    }

    private function unitSaya(?DataPegawai $pegawai): ?array
    {
        $unit = $pegawai?->kepegawaian?->unit_kerja;
        if (! $unit) {
            return null;
        }

        $rekan = DataPegawai::with('kepegawaian')
            ->where('id', '!=', $pegawai->id)
            ->whereHas('kepegawaian', fn ($q) => $q->where('status_rekrutmen', 'Aktif'))
            ->whereHas('penugasanAktif', fn ($q) => $q->where('unit_kerja', $unit))
            ->get();

        $cuti = PengajuanCuti::with('dataPegawai')->where('status', PengajuanCuti::STATUS_APPROVED)->where('unit_kerja', $unit)
            ->where('data_pegawai_id', '!=', $pegawai->id)
            ->whereDate('tanggal_mulai', '<=', now())->whereDate('tanggal_selesai', '>=', now())->get();

        return [
            'nama' => $unit,
            'kepala' => InterviewFlow::kepalaUnit($unit)?->name,
            'jumlahRekan' => $rekan->count(),
            'cutiHariIni' => $cuti,
        ];
    }

    private function pengajuanTerbaru(?DataPegawai $pegawai): Collection
    {
        if (! $pegawai) {
            return collect();
        }

        $cuti = $pegawai->pengajuanCutis()->latest()->take(5)->get()->map(fn ($c) => ['jenis' => $c->jenisLabel(), 'ikon' => 'bi-calendar2-check', 'tanggal' => $c->tanggal_mulai,
            'detail' => "{$c->lama_cuti} hari", 'status' => $c->statusLabel(), 'badge' => $c->badgeClass(), 'tahap' => $c->isPending() ? $c->currentLevelLabel() : null,
            'catatan' => $c->catatan_admin, 'dibuat' => $c->created_at]);
        $lembur = $pegawai->pengajuanLemburs()->latest()->take(5)->get()->map(fn ($l) => ['jenis' => 'Lembur', 'ikon' => 'bi-clock-history', 'tanggal' => $l->tanggal,
            'detail' => rtrim(rtrim(number_format($l->jumlah_jam, 2, ',', '.'), '0'), ',') . ' jam' . ($l->unit_kerja ? " · {$l->unit_kerja}" : ''),
            'status' => $l->statusLabel(), 'badge' => $l->badgeClass(), 'tahap' => $l->isPending() ? $l->currentLevelLabel() : null,
            'catatan' => $l->catatan_admin, 'dibuat' => $l->created_at]);

        return $cuti->concat($lembur)->sortByDesc('dibuat')->take(5)->values();
    }
}
