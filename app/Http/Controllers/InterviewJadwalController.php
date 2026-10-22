<?php

namespace App\Http\Controllers;

use App\Models\InterviewJadwal;
use App\Models\User;
use App\Support\Urutan;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class InterviewJadwalController extends Controller
{
    public function milikSaya(): View
    {
        abort_unless(Auth::user()?->bisaMenginterview(), 403, 'Menu ini hanya untuk Kepala SDM, GM, dan Kepala Bagian.');

        $query = InterviewJadwal::with(['lamaran.dataPelamar', 'lamaran.dataPegawai', 'lamaran.lowongan'])
            ->where('interviewer_id', Auth::id());

        // Nama kandidat: pelamar umum atau kandidat internal (pegawai).
        $namaKandidat = '(select coalesce('
            . '(select nama_lengkap from data_pelamars where data_pelamars.id = lamarans.data_pelamar_id), '
            . '(select nama_lengkap from data_pegawais where data_pegawais.id = lamarans.data_pegawai_id)) '
            . 'from lamarans where lamarans.id = interview_jadwals.lamaran_id)';
        $urut = Urutan::terapkan($query, [
            'jadwal_baru' => ['Jadwal terbaru', fn ($q, $arah) => $q->orderBy('interview_jadwals.tanggal', $arah)->orderBy('interview_jadwals.waktu_mulai', $arah), 'desc'],
            'jadwal_lama' => ['Jadwal terlama', fn ($q, $arah) => $q->orderBy('interview_jadwals.tanggal', $arah)->orderBy('interview_jadwals.waktu_mulai', $arah), 'asc'],
            ...Urutan::abjad('nama', 'Nama kandidat', $namaKandidat),
        ]);
        $jadwals = $query->get();

        /** @var User $user */
        $user = Auth::user();
        $layout = $user->panelLayout();

        return view('interview-saya', compact('jadwals', 'layout') + $urut);
    }
}
