<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DataPegawai;
use App\Models\Jabatan;
use App\Models\Penugasan;
use App\Models\Unit;
use App\Models\User;
use App\Support\ExcelExporter;
use App\Support\PenugasanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Master Unit: daftar unit kerja + koordinator (kepala) tiap unit.
 * Koordinator dicatat sebagai penugasan kepala (rangkap bila unit utamanya lain), jadi terhubung ke Mutasi & Rangkap.
 */
class UnitController extends Controller
{
    public function index(): View
    {
        $units = Unit::withCount('jabatans')->orderBy('urutan')->orderBy('nama')->get();
        $kepala = Penugasan::aktif()->kepala()->with('dataPegawai:id,nama_lengkap')->get()->groupBy('unit_kerja');
        $anggota = Penugasan::aktif()->select('unit_kerja', DB::raw('count(distinct data_pegawai_id) as n'))->groupBy('unit_kerja')->pluck('n', 'unit_kerja');
        // GM juga bisa jadi koordinator/kepala unit (yang punya data pegawai → rangkap; yang tidak → dicatat di akun).
        $gms = User::with('dataPegawai.kepegawaian')->where('role', User::ROLE_GM)->orderBy('name')->get();

        return view('admin.unit.index', [
            'units' => $units,
            'kepala' => $kepala,
            'anggota' => $anggota,
            // GM yang memimpin unit tanpa data pegawai (diatur di Kelola Akun) — ditampilkan saja.
            'gmKepala' => $units->mapWithKeys(fn (Unit $u) => [$u->nama => User::gmKepalaTanpaPegawai($u->nama)->get(['id', 'name'])]),
            'pegawaiOpsi' => DataPegawai::with('kepegawaian:id,data_pegawai_id,unit_kerja,jabatan')
                ->whereHas('kepegawaian', fn ($q) => $q->where('status_rekrutmen', 'Aktif'))
                ->whereNotIn('id', $gms->pluck('data_pegawai_id')->filter())   // GM sudah tampil di grup GM
                ->orderBy('nama_lengkap')->get(['id', 'nama_lengkap', 'nip']),
            'gms' => $gms,
            'jabatanPerUnit' => Jabatan::perUnit(),
            'bisaKelola' => Auth::user()->can('kelola-sistem'),
        ]);
    }

    public function export(): BinaryFileResponse
    {
        $kepala = Penugasan::aktif()->kepala()->with('dataPegawai:id,nama_lengkap')->get()->groupBy('unit_kerja');
        $anggota = Penugasan::aktif()->select('unit_kerja', DB::raw('count(distinct data_pegawai_id) as n'))->groupBy('unit_kerja')->pluck('n', 'unit_kerja');

        $rows = Unit::withCount('jabatans')->orderBy('urutan')->orderBy('nama')->get()->map(fn (Unit $u) => [
            $u->urutan,
            $u->nama,
            $u->keterangan,
            $kepala->get($u->nama, collect())->map(fn ($p) => ($p->dataPegawai?->nama_lengkap ?? '-') . ($p->is_utama ? '' : ' (rangkap)'))
                ->concat(User::gmKepalaTanpaPegawai($u->nama)->pluck('name')->map(fn ($n) => "{$n} (GM)"))
                ->implode(', ') ?: 'Belum ada koordinator',
            $anggota[$u->nama] ?? 0,
            $u->jabatans_count,
        ]);

        return ExcelExporter::download(ExcelExporter::namaFile('master-unit'), ['No', 'Unit', 'Keterangan', 'Koordinator', 'Pegawai', 'Jabatan'], $rows, 'Master Unit');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validasi($request);
        $unit = Unit::create($data + ['urutan' => (int) Unit::max('urutan') + 1]);

        return redirect()->route('admin.unit.index')->with('success', "Unit {$unit->nama} ditambahkan. Atur koordinatornya lewat tombol Koordinator.");
    }

    public function update(Request $request, Unit $unit): RedirectResponse
    {
        $data = $this->validasi($request, $unit);
        $lama = $unit->nama;
        $jumlah = $unit->ubahNama($data['nama']);
        $unit->update(collect($data)->except('nama')->all());

        return redirect()->route('admin.unit.index')->with('success', $lama === $unit->nama
            ? "Unit {$unit->nama} diperbarui."
            : "Unit {$lama} diganti menjadi {$unit->nama}." . ($jumlah ? " {$jumlah} data ikut diperbarui." : ''));
    }

    public function destroy(Unit $unit): RedirectResponse
    {
        if ($pakai = array_sum($unit->pemakaian())) {
            return back()->with('error', "Unit {$unit->nama} masih dipakai di {$pakai} data (pegawai, penugasan, pengajuan, lowongan, ...). Pindahkan datanya dulu.");
        }
        if ($config = Unit::dipakaiConfig($unit->nama)) {
            return back()->with('error', "Unit {$unit->nama} dipakai di pengaturan sistem ({$config}) dan tidak bisa dihapus.");
        }
        $unit->delete();

        return back()->with('success', "Unit {$unit->nama} dihapus.");
    }

    /** Tetapkan / ganti / kosongkan koordinator unit. */
    public function koordinator(Request $request, Unit $unit): RedirectResponse
    {
        // Pilihan: "pegawai:<id data pegawai>" atau "gm:<id akun GM tanpa data pegawai>"; kosong = kosongkan koordinator.
        $data = $request->validate([
            'koordinator' => ['nullable', 'string', 'regex:/^(pegawai|gm):\d+$/'],
            'jabatan' => ['nullable', 'string', 'max:150'],
        ], ['koordinator.regex' => 'Pilihan koordinator tidak valid.']);
        [$jenis, $id] = ! empty($data['koordinator']) ? explode(':', $data['koordinator']) : [null, null];
        $baru = $jenis === 'pegawai' ? DataPegawai::findOrFail($id) : null;
        $gm = $jenis === 'gm' ? User::where('role', User::ROLE_GM)->whereNull('data_pegawai_id')->findOrFail($id) : null;
        $jabatan = isset($data['jabatan']) ? mb_strtoupper(trim(preg_replace('/\s+/', ' ', $data['jabatan']))) : null;

        $peringatan = PenugasanService::tetapkanKoordinator($unit->nama, $baru, $jabatan ?: null, Auth::user(), $gm);
        $nama = $baru?->nama_lengkap ?? $gm?->name;

        return redirect()->route('admin.unit.index')
            ->with('success', $nama ? "{$nama} ditetapkan sebagai koordinator {$unit->nama}." : "Koordinator {$unit->nama} dikosongkan.")
            ->with('peringatan', $peringatan);
    }

    /** @return array{nama: string, urutan?: int, keterangan: ?string} */
    private function validasi(Request $request, ?Unit $unit = null): array
    {
        $request->merge(['nama' => mb_strtoupper(trim(preg_replace('/\s+/', ' ', (string) $request->input('nama'))))]);

        return $request->validate([
            'nama' => ['required', 'string', 'max:100', Rule::unique('units', 'nama')->ignore($unit?->id)],
            'urutan' => [$unit ? 'required' : 'nullable', 'integer', 'min:0', 'max:9999'],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ], [
            'nama.required' => 'Nama unit wajib diisi.',
            'nama.unique' => 'Unit dengan nama ini sudah ada.',
        ]);
    }
}
