<?php

namespace App\Http\Controllers\Pelamar;

use App\Http\Controllers\Controller;
use App\Models\Lamaran;
use App\Models\Lowongan;
use App\Support\Urutan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LamaranController extends Controller
{
    public function index(): View
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $urut = ['urutOpsi' => [], 'urutAktif' => null];
        $lamarans = collect();

        if ($user->dataPelamar) {
            $query = $user->dataPelamar->lamarans()->with('lowongan');
            $urut = Urutan::terapkan($query, [
                ...Urutan::tanggal('dikirim', 'Dikirim', 'lamarans.created_at'),
                ...Urutan::tanggal('diperbarui', 'Diperbarui', 'lamarans.updated_at'),
                ...Urutan::abjad('posisi', 'Posisi', 'coalesce(lamarans.jabatan_tujuan, (select judul from lowongans where lowongans.id = lamarans.lowongan_id))'),
            ]);
            $lamarans = $query->get();
        }

        return view('pelamar.lamaran.index', compact('lamarans') + $urut);
    }

    public function apply(Lowongan $lowongan): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (! $user->dataPelamar || ! $user->dataPelamar->isLengkap()) {
            return redirect()
                ->route('pelamar.data-pelamar.edit')
                ->with('error', 'Lengkapi data pelamar terlebih dahulu sebelum melamar.');
        }

        if (! $lowongan->isMenerimaLamaran()) {
            return redirect()->back()->with('error', 'Lowongan ini sudah tidak menerima lamaran.');
        }

        $sedangProsesLanjut = Lamaran::where('data_pelamar_id', $user->data_pelamar_id)
            ->where('status', Lamaran::STATUS_PROSES)
            ->whereIn('tahap', [Lamaran::TAHAP_INTERVIEW, Lamaran::TAHAP_SCREENING, Lamaran::TAHAP_KONTRAK])
            ->exists();

        if ($sedangProsesLanjut) {
            return redirect()->back()->with('error', 'Anda sedang dalam tahap interview/screening/kontrak pada lamaran lain. Selesaikan proses tersebut terlebih dahulu sebelum melamar lowongan baru.');
        }

        $sudahMelamar = Lamaran::where('data_pelamar_id', $user->data_pelamar_id)
            ->where('lowongan_id', $lowongan->id)
            ->whereNotIn('status', [Lamaran::STATUS_DITOLAK, Lamaran::STATUS_DIBATALKAN])
            ->exists();

        if ($sudahMelamar) {
            return redirect()->back()->with('error', 'Anda sudah melamar pada lowongan ini.');
        }

        Lamaran::create([
            'data_pelamar_id' => $user->data_pelamar_id,
            'lowongan_id' => $lowongan->id,
            'status' => Lamaran::STATUS_MENUNGGU_VERIFIKASI,
            'tahap' => Lamaran::TAHAP_VERIFIKASI,
        ]);

        return redirect()
            ->route('pelamar.lamaran.index')
            ->with('success', 'Lamaran berhasil dikirim. Menunggu verifikasi Admin/SDM.');
    }

    public function show(Lamaran $lamaran): View
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($lamaran->data_pelamar_id !== $user->data_pelamar_id) {
            abort(403);
        }

        $lamaran->load(['lowongan', 'interviewJadwals.interviewer', 'screeningOnboardings', 'kontrakJadwals']);

        return view('pelamar.lamaran.show', compact('lamaran'));
    }

    public function tolak(Lamaran $lamaran): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($lamaran->data_pelamar_id !== $user->data_pelamar_id) {
            abort(403);
        }

        if ($lamaran->isSelesai()) {
            return redirect()->back()->with('error', 'Lamaran ini sudah memiliki keputusan akhir.');
        }

        $lamaran->update([
            'status' => Lamaran::STATUS_DIBATALKAN,
            'keputusan' => Lamaran::KEPUTUSAN_TOLAK_PELAMAR,
            'tahap' => Lamaran::TAHAP_SELESAI,
            'diputuskan_oleh' => $user->id,
            'diputuskan_pada' => now(),
        ]);

        return redirect()
            ->route('pelamar.lamaran.index')
            ->with('success', 'Lamaran dibatalkan.');
    }
}
