<?php

namespace App\Http\Controllers;

use App\Models\Lamaran;
use Illuminate\View\View;

class JadwalKandidatController extends Controller
{
    public function show(string $token): View
    {
        $lamaran = Lamaran::with([
            'dataPelamar', 'dataPegawai', 'lowongan',
            'interviewJadwals' => fn ($q) => $q->where('status', '!=', 'dibatalkan')->orderBy('tahap_interview')->orderBy('id'),
            'interviewJadwals.interviewer',
            'screeningOnboardings',
            'kontrakJadwals',
        ])->where('token_jadwal', $token)->firstOrFail();

        return view('jadwal-kandidat', compact('lamaran'));
    }
}
