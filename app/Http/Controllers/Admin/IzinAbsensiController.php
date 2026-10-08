<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DataPegawai;
use App\Models\IzinAbsensi;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Izin telat / pulang cepat (mis. hujan deras) untuk satu/beberapa pegawai, satu unit, atau semua. */
class IzinAbsensiController extends Controller
{
    public function index(Request $request): View
    {
        try {
            $bulan = Carbon::createFromFormat('!Y-m-d', $request->query('bulan', now()->format('Y-m')) . '-01');
        } catch (\Throwable) {
            $bulan = now()->startOfMonth();
        }

        return view('admin.absensi.izin', [
            'layout' => $request->user()->panelLayout(),
            'bulan' => $bulan,
            'izins' => IzinAbsensi::with(['dataPegawai', 'pembuat'])
                ->whereBetween('tanggal', [$bulan->toDateString(), $bulan->copy()->endOfMonth()->toDateString()])
                ->orderByDesc('tanggal')->orderByDesc('id')->get(),
            'pegawais' => DataPegawai::where('ikut_absensi', true)->orderBy('nama_lengkap')->get(['id', 'nama_lengkap', 'nip']),
            'unitOpsi' => DB::table('kepegawaians')->join('data_pegawais', 'data_pegawais.id', '=', 'kepegawaians.data_pegawai_id')
                ->where('data_pegawais.ikut_absensi', true)->whereNotNull('unit_kerja')->distinct()->orderBy('unit_kerja')->pluck('unit_kerja'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $v = $request->validate([
            'tanggal' => ['required', 'date'],
            'jenis' => ['required', Rule::in(array_keys(IzinAbsensi::JENIS))],
            'menit' => ['required', 'integer', 'min:1', 'max:480'],
            'sasaran' => ['required', Rule::in(['semua', 'unit', 'pegawai'])],
            'unit_kerja' => ['required_if:sasaran,unit', 'nullable', 'string', 'max:255'],
            'pegawai_ids' => ['required_if:sasaran,pegawai', 'nullable', 'array'],
            'pegawai_ids.*' => ['integer', 'exists:data_pegawais,id'],
            'alasan' => ['required', 'string', 'max:255'],
        ], [
            'unit_kerja.required_if' => 'Pilih unit.',
            'pegawai_ids.required_if' => 'Pilih minimal satu pegawai.',
            'alasan.required' => 'Isi alasan, mis. "Hujan deras".',
        ]);

        $dasar = ['tanggal' => $v['tanggal'], 'jenis' => $v['jenis'], 'menit' => $v['menit'], 'alasan' => $v['alasan'], 'dibuat_oleh' => Auth::id()];

        $jumlah = match ($v['sasaran']) {
            'semua' => IzinAbsensi::create($dasar) ? 1 : 0,
            'unit' => IzinAbsensi::create($dasar + ['unit_kerja' => $v['unit_kerja']]) ? 1 : 0,
            'pegawai' => collect($v['pegawai_ids'])->unique()->each(fn ($id) => IzinAbsensi::create($dasar + ['data_pegawai_id' => $id]))->count(),
        };

        $sasaran = ['semua' => 'semua pegawai', 'unit' => 'unit ' . ($v['unit_kerja'] ?? ''), 'pegawai' => "{$jumlah} pegawai"][$v['sasaran']];

        return redirect()->route('admin.absensi.izin', ['bulan' => Carbon::parse($v['tanggal'])->format('Y-m')])
            ->with('success', IzinAbsensi::JENIS[$v['jenis']] . " {$v['menit']} menit untuk {$sasaran} pada " . Carbon::parse($v['tanggal'])->translatedFormat('d M Y') . ' disimpan.');
    }

    public function destroy(IzinAbsensi $izin): RedirectResponse
    {
        $izin->delete();

        return back()->with('success', 'Izin dihapus.');
    }
}
