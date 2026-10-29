<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AlurPegawai;
use App\Models\DataPegawai;
use App\Models\DokumenPegawai;
use App\Models\Jabatan;
use App\Models\User;
use App\Support\EksporPegawai;
use App\Support\ExcelExporter;
use App\Support\FilterTanggal;
use App\Support\KelompokPegawai;
use App\Support\PegawaiKeluar;
use App\Support\PengajuanWorkflow;
use App\Support\PenugasanService;
use App\Support\Urutan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PegawaiController extends Controller
{
    private array $dokumenFields = [
        'upload_ktp',
        'upload_kk',
        'upload_cv',
        'upload_ijazah_terakhir',
        'upload_buku_tabungan',
        'pas_foto',
    ];

    public function index(Request $request): View
    {
        $query = $this->filteredQuery($request, $saringanDashboard);

        $kepeg = fn (string $kolom) => "(select {$kolom} from kepegawaians where kepegawaians.data_pegawai_id = data_pegawais.id limit 1)";
        $urut = Urutan::terapkan($query, [
            ...Urutan::tanggal('dibuat', 'Ditambahkan', 'data_pegawais.created_at'),
            ...Urutan::abjad('nama', 'Nama', 'data_pegawais.nama_lengkap'),
            ...Urutan::tanggal('masuk', 'Tanggal masuk', $kepeg('tanggal_masuk')),
            ...Urutan::abjad('jabatan', 'Jabatan', $kepeg('jabatan')),
            ...Urutan::abjad('unit', 'Bagian', $kepeg('unit_kerja')),
            ...Urutan::tanggal('diperbarui', 'Diperbarui', 'data_pegawais.updated_at'),
        ]);
        $pegawais = $query->paginate(15)->withQueryString();

        $unitKerjaOptions = config('kepegawaian.unit_kerja');

        return view('admin.pegawai.index', compact('pegawais', 'unitKerjaOptions', 'saringanDashboard') + $urut);
    }

    public function export(Request $request): BinaryFileResponse
    {
        $pegawais = $this->filteredQuery($request)->orderBy('id')->get();

        $filename = ExcelExporter::namaFile('data-pegawai');

        $headers = [
            'NIP', 'Nama Lengkap', 'Email', 'NIK', 'Jenis Kelamin', 'Tempat Lahir', 'Tanggal Lahir', 'Agama',
            'Status Pernikahan', 'Pendidikan Terakhir', 'No. HP', 'Alamat Domisili',
            'Jabatan', 'Jabatan Tambahan', 'Bagian', 'Rangkap Tugas', 'Lokasi Kerja',
            'Status Rekrutmen', 'Status Kerja', 'Tanggal Masuk', 'Habis Kontrak',
            'BPJS Ketenagakerjaan', 'BPJS Kesehatan', 'Kontak Darurat',
        ];

        $rows = $pegawais->map(function ($p) {
            $kepegawaian = $p->kepegawaian;
            $kontak = $p->kontakDarurat;

            return [
                $p->nip,
                $p->nama_lengkap,
                $p->email,
                $p->nik,
                $p->jenis_kelamin,
                $p->tempat_lahir,
                optional($p->tanggal_lahir)->format('d-m-Y'),
                $p->agama,
                $p->status_pernikahan,
                $p->pendidikan_terakhir,
                $p->no_hp,
                $p->alamat_domisili ?: $p->alamat_ktp,
                $kepegawaian?->jabatan,
                $kepegawaian?->jabatan_tambahan,
                $kepegawaian?->unit_kerja,
                $p->penugasanAktif->where('is_utama', false)->pluck('unit_kerja')->implode(', '),
                $kepegawaian?->lokasi_kerja,
                $kepegawaian?->status_rekrutmen,
                $kepegawaian?->status_kerja,
                optional($kepegawaian?->tanggal_masuk)->format('d-m-Y'),
                optional($kepegawaian?->tanggal_habis_kontrak)->format('d-m-Y'),
                $p->bpjs?->no_bpjs_ketenagakerjaan,
                $p->bpjs?->no_bpjs_kesehatan,
                $kontak ? trim("{$kontak->nama_kerabat} ({$kontak->hubungan_kerabat}) {$kontak->no_hp_kerabat}") : null,
            ];
        });

        return ExcelExporter::download($filename, $headers, $rows, 'Data Pegawai');
    }

    /** Ekspor lengkap satu pegawai: biodata, kepegawaian, riwayat penugasan, cuti, lembur (satu sheet per bagian). */
    public function exportDetail(string $id): BinaryFileResponse
    {
        $p = DataPegawai::with(['kepegawaian', 'kontakDarurat', 'bpjs', 'penugasans.dibuatOleh', 'pengajuanCutis', 'pengajuanLemburs'])->findOrFail($id);
        $this->authorizeDivisi($p);

        return ExcelExporter::downloadMultiSheet(ExcelExporter::namaFile('pegawai-' . str($p->nama_lengkap)->slug()), EksporPegawai::lembar($p));
    }

    private function filteredQuery(Request $request, ?array &$saringan = null)
    {
        $query = DataPegawai::with(['kepegawaian', 'kontakDarurat', 'bpjs', 'dokumen', 'penugasanAktif']);

        /** @var User|null $user */
        $user = Auth::user();

        if ($user?->isKepalaBagian()) {
            $query->whereHas('penugasanAktif', fn ($q) => $q->whereIn('unit_kerja', $user->unitsDipimpinUntukQuery()));
        }

        if ($search = $request->query('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('nama_lengkap', 'like', "%{$search}%")
                    ->orWhere('nip', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhereHas('kepegawaian', function ($q) use ($search) {
                        $q->where('jabatan', 'like', "%{$search}%");
                    });
            });
        }

        if ($unitKerja = $request->query('unit_kerja')) {
            $query->whereHas('penugasanAktif', fn ($q) => $q->where('unit_kerja', $unitKerja));
        }

        if ($status = $request->query('status_rekrutmen')) {
            $query->whereHas('kepegawaian', function ($q) use ($status) {
                $q->where('status_rekrutmen', $status);
            });
        }

        // Rentang tanggal masuk (kepegawaian.tanggal_masuk).
        if (FilterTanggal::aktif($request)) {
            $query->whereHas('kepegawaian', fn ($q) => FilterTanggal::terapkan($q, 'tanggal_masuk', $request));
        }

        // Filter dari grafik dashboard (status kerja, jenis kelamin, masa kerja, usia, pendidikan, area, bulan masuk/keluar, kontrak).
        $saringan = KelompokPegawai::terapkan($query, $request->query());

        return $query;
    }

    public function create(): View
    {
        $this->authorizeManage();

        return view('admin.pegawai.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeManage();

        $validated = $this->validateRequest($request);

        try {
            $pegawai = DB::transaction(function () use ($request, $validated) {
                $pegawai = DataPegawai::create($validated['biodata']);

                $pegawai->kepegawaian()->create($validated['kepegawaian']);
                PenugasanService::buatUtama($pegawai, $validated['kepegawaian']['unit_kerja'], $validated['kepegawaian']['jabatan'],
                    $validated['kepegawaian']['tanggal_masuk'] ?? null, oleh: Auth::user());
                $pegawai->kontakDarurat()->create($validated['kontak_darurat']);

                $pegawai->bpjs()->create($validated['bpjs']);

                $dokumenPaths = $this->handleFileUploads($request, $pegawai);
                $pegawai->dokumen()->create($dokumenPaths);

                return $pegawai;
            });
        } catch (\Throwable $e) {
            report($e);

            return redirect()->back()->withInput()->with('error', 'Gagal menyimpan data pegawai. Silakan coba lagi. (' . $e->getMessage() . ')');
        }

        return redirect()->route('admin.pegawai.show', $pegawai->id)->with('success', 'Data pegawai berhasil ditambahkan.');
    }

    private function authorizeManage(): void
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (! $user?->hasAbility(User::ABILITY_KELOLA_PEGAWAI)) {
            abort(403, 'Hanya Admin, Kepala SDM, dan Staff SDM yang bisa mengubah data pegawai.');
        }
    }

    private function authorizeDivisi(DataPegawai $pegawai): void
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (! $user || ! $user->isKepalaBagian()) {
            return;
        }

        $unitsPegawai = $pegawai->penugasanAktif->pluck('unit_kerja')->push($pegawai->kepegawaian?->unit_kerja)->filter()->all();
        if (! array_intersect($unitsPegawai, $user->unitsDipimpin())) {
            abort(403, 'Anda hanya bisa melihat data pegawai divisi Anda sendiri.');
        }
    }

    public function show(string $id): View
    {
        $pegawai = DataPegawai::with(['kepegawaian', 'kontakDarurat', 'bpjs', 'dokumen', 'penugasanAktif', 'penugasans.dibuatOleh'])
            ->findOrFail($id);

        $this->authorizeDivisi($pegawai);

        return view('admin.pegawai.show', [
            'pegawai' => $pegawai,
            ...$this->alurApproval($pegawai),
            // Tombol & dialog "Proses Keluar": hanya pegawai aktif, untuk pengelola data pegawai.
            'dampakKeluar' => $pegawai->kepegawaian?->status_rekrutmen === 'Aktif' && Auth::user()->can('kelola-pegawai')
                ? PegawaiKeluar::dampak($pegawai) : null,
        ]);
    }

    /**
     * @return array{akun: ?User, alurApproval: list<array{jenis: string, unit: ?string, tahap: array}>, unitsDisetujui: list<string>}
     */
    private function alurApproval(DataPegawai $pegawai): array
    {
        $akun = User::where('data_pegawai_id', $pegawai->id)
            ->orderByRaw('role = ? asc', [User::ROLE_ADMIN])
            ->first();

        if (! $akun) {
            return ['akun' => null, 'semuaAkun' => collect(), 'alurApproval' => [], 'unitsDisetujui' => [], 'alurKhusus' => null, 'bolehAturAkun' => false, 'pilihanAlur' => null];
        }

        $akun->setRelation('dataPegawai', $pegawai);
        $alur = [];

        if ($akun->hasAbility(User::ABILITY_AJUKAN_CUTI)) {
            $alur[] = ['jenis' => 'Cuti', 'unit' => $akun->unitKerja(), 'tahap' => PengajuanWorkflow::rute($akun, $akun->unitKerja())];
        }
        if ($akun->hasAbility(User::ABILITY_AJUKAN_LEMBUR)) {
            foreach ($akun->unitsLembur() as $unit) {
                $alur[] = ['jenis' => 'Lembur', 'unit' => $unit, 'tahap' => PengajuanWorkflow::rute($akun, $unit, lembur: true)];
            }
        }

        $semuaAkun = User::where('data_pegawai_id', $pegawai->id)->orderBy('id')->get()->each->setRelation('dataPegawai', $pegawai);
        $unitsDisetujui = $semuaAkun
            ->filter(fn (User $u) => $u->hasAbility(User::ABILITY_APPROVE))
            ->flatMap(fn (User $u) => $u->unitsDipimpin())
            ->unique()->values()->all();

        // Pengaturan alur khusus & wajib ganti password (Kepala SDM/Admin).
        $bolehAtur = Auth::user()->can('atur-akun-pegawai');

        return [
            'akun' => $akun,
            'semuaAkun' => $semuaAkun,
            'alurApproval' => $alur,
            'unitsDisetujui' => $unitsDisetujui,
            'alurKhusus' => AlurPegawai::with(['atasan', 'gm', 'pengubah'])->where('data_pegawai_id', $pegawai->id)->first(),
            'bolehAturAkun' => $bolehAtur,
            'pilihanAlur' => $bolehAtur ? AlurPegawaiController::pilihan($pegawai) : null,
        ];
    }

    public function edit(string $id): View
    {
        $this->authorizeManage();

        $pegawai = DataPegawai::with(['kepegawaian', 'kontakDarurat', 'bpjs', 'dokumen'])
            ->findOrFail($id);

        return view('admin.pegawai.edit', compact('pegawai'));
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        $this->authorizeManage();

        $pegawai = DataPegawai::with(['dokumen', 'bpjs', 'kepegawaian'])->findOrFail($id);
        $validated = $this->validateRequest($request, $pegawai->id);

        // Aktif ⇄ Tidak Aktif lewat form ini memakai proses keluar yang sama (akun jadi pelamar / dikembalikan).
        $statusLama = $pegawai->kepegawaian?->status_rekrutmen;
        $statusBaru = $validated['kepegawaian']['status_rekrutmen'] ?? $statusLama;
        $keluar = $statusLama === 'Aktif' && $statusBaru === 'Tidak Aktif';
        $batalKeluar = $statusLama === 'Tidak Aktif' && $statusBaru === 'Aktif' && $pegawai->kepegawaian?->keluar_snapshot;
        $tanggalKeluar = $validated['kepegawaian']['tanggal_keluar'] ?? null;
        if ($keluar || $batalKeluar) {
            unset($validated['kepegawaian']['status_rekrutmen'], $validated['kepegawaian']['tanggal_keluar']);
        }

        // Selain Admin, unit utama hanya bisa diubah lewat Mutasi. Admin boleh koreksi langsung.
        $unitBaru = Auth::user()->isAdmin() ? ($validated['kepegawaian']['unit_kerja'] ?? null) : null;
        unset($validated['kepegawaian']['unit_kerja']);

        try {
            DB::transaction(function () use ($request, $validated, $pegawai, $unitBaru) {
                $pegawai->update($validated['biodata']);

                $pegawai->penugasans()->aktif()->where('is_utama', true)->update(array_filter([
                    'jabatan' => $validated['kepegawaian']['jabatan'],
                    'unit_kerja' => $unitBaru,
                ]));
                if ($unitBaru) {
                    $validated['kepegawaian']['unit_kerja'] = $unitBaru;
                }

                $pegawai->kepegawaian()->updateOrCreate(
                    ['data_pegawai_id' => $pegawai->id],
                    $validated['kepegawaian']
                );
                if ($unitBaru) {
                    PenugasanService::sinkron($pegawai);
                }

                $pegawai->kontakDarurat()->updateOrCreate(
                    ['data_pegawai_id' => $pegawai->id],
                    $validated['kontak_darurat']
                );

                $pegawai->bpjs()->updateOrCreate(
                    ['data_pegawai_id' => $pegawai->id],
                    $validated['bpjs']
                );

                $dokumenPaths = $this->handleFileUploads($request, $pegawai, $pegawai->dokumen);
                if (! empty($dokumenPaths)) {
                    $pegawai->dokumen()->updateOrCreate(
                        ['data_pegawai_id' => $pegawai->id],
                        $dokumenPaths
                    );
                }
            });
        } catch (\Throwable $e) {
            report($e);

            return redirect()->back()->withInput()->with('error', 'Gagal memperbarui data pegawai. Silakan coba lagi. (' . $e->getMessage() . ')');
        }

        if ($keluar || $batalKeluar) {
            $pegawai->unsetRelation('kepegawaian');
            $hasil = $keluar
                ? PegawaiKeluar::proses($pegawai, $tanggalKeluar ?? now()->toDateString(), 'lainnya', 'Dinonaktifkan lewat Edit Data Pegawai.', Auth::user())
                : PegawaiKeluar::batalkan($pegawai);

            return redirect()->route('admin.pegawai.show', $pegawai->id)
                ->with('success', 'Data pegawai berhasil diperbarui' . ($keluar ? ' dan diproses keluar.' : '; proses keluar dibatalkan.'))
                ->with('peringatan_keluar', $hasil);
        }

        return redirect()->route('admin.pegawai.show', $pegawai->id)->with('success', 'Data pegawai berhasil diperbarui.');
    }

    public function destroy(string $id): RedirectResponse
    {
        $this->authorizeManage();

        $pegawai = DataPegawai::with(['dokumen', 'bpjs'])->findOrFail($id);

        try {
            DB::transaction(function () use ($pegawai) {
                if ($pegawai->dokumen) {
                    foreach ($this->dokumenFields as $field) {
                        if ($pegawai->dokumen->{$field}) {
                            Storage::disk('public')->delete($pegawai->dokumen->{$field});
                        }
                    }
                }

                $pegawai->delete();
            });
        } catch (\Throwable $e) {
            report($e);

            return redirect()->back()->with('error', 'Gagal menghapus data pegawai. Silakan coba lagi. (' . $e->getMessage() . ')');
        }

        return redirect()->route('admin.pegawai.index')->with('success', 'Data pegawai berhasil dihapus.');
    }

    private function validateRequest(Request $request, ?int $ignoreId = null): array
    {
        $aturan = [
            'email' => [
                'required', 'email', 'max:255',
                Rule::unique('data_pegawais', 'email')->ignore($ignoreId),
            ],
            'nip' => [
                'required', 'string', 'max:50',
                Rule::unique('data_pegawais', 'nip')->ignore($ignoreId),
            ],
            'nama_lengkap' => ['required', 'string', 'max:255'],
            'nik' => [
                'required', 'string', 'size:16',
                Rule::unique('data_pegawais', 'nik')->ignore($ignoreId),
            ],
            'npwp' => ['nullable', 'string', 'max:25'],
            'no_kk' => ['required', 'string', 'max:20'],
            'no_hp' => ['required', 'string', 'max:20'],
            'tempat_lahir' => ['required', 'string', 'max:255'],
            'tanggal_lahir' => ['required', 'date', 'before:today'],
            'jenis_kelamin' => ['required', Rule::in(['Laki-laki', 'Perempuan'])],
            'agama' => ['required', 'string', 'max:50'],
            'golongan_darah' => ['nullable', Rule::in(DataPegawai::GOLONGAN_DARAH)],
            'status_pernikahan' => ['required', Rule::in(['Belum Menikah', 'Menikah', 'Cerai Hidup', 'Cerai Mati'])],
            'jumlah_tanggungan' => ['nullable', 'integer', 'min:0', 'max:20'],
            'nama_ibu_kandung' => ['required', 'string', 'max:255'],
            'pendidikan_terakhir' => ['required', 'string', 'max:100'],
            'alamat_ktp' => ['required', 'string'],
            'alamat_domisili' => ['nullable', 'string'],
            'jabatan' => ['required', 'string', Rule::in(Jabatan::daftar())],
            'jabatan_tambahan' => ['nullable', 'string', Rule::in(Jabatan::daftar())],
            'unit_kerja' => [$ignoreId ? 'nullable' : 'required', 'string', Rule::in(config('kepegawaian.unit_kerja'))],
            'lokasi_kerja' => ['required', 'string', Rule::in(config('kepegawaian.lokasi_kerja'))],
            'tanggal_masuk' => ['required', 'date'],
            'status_rekrutmen' => ['required', 'string', 'max:100'],
            'status_kerja' => ['required', Rule::in(['Tetap', 'Kontrak', 'Magang', 'Harian Lepas', 'Probation'])],
            'tanggal_habis_kontrak' => ['nullable', 'date', 'after_or_equal:tanggal_masuk'],
            'tanggal_keluar' => ['nullable', 'date', 'after_or_equal:tanggal_masuk', 'before_or_equal:today'],
            'no_hp_kerabat' => ['required', 'string', 'max:20'],
            'nama_kerabat' => ['required', 'string', 'max:255'],
            'hubungan_kerabat' => ['required', 'string', 'max:100'],
            'no_bpjs_ketenagakerjaan' => ['nullable', 'string', 'max:30'],
            'no_bpjs_kesehatan' => ['nullable', 'string', 'max:30'],
            'faskes_tingkat_1' => ['nullable', 'string', 'max:255'],
            'upload_ktp' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
            'upload_kk' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
            'upload_cv' => ['nullable', 'file', 'mimes:pdf', 'max:2048'],
            'upload_ijazah_terakhir' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
            'upload_buku_tabungan' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
            'pas_foto' => ['nullable', 'file', 'mimes:jpg,jpeg,png', 'max:2048'],
        ];

        // Admin boleh menyimpan data yang belum lengkap (mis. data lama): hanya kolom yang wajib di database tetap wajib.
        if (Auth::user()?->isAdmin()) {
            // status_rekrutmen & status_kerja NOT NULL di database (pilihan form selalu terisi).
            $tetapWajib = ['nama_lengkap', 'jabatan', 'status_rekrutmen', 'status_kerja', ...($ignoreId ? [] : ['unit_kerja'])];
            foreach ($aturan as $kolom => $r) {
                if (! in_array($kolom, $tetapWajib, true)) {
                    $aturan[$kolom] = array_map(fn ($x) => $x === 'required' ? 'nullable' : $x, $r);
                }
            }
            $aturan['tanggal_lahir'] = ['nullable', 'date'];
        }

        $validated = $request->validate($aturan, [
            'nik.size' => 'NIK harus terdiri dari 16 digit.',
            'tanggal_lahir.before' => 'Tanggal lahir tidak boleh lebih dari hari ini.',
            'tanggal_habis_kontrak.after_or_equal' => 'Tanggal habis kontrak tidak boleh sebelum tanggal masuk.',
            'tanggal_keluar.after_or_equal' => 'Tanggal keluar tidak boleh sebelum tanggal masuk.',
            'tanggal_keluar.before_or_equal' => 'Tanggal keluar tidak boleh di masa depan.',
        ]);

        return [
            'biodata' => collect($validated)->only([
                'email', 'nip', 'nama_lengkap', 'nik', 'npwp', 'no_kk', 'no_hp',
                'tempat_lahir', 'tanggal_lahir', 'jenis_kelamin', 'agama',
                'golongan_darah', 'status_pernikahan', 'jumlah_tanggungan',
                'nama_ibu_kandung', 'pendidikan_terakhir', 'alamat_ktp', 'alamat_domisili',
            ])->toArray(),
            'kepegawaian' => collect($validated)->only([
                'jabatan', 'jabatan_tambahan', 'unit_kerja', 'lokasi_kerja',
                'tanggal_masuk', 'status_rekrutmen', 'status_kerja',
                'tanggal_habis_kontrak', 'tanggal_keluar',
            ])->toArray(),
            'kontak_darurat' => collect($validated)->only([
                'no_hp_kerabat', 'nama_kerabat', 'hubungan_kerabat',
            ])->toArray(),
            'bpjs' => collect($validated)->only([
                'no_bpjs_ketenagakerjaan', 'no_bpjs_kesehatan', 'faskes_tingkat_1',
            ])->toArray(),
        ];
    }

    private function handleFileUploads(Request $request, DataPegawai $pegawai, ?DokumenPegawai $existing = null): array
    {
        $paths = [];

        foreach ($this->dokumenFields as $field) {
            if ($request->hasFile($field)) {
                if ($existing && $existing->{$field}) {
                    Storage::disk('public')->delete($existing->{$field});
                }

                $paths[$field] = $request->file($field)->store($pegawai->folderDokumen(), 'public');
            }
        }

        return $paths;
    }
}
