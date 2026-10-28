<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DataPegawai;
use App\Models\User;
use App\Support\PegawaiKeluar;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/** Proses pegawai keluar (resign dll.) dan pembatalannya. Hak: kelola-pegawai (dijaga di route). */
class PegawaiKeluarController extends Controller
{
    public function store(Request $request, DataPegawai $pegawai): RedirectResponse
    {
        $v = $request->validateWithBag('keluar', [
            'jenis_keluar' => ['required', Rule::in(array_keys(PegawaiKeluar::JENIS))],
            'tanggal_keluar' => ['required', 'date', 'before_or_equal:today'],
            'alasan_keluar' => [$request->input('jenis_keluar') === 'lainnya' ? 'required' : 'nullable', 'string', 'max:1000'],
        ], [
            'jenis_keluar.required' => 'Pilih jenis keluar.',
            'tanggal_keluar.before_or_equal' => 'Tanggal keluar tidak boleh di masa depan. Proses pada hari terakhir bekerja.',
            'alasan_keluar.required' => 'Tulis alasan untuk jenis "Lainnya".',
        ]);

        /** @var User $oleh */
        $oleh = Auth::user();
        $hasil = PegawaiKeluar::proses($pegawai->load('kepegawaian'), $v['tanggal_keluar'], $v['jenis_keluar'], $v['alasan_keluar'] ?? null, $oleh);

        return redirect()->route('admin.pegawai.show', $pegawai)
            ->with('success', "{$pegawai->nama_lengkap} diproses keluar (" . PegawaiKeluar::JENIS[$v['jenis_keluar']] . ').')
            ->with('peringatan_keluar', $hasil);
    }

    public function destroy(DataPegawai $pegawai): RedirectResponse
    {
        $hasil = PegawaiKeluar::batalkan($pegawai->load('kepegawaian'));

        return redirect()->route('admin.pegawai.show', $pegawai)
            ->with('success', "Proses keluar {$pegawai->nama_lengkap} dibatalkan — pegawai aktif kembali.")
            ->with('peringatan_keluar', $hasil);
    }
}
