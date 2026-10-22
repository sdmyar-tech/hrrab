<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Jabatan;
use App\Models\Unit;
use App\Support\ExcelExporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class JabatanController extends Controller
{
    public function index(Request $request): View
    {
        // Pencarian & penyaringan dilakukan langsung di halaman (?q= dipakai sebagai isian awal kotak cari).
        $rincian = $this->rincianPemakaian();

        return view('admin.jabatan.index', [
            'jabatans' => Jabatan::with('units:id,nama')->orderBy('nama')->get(),
            'semuaJabatan' => Jabatan::orderBy('nama')->get(['id', 'nama']),
            'units' => Unit::orderBy('urutan')->orderBy('nama')->get(['id', 'nama']),
            'dipakai' => array_map('array_sum', $rincian),
            'rincian' => $rincian,
        ]);
    }

    public function export(): BinaryFileResponse
    {
        $dipakai = $this->hitungPemakaian();
        $rows = Jabatan::with('units:id,nama')->orderBy('nama')->get()
            ->map(fn (Jabatan $j, $i) => [$i + 1, $j->nama, $j->units->pluck('nama')->implode(', '), $dipakai[$j->nama] ?? 0]);

        return ExcelExporter::download(ExcelExporter::namaFile('master-jabatan'), ['No', 'Nama Jabatan', 'Unit', 'Dipakai'], $rows, 'Master Jabatan');
    }

    public function store(Request $request): RedirectResponse
    {
        $nama = $this->validasiNama($request);
        $unitId = $request->validate(['unit_id' => ['nullable', 'integer', Rule::exists('units', 'id')]])['unit_id'] ?? null;

        $jabatan = Jabatan::create(['nama' => $nama]);
        $jabatan->aturUnit($unitId ? [$unitId] : []);

        return $this->kembali($jabatan)->with('success', "Jabatan {$nama} ditambahkan" . ($unitId ? ' di unit ' . Unit::find($unitId)?->nama : ' (belum ditautkan ke unit)') . '.');
    }

    public function update(Request $request, Jabatan $jabatan): RedirectResponse
    {
        // Galat form ubah memakai bag "ubah" supaya modal yang benar dibuka lagi (bukan form tambah).
        $nama = $this->validasiNama($request, $jabatan->id, 'ubah');
        $unitIds = $request->validateWithBag('ubah', [
            'unit_ids' => ['nullable', 'array'],
            'unit_ids.*' => ['integer', Rule::exists('units', 'id')],
        ])['unit_ids'] ?? [];
        $lama = $jabatan->nama;
        $unitLama = $jabatan->units()->pluck('units.id')->sort()->values()->all();

        $pesan = [];
        if ($nama !== $lama) {
            $dipakai = $jabatan->jumlahDipakai();
            $jabatan->ubahNama($nama);
            $pesan[] = "Jabatan {$lama} diubah menjadi {$nama}." . ($dipakai ? " {$dipakai} data (pegawai, mutasi, lowongan, formasi, lamaran) ikut diperbarui." : '');
        }
        // Unit hanya disinkronkan bila bagian unit ikut dikirim dari form ubah.
        if ($request->boolean('unit_dikirim') && collect($unitIds)->map('intval')->sort()->values()->all() !== $unitLama) {
            $jabatan->aturUnit($unitIds);
            $pesan[] = 'Unit jabatan ' . $jabatan->nama . ': ' . ($jabatan->units()->pluck('nama')->implode(', ') ?: 'tidak ada') . '.';
        }

        return $this->kembali($jabatan)->with($pesan ? 'success' : 'info', $pesan ? implode(' ', $pesan) : 'Tidak ada perubahan.');
    }

    /** Kembali ke daftar, menyorot & menggulir ke jabatan yang baru diubah, dengan pencarian sebelumnya. */
    private function kembali(Jabatan $jabatan): RedirectResponse
    {
        $q = request()->input('kembali_q');

        return redirect()->to(route('admin.jabatan.index', array_filter(['q' => $q])) . '#jabatan-' . $jabatan->id)
            ->with('jabatan_disorot', $jabatan->id);
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

    private function validasiNama(Request $request, ?int $abaikanId = null, string $bag = 'default'): string
    {
        $request->merge(['nama' => mb_strtoupper(trim(preg_replace('/\s+/', ' ', (string) $request->input('nama'))))]);

        return $request->validateWithBag($bag, [
            'nama' => ['required', 'string', 'max:150', Rule::unique('jabatans', 'nama')->ignore($abaikanId)],
        ], [
            'nama.required' => 'Nama jabatan wajib diisi.',
            'nama.unique' => 'Jabatan dengan nama ini sudah ada. Gunakan "Gabungkan" bila keduanya jabatan yang sama.',
        ])['nama'];
    }

    /** @return array<string, int> nama jabatan => jumlah data yang memakainya */
    private function hitungPemakaian(): array
    {
        return array_map('array_sum', $this->rincianPemakaian());
    }

    /**
     * Pemakaian per jabatan, dirinci per sumber data (untuk info dampak di modal ubah/hapus).
     *
     * @return array<string, array<string, int>> nama jabatan => [label sumber => jumlah]
     */
    private function rincianPemakaian(): array
    {
        $sumber = [
            'Pegawai' => ['kepegawaians', 'jabatan'], 'Jabatan tambahan' => ['kepegawaians', 'jabatan_tambahan'],
            'Mutasi/rangkap' => ['penugasans', 'jabatan'], 'Lowongan' => ['lowongans', 'jabatan'],
            'Formasi' => ['pengajuan_formasis', 'jabatan'], 'Lamaran' => ['lamarans', 'jabatan_tujuan'],
        ];

        $hasil = [];
        foreach ($sumber as $label => [$tabel, $kolom]) {
            $baris = DB::table($tabel)->whereNotNull($kolom)->groupBy($kolom)
                ->selectRaw("{$kolom} as nama, count(*) as jumlah")->pluck('jumlah', 'nama');

            foreach ($baris as $nama => $jumlah) {
                $hasil[$nama][$label] = (int) $jumlah;
            }
        }

        return $hasil;
    }
}
