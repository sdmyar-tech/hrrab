<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Support\ExcelExporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Master Role: sesuaikan role bawaan (nama tampilan, keterangan, hak akses) dan buat role kustom berbasis jenis dasar.
 * Panel & alur persetujuan selalu mengikuti jenis dasar (users.role); hak akses mengikuti Master Role.
 */
class RoleController extends Controller
{
    public function index(): View
    {
        ['roles' => $roles, 'dihapus' => $dihapus] = $this->data();

        return view('admin.role.index', [
            'roles' => $roles,
            'dihapus' => $dihapus,
            // Tujuan pemindahan akun saat menghapus role (Admin tidak ditawarkan).
            'pilihanPengganti' => Role::pilihanAktif([User::ROLE_ADMIN]),
        ]);
    }

    /** Ekspor daftar role (termasuk yang dihapus) + matriks hak akses. */
    public function export(): BinaryFileResponse
    {
        ['roles' => $roles, 'dihapus' => $dihapus] = $this->data();
        $jenis = ['bawaan' => 'Bawaan', 'disesuaikan' => 'Bawaan (disesuaikan)', 'kustom' => 'Kustom'];

        $daftar = $roles->map(fn ($r) => [
            $r->nama, $jenis[$r->jenis], User::LABEL_ROLE[$r->dasar] ?? $r->dasar, $r->keterangan, $r->jumlah, count($r->hak),
            collect($r->hak)->map(fn ($h) => Role::HAK_AKSES[$h][0] ?? $h)->implode(', '), 'Aktif',
        ])->concat($dihapus->map(fn ($d) => [
            $d->nama, 'Bawaan (dihapus)', $d->namaAsli, null, $d->jumlah, null, null, 'Dihapus' . ($d->pengganti ? " → akun ke {$d->pengganti}" : ''),
        ]));
        $matriks = collect(Role::HAK_AKSES)->map(fn ($info, $hak) => [
            $info[0],
            ...$roles->map(fn ($r) => in_array($hak, $r->kunci, true) ? 'Ya (terkunci)' : (in_array($hak, $r->hak, true) ? 'Ya' : '-'))->all(),
        ])->values();

        return ExcelExporter::downloadMultiSheet(ExcelExporter::namaFile('master-role'), [
            ['name' => 'Daftar Role', 'headers' => ['Role', 'Jenis', 'Jenis Dasar', 'Keterangan', 'Jumlah Akun', 'Jumlah Hak', 'Hak Akses', 'Status'], 'rows' => $daftar],
            ['name' => 'Matriks Hak Akses', 'headers' => ['Hak Akses', ...$roles->pluck('nama')->all()], 'rows' => $matriks],
        ]);
    }

