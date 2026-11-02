<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Jabatan;
use App\Models\Kepegawaian;
use App\Models\Penugasan;
use App\Models\TanggaAlur;
use App\Models\Unit;
use App\Models\User;
use App\Support\PengajuanWorkflow;
use App\Support\TanggaAlurWorkflow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/** Kepala SDM & Admin: atur Tangga Alur Pengajuan per bagian (pegawai biasa & koordinator). */
class TanggaAlurController extends Controller
{
    public function index(Request $request): View
    {
        $tangga = TanggaAlur::with('pengubah:id,name')->get()->groupBy('unit_id');
        $semua = Unit::orderBy('urutan')->orderBy('nama')->get()->map(fn (Unit $unit) => [
            'unit' => $unit,
            'tangga' => collect(array_keys(TanggaAlur::SASARAN_LABEL))->mapWithKeys(fn ($sasaran) => [
                $sasaran => self::ringkas($tangga->get($unit->id)?->firstWhere('sasaran', $sasaran), $unit->nama, $sasaran),
            ]),
        ]);
        // Saring tampilan: bagian yang sudah punya tangga / yang tangganya punya tahap tanpa penyetuju.
        $saring = [
            'diatur' => fn ($b) => $b['tangga']->contains(fn ($t) => $t['model'] !== null),
            'perhatian' => fn ($b) => $b['tangga']->contains(fn ($t) => $t['peringatan']),
        ];
        $tampil = array_key_exists($request->query('tampil'), $saring) ? $request->query('tampil') : 'semua';

        return view('admin.tangga-alur.index', [
            'baris' => $tampil === 'semua' ? $semua : $semua->filter($saring[$tampil])->values(),
            'tampil' => $tampil,
            'jumlah' => ['semua' => $semua->count(), ...array_map(fn ($f) => $semua->filter($f)->count(), $saring)],
            'jumlahDiatur' => $tangga->flatten()->count(),
            'jumlahPeringatan' => $semua->sum(fn ($b) => $b['tangga']->filter(fn ($t) => $t['peringatan'])->count()),
        ]);
    }

    public function edit(Unit $unit, string $sasaran): View
    {
        $tangga = TanggaAlur::where('unit_id', $unit->id)->where('sasaran', $sasaran)->first();
        $lama = old('tahap_json') ? json_decode(old('tahap_json'), true) : null;

        return view('admin.tangga-alur.form', [
            'unit' => $unit,
            'sasaran' => $sasaran,
            'tangga' => $tangga,
            'tahapAwal' => is_array($lama) ? $lama : ($tangga?->daftarTahap() ?? self::tahapBawaan($sasaran)),
            'pilihan' => self::pilihan(),
            'unitLain' => Unit::whereKeyNot($unit->id)->orderBy('urutan')->orderBy('nama')->pluck('nama', 'id'),
        ]);
    }

