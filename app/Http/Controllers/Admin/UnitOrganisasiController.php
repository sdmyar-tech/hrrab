<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Unit;
use App\Models\UnitOrganisasi;
use App\Support\ExcelExporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/** Master Unit (tanpa koordinator): membawahi bagian; unit pegawai terisi otomatis dari bagiannya. */
class UnitOrganisasiController extends Controller
{
    public function index(): View
    {
        return view('admin.unit-organisasi.index', $this->data());
    }

    public function export(): BinaryFileResponse
    {
        ['unit' => $unit, 'pegawai' => $pegawai] = $this->data();
        $rows = $unit->values()->map(fn (UnitOrganisasi $u, $i) => [$i + 1, $u->nama, $u->bagians->pluck('nama')->implode(', '), $u->keterangan, $pegawai[$u->nama] ?? 0]);

        return ExcelExporter::download(ExcelExporter::namaFile('master-unit'), ['No', 'Unit', 'Bagian', 'Keterangan', 'Pegawai Aktif'], $rows, 'Unit');
    }

    /** Daftar unit (+ bagian yang dibawahi) + jumlah pegawai aktif + semua bagian untuk pilihan (halaman & ekspor). */
    private function data(): array
    {
        return [
            'unit' => UnitOrganisasi::with('bagians:id,nama,unit_organisasi_id')->orderBy('urutan')->orderBy('nama')->get(),
            'pegawai' => DB::table('kepegawaians')->where('status_rekrutmen', 'Aktif')->whereNotNull('unit_organisasi')
                ->groupBy('unit_organisasi')->pluck(DB::raw('count(*)'), 'unit_organisasi'),
            'bagian' => Unit::with('unitOrganisasi:id,nama')->orderBy('urutan')->orderBy('nama')->get(['id', 'nama', 'unit_organisasi_id']),
        ];
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validasi($request);
        $unit = UnitOrganisasi::create(collect($data)->except('bagian_ids')->all() + ['urutan' => (int) UnitOrganisasi::max('urutan') + 1]);
        $n = $unit->aturBagian($data['bagian_ids'] ?? []);

        return redirect()->route('admin.master-unit.index')->with('success', "Unit {$unit->nama} ditambahkan." . $this->pesanBagian($unit, $n));
    }

    public function update(Request $request, UnitOrganisasi $unitOrganisasi): RedirectResponse
    {
        $data = $this->validasi($request, $unitOrganisasi);
        $lama = $unitOrganisasi->nama;
        $jumlah = $unitOrganisasi->ubahNama($data['nama']);
        $unitOrganisasi->update(collect($data)->except(['nama', 'bagian_ids'])->all());
        $n = $unitOrganisasi->aturBagian($data['bagian_ids'] ?? []);

        return redirect()->route('admin.master-unit.index')->with('success', ($lama === $unitOrganisasi->nama
            ? "Unit {$unitOrganisasi->nama} diperbarui."
            : "Unit {$lama} diganti menjadi {$unitOrganisasi->nama}." . ($jumlah ? " {$jumlah} data pegawai ikut diperbarui." : ''))
            . $this->pesanBagian($unitOrganisasi, $n));
    }

    private function pesanBagian(UnitOrganisasi $unit, int $diperbarui): string
    {
        $bagian = $unit->bagians()->pluck('nama');

        return ($bagian->isNotEmpty() ? ' Bagian: ' . $bagian->implode(', ') . '.' : '')
            . ($diperbarui ? " Unit {$diperbarui} data pegawai disesuaikan dengan bagiannya." : '');
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
            // Bagian yang dibawahi unit ini (bagian dari unit lain ikut pindah ke unit ini).
            'bagian_ids' => ['nullable', 'array'],
            'bagian_ids.*' => ['integer', Rule::exists('units', 'id')],
        ], [
            'nama.required' => 'Nama unit wajib diisi.',
            'nama.unique' => 'Unit dengan nama ini sudah ada.',
        ]);
    }
}