    /** Role aktif (bawaan lalu kustom) & role bawaan yang dihapus, dalam bentuk siap tampil/ekspor. */
    private function data(): array
    {
        $jumlahBawaan = User::whereNull('role_id')->whereIn('role', User::INTERNAL_ROLES)->selectRaw('role, count(*) n')->groupBy('role')->pluck('n', 'role');
        $jumlahKustom = User::whereNotNull('role_id')->selectRaw('role_id, count(*) n')->groupBy('role_id')->pluck('n', 'role_id');

        // Satu bentuk data untuk baris daftar & kolom matriks: role bawaan aktif lebih dulu, lalu role kustom.
        $roles = collect(Role::bawaanAktif())->map(fn (string $kode) => (object) [
            'jenis' => Role::penyesuaian($kode) ? 'disesuaikan' : 'bawaan',
            'nama' => User::roleLabelFor($kode),
            'namaAsli' => User::LABEL_ROLE[$kode],
            'dasar' => $kode,
            'keterangan' => Role::penyesuaian($kode)?->keterangan,
            'hak' => Role::hakAksesBawaan($kode),
            'kunci' => Role::DIKUNCI[$kode] ?? [],
            'jumlah' => (int) ($jumlahBawaan[$kode] ?? 0),
            'urlUbah' => route('admin.role.bawaan.edit', $kode),
            'urlAkun' => route('admin.users.index', ['role' => $kode]),
            'urlReset' => Role::penyesuaian($kode) ? route('admin.role.bawaan.reset', $kode) : null,
            'urlHapus' => in_array($kode, Role::TIDAK_BISA_DIHAPUS, true) ? null : route('admin.role.bawaan.hapus', $kode),
            'nilai' => $kode,
            'otomatis' => in_array($kode, Role::DIPASANG_OTOMATIS, true),
        ])->concat(Role::kustom()->map(fn (Role $r) => (object) [
            'jenis' => 'kustom',
            'nama' => $r->nama,
            'namaAsli' => null,
            'dasar' => $r->jenis_dasar,
            'keterangan' => $r->keterangan,
            'hak' => $r->hakAksesEfektif(),
            'kunci' => [],
            'jumlah' => (int) ($jumlahKustom[$r->id] ?? 0),
            'urlUbah' => route('admin.role.edit', $r),
            'urlAkun' => route('admin.users.index', ['role' => 'kustom:' . $r->id]),
            'urlReset' => null,
            'urlHapus' => route('admin.role.destroy', $r),
            'nilai' => 'kustom:' . $r->id,
            'otomatis' => false,
        ]));

        // Role bawaan yang sudah dihapus: bisa dipulihkan.
        $pilihan = Role::pilihanAktif();
        $dihapus = collect(Role::bawaanNonaktif())->map(fn (string $kode) => (object) [
            'kode' => $kode,
            'nama' => User::roleLabelFor($kode),
            'namaAsli' => User::LABEL_ROLE[$kode],
            'pengganti' => ($p = Role::penyesuaian($kode)?->pengganti) ? ($pilihan[$p] ?? $p) : null,
            'jumlah' => (int) ($jumlahBawaan[$kode] ?? 0),
            'urlPulihkan' => route('admin.role.bawaan.pulihkan', $kode),
        ]);

        return ['roles' => $roles, 'dihapus' => $dihapus];
    }

