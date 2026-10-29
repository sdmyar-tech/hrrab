<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DataPegawai;
use App\Models\DataPelamar;
use App\Models\Jabatan;
use App\Models\Lamaran;
use App\Models\Lowongan;
use App\Models\PengajuanFormasi;
use App\Models\User;
use App\Support\BaganStatis;
use App\Support\RekrutmenAccess;
use App\Support\RiwayatPelamar;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class KandidatInternalController extends Controller
{
    public const JENIS_LUAR = 'luar';

    public const JENIS_PEGAWAI = 'pegawai';

    public function create(): View
    {
        $this->authorizeReview();

        return view('admin.lamaran.internal-create', [
            'pegawais' => DataPegawai::with('kepegawaian')->orderBy('nama_lengkap')->get(['id', 'nama_lengkap', 'nip']),
            'lowongans' => Lowongan::where('status', '!=', Lowongan::STATUS_DRAFT)
                ->whereColumn('jumlah_terisi', '<', 'jumlah_dibutuhkan')
                ->latest()->get(['id', 'judul', 'unit_kerja', 'status', 'jumlah_terisi', 'jumlah_dibutuhkan']),
            'jabatanPerUnit' => $this->jabatanPerUnit(),
        ]);
    }

    /**
     * Gambaran posisi kandidat di bagan unit tujuan (HTML, dimuat formulir lewat fetch).
     * Dari lowongan: unit & jabatan lowongan; lowongan hasil formasi replacement ikut menandai pegawai yang digantikan.
     */
    public function bagan(Request $request): View
    {
        $this->authorizeReview();

        $lowongan = $request->filled('lowongan_id') ? Lowongan::with('pengajuanFormasi')->find($request->integer('lowongan_id')) : null;
        $unit = (string) ($lowongan?->unit_kerja ?? $request->query('unit', ''));
        $jabatan = (string) ($lowongan?->jabatan ?? $request->query('jabatan', ''));
        abort_if($unit === '' || $jabatan === '', 404);

        $formasi = $lowongan?->pengajuanFormasi;
        $diganti = $formasi?->jenis_kebutuhan === PengajuanFormasi::JENIS_REPLACEMENT ? ($formasi->diganti ?? []) : [];

        return view('admin.lamaran._bagan-kandidat', [
            'unit' => $unit,
            'jabatan' => $jabatan,
            'diganti' => $diganti,
            'bagan' => BaganStatis::susun(RekrutmenAccess::baganUnit($unit), $jabatan, $diganti, [
                ['nama' => 'Kandidat', 'judul' => 'Posisi yang akan diisi kandidat', 'kelas' => 'kosong slot kandidat'],
            ]),
        ]);
    }

    /**
     * Jabatan di tiap unit menurut Master Jabatan.
     * Dipakai formulir "Tanpa lowongan": pilih unit dulu, jabatan unit itu tampil paling atas.
     *
     * @return array<string, list<string>> unit => nama jabatan (sesuai Master Jabatan)
     */
    private function jabatanPerUnit(): array
    {
        return Jabatan::perUnit();
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeReview();

        $validated = $request->validate([
            'jenis' => ['required', Rule::in([self::JENIS_LUAR, self::JENIS_PEGAWAI])],
            'posisi' => ['required', Rule::in(['lowongan', 'manual'])],
            'lowongan_id' => ['required_if:posisi,lowongan', 'nullable', 'exists:lowongans,id'],
            'jabatan_tujuan' => ['required_if:posisi,manual', 'nullable', Rule::in(Jabatan::daftar())],
            'unit_kerja_tujuan' => ['required_if:posisi,manual', 'nullable', Rule::in(config('kepegawaian.unit_kerja'))],
            'lokasi_kerja_tujuan' => ['nullable', Rule::in(config('kepegawaian.lokasi_kerja'))],
            'direkomendasikan_oleh' => ['nullable', 'string', 'max:150', Rule::in(DataPegawai::opsiPilihan()->pluck('nama'))],
            'data_pegawai_id' => ['required_if:jenis,pegawai', 'nullable', 'exists:data_pegawais,id'],
            ...$this->biodataRules($request),
        ], [
            'lowongan_id.required_if' => 'Pilih lowongan tujuan.',
            'jabatan_tujuan.required_if' => 'Jabatan tujuan wajib diisi.',
            'unit_kerja_tujuan.required_if' => 'Bagian tujuan wajib diisi.',
            'data_pegawai_id.required_if' => 'Pilih pegawai yang akan diikutkan seleksi.',
            'direkomendasikan_oleh.in' => 'Pilih pengusul dari daftar pegawai.',
            ...RiwayatPelamar::messages(),
        ]);

        $lowongan = $validated['posisi'] === 'lowongan' ? Lowongan::find($validated['lowongan_id']) : null;

        // Admin bebas dari batas kuota, lamaran ganda, dan jabatan sama.
        $admin = (bool) Auth::user()?->isAdmin();

        if ($lowongan?->isKuotaPenuh() && ! $admin) {
            return back()->withInput()->with('error', 'Kuota lowongan ini sudah terpenuhi.');
        }

        $posisi = $lowongan
            ? ['lowongan_id' => $lowongan->id]
            : collect($validated)->only(['jabatan_tujuan', 'unit_kerja_tujuan', 'lokasi_kerja_tujuan'])->all();

        $kandidat = $validated['jenis'] === self::JENIS_PEGAWAI
            ? $this->kandidatPegawai($validated, $lowongan)
            : $this->kandidatLuar($validated);

        if ($kandidat instanceof RedirectResponse) {
            return $kandidat;
        }

        $lamaran = Lamaran::create([
            ...$kandidat,
            ...$posisi,
            'jalur' => Lamaran::JALUR_INTERNAL,
            'direkomendasikan_oleh' => $validated['direkomendasikan_oleh'] ?? null,
            'status' => Lamaran::STATUS_PROSES,
            'tahap' => Lamaran::TAHAP_INTERVIEW,
            'diverifikasi_oleh' => Auth::id(),
            'diverifikasi_pada' => now(),
            'token_jadwal' => Str::random(40),
        ]);

        return redirect()
            ->route('admin.lamaran.show', $lamaran)
            ->with('success', 'Kandidat ditambahkan. Silakan jadwalkan Interview 1, lalu bagikan link jadwal ke kandidat.');
    }

    private function biodataRules(Request $request): array
    {
        if ($request->input('jenis') !== self::JENIS_LUAR) {
            return [];
        }

        $pelamarId = DataPelamar::where('email', $request->input('email'))->value('id');

        return [
            'nama_lengkap' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150'],
            'no_hp' => ['required', 'string', 'max:20'],
            'nik' => ['required', 'string', 'max:20', Rule::unique('data_pelamars', 'nik')->ignore($pelamarId)],
            'no_kk' => ['nullable', 'string', 'max:20'],
            'tempat_lahir' => ['required', 'string', 'max:150'],
            'tanggal_lahir' => ['required', 'date', 'before:today'],
            'jenis_kelamin' => ['required', 'in:Laki-laki,Perempuan'],
            'agama' => ['required', 'string', 'max:50'],
            'status_pernikahan' => ['required', 'in:Belum Menikah,Menikah,Cerai Hidup,Cerai Mati'],
            'nama_ibu_kandung' => ['required', 'string', 'max:150'],
            'pendidikan_terakhir' => ['required', 'string', 'max:100'],
            'alamat_ktp' => ['required', 'string', 'max:1000'],
            'alamat_domisili' => ['nullable', 'string', 'max:1000'],
            ...RiwayatPelamar::rules(),
        ];
    }

    private function kandidatLuar(array $validated): array|RedirectResponse
    {
        $emailInternal = User::where('email', $validated['email'])->where('role', '!=', User::ROLE_PELAMAR)->exists()
            || DataPegawai::where('email', $validated['email'])->exists();

        if ($emailInternal) {
            return back()->withInput()->with('error', 'Email ini milik pegawai yang sudah ada. Pilih jenis kandidat "Pegawai yang sudah ada".');
        }

        $biodata = collect($validated)->only([
            'nama_lengkap', 'email', 'no_hp', 'nik', 'no_kk', 'tempat_lahir', 'tanggal_lahir', 'jenis_kelamin',
            'agama', 'status_pernikahan', 'nama_ibu_kandung', 'pendidikan_terakhir', 'alamat_ktp', 'alamat_domisili',
        ])->all();

        $biodataLengkap = collect($validated)->only(RiwayatPelamar::FIELDS)->all();

        $pelamar = DB::transaction(function () use ($biodata, $biodataLengkap) {
            $pelamar = DataPelamar::firstOrNew(['email' => $biodata['email']]);
            foreach ($biodata as $kolom => $nilai) {
                if (blank($pelamar->{$kolom})) {
                    $pelamar->{$kolom} = $nilai;
                }
            }
            $pelamar->save();

            RiwayatPelamar::simpan($pelamar, $biodataLengkap, timpa: false);

            return $pelamar;
        });

        if (Lamaran::where('data_pelamar_id', $pelamar->id)->where('status', Lamaran::STATUS_PROSES)->exists() && ! Auth::user()?->isAdmin()) {
            return back()->withInput()->with('error', "{$pelamar->nama_lengkap} masih punya lamaran yang sedang berjalan.");
        }

        return ['data_pelamar_id' => $pelamar->id];
    }

    private function kandidatPegawai(array $validated, ?Lowongan $lowongan): array|RedirectResponse
    {
        $pegawai = DataPegawai::with('kepegawaian')->findOrFail($validated['data_pegawai_id']);

        if (Lamaran::where('data_pegawai_id', $pegawai->id)->where('status', Lamaran::STATUS_PROSES)->exists() && ! Auth::user()?->isAdmin()) {
            return back()->withInput()->with('error', "{$pegawai->nama_lengkap} masih punya proses seleksi yang sedang berjalan.");
        }

        $jabatan = $lowongan?->jabatan ?? $validated['jabatan_tujuan'] ?? null;
        $unit = $lowongan?->unit_kerja ?? $validated['unit_kerja_tujuan'] ?? null;

        if ($pegawai->kepegawaian?->jabatan === $jabatan && $pegawai->kepegawaian?->unit_kerja === $unit && ! Auth::user()?->isAdmin()) {
            return back()->withInput()->with('error', "{$pegawai->nama_lengkap} sudah menjabat {$jabatan} di {$unit}.");
        }

        return ['data_pegawai_id' => $pegawai->id];
    }

    private function authorizeReview(): void
    {
        /** @var User $user */
        $user = Auth::user();

        if (! RekrutmenAccess::canReview($user)) {
            abort(403, 'Hanya Kepala SDM/GM yang bisa menambahkan kandidat langsung.');
        }
    }
}
