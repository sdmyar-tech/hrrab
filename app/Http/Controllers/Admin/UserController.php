<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DataPegawai;
use App\Models\User;
use App\Support\PenugasanService;
use App\Support\Urutan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use App\Support\ExcelExporter;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class UserController extends Controller
{
    private const ROLES = [...User::INTERNAL_ROLES, User::ROLE_PELAMAR];

    public function index(Request $request): View
    {
        $query = $this->filteredQuery($request);
        $urut = Urutan::terapkan($query, [
            ...Urutan::tanggal('dibuat', 'Dibuat', 'users.created_at'),
            ...Urutan::abjad('nama', 'Nama', 'users.name'),
            ...Urutan::abjad('email', 'Email', 'users.email'),
            ...Urutan::tanggal('diperbarui', 'Diperbarui', 'users.updated_at'),
        ]);

        return view('admin.users.index', [
            'users' => $query->paginate(15)->withQueryString(),
            'roles' => self::ROLES,
            ...$urut,
        ]);
    }

    public function export(Request $request): BinaryFileResponse
    {
        $rows = $this->filteredQuery($request)->orderBy('id')->get()->map(fn (User $u) => [
            optional($u->created_at)->format('d-m-Y'),
            $u->name,
            $u->email,
            $u->roleLabel(),
            $u->nip,
            $u->isGm() ? ($u->divisi ? implode(', ', $u->divisi) : 'Semua divisi') : null,
            implode(', ', $u->unitsDipimpin()),
            $u->dataPegawai ? 'Pegawai: ' . $u->dataPegawai->nama_lengkap : ($u->dataPelamar ? 'Pelamar: ' . $u->dataPelamar->nama_lengkap : null),
            $u->google_id ? 'Ya' : 'Tidak',
        ]);

        return ExcelExporter::download(ExcelExporter::namaFile('akun-pengguna'), [
            'Dibuat', 'Nama', 'Email', 'Role', 'NIP', 'Bidang GM', 'Kepala Unit', 'Terhubung Dengan', 'Login Google',
        ], $rows, 'Akun Pengguna');
    }

    private function filteredQuery(Request $request)
    {
        $query = User::with(['dataPegawai.penugasanAktif', 'dataPelamar']);

        if ($search = $request->query('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('nip', 'like', "%{$search}%");
            });
        }

        if ($role = $request->query('role')) {
            $query->where('role', $role);
        }

        return $query;
    }

    public function create(): View
    {
        return view('admin.users.create', $this->formOptions());
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
            'role' => ['required', Rule::in(self::ROLES)],
            'divisi' => ['nullable', 'array'],
            'divisi.*' => ['string', Rule::in(config('kepegawaian.unit_kerja'))],
            'kepala_unit' => ['nullable', 'array'],
            'kepala_unit.*' => ['string', Rule::in(config('kepegawaian.unit_kerja'))],
            'nip' => ['nullable', 'string', 'max:50'],
            'wajib_ganti_password' => ['nullable', 'boolean'],
            'data_pegawai_id' => ['nullable', 'integer', 'exists:data_pegawais,id'],
        ]);

        unset($validated['password_confirmation']);
        $validated['wajib_ganti_password'] = $request->boolean('wajib_ganti_password');
        $validated = $this->normalizeDivisi($validated);
        [$validated, $kepalaUnit] = $this->pisahkanKepalaUnit($validated);

        DB::transaction(fn () => $this->simpanKepalaUnit(User::create($validated), $kepalaUnit));

        return redirect()->route('admin.users.index')->with('success', 'Akun user berhasil ditambahkan.');
    }

    public function edit(User $user): View
    {
        return view('admin.users.edit', ['user' => $user, ...$this->formOptions()]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        // Pilihan "Lepaskan tautan": akun (mis. GM) tidak lagi terhubung ke Data Pegawai.
        $lepasTautan = $request->input('data_pegawai_id') === 'lepas';
        if ($lepasTautan) {
            $request->merge(['data_pegawai_id' => null]);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:6', 'confirmed'],
            'role' => ['required', Rule::in(self::ROLES)],
            'divisi' => ['nullable', 'array'],
            'divisi.*' => ['string', Rule::in(config('kepegawaian.unit_kerja'))],
            'kepala_unit' => ['nullable', 'array'],
            'kepala_unit.*' => ['string', Rule::in(config('kepegawaian.unit_kerja'))],
            'nip' => ['nullable', 'string', 'max:50'],
            'wajib_ganti_password' => ['nullable', 'boolean'],
            'data_pegawai_id' => ['nullable', 'integer', 'exists:data_pegawais,id'],
        ]);

        if (empty($validated['password'])) {
            unset($validated['password']);
        }
        unset($validated['password_confirmation']);
        // Akun sendiri tidak bisa dikunci dari form ini.
        $validated['wajib_ganti_password'] = $user->id === Auth::id()
            ? $user->wajib_ganti_password
            : $request->boolean('wajib_ganti_password');
        $validated = $this->normalizeDivisi($validated);

        if ($user->isAdmin() && $validated['role'] !== User::ROLE_ADMIN && User::where('role', User::ROLE_ADMIN)->count() <= 1) {
            return redirect()->back()->withInput()->with('error', 'Tidak bisa mengubah role — ini Admin terakhir di sistem.');
        }

        if ($lepasTautan) {
            $validated['nip'] = null; // NIP yang tersisa akan menautkan ulang, jadi ikut dikosongkan
        }
        [$validated, $kepalaUnit] = $this->pisahkanKepalaUnit($validated);
        if ($lepasTautan) {
            $validated['data_pegawai_id'] = null;
        }

        DB::transaction(function () use ($user, $validated, $kepalaUnit) {
            $user->update($validated);
            $this->simpanKepalaUnit($user, $kepalaUnit);
        });

        return redirect()->route('admin.users.index')->with('success', 'Akun user berhasil diperbarui.');
    }

    public function destroy(User $user): RedirectResponse
    {
        /** @var User $current */
        $current = Auth::user();

        if ($user->id === $current->id) {
            return redirect()->back()->with('error', 'Anda tidak bisa menghapus akun Anda sendiri.');
        }

        if ($user->isAdmin() && User::where('role', User::ROLE_ADMIN)->count() <= 1) {
            return redirect()->back()->with('error', 'Tidak bisa menghapus — ini Admin terakhir di sistem.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'Akun user berhasil dihapus.');
    }

    private function formOptions(): array
    {
        return [
            'roles' => self::ROLES,
            'divisiOptions' => config('kepegawaian.unit_kerja'),
            'gmBidang' => config('kepegawaian.gm_bidang'),
            'pegawaiOpsi' => $this->pegawaiOpsi(),
        ];
    }

    /**
     * Pilihan dropdown "Data Pegawai": nama, NIP, email, unit, dan jumlah akun yang sudah tertaut.
     *
     * @return \Illuminate\Support\Collection<int, array{id: int, nama: string, nip: ?string, email: ?string, unit: ?string, akun: int}>
     */
    private function pegawaiOpsi(): \Illuminate\Support\Collection
    {
        $akun = User::whereNotNull('data_pegawai_id')->selectRaw('data_pegawai_id, count(*) n')->groupBy('data_pegawai_id')->pluck('n', 'data_pegawai_id');

        return DataPegawai::with('kepegawaian:id,data_pegawai_id,unit_kerja,jabatan')
            ->orderBy('nama_lengkap')
            ->get(['id', 'nama_lengkap', 'nip', 'email'])
            ->map(fn (DataPegawai $p) => [
                'id' => $p->id,
                'nama' => $p->nama_lengkap,
                'nip' => $p->nip,
                'email' => $p->email,
                'unit' => $p->kepegawaian?->unit_kerja,
                'akun' => (int) ($akun[$p->id] ?? 0),
            ]);
    }

    private function normalizeDivisi(array $validated): array
    {
        $divisi = array_values(array_unique($validated['divisi'] ?? []));
        $validated['divisi'] = ($validated['role'] === User::ROLE_GM && $divisi) ? $divisi : null;
        $kepalaUnit = array_values(array_unique($validated['kepala_unit'] ?? []));

        // Staff yang dicentang memimpin unit otomatis naik menjadi Kepala Bagian (sebelumnya centangan diabaikan diam-diam).
        if ($kepalaUnit && $validated['role'] === User::ROLE_STAFF) {
            $validated['role'] = User::ROLE_KEPALA_BAGIAN;
        }

        if ($kepalaUnit && ! in_array($validated['role'], [User::ROLE_GM, User::ROLE_KEPALA_BAGIAN, User::ROLE_KEPALA_SDM], true)) {
            throw ValidationException::withMessages([
                'kepala_unit' => 'Role ' . User::roleLabelFor($validated['role']) . ' tidak bisa memimpin unit. Pilih role Kepala Bagian, Kepala SDM, GM, atau Staff.',
            ]);
        }

        $validated['kepala_unit'] = $kepalaUnit;

        return $validated;
    }

    /**
     * @return array{0: array, 1: list<string>}
     */
    private function pisahkanKepalaUnit(array $validated): array
    {
        $kepalaUnit = $validated['kepala_unit'];
        unset($validated['kepala_unit']);

        // Utamakan pegawai yang dipilih dari dropdown; bila kosong, cocokkan lewat NIP yang diketik.
        if (! empty($validated['data_pegawai_id'])) {
            $validated['nip'] = $validated['nip'] ?: DataPegawai::whereKey($validated['data_pegawai_id'])->value('nip');
        } elseif (! empty($validated['nip']) && ($pegawaiId = DataPegawai::where('nip', $validated['nip'])->value('id'))) {
            $validated['data_pegawai_id'] = $pegawaiId;
        } else {
            unset($validated['data_pegawai_id']); // jangan memutus tautan lama hanya karena dropdown kosong
        }

        return [$validated, $kepalaUnit];
    }

    /**
     * @param  list<string>  $units
     */
    private function simpanKepalaUnit(User $user, array $units): void
    {
        $pegawai = $user->dataPegawai;

        if (! $pegawai) {
            if ($units) {
                throw ValidationException::withMessages([
                    'kepala_unit' => 'Akun ini belum tertaut ke Data Pegawai. Isi NIP yang sama dengan Data Pegawai agar bisa memimpin unit.',
                ]);
            }

            return;
        }

        if (! $units && ($user->isKepalaBagian() || $user->isKepalaSdm()) && $pegawai->kepegawaian?->unit_kerja) {
            $units = [$pegawai->kepegawaian->unit_kerja];
        }

        if (in_array($user->role, [User::ROLE_GM, User::ROLE_KEPALA_BAGIAN, User::ROLE_KEPALA_SDM, User::ROLE_STAFF], true)) {
            PenugasanService::aturKepala($pegawai, $units, Auth::user());
        }
    }
}
