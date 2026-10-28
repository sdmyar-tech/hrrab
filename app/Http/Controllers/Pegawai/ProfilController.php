<?php

namespace App\Http\Controllers\Pegawai;

use App\Http\Controllers\Controller;
use App\Models\DataPegawai;
use App\Models\User;
use App\Support\PenugasanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfilController extends Controller
{
    private array $dokumenFields = [
        'upload_ktp',
        'upload_kk',
        'upload_cv',
        'upload_ijazah_terakhir',
        'upload_buku_tabungan',
        'pas_foto',
    ];

    public const AGAMA = ['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Konghucu'];

    public const GOLONGAN_DARAH = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-', 'Belum Diketahui'];

    public const STATUS_PERNIKAHAN = ['Belum Menikah', 'Menikah', 'Cerai Hidup', 'Cerai Mati'];

    public const HUBUNGAN_KERABAT = ['Ayah/Ibu', 'Istri/Suami', 'Anak', 'Adik/Kakak Kandung', 'Saudara', 'Lainnya'];

    private array $requiredFields = [
        'nama_lengkap', 'nik', 'no_kk', 'no_hp', 'tempat_lahir', 'tanggal_lahir',
        'jenis_kelamin', 'agama', 'alamat_ktp', 'status_pernikahan',
        'nama_ibu_kandung', 'pendidikan_terakhir',
    ];

    public function dataPegawai(): View|RedirectResponse
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (! $user || ! $user->isStaff()) {
            return redirect()->route('admin.dashboard')->with('error', 'Anda tidak berhak mengakses halaman ini.');
        }

        $user->load([
            'dataPegawai.kepegawaian',
            'dataPegawai.kontakDarurat',
            'dataPegawai.bpjs',
            'dataPegawai.dokumen',
        ]);

        return view('pegawai.data-pegawai', compact('user'));
    }

    public function pengkinianData(): View|RedirectResponse
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (! $user || ! $user->isStaff()) {
            return redirect()->route('admin.dashboard')->with('error', 'Anda tidak berhak mengakses halaman ini.');
        }

        $user->load([
            'dataPegawai.kepegawaian',
            'dataPegawai.kontakDarurat',
            'dataPegawai.bpjs',
            'dataPegawai.dokumen',
        ]);

        if ($this->isDataLengkap($user)) {
            return redirect()
                ->route('pegawai.data-pegawai')
                ->with('info', 'Data Anda sudah lengkap. Untuk mengubah, silakan hubungi Admin HRD.');
        }

        return view('pegawai.pengkinian-data', compact('user'));
    }

    public function storePengkinianData(Request $request): RedirectResponse
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (! $user || ! $user->isStaff()) {
            return redirect()->route('admin.dashboard')->with('error', 'Anda tidak berhak mengakses halaman ini.');
        }

        if ($this->isDataLengkap($user)) {
            return redirect()
                ->route('pegawai.data-pegawai')
                ->with('warning', 'Data Anda sudah lengkap. Hubungi Admin HRD untuk perubahan.');
        }

        foreach (['jabatan', 'hubungan_kerabat'] as $field) {
            if ($request->input($field) === 'Lainnya' && $request->filled("{$field}_lainnya")) {
                $request->merge([$field => trim($request->input("{$field}_lainnya"))]);
            }
        }

        $validated = $request->validate([
            'nama_lengkap' => ['sometimes', 'required', 'string', 'max:255'],
            'nik' => ['sometimes', 'required', 'string', 'size:16'],
            'npwp' => ['nullable', 'string', 'max:25'],
            'no_kk' => ['sometimes', 'required', 'string', 'max:20'],
            'no_hp' => ['sometimes', 'required', 'string', 'max:20'],
            'tempat_lahir' => ['sometimes', 'required', 'string', 'max:255'],
            'tanggal_lahir' => ['sometimes', 'required', 'date', 'before:today'],
            'jenis_kelamin' => ['sometimes', 'required', Rule::in(['Laki-laki', 'Perempuan'])],
            'agama' => ['sometimes', 'required', 'string', 'max:50'],
            'golongan_darah' => ['nullable', Rule::in(self::GOLONGAN_DARAH)],
            'alamat_ktp' => ['sometimes', 'required', 'string'],
            'alamat_domisili' => ['nullable', 'string'],
            'status_pernikahan' => ['sometimes', 'required', Rule::in(self::STATUS_PERNIKAHAN)],
            'jumlah_tanggungan' => ['nullable', 'integer', 'min:0', 'max:20'],
            'nama_ibu_kandung' => ['sometimes', 'required', 'string', 'max:255'],
            'pendidikan_terakhir' => ['sometimes', 'required', 'string', 'max:100'],

            'jabatan' => ['sometimes', 'required', 'string', 'max:255', 'not_in:Lainnya'],
            'unit_kerja' => ['sometimes', 'required', 'string', 'max:255'],
            'lokasi_kerja' => ['sometimes', 'required', 'string', 'max:255'],
            'tanggal_masuk' => ['sometimes', 'required', 'date'],

            'no_hp_kerabat' => ['sometimes', 'required', 'string', 'max:20'],
            'nama_kerabat' => ['sometimes', 'required', 'string', 'max:255'],
            'hubungan_kerabat' => ['sometimes', 'required', 'string', 'max:100', 'not_in:Lainnya'],

            'no_bpjs_ketenagakerjaan' => ['nullable', 'string', 'max:30'],
            'no_bpjs_kesehatan' => ['nullable', 'string', 'max:30'],
            'faskes_tingkat_1' => ['nullable', 'string', 'max:255'],

            'upload_ktp' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
            'upload_kk' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
            'upload_cv' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
            'upload_ijazah_terakhir' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
            'upload_buku_tabungan' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
            'pas_foto' => ['nullable', 'file', 'mimes:jpg,jpeg,png', 'max:2048'],
        ], [
            'nik.size' => 'NIK harus 16 digit.',
            'tanggal_lahir.before' => 'Tanggal lahir tidak boleh hari ini atau masa depan.',
            'jabatan.not_in' => 'Tuliskan jabatan Anda pada kolom "Jabatan lainnya".',
            'hubungan_kerabat.not_in' => 'Tuliskan hubungan dengan kerabat pada kolom "Hubungan lainnya".',
        ]);

        try {
            DB::transaction(function () use ($request, $validated, $user) {
                $pegawai = $user->dataPegawai;

                $biodataFields = [
                    'nama_lengkap', 'nik', 'npwp', 'no_kk', 'no_hp',
                    'tempat_lahir', 'tanggal_lahir', 'jenis_kelamin', 'agama',
                    'golongan_darah', 'status_pernikahan', 'jumlah_tanggungan',
                    'nama_ibu_kandung', 'pendidikan_terakhir',
                    'alamat_ktp', 'alamat_domisili',
                ];

                $biodata = collect($validated)->only($biodataFields)->toArray();

                if (isset($biodata['jumlah_tanggungan'])) {
                    $biodata['jumlah_tanggungan'] = (int) $biodata['jumlah_tanggungan'];
                }

                if (! $pegawai) {
                    $biodata['email'] = $user->email;
                    $biodata['nip'] = $user->nip ?? '-';

                    $pegawai = DataPegawai::create($biodata);

                    $user->data_pegawai_id = $pegawai->id;
                    $user->save();
                } else {
                    $pegawai->update($biodata);
                }

                $kepegawaianData = collect($validated)->only([
                    'jabatan', 'unit_kerja',
                    'lokasi_kerja', 'tanggal_masuk',
                ])->toArray();

                $unitLama = $pegawai->kepegawaian()->value('unit_kerja');
                if ($unitLama) {
                    unset($kepegawaianData['unit_kerja']);
                }

                if (! empty($kepegawaianData)) {
                    $pegawai->kepegawaian()->updateOrCreate(
                        ['data_pegawai_id' => $pegawai->id],
                        $kepegawaianData
                    );

                    if (! $unitLama && ! empty($kepegawaianData['unit_kerja']) && ! $pegawai->penugasans()->exists()) {
                        PenugasanService::buatUtama($pegawai, $kepegawaianData['unit_kerja'], $kepegawaianData['jabatan'] ?? null,
                            min($kepegawaianData['tanggal_masuk'] ?? now()->toDateString(), now()->toDateString()));
                    } elseif (! empty($kepegawaianData['jabatan'])) {
                        $pegawai->penugasans()->aktif()->where('is_utama', true)->update(['jabatan' => $kepegawaianData['jabatan']]);
                    }
                }

                $kontakData = collect($validated)->only([
                    'no_hp_kerabat', 'nama_kerabat', 'hubungan_kerabat',
                ])->toArray();

                if (! empty($kontakData)) {
                    $pegawai->kontakDarurat()->updateOrCreate(
                        ['data_pegawai_id' => $pegawai->id],
                        $kontakData
                    );
                }

                $bpjsData = collect($validated)->only([
                    'no_bpjs_ketenagakerjaan', 'no_bpjs_kesehatan', 'faskes_tingkat_1',
                ])->toArray();

                if (! empty($bpjsData)) {
                    $pegawai->bpjs()->updateOrCreate(
                        ['data_pegawai_id' => $pegawai->id],
                        $bpjsData
                    );
                }

                $dokumenPaths = [];
                foreach ($this->dokumenFields as $field) {
                    if ($request->hasFile($field)) {
                        if ($pegawai->dokumen && $pegawai->dokumen->{$field}) {
                            Storage::disk('public')->delete($pegawai->dokumen->{$field});
                        }
                        $dokumenPaths[$field] = $request->file($field)
                            ->store($pegawai->folderDokumen(), 'public');
                    }
                }

                if (! empty($dokumenPaths)) {
                    $pegawai->dokumen()->updateOrCreate(
                        ['data_pegawai_id' => $pegawai->id],
                        $dokumenPaths
                    );
                }
            });
        } catch (\Throwable $e) {
            report($e);

            return redirect()->back()
                ->withInput()
                ->with('error', 'Gagal menyimpan data: ' . $e->getMessage());
        }

        $user->refresh()->load('dataPegawai.kepegawaian', 'dataPegawai.kontakDarurat', 'dataPegawai.dokumen');

        if ($this->isDataLengkap($user)) {
            return redirect()
                ->route('pegawai.data-pegawai')
                ->with('success', 'Selamat! Data Anda sudah lengkap. Terima kasih.');
        }

        return redirect()
            ->route('pegawai.pengkinian-data')
            ->with('success', 'Data berhasil disimpan. Silakan lengkapi field yang masih kosong.');
    }

    private function isDataLengkap($user): bool
    {
        $pegawai = $user->dataPegawai;

        if (! $pegawai) {
            return false;
        }

        foreach ($this->requiredFields as $field) {
            if (empty($pegawai->{$field})) {
                return false;
            }
        }

        if (! $pegawai->kepegawaian) {
            return false;
        }
        foreach (['jabatan', 'unit_kerja', 'lokasi_kerja', 'tanggal_masuk'] as $field) {
            if (empty($pegawai->kepegawaian->{$field})) {
                return false;
            }
        }

        if (! $pegawai->kontakDarurat) {
            return false;
        }
        foreach (['no_hp_kerabat', 'nama_kerabat', 'hubungan_kerabat'] as $field) {
            if (empty($pegawai->kontakDarurat->{$field})) {
                return false;
            }
        }

        if (! $pegawai->dokumen) {
            return false;
        }
        foreach (['upload_ktp', 'upload_kk', 'pas_foto'] as $field) {
            if (empty($pegawai->dokumen->{$field})) {
                return false;
            }
        }

        return true;
    }
}