    public function update(Request $request, Unit $unit, string $sasaran): RedirectResponse
    {
        $request->merge(['tahap' => json_decode((string) $request->input('tahap_json'), true) ?: []]);
        $unitSah = [TanggaAlur::UNIT_PENGAJU, TanggaAlur::UNIT_SEMUA, ...Unit::pluck('id')->map(fn ($id) => (string) $id)];
        $sumber = 'tahap.*.penyetuju.*';

        $v = $request->validate([
            'tahap' => ['required', 'array', 'min:1', 'max:' . TanggaAlur::MAKS_TAHAP],
            'tahap.*.nama' => ['nullable', 'string', 'max:60'],
            'tahap.*.semua' => ['nullable', 'boolean'],
            'tahap.*.penyetuju' => ['required', 'array', 'min:1', 'max:10'],
            "{$sumber}.tipe" => ['required', Rule::in(array_keys(TanggaAlur::TIPE_LABEL))],
            "{$sumber}.unit" => ['nullable', Rule::in($unitSah)],
            "{$sumber}.jabatan_id" => ["required_if:{$sumber}.tipe," . TanggaAlur::TIPE_JABATAN, 'nullable', Rule::exists('jabatans', 'id')],
            "{$sumber}.user_id" => ["required_if:{$sumber}.tipe," . TanggaAlur::TIPE_AKUN, 'nullable', Rule::in(self::akunPenyetuju()->pluck('id')->all())],
            'catatan' => ['nullable', 'string', 'max:255'],
            'terapkan_ke' => ['nullable', 'array'],
            'terapkan_ke.*' => ['integer', Rule::exists('units', 'id')],
        ], [
            'tahap.required' => 'Tambahkan minimal satu tahap persetujuan.',
            'tahap.min' => 'Tambahkan minimal satu tahap persetujuan.',
            'tahap.max' => 'Paling banyak ' . TanggaAlur::MAKS_TAHAP . ' tahap.',
            'tahap.*.penyetuju.required' => 'Setiap tahap harus punya minimal satu penyetuju.',
            'tahap.*.penyetuju.min' => 'Setiap tahap harus punya minimal satu penyetuju.',
            "{$sumber}.jabatan_id.required_if" => 'Pilih jabatan untuk penyetuju "Pemegang jabatan".',
            "{$sumber}.user_id.required_if" => 'Pilih akun untuk penyetuju "Akun tertentu".',
            "{$sumber}.user_id.in" => 'Akun yang dipilih tidak punya hak menyetujui pengajuan.',
        ]);

        $tahap = collect($v['tahap'])->map(fn (array $t) => [
            'nama' => trim((string) ($t['nama'] ?? '')) ?: null,
            'semua' => (bool) ($t['semua'] ?? false),
            'penyetuju' => collect($t['penyetuju'])->map(fn (array $s) => self::rapikanSumber($s))->unique(fn ($s) => json_encode($s))->values()->all(),
        ])->values()->all();
        foreach ($tahap as $i => $t) {
            foreach ($t['penyetuju'] as $s) {
                if ($s['tipe'] === TanggaAlur::TIPE_KOORDINATOR && $s['unit'] === TanggaAlur::UNIT_SEMUA) {
                    throw ValidationException::withMessages(['tahap' => 'Tahap ' . ($i + 1) . ': koordinator harus dari satu bagian (bagian pengaju atau bagian tertentu).']);
                }
            }
        }

        $unitIds = collect([$unit->id, ...($v['terapkan_ke'] ?? [])])->map(fn ($id) => (int) $id)->unique();
        DB::transaction(function () use ($unitIds, $sasaran, $tahap, $v) {
            foreach ($unitIds as $id) {
                TanggaAlur::updateOrCreate(['unit_id' => $id, 'sasaran' => $sasaran], [
                    'tahap' => $tahap, 'catatan' => $v['catatan'] ?? null, 'diubah_oleh' => Auth::id(),
                ]);
            }
        });

        $label = TanggaAlur::SASARAN_LABEL[$sasaran];
        $kosong = Unit::whereIn('id', $unitIds)->get()
            ->flatMap(fn (Unit $u) => collect(self::ringkas(TanggaAlur::where('unit_id', $u->id)->where('sasaran', $sasaran)->first(), $u->nama, $sasaran)['tahap'])
                ->filter(fn ($t) => $t['kosong'])->map(fn ($t) => "{$u->nama}: {$t['label']}"));
        $redirect = redirect()->route('admin.tangga-alur.index')
            ->with('success', "Tangga alur {$label} disimpan untuk " . $unitIds->count() . ' bagian. Berlaku untuk pengajuan baru; pengajuan yang sedang berjalan tetap memakai tangga saat diajukan.');

        return $kosong->isEmpty() ? $redirect
            : $redirect->with('warning', 'Tahap tanpa penyetuju (pengajuan akan ditolak sistem sampai diisi): ' . $kosong->take(5)->implode('; ') . '.');
    }

