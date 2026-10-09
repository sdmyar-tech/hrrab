<?php

namespace App\Http\Controllers;

use App\Support\Absensi\PenilaianAbsensi;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/** Absensi milik pegawai yang sedang login (hanya lihat). */
class AbsensiSayaController extends Controller
{
    public function index(Request $request): View
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $pegawai = $user->dataPegawai;
        abort_unless($pegawai, 404);

        try {
            $bulan = Carbon::createFromFormat('!Y-m-d', $request->query('bulan', now()->format('Y-m')) . '-01');
        } catch (\Throwable) {
            $bulan = now()->startOfMonth();
        }
        // Satu bulan penuh (tanggal mendatang otomatis berstatus "belum ada data") agar kalender lengkap.
        $hari = $pegawai->ikut_absensi && $bulan->lte(now())
            ? PenilaianAbsensi::rentang(collect([$pegawai]), $bulan, $bulan->copy()->endOfMonth())[$pegawai->id] ?? []
            : [];

        return view('absensi-saya', [
            'layout' => $user->panelLayout(),
            'pegawai' => $pegawai->load('jadwalKerja'),
            'bulan' => $bulan,
            'hari' => array_reverse($hari),
            'rekap' => PenilaianAbsensi::rekap($hari),
        ]);
    }
}
