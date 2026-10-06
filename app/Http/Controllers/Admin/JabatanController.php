<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Jabatan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use App\Support\ExcelExporter;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class JabatanController extends Controller
{
    public function index(Request $request): View
    {
        $query = Jabatan::orderBy('nama');

        if ($cari = $request->query('q')) {
            $query->where('nama', 'like', "%{$cari}%");
        }

        return view('admin.jabatan.index', [
            'jabatans' => $query->get(),
            'dipakai' => $this->hitungPemakaian(),
        ]);
    }

    public function export(): BinaryFileResponse
    {
        $dipakai = $this->hitungPemakaian();
        $rows = Jabatan::orderBy('nama')->get()->map(fn (Jabatan $j, $i) => [$i + 1, $j->nama, $dipakai[$j->nama] ?? 0]);

        return ExcelExporter::download(ExcelExporter::namaFile('master-jabatan'), ['No', 'Nama Jabatan', 'Dipakai'], $rows, 'Master Jabatan');
    }

    public function store(Request $request): RedirectResponse
    {
        $nama = $this->validasiNama($request);

        Jabatan::create(['nama' => $nama]);

        return back()->with('success', "Jabatan {$nama} ditambahkan.");
    }

    public function update(Request $request, Jabatan $jabatan): RedirectResponse
    {
        $nama = $this->validasiNama($request, $jabatan->id);

        $jabatan->ubahNama($nama);

        return back()->with('success', "Jabatan diubah menjadi {$nama}. Data pegawai, penugasan, lowongan & formasi ikut diperbarui.");
    }

    public function destroy(Jabatan $jabatan): RedirectResponse
    {
        if (($jumlah = $jabatan->jumlahDipakai()) > 0) {
            return back()->with('error', "Jabatan {$jabatan->nama} masih dipakai di {$jumlah} data. Ganti dulu jabatannya, atau ubah namanya saja.");
        }

        $jabatan->delete();

        return back()->with('success', "Jabatan {$jabatan->nama} dihapus.");
    }

    private function validasiNama(Request $request, ?int $abaikanId = null): string
    {
        $request->merge(['nama' => mb_strtoupper(trim(preg_replace('/\s+/', ' ', (string) $request->input('nama'))))]);

        return $request->validate([
            'nama' => ['required', 'string', 'max:150', Rule::unique('jabatans', 'nama')->ignore($abaikanId)],
        ], [
            'nama.unique' => 'Jabatan dengan nama ini sudah ada.',
        ])['nama'];
    }

    /** @return array<string, int> nama jabatan => jumlah data yang memakainya */
    private function hitungPemakaian(): array
    {
        $sumber = [
            ['kepegawaians', 'jabatan'], ['kepegawaians', 'jabatan_tambahan'], ['penugasans', 'jabatan'],
            ['lowongans', 'jabatan'], ['pengajuan_formasis', 'jabatan'], ['lamarans', 'jabatan_tujuan'],
        ];

        $total = [];
        foreach ($sumber as [$tabel, $kolom]) {
            $baris = DB::table($tabel)->whereNotNull($kolom)->groupBy($kolom)
                ->selectRaw("{$kolom} as nama, count(*) as jumlah")->pluck('jumlah', 'nama');

            foreach ($baris as $nama => $jumlah) {
                $total[$nama] = ($total[$nama] ?? 0) + $jumlah;
            }
        }

        return $total;
    }
}
