<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LokasiKerja;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Master Lokasi Kerja: area/tempat kerja pegawai (dipakai di data pegawai, lowongan, dan kandidat langsung). */
class LokasiKerjaController extends Controller
{
    public function index(): View
    {
        return view('admin.lokasi-kerja.index', [
            'lokasi' => LokasiKerja::orderBy('urutan')->orderBy('nama')->get(),
            'pegawai' => DB::table('kepegawaians')->where('status_rekrutmen', 'Aktif')->whereNotNull('lokasi_kerja')
                ->groupBy('lokasi_kerja')->pluck(DB::raw('count(*)'), 'lokasi_kerja'),
            'lowongan' => DB::table('lowongans')->whereNotNull('lokasi_kerja')->groupBy('lokasi_kerja')->pluck(DB::raw('count(*)'), 'lokasi_kerja'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validasi($request);
        $lokasi = LokasiKerja::create($data + ['urutan' => (int) LokasiKerja::max('urutan') + 1]);

        return redirect()->route('admin.lokasi-kerja.index')->with('success', "Lokasi kerja {$lokasi->nama} ditambahkan.");
    }

    public function update(Request $request, LokasiKerja $lokasiKerja): RedirectResponse
    {
        $data = $this->validasi($request, $lokasiKerja);
        $lama = $lokasiKerja->nama;
        $jumlah = $lokasiKerja->ubahNama($data['nama']);
        $lokasiKerja->update(collect($data)->except('nama')->all());

        return redirect()->route('admin.lokasi-kerja.index')->with('success', $lama === $lokasiKerja->nama
            ? "Lokasi kerja {$lokasiKerja->nama} diperbarui."
            : "Lokasi kerja {$lama} diganti menjadi {$lokasiKerja->nama}." . ($jumlah ? " {$jumlah} data ikut diperbarui." : ''));
    }

    public function destroy(LokasiKerja $lokasiKerja): RedirectResponse
    {
        if ($pakai = array_sum($lokasiKerja->pemakaian())) {
            return back()->with('error', "Lokasi kerja {$lokasiKerja->nama} masih dipakai di {$pakai} data (pegawai, lowongan, kandidat). Ganti lokasinya dulu atau ubah namanya.");
        }
        $lokasiKerja->delete();

        return back()->with('success', "Lokasi kerja {$lokasiKerja->nama} dihapus.");
    }

    /** @return array{nama: string, urutan?: int, keterangan: ?string} */
    private function validasi(Request $request, ?LokasiKerja $lokasi = null): array
    {
        $request->merge(['nama' => mb_strtoupper(trim(preg_replace('/\s+/', ' ', (string) $request->input('nama'))))]);

        return $request->validate([
            'nama' => ['required', 'string', 'max:100', Rule::unique('lokasi_kerjas', 'nama')->ignore($lokasi?->id)],
            'urutan' => [$lokasi ? 'required' : 'nullable', 'integer', 'min:0', 'max:9999'],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ], [
            'nama.required' => 'Nama lokasi kerja wajib diisi.',
            'nama.unique' => 'Lokasi kerja dengan nama ini sudah ada.',
        ]);
    }
}