    /** Hapus tangga: bagian ini kembali ke alur otomatis. */
    public function destroy(Unit $unit, string $sasaran): RedirectResponse
    {
        TanggaAlur::where('unit_id', $unit->id)->where('sasaran', $sasaran)->delete();

        return redirect()->route('admin.tangga-alur.index')
            ->with('success', 'Tangga alur ' . TanggaAlur::SASARAN_LABEL[$sasaran] . " bagian {$unit->nama} dihapus. Bagian ini kembali ke alur otomatis.");
    }

    /**
     * Ringkasan satu tangga untuk tampilan: tahap + penyetuju (contoh pengaju koordinator: koordinator bagian itu sendiri).
     *
     * @return array{model: ?TanggaAlur, tahap: list<array{label: string, oleh: ?string, final: bool, semua: bool, kosong: bool}>, peringatan: bool, otomatis: string}
     */
    private static function ringkas(?TanggaAlur $tangga, string $unit, string $sasaran): array
    {
        $koordinator = PengajuanWorkflow::kepalaUnit($unit);
        $contohPengaju = $sasaran === TanggaAlur::SASARAN_KOORDINATOR ? $koordinator->first()?->data_pegawai_id : null;
        $daftar = $tangga?->daftarTahap() ?? [];
        $tahap = array_map(function (array $t, int $i) use ($unit, $contohPengaju, $daftar) {
            $orang = TanggaAlurWorkflow::penyetuju($t, $unit, $contohPengaju);

            return [
                'label' => TanggaAlurWorkflow::namaTahap($t, $unit),
                'oleh' => $orang->isEmpty() ? null : $orang->take(3)->pluck('name')->implode(', ') . ($orang->count() > 3 ? ' +' . ($orang->count() - 3) : ''),
                'final' => $i === count($daftar) - 1,
                'semua' => (bool) ($t['semua'] ?? false),
                'kosong' => $orang->isEmpty(),
            ];
        }, $daftar, array_keys($daftar));

        return [
            'model' => $tangga,
            'tahap' => $tahap,
            'peringatan' => collect($tahap)->contains('kosong', true),
            'otomatis' => $sasaran === TanggaAlur::SASARAN_STAF && $koordinator->isNotEmpty() ? 'Koordinator bagian → Kepala SDM' : 'GM → Kepala SDM',
        ];
    }

    /** Titik awal form untuk bagian yang belum punya tangga: setara alur otomatis sekarang. */
    private static function tahapBawaan(string $sasaran): array
    {
        $pertama = $sasaran === TanggaAlur::SASARAN_STAF
            ? ['tipe' => TanggaAlur::TIPE_KOORDINATOR, 'unit' => TanggaAlur::UNIT_PENGAJU]
            : ['tipe' => TanggaAlur::TIPE_GM, 'unit' => TanggaAlur::UNIT_PENGAJU];

        return [
            ['nama' => null, 'semua' => false, 'penyetuju' => [$pertama]],
            ['nama' => null, 'semua' => false, 'penyetuju' => [['tipe' => TanggaAlur::TIPE_KEPALA_SDM]]],
        ];
    }

    /** Simpan hanya kolom yang dipakai jenis sumbernya; id disimpan sebagai angka. */
    private static function rapikanSumber(array $s): array
    {
        $unit = $s['unit'] ?? TanggaAlur::UNIT_PENGAJU;
        $unit = in_array($unit, [TanggaAlur::UNIT_PENGAJU, TanggaAlur::UNIT_SEMUA], true) ? $unit : (int) $unit;

        return match ($s['tipe']) {
            TanggaAlur::TIPE_KOORDINATOR, TanggaAlur::TIPE_GM => ['tipe' => $s['tipe'], 'unit' => $unit],
            TanggaAlur::TIPE_JABATAN => ['tipe' => $s['tipe'], 'jabatan_id' => (int) $s['jabatan_id'], 'unit' => $unit],
            TanggaAlur::TIPE_AKUN => ['tipe' => $s['tipe'], 'user_id' => (int) $s['user_id']],
            default => ['tipe' => $s['tipe']],
        };
    }

