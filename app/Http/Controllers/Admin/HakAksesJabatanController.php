<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Jabatan;
use App\Models\Penugasan;
use App\Models\Role;
use App\Models\Unit;
use App\Support\ExcelExporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Hak Akses Jabatan: hak tambahan untuk pemegang jabatan tertentu di tiap unit (di atas hak role akunnya),
 * mis. "STAFF" unit A boleh melihat absensi sedangkan "STAFF" unit B tidak. Hak: kelola-sistem (dijaga di route).
 */
class HakAksesJabatanController extends Controller
{
    public function index(Request $request): View
    {
        return view('admin.jabatan.hak-akses', [
            'baris' => $this->baris($request),
            'unitOpsi' => Unit::orderBy('urutan')->orderBy('nama')->pluck('nama', 'id'),
            'hakOpsi' => self::hakOpsi(),
            'jumlahBerhak' => DB::table('jabatan_unit')->whereNotNull('hak_akses')->count(),
        ]);
    }

    public function update(Request $request, Jabatan $jabatan, Unit $unit): RedirectResponse
    {
        abort_unless($jabatan->units()->whereKey($unit->id)->exists(), 404, 'Jabatan ini tidak terdaftar di unit tersebut.');
        $hak = $request->validate([
            'hak_akses' => ['nullable', 'array'],
            'hak_akses.*' => [Rule::in(array_keys(self::hakOpsi()))],
        ])['hak_akses'] ?? [];

        $jabatan->aturHakAkses($unit, $hak);

        return back()->with('success', "Hak akses {$jabatan->nama} di {$unit->nama} disimpan"
            . ($hak ? ' (' . count($hak) . ' hak tambahan).' : ' — tanpa hak tambahan.'));
    }

    public function export(Request $request): BinaryFileResponse
    {
        $hakOpsi = self::hakOpsi();
        $rows = $this->baris($request)->map(fn ($b) => [
            $b->unit, $b->jabatan, $b->pemegang,
            collect($b->hak)->map(fn ($h) => $hakOpsi[$h][0] ?? $h)->implode(', ') ?: '-',
        ]);

        return ExcelExporter::download(ExcelExporter::namaFile('hak-akses-jabatan'), ['Unit', 'Jabatan', 'Pemegang Aktif', 'Hak Akses Tambahan'], $rows, 'Hak Akses Jabatan');
    }

    /** Hak yang boleh diberikan lewat jabatan (semua hak Master Role kecuali yang setara Admin). */
    public static function hakOpsi(): array
    {
        return array_diff_key(Role::HAK_AKSES, array_flip(Jabatan::HAK_TERLARANG));
    }

    /** Pasangan unit × jabatan (Master Jabatan) + jumlah pemegang aktif + hak tambahannya, sesuai filter. */
    private function baris(Request $request): Collection
    {
        $pemegang = Penugasan::aktif()->select('unit_kerja', 'jabatan', DB::raw('count(distinct data_pegawai_id) as n'))
            ->groupBy('unit_kerja', 'jabatan')->get()
            ->mapWithKeys(fn ($p) => [Jabatan::kunciHak($p->unit_kerja, $p->jabatan) => (int) $p->n]);
        $kata = trim((string) $request->query('q'));

        return DB::table('jabatan_unit')
            ->join('jabatans', 'jabatans.id', '=', 'jabatan_unit.jabatan_id')
            ->join('units', 'units.id', '=', 'jabatan_unit.unit_id')
            ->when($request->filled('unit'), fn ($q) => $q->where('units.id', (int) $request->query('unit')))
            ->when($kata !== '', fn ($q) => $q->where('jabatans.nama', 'like', "%{$kata}%"))
            ->when($request->boolean('ada'), fn ($q) => $q->whereNotNull('jabatan_unit.hak_akses'))
            ->orderBy('units.urutan')->orderBy('units.nama')->orderBy('jabatans.nama')
            ->get(['jabatans.id as jabatan_id', 'jabatans.nama as jabatan', 'units.id as unit_id', 'units.nama as unit', 'jabatan_unit.hak_akses'])
            ->map(function ($r) use ($pemegang) {
                $r->hak = array_values(array_diff((array) json_decode((string) $r->hak_akses, true), Jabatan::HAK_TERLARANG));
                $r->pemegang = $pemegang[Jabatan::kunciHak($r->unit, $r->jabatan)] ?? 0;

                return $r;
            });
    }
}
