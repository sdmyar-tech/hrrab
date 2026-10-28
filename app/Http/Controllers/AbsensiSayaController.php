<?php

namespace App\Http\Controllers;

use App\Models\DataPegawai;
use App\Models\User;
use App\Support\Absensi\EksporAbsensi;
use App\Support\Absensi\PenilaianAbsensi;
use App\Support\Absensi\TanggalAbsensi;
use App\Support\ExcelExporter;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/** Absensi milik pegawai yang sedang login (hanya lihat). */
class AbsensiSayaController extends Controller
{
    public function index(Request $request): View
    {
        /** @var User $user */
        $user = Auth::user();
        [$pegawai, $bulan, $hari] = $this->data($request, $user);

        return view('absensi-saya', [
            'layout' => $user->panelLayout(),
            'pegawai' => $pegawai->load('jadwalKerja'),
            'bulan' => $bulan,
            'hari' => array_reverse($hari),
            'rekap' => PenilaianAbsensi::rekap($hari),
        ]);
    }

    /** Ekspor absensi sendiri pada bulan yang dilihat (ringkasan + detail per hari). */
    public function export(Request $request): BinaryFileResponse
    {
        /** @var User $user */
        $user = Auth::user();
        [$pegawai, $bulan, $hari] = $this->data($request, $user);

        return ExcelExporter::downloadMultiSheet('absensi-saya-' . $bulan->format('Y-m'), EksporAbsensi::satuPegawai($pegawai->load('kepegawaian'), $hari));
    }

    /** @return array{0: DataPegawai, 1: Carbon, 2: array} pegawai, bulan, hasil penilaian per hari */
    private function data(Request $request, User $user): array
    {
        $pegawai = $user->dataPegawai;
        abort_unless($pegawai, 404);

        // Bulan tidak valid atau di masa depan → bulan ini.
        $bulan = TanggalAbsensi::bulan($request->query('bulan')) ?? now()->startOfMonth();
        if ($bulan->gt(now())) {
            $bulan = now()->startOfMonth();
        }
        // Satu bulan penuh (tanggal mendatang otomatis berstatus "belum ada data") agar kalender lengkap.
        $hari = $pegawai->ikut_absensi && $bulan->lte(now())
            ? PenilaianAbsensi::rentang(collect([$pegawai]), $bulan, $bulan->copy()->endOfMonth())[$pegawai->id] ?? []
            : [];

        return [$pegawai, $bulan, $hari];
    }
}