    /** Akun yang bisa ditunjuk langsung sebagai penyetuju: punya hak menyetujui (termasuk lewat Hak Akses Jabatan), bukan Admin. */
    private static function akunPenyetuju(): Collection
    {
        return User::with('dataPegawai.kepegawaian')->whereIn('role', User::INTERNAL_ROLES)->where('role', '!=', User::ROLE_ADMIN)
            ->orderBy('name')->get()
            ->filter(fn (User $u) => $u->hasAbility(User::ABILITY_APPROVE))
            ->values();
    }

    /** Data pilihan & pratinjau nama penyetuju untuk form (dihitung sekali, dibaca JS). */
    private static function pilihan(): array
    {
        $unit = Unit::orderBy('urutan')->orderBy('nama')->pluck('nama', 'id');
        $nama = fn (Collection $users) => $users->pluck('name')->unique()->values()->all();

        return [
            'unit' => $unit->map(fn ($n, $id) => ['id' => $id, 'nama' => $n])->values()->all(),
            'koordinator' => $unit->map(fn ($n) => $nama(PengajuanWorkflow::kepalaUnit($n)))->all(),
            'gm' => $unit->map(fn ($n) => $nama(User::gmUtamaUntuk($n)->whenEmpty(fn () => User::where('role', User::ROLE_GM)->get()->filter(fn ($g) => empty($g->divisi)))))->all()
                + ['semua' => $nama(User::where('role', User::ROLE_GM)->orderBy('name')->get())],
            'kepalaSdm' => $nama(PengajuanWorkflow::kepalaSdmTersedia(null)),
            'jabatan' => Jabatan::orderBy('nama')->get(['id', 'nama'])->map(fn ($j) => ['id' => $j->id, 'nama' => $j->nama])->all(),
            'pemegang' => self::petaPemegang($unit),
            'akun' => self::akunPenyetuju()->map(fn (User $u) => [
                'id' => $u->id, 'nama' => $u->name, 'ket' => trim($u->roleLabel() . ($u->unitKerja() ? ' · ' . $u->unitKerja() : '')),
            ])->all(),
        ];
    }

    /**
     * Pemegang jabatan yang bisa menyetujui, per bagian: [jabatan_id][unit_id | 'semua'] => nama akun.
     *
     * @param  Collection<int, string>  $unit  id => nama bagian
     */
    private static function petaPemegang(Collection $unit): array
    {
        $jabatanId = Jabatan::pluck('id', 'nama')->mapWithKeys(fn ($id, $n) => [mb_strtoupper($n) => $id]);
        $unitId = $unit->flip();
        $baris = Penugasan::aktif()->get(['data_pegawai_id', 'unit_kerja', 'jabatan'])->map(fn ($p) => [$p->data_pegawai_id, $p->unit_kerja, $p->jabatan])
            ->merge(Kepegawaian::where('status_rekrutmen', 'Aktif')->get(['data_pegawai_id', 'unit_kerja', 'jabatan', 'jabatan_tambahan'])
                ->flatMap(fn ($k) => [[$k->data_pegawai_id, $k->unit_kerja, $k->jabatan], [$k->data_pegawai_id, $k->unit_kerja, $k->jabatan_tambahan]]));
        $akun = User::whereIn('data_pegawai_id', $baris->pluck(0)->unique())->whereIn('role', User::INTERNAL_ROLES)->where('role', '!=', User::ROLE_ADMIN)->get()
            ->filter(fn (User $u) => $u->hasAbility(User::ABILITY_APPROVE))->keyBy('data_pegawai_id');

        $peta = [];
        foreach ($baris as [$pegawai, $namaUnit, $jabatan]) {
            $j = $jabatanId[mb_strtoupper((string) $jabatan)] ?? null;
            if (! $j || ! isset($akun[$pegawai])) {
                continue;
            }
            foreach (array_filter([$unitId[$namaUnit] ?? null, TanggaAlur::UNIT_SEMUA]) as $k) {
                $peta[$j][$k][$akun[$pegawai]->name] = true;
            }
        }

        return array_map(fn ($perUnit) => array_map('array_keys', $perUnit), $peta);
    }
}
