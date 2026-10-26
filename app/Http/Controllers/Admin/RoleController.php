<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Master Role: sesuaikan role bawaan (nama tampilan, keterangan, hak akses) dan buat role kustom berbasis jenis dasar.
 * Panel & alur persetujuan selalu mengikuti jenis dasar (users.role); hak akses mengikuti Master Role.
 */
class RoleController extends Controller
{
    public function index(): View
    {
        $jumlahBawaan = User::whereNull('role_id')->whereIn('role', User::INTERNAL_ROLES)->selectRaw('role, count(*) n')->groupBy('role')->pluck('n', 'role');
        $jumlahKustom = User::whereNotNull('role_id')->selectRaw('role_id, count(*) n')->groupBy('role_id')->pluck('n', 'role_id');

        $bawaan = collect(User::INTERNAL_ROLES)->map(fn (string $kode) => (object) [
            'kode' => $kode,
            'nama' => User::roleLabelFor($kode),
            'namaAsli' => User::LABEL_ROLE[$kode],
            'keterangan' => Role::penyesuaian($kode)?->keterangan,
            'hakAkses' => Role::hakAksesBawaan($kode),
            'disesuaikan' => (bool) Role::penyesuaian($kode),
            'jumlah' => (int) ($jumlahBawaan[$kode] ?? 0),
        ]);

        return view('admin.role.index', [
            'bawaan' => $bawaan,
            'kustom' => Role::kustom()->map(fn (Role $r) => tap($r, fn ($r) => $r->jumlah = (int) ($jumlahKustom[$r->id] ?? 0))),
        ]);
    }

    public function create(Request $request): View
    {
        $dasar = in_array($request->query('dasar'), Role::JENIS_DASAR_KUSTOM, true) ? $request->query('dasar') : User::ROLE_STAFF;

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

    /** Hapus role kustom; akun yang memakainya kembali ke role bawaan jenis dasarnya. */
    public function destroy(Role $role): RedirectResponse
    {
        abort_if($role->bawaan, 404);
        $jumlah = DB::transaction(function () use ($role) {
            $n = $role->users()->update(['role_id' => null]);
            $role->delete();

            return $n;
        });

        return redirect()->route('admin.role.index')->with('success', "Role {$role->nama} dihapus." . ($jumlah ? " {$jumlah} akun kembali ke role " . User::roleLabelFor($role->jenis_dasar) . '.' : ''));
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
            'jenis_dasar' => [$kodeBawaan ? 'nullable' : 'required', Rule::in(Role::JENIS_DASAR_KUSTOM)],
            'hak_akses' => ['nullable', 'array'],
            'hak_akses.*' => [Rule::in(array_keys(Role::HAK_AKSES))],
        ], ['nama.required' => 'Nama role wajib diisi.', 'jenis_dasar.required' => 'Pilih jenis dasar role.']);
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