    public function create(Request $request): View
    {
        $boleh = $this->jenisDasarAktif();
        $dasar = in_array($request->query('dasar'), $boleh, true) ? $request->query('dasar') : ($boleh[0] ?? User::ROLE_STAFF);

        return view('admin.role.form', [
            'mode' => 'kustom',
            'role' => new Role(['jenis_dasar' => $dasar, 'hak_akses' => Role::hakAksesBawaan($dasar)]),
            'jumlahAkun' => 0,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $v = $this->validasi($request);
        $role = Role::create([
            'kode' => $this->kodeUnik($v['nama']),
            'nama' => $v['nama'],
            'keterangan' => $v['keterangan'] ?? null,
            'jenis_dasar' => $v['jenis_dasar'],
            'hak_akses' => Role::rapikan($v['jenis_dasar'], $v['hak_akses'] ?? []),
            'bawaan' => false,
        ]);

        return redirect()->route('admin.role.index')->with('success', "Role {$role->nama} dibuat. Pasang ke akun lewat Kelola Akun.");
    }

    public function edit(Role $role): View
    {
        abort_if($role->bawaan, 404);

        return view('admin.role.form', ['mode' => 'kustom', 'role' => $role, 'jumlahAkun' => $role->users()->count()]);
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        abort_if($role->bawaan, 404);
        $jumlahAkun = $role->users()->count();
        $v = $this->validasi($request, $role);
        // Jenis dasar menentukan panel & alur akun; tidak diganti selama role sudah dipakai.
        $dasar = $jumlahAkun ? $role->jenis_dasar : $v['jenis_dasar'];

        $role->update([
            'nama' => $v['nama'],
            'keterangan' => $v['keterangan'] ?? null,
            'jenis_dasar' => $dasar,
            'hak_akses' => Role::rapikan($dasar, $v['hak_akses'] ?? []),
        ]);

        return redirect()->route('admin.role.index')->with('success', "Role {$role->nama} diperbarui." . ($jumlahAkun ? " Berlaku untuk {$jumlahAkun} akun." : ''));
    }

    /**
     * Hapus role kustom. Akun yang memakainya dipindah ke role pengganti yang dipilih,
     * atau (tanpa pilihan) kembali ke role bawaan jenis dasarnya.
     */
    public function destroy(Request $request, Role $role): RedirectResponse
    {
        abort_if($role->bawaan, 404);
        $tujuan = $this->validasiPengganti($request, 'kustom:' . $role->id, false);

        $akun = DB::transaction(function () use ($role, $tujuan) {
            // Per akun (bukan update massal) agar aturan model ikut jalan, mis. jenis dasar yang sudah dihapus → penggantinya.
            $akun = $role->users()->get();
            $akun->each(fn (User $u) => $u->update($tujuan ?? ['role_id' => null]));
            $role->delete();

            return $akun;
        });
        $jumlah = $akun->count();
        // Nama role tujuan yang benar-benar dipakai (bisa berbeda bila jenis dasar sudah dihapus dan diarahkan ke penggantinya).
        $ke = $akun->first()?->fresh()->roleLabel();

        return redirect()->route('admin.role.index')->with('success', "Role {$role->nama} dihapus." . ($jumlah ? " {$jumlah} akun dipindah ke role {$ke}." : ''));
    }

    /**
     * Hapus (nonaktifkan) role bawaan selain Admin: hilang dari pilihan & daftar aktif, bisa dipulihkan.
     * Akun yang memakainya dipindah ke role pengganti; role yang dipasang otomatis (Staff, Kepala Bagian) wajib punya pengganti.
     */
    public function hapusBawaan(Request $request, string $kode): RedirectResponse
    {
        abort_unless(in_array($kode, Role::bawaanAktif(), true) && ! in_array($kode, Role::TIDAK_BISA_DIHAPUS, true), 404);
        $akun = User::where('role', $kode)->whereNull('role_id');
        $wajib = $akun->exists() || in_array($kode, Role::DIPASANG_OTOMATIS, true);
        $tujuan = $this->validasiPengganti($request, $kode, $wajib);
        $nama = User::roleLabelFor($kode);
        $p = Role::penyesuaian($kode);

        $jumlah = DB::transaction(function () use ($akun, $tujuan, $kode, $p, $request) {
            $n = $tujuan ? $akun->update($tujuan) : 0;
            Role::updateOrCreate(['kode' => $kode], [
                'nama' => $p?->nama ?? User::LABEL_ROLE[$kode],
                'keterangan' => $p?->keterangan,
                'jenis_dasar' => $kode,
                'hak_akses' => Role::hakAksesBawaan($kode),
                'bawaan' => true,
                'aktif' => false,
                'pengganti' => $tujuan ? $request->input('pengganti') : null,
            ]);

            return $n;
        });
        $ke = $tujuan ? (Role::pilihanAktif()[$request->input('pengganti')] ?? $request->input('pengganti')) : null;

        return redirect()->route('admin.role.index')->with('success', "Role {$nama} dihapus." . ($jumlah ? " {$jumlah} akun dipindah ke role {$ke}." : '')
            . ($ke && in_array($kode, Role::DIPASANG_OTOMATIS, true) ? " Akun yang otomatis mendapat role {$nama} kini memakai {$ke}." : ''));
    }

    /** Pulihkan role bawaan yang sudah dihapus. */
    public function pulihkanBawaan(string $kode): RedirectResponse
    {
        $p = Role::penyesuaian($kode);
        abort_unless($p && $p->aktif === false, 404);

        // Baris yang dibuat hanya untuk menonaktifkan (nama & hak masih bawaan) dihapus agar role kembali "Bawaan" murni.
        $hakAsli = collect(Role::rapikan($kode, User::ABILITIES[$kode] ?? []))->sort()->values()->all();
        $masihAsli = $p->nama === User::LABEL_ROLE[$kode] && ! $p->keterangan && collect($p->hakAksesEfektif())->sort()->values()->all() === $hakAsli;
        $masihAsli ? $p->delete() : $p->update(['aktif' => true, 'pengganti' => null]);

        return redirect()->route('admin.role.index')->with('success', 'Role ' . User::roleLabelFor($kode) . ' dipulihkan dan bisa dipilih lagi di Kelola Akun.');
    }

    public function editBawaan(string $kode): View
    {
        abort_unless(in_array($kode, User::INTERNAL_ROLES, true), 404);
        $p = Role::penyesuaian($kode);

        return view('admin.role.form', [
            'mode' => 'bawaan',
            'role' => new Role([
                'kode' => $kode,
                'nama' => $p?->nama ?? User::LABEL_ROLE[$kode],
                'keterangan' => $p?->keterangan,
                'jenis_dasar' => $kode,
                'hak_akses' => Role::hakAksesBawaan($kode),
            ]),
            'jumlahAkun' => User::where('role', $kode)->whereNull('role_id')->count(),
            'disesuaikan' => (bool) $p,
        ]);
    }

    public function updateBawaan(Request $request, string $kode): RedirectResponse
    {
        abort_unless(in_array($kode, User::INTERNAL_ROLES, true), 404);
        $p = Role::penyesuaian($kode);
        $v = $this->validasi($request, $p, $kode);

        Role::updateOrCreate(['kode' => $kode], [
            'nama' => $v['nama'],
            'keterangan' => $v['keterangan'] ?? null,
            'jenis_dasar' => $kode,
            'hak_akses' => Role::rapikan($kode, $v['hak_akses'] ?? []),
            'bawaan' => true,
        ]);

        return redirect()->route('admin.role.index')->with('success', "Role bawaan {$v['nama']} disesuaikan.");
    }

    /** Kembalikan role bawaan ke nama & hak akses dari sistem. */
    public function resetBawaan(string $kode): RedirectResponse
    {
        abort_unless(in_array($kode, User::INTERNAL_ROLES, true), 404);
        Role::penyesuaian($kode)?->delete();

        return redirect()->route('admin.role.index')->with('success', 'Role ' . User::LABEL_ROLE[$kode] . ' dikembalikan ke pengaturan bawaan.');
    }

    /** @return array{nama: string, keterangan: ?string, jenis_dasar?: string, hak_akses?: list<string>} */
    private function validasi(Request $request, ?Role $role = null, ?string $kodeBawaan = null): array
    {
        $request->merge(['nama' => trim(preg_replace('/\s+/', ' ', (string) $request->input('nama')))]);
        // Nama tidak boleh sama dengan role lain (bawaan maupun kustom) agar tidak membingungkan di daftar akun.
        $namaLain = collect(User::INTERNAL_ROLES)->reject(fn ($k) => $k === $kodeBawaan)->map(fn ($k) => mb_strtolower(User::roleLabelFor($k)))
            ->merge(Role::kustom()->reject(fn (Role $r) => $r->id === $role?->id)->map(fn (Role $r) => mb_strtolower($r->nama)));

        return $request->validate([
            'nama' => ['required', 'string', 'max:100', function ($a, $nilai, $gagal) use ($namaLain) {
                if ($namaLain->contains(mb_strtolower($nilai))) {
                    $gagal('Nama role ini sudah dipakai role lain.');
                }
            }],
            'keterangan' => ['nullable', 'string', 'max:255'],
            // Jenis dasar aktif; jenis dasar role ini sendiri tetap sah walau sudah dihapus (role lama yang terkunci).
            'jenis_dasar' => [$kodeBawaan ? 'nullable' : 'required', Rule::in([...$this->jenisDasarAktif(), ...array_filter([$role?->jenis_dasar])])],
            'hak_akses' => ['nullable', 'array'],
            'hak_akses.*' => [Rule::in(array_keys(Role::HAK_AKSES))],
        ], ['nama.required' => 'Nama role wajib diisi.', 'jenis_dasar.required' => 'Pilih jenis dasar role.']);
    }

    /** @return list<string> jenis dasar role kustom yang masih aktif */
    private function jenisDasarAktif(): array
    {
        return array_values(array_intersect(Role::JENIS_DASAR_KUSTOM, Role::bawaanAktif()));
    }

    /**
     * Role pengganti dari input "pengganti" ("<kode bawaan>" / "kustom:<id>"): harus aktif, bukan Admin, bukan role yang dihapus.
     *
     * @return array{role: string, role_id: ?int}|null
     */
    private function validasiPengganti(Request $request, string $dihapus, bool $wajib): ?array
    {
        $pilihan = array_keys(Role::pilihanAktif([User::ROLE_ADMIN, $dihapus]));
        $request->validate(['pengganti' => [$wajib ? 'required' : 'nullable', Rule::in($pilihan)]], [
            'pengganti.required' => 'Pilih role pengganti untuk akun yang memakai role ini.',
            'pengganti.in' => 'Role pengganti tidak valid.',
        ]);

        return $request->filled('pengganti') ? Role::urai($request->input('pengganti')) : null;
    }

    private function kodeUnik(string $nama): string
    {
        $dasar = 'kustom-' . (Str::slug(Str::limit($nama, 40, '')) ?: 'role');
        $kode = $dasar;
        for ($i = 2; Role::where('kode', $kode)->exists(); $i++) {
            $kode = "{$dasar}-{$i}";
        }

        return $kode;
    }
}
