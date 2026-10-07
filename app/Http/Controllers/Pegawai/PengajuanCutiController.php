<?php

namespace App\Http\Controllers\Pegawai;

use App\Http\Controllers\Controller;
use App\Models\PengajuanCuti;
use App\Support\PengajuanWorkflow;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use App\Support\Notifikasi;

class PengajuanCutiController extends Controller
{
    private const MIN_HARI_SEBELUM_CUTI = 7;

    public function index(): View
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $pegawai = $user->dataPegawai()->with('kepegawaian')->first();
        $kepegawaian = $pegawai?->kepegawaian;

        $masaKerjaCukup = $kepegawaian?->tanggal_masuk
            && Carbon::parse($kepegawaian->tanggal_masuk)->diffInYears(now()) >= 1;

        $sisaCuti = PengajuanCuti::sisaCutiTahunan($pegawai);

        $riwayat = $pegawai
            ? $pegawai->pengajuanCutis()->latest('tanggal_mulai')->latest('id')->get()
            : collect();

        return view('pegawai.pengajuan-cuti', [
            'pegawai' => $pegawai,
            'kepegawaian' => $kepegawaian,
            'eligible' => (bool) $masaKerjaCukup,
            'sisaCuti' => $sisaCuti,
            'annualQuota' => PengajuanCuti::ANNUAL_QUOTA,
            'maxPerRequest' => PengajuanCuti::MAX_PER_REQUEST,
            'jumlahPengajuanTahunIni' => PengajuanCuti::jumlahPengajuanTahunIni($pegawai),
            'maxPengajuanTahunan' => PengajuanCuti::MAX_PENGAJUAN_PER_TAHUN,
            'kategoriBesarOptions' => PengajuanCuti::KATEGORI_BESAR_OPTIONS,
            'riwayat' => $riwayat,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $pegawai = $user->dataPegawai()->with('kepegawaian')->first();
        $kepegawaian = $pegawai?->kepegawaian;

        $masaKerjaCukup = $kepegawaian?->tanggal_masuk
            && Carbon::parse($kepegawaian->tanggal_masuk)->diffInYears(now()) >= 1;

        if (! $pegawai || ! $masaKerjaCukup) {
            return redirect()
                ->route('pegawai.pengajuan-cuti')
                ->with('error', 'Anda belum bisa mengajukan cuti karena masa kerja belum genap 1 tahun.');
        }

        // Cuti mendadak (duka, melahirkan, bencana, panggilan) tidak terikat aturan H-7.
        $dadakan = PengajuanCuti::isDadakan($request->input('jenis_cuti'), $request->input('kategori_cuti_besar'));
        $tanggalMinimal = $dadakan
            ? now()
            : now()->addDays(self::MIN_HARI_SEBELUM_CUTI);

        $validated = $request->validate([
            'jenis_cuti' => ['required', 'in:tahunan,besar'],
            'kategori_cuti_besar' => ['required_if:jenis_cuti,besar', 'nullable', 'in:' . implode(',', array_keys(PengajuanCuti::KATEGORI_BESAR_OPTIONS))],
            'tanggal_mulai' => [
                'required',
                'date',
                'after_or_equal:' . $tanggalMinimal->format('Y-m-d'),
            ],
            'tanggal_selesai' => ['required', 'date', 'after_or_equal:tanggal_mulai'],
            'keterangan' => [$dadakan ? 'required' : 'nullable', 'string', 'max:1000'],
        ], [
            'kategori_cuti_besar.required_if' => 'Jenis cuti besar wajib dipilih.',
            'tanggal_mulai.after_or_equal' => $dadakan
                ? 'Cuti mendadak paling cepat dimulai hari ini (tidak bisa untuk tanggal yang sudah lewat).'
                : 'Tanggal mulai cuti minimal ' . self::MIN_HARI_SEBELUM_CUTI . ' hari setelah tanggal pengajuan.',
            'keterangan.required' => 'Keterangan wajib diisi untuk cuti mendadak (mis. nama keluarga & hubungan).',
            'tanggal_selesai.after_or_equal' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
        ]);

        $lamaCuti = Carbon::parse($validated['tanggal_mulai'])->diffInDays(Carbon::parse($validated['tanggal_selesai'])) + 1;

        if (PengajuanCuti::jumlahPengajuanTahunIni($pegawai, Carbon::parse($validated['tanggal_mulai'])->year) >= PengajuanCuti::MAX_PENGAJUAN_PER_TAHUN) {
            return redirect()->back()->withInput()->with(
                'error',
                'Pengajuan cuti sudah mencapai batas maksimal ' . PengajuanCuti::MAX_PENGAJUAN_PER_TAHUN . ' kali dalam setahun.'
            );
        }

        if (PengajuanCuti::adaBentrokBerturutTurut($pegawai, $validated['tanggal_mulai'], $validated['tanggal_selesai'])) {
            return redirect()->back()->withInput()->with(
                'error',
                'Tidak bisa mengajukan cuti berturut-turut/bentrok dengan cuti lain yang masih menunggu atau sudah disetujui. Beri jeda minimal 1 hari.'
            );
        }

        if ($validated['jenis_cuti'] === PengajuanCuti::JENIS_TAHUNAN) {
            if ($lamaCuti > PengajuanCuti::MAX_PER_REQUEST) {
                return redirect()->back()->withInput()->with(
                    'error',
                    'Cuti tahunan maksimal ' . PengajuanCuti::MAX_PER_REQUEST . ' hari per pengajuan.'
                );
            }

            $sisaCuti = PengajuanCuti::sisaCutiTahunan($pegawai);
            if ($lamaCuti > $sisaCuti) {
                return redirect()->back()->withInput()->with(
                    'error',
                    "Sisa cuti tahunan Anda hanya {$sisaCuti} hari."
                );
            }
        } else {
            $lamaCuti = $this->hitungHariKerja(
                Carbon::parse($validated['tanggal_mulai']),
                Carbon::parse($validated['tanggal_selesai'])
            );

            $durasiTetap = PengajuanCuti::durasiKategoriBesar($validated['kategori_cuti_besar']);
            if ($durasiTetap !== null && $lamaCuti !== $durasiTetap) {
                return redirect()->back()->withInput()->with(
                    'error',
                    'Cuti besar untuk kategori ini harus ' . $durasiTetap . ' hari kerja.'
                );
            }
        }

        $unit = $user->unitKerja();

        if ($error = PengajuanWorkflow::cekApprover($user, $unit)) {
            return redirect()->back()->withInput()->with('error', $error);
        }

        $pengajuan = $pegawai->pengajuanCutis()->create([
            'unit_kerja' => $unit,
            'jenis_cuti' => $validated['jenis_cuti'],
            'kategori_cuti_besar' => $validated['jenis_cuti'] === PengajuanCuti::JENIS_BESAR
                ? $validated['kategori_cuti_besar']
                : null,
            'tanggal_mulai' => $validated['tanggal_mulai'],
            'tanggal_selesai' => $validated['tanggal_selesai'],
            'lama_cuti' => $lamaCuti,
            'keterangan' => $validated['keterangan'] ?? null,
            'status' => PengajuanCuti::STATUS_PENDING,
            'current_level' => PengajuanWorkflow::initialLevel($user, $unit),
        ]);
        Notifikasi::pengajuanMenunggu($pengajuan);

        return redirect()
            ->route('pegawai.pengajuan-cuti')
            ->with('success', 'Pengajuan cuti berhasil dikirim. Menunggu persetujuan admin.');
    }

    private function hitungHariKerja(Carbon $mulai, Carbon $selesai): int
    {
        $hariKerja = 0;
        $tanggal = $mulai->copy();

        while ($tanggal->lte($selesai)) {
            if (! $tanggal->isWeekend()) {
                $hariKerja++;
            }
            $tanggal->addDay();
        }

        return $hariKerja;
    }
}
