<?php

namespace App\Http\Controllers;

use App\Models\InterviewJadwal;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class InterviewJadwalController extends Controller
{
    public function milikSaya(): View
    {
        abort_unless(Auth::user()?->bisaMenginterview(), 403, 'Menu ini hanya untuk Kepala SDM, GM, dan Kepala Bagian.');

        $jadwals = InterviewJadwal::with(['lamaran.dataPelamar', 'lamaran.dataPegawai', 'lamaran.lowongan'])
            ->where('interviewer_id', Auth::id())
            ->orderByDesc('tanggal')
            ->get();

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $layout = $user->panelLayout();

        return view('interview-saya', compact('jadwals', 'layout'));
    }
}
