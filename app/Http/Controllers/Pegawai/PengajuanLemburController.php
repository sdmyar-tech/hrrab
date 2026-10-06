<?php

namespace App\Http\Controllers\Pegawai;

use App\Http\Controllers\Controller;
use App\Models\PengajuanLembur;
use App\Support\PengajuanWorkflow;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use App\Support\Notifikasi;

class PengajuanLemburController extends Controller
{
    public function index(): View
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $pegawai = $user->dataPegawai;

        $pengajuanLemburs = $pegawai
            ? $pegawai->pengajuanLemburs()->latest('tanggal')->latest('id')->get()
            : collect();

        $unitOptions = $user->unitsLembur();

        return view('pegawai.pengajuan-lembur', compact('pengajuanLemburs', 'unitOptions'));
    }

    public function store(Request $request): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $pegawai = $user->dataPegawai;

        if (! $pegawai) {
            return redirect()
                ->route('pegawai.pengajuan-lembur')
                ->with('error', 'Data pegawai Anda belum lengkap. Lengkapi data terlebih dahulu.');
        }

        $unitOptions = $user->unitsLembur();

        $validated = $request->validate([
            'unit_kerja' => [count($unitOptions) > 1 ? 'required' : 'nullable', 'string', Rule::in($unitOptions)],
            'tanggal' => ['required', 'date'],
            'uraian_tugas' => ['required', 'string', 'max:1000'],
            'jam_mulai' => ['required', 'date_format:H:i'],
            'jam_selesai' => ['required', 'date_format:H:i', 'after:jam_mulai'],
        ], [
            'unit_kerja.required' => 'Pilih unit tempat Anda lembur.',
            'unit_kerja.in' => 'Unit tidak valid untuk pengajuan lembur Anda.',
            'tanggal.required' => 'Tanggal wajib diisi.',
            'uraian_tugas.required' => 'Uraian tugas wajib diisi.',
            'jam_mulai.required' => 'Jam mulai wajib diisi.',
            'jam_selesai.required' => 'Jam selesai wajib diisi.',
            'jam_selesai.after' => 'Jam selesai harus setelah jam mulai.',
        ]);

        $mulai = Carbon::createFromFormat('H:i', $validated['jam_mulai']);
        $selesai = Carbon::createFromFormat('H:i', $validated['jam_selesai']);
        $jumlahJam = round($mulai->diffInMinutes($selesai) / 60, 2);

        $unit = $validated['unit_kerja'] ?? $unitOptions[0];

        if ($error = PengajuanWorkflow::cekApprover($user, $unit, lembur: true)) {
            return redirect()->back()->withInput()->with('error', $error);
        }

        $pengajuan = $pegawai->pengajuanLemburs()->create([
            'unit_kerja' => $unit,
            'tanggal' => $validated['tanggal'],
            'hari' => Carbon::parse($validated['tanggal'])->translatedFormat('l'),
            'uraian_tugas' => $validated['uraian_tugas'],
            'jam_mulai' => $validated['jam_mulai'],
            'jam_selesai' => $validated['jam_selesai'],
            'jumlah_jam' => $jumlahJam,
            'status' => PengajuanLembur::STATUS_PENDING,
            'current_level' => PengajuanWorkflow::initialLevel($user, $unit, lembur: true),
        ]);
        Notifikasi::pengajuanMenunggu($pengajuan);

        return redirect()
            ->route('pegawai.pengajuan-lembur')
            ->with('success', 'Pengajuan lembur berhasil dikirim. Menunggu persetujuan admin.');
    }
}
