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
        // Pencarian & penyaringan dilakukan langsung di halaman (?q= dipakai sebagai isian awal kotak cari).
        return view('admin.jabatan.index', [
            'jabatans' => Jabatan::orderBy('nama')->get(),
            'semuaJabatan' => Jabatan::orderBy('nama')->get(['id', 'nama']),
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

    public function destroy(Request $request, Jabatan $jabatan): RedirectResponse
    {
        $request->validate([
            'pengganti_id' => ['nullable', 'integer', Rule::exists('jabatans', 'id'), Rule::notIn([$jabatan->id])],
        ], ['pengganti_id.not_in' => 'Jabatan pengganti tidak boleh jabatan yang sama.']);

        $dipakai = $jabatan->jumlahDipakai();
        $pengganti = $request->filled('pengganti_id') ? Jabatan::find($request->integer('pengganti_id')) : null;

        // Jabatan yang masih dipakai wajib diberi pengganti, supaya data pegawai tidak memakai jabatan yang sudah tidak ada.
        if ($dipakai > 0 && ! $pengganti) {
            return back()->with('error', "Jabatan {$jabatan->nama} masih dipakai di {$dipakai} data. Pilih jabatan pengganti untuk memindahkan data tersebut.");
        }

        $dipindah = $jabatan->hapusDanGanti($pengganti);

        return back()->with('success', "Jabatan {$jabatan->nama} dihapus."
            . ($pengganti ? " {$dipindah} data dipindahkan ke {$pengganti->nama}." : ''));
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
