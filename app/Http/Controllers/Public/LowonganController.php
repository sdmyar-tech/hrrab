<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Lamaran;
use App\Models\Lowongan;
use App\Support\Urutan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LowonganController extends Controller
{
    public function index(Request $request): View
    {
        Lowongan::tutupYangKedaluwarsa();

        $query = Lowongan::terbuka()->latest('dipublikasikan_pada');

        if ($request->filled('q')) {
            $keyword = $request->string('q');
            $query->where(function ($q) use ($keyword) {
                $q->where('judul', 'like', "%{$keyword}%")
                    ->orWhere('unit_kerja', 'like', "%{$keyword}%")
                    ->orWhere('jabatan', 'like', "%{$keyword}%");
            });
        }

        if ($request->filled('unit_kerja')) {
            $query->where('unit_kerja', $request->string('unit_kerja'));
        }

        $unitKerjaOptions = Lowongan::terbuka()->distinct()->orderBy('unit_kerja')->pluck('unit_kerja');

        $urut = Urutan::terapkan($query, [
            ...Urutan::tanggal('terbit', 'Dipublikasikan', 'lowongans.dipublikasikan_pada'),
            // "Segera tutup" = tanggal tutup paling dekat di atas.
            'tutup' => ['Segera ditutup', 'lowongans.tanggal_tutup', 'asc'],
            ...Urutan::abjad('judul', 'Judul', 'lowongans.judul'),
            ...Urutan::abjad('unit', 'Unit', 'lowongans.unit_kerja'),
        ]);
        $lowongans = $query->paginate(12)->withQueryString();

        return view('public.lowongan.index', compact('lowongans', 'unitKerjaOptions') + $urut);
    }

    public function show(Lowongan $lowongan): View
    {
        if (Lowongan::tutupYangKedaluwarsa()) {
            $lowongan->refresh();
        }

        if (! in_array($lowongan->status, [Lowongan::STATUS_DIPUBLIKASIKAN, Lowongan::STATUS_DITUTUP], true)) {
            abort(404);
        }

        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        $siapMelamar = $user?->isPelamar() && $user->dataPelamar?->isLengkap();
        if (! $siapMelamar && $lowongan->isMenerimaLamaran()) {
            session(['lowongan_tujuan' => route('lowongan.show', $lowongan)]);
        }

        $sudahMelamar = $user?->data_pelamar_id
            ? Lamaran::where('data_pelamar_id', $user->data_pelamar_id)
                ->where('lowongan_id', $lowongan->id)
                ->whereNotIn('status', [Lamaran::STATUS_DITOLAK, Lamaran::STATUS_DIBATALKAN])
                ->exists()
            : false;

        return view('public.lowongan.show', compact('lowongan', 'sudahMelamar'));
    }
}
