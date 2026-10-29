<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UnitOrganisasi;
use App\Support\ExcelExporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/** Master Unit (tanpa koordinator): pengelompokan pegawai yang dipilih di data kepegawaian. */
class UnitOrganisasiController extends Controller
{
    public function index(): View
    {
        return view('admin.unit-organisasi.index', $this->data());
    }

    public function export(): BinaryFileResponse
    {
        ['unit' => $unit, 'pegawai' => $pegawai] = $this->data();
        $rows = $unit->values()->map(fn (UnitOrganisasi $u, $i) => [$i + 1, $u->nama, $u->keterangan, $pegawai[$u->nama] ?? 0]);

        return ExcelExporter::download(ExcelExporter::namaFile('master-unit'), ['No', 'Unit', 'Keterangan', 'Pegawai Aktif'], $rows, 'Unit');
    }

    /** Daftar unit + jumlah pegawai aktif (halaman & ekspor). */
    private function data(): array
    {
        return [
            'unit' => UnitOrganisasi::orderBy('urutan')->orderBy('nama')->get(),
            'pegawai' => DB::table('kepegawaians')->where('status_rekrutmen', 'Aktif')->whereNotNull('unit_organisasi')
                ->groupBy('unit_organisasi')->pluck(DB::raw('count(*)'), 'unit_organisasi'),
        ];
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validasi($request);
        $unit = UnitOrganisasi::create($data + ['urutan' => (int) UnitOrganisasi::max('urutan') + 1]);

        return redirect()->route('admin.master-unit.index')->with('success', "Unit {$unit->nama} ditambahkan.");
    }

    public function update(Request $request, UnitOrganisasi $unitOrganisasi): RedirectResponse
    {
        $data = $this->validasi($request, $unitOrganisasi);
        $lama = $unitOrganisasi->nama;
        $jumlah = $unitOrganisasi->ubahNama($data['nama']);
        $unitOrganisasi->update(collect($data)->except('nama')->all());

        return redirect()->route('admin.master-unit.index')->with('success', $lama === $unitOrganisasi->nama
            ? "Unit {$unitOrganisasi->nama} diperbarui."
            : "Unit {$lama} diganti menjadi {$unitOrganisasi->nama}." . ($jumlah ? " {$jumlah} data pegawai ikut diperbarui." : ''));
    }

    public function destroy(UnitOrganisasi $unitOrganisasi): RedirectResponse
    {
        if ($pakai = array_sum($unitOrganisasi->pemakaian())) {
            return back()->with('error', "Unit {$unitOrganisasi->nama} masih dipakai {$pakai} pegawai. Ganti unit pegawai tersebut dulu atau ubah nama unitnya.");
        }
        $unitOrganisasi->delete();

        return back()->with('success', "Unit {$unitOrganisasi->nama} dihapus.");
    }

    /** @return array{nama: string, urutan?: int, keterangan: ?string} */
    private function validasi(Request $request, ?UnitOrganisasi $unit = null): array
    {
        $request->merge(['nama' => mb_strtoupper(trim(preg_replace('/\s+/', ' ', (string) $request->input('nama'))))]);

        return $request->validate([
            'nama' => ['required', 'string', 'max:100', Rule::unique('unit_organisasis', 'nama')->ignore($unit?->id)],
            'urutan' => [$unit ? 'required' : 'nullable', 'integer', 'min:0', 'max:9999'],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ], [
            'nama.required' => 'Nama unit wajib diisi.',
            'nama.unique' => 'Unit dengan nama ini sudah ada.',
        ]);
    }
}
