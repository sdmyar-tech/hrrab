<?php

namespace App\Http\Controllers\Pegawai;

use App\Http\Controllers\Controller;
use App\Models\DataPegawai;
use App\Models\PengajuanCuti;
use App\Models\PengajuanIzinKeluar;
use App\Models\PengajuanLembur;
use App\Models\User;
use App\Support\Notifikasi;
use App\Support\PengajuanWorkflow;
use App\Support\Urutan;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PengajuanLemburController extends Controller
{
    /** Lembur yang lupa diajukan masih bisa dibuat paling lambat sekian hari setelah hari lembur. */
    private const MAKS_HARI_MUNDUR = 3;

    private const MAKS_HARI_KE_DEPAN = 14;

    /**
     * Jam kerja pegawai pada tanggal itu menurut Atur Jam Kerja di Absensi (null: hari libur/bukan hari kerjanya/jadwal belum diatur).
     *
     * @return array{masuk: string, pulang: string}|null
     */
    private function jamKerja(DataPegawai $pegawai, Carbon $tanggal): ?array
    {
        if (in_array($tanggal->toDateString(), config('hrrab.hari_libur', []), true)) {
            return null;
        }

        return $pegawai->jadwalKerja?->jamPada($tanggal);
    }

    /** Logika waktu: tidak menimpa jam kerja, lembur lain, cuti, atau izin tidak masuk di hari yang sama. */
    private function cekWaktu(DataPegawai $pegawai, string $tanggal, string $mulai, string $selesai): ?string
    {
        $tgl = Carbon::parse($tanggal);

        $jam = $this->jamKerja($pegawai, $tgl);
        if ($jam && $mulai < $jam['pulang'] && $selesai > $jam['masuk']) {
            return "Jam lembur tidak boleh bertumpuk dengan jam kerja Anda ({$jam['masuk']}–{$jam['pulang']}). Lembur sesudah jam kerja dimulai paling cepat jam {$jam['pulang']}.";
        }

        $lain = $pegawai->pengajuanLemburs()->whereDate('tanggal', $tanggal)->where('status', '!=', PengajuanLembur::STATUS_REJECTED)->get()
            ->first(fn ($l) => substr((string) $l->jam_mulai, 0, 5) < $selesai && substr((string) $l->jam_selesai, 0, 5) > $mulai);
        if ($lain) {
            return 'Sudah ada pengajuan lembur lain pada jam yang bertumpuk (' . substr((string) $lain->jam_mulai, 0, 5) . '–' . substr((string) $lain->jam_selesai, 0, 5) . ').';
        }

        if ($pegawai->pengajuanCutis()->where('status', '!=', PengajuanCuti::STATUS_REJECTED)
            ->whereDate('tanggal_mulai', '<=', $tanggal)->whereDate('tanggal_selesai', '>=', $tanggal)->exists()) {
            return 'Tanggal itu bertumpuk dengan cuti Anda (menunggu/disetujui), tidak bisa lembur.';
        }
        if ($pegawai->pengajuanIzinKeluars()->where('jenis', PengajuanIzinKeluar::JENIS_HARIAN)->bertumpuk($tanggal, $tanggal)->exists()) {
            return 'Tanggal itu bertumpuk dengan izin tidak masuk Anda (menunggu/disetujui), tidak bisa lembur.';
        }

        return null;
    }

    public function index(): View
    {
        /** @var User $user */
        $user = Auth::user();
        $pegawai = $user->dataPegawai;

        $urut = ['urutOpsi' => [], 'urutAktif' => null];
        $pengajuanLemburs = collect();
        if ($pegawai) {
            $query = $pegawai->pengajuanLemburs();
            $urut = Urutan::terapkan($query, [
                ...Urutan::tanggal('tgl', 'Tanggal lembur', 'pengajuan_lemburs.tanggal'),
                ...Urutan::tanggal('diajukan', 'Diajukan', 'pengajuan_lemburs.created_at'),
            ]);
            $pengajuanLemburs = $query->get();
        }

        $unitOptions = $user->unitsLembur();
        $alurPerUnit = collect($unitOptions)->mapWithKeys(fn ($unit) => [$unit => PengajuanWorkflow::tahapTampil(PengajuanWorkflow::rute($user, $unit, lembur: true))])
            ->filter()->all();

        // Jam kerja per hari (ISO 1–7) dari Atur Jam Kerja di Absensi: dasar jam mulai lembur.
        $jadwalHari = [];
        for ($d = 1; $d <= 7; $d++) {
            $jadwalHari[$d] = $pegawai?->jadwalKerja?->jamPada(Carbon::now()->startOfWeek()->addDays($d - 1));
        }

        return view('pegawai.pengajuan-lembur', compact('pengajuanLemburs', 'unitOptions', 'alurPerUnit', 'jadwalHari') + $urut + [
            'maksMundur' => self::MAKS_HARI_MUNDUR,
            'maksKeDepan' => self::MAKS_HARI_KE_DEPAN,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $pegawai = $user->dataPegawai;

        if (! $pegawai) {
            return redirect()
                ->route('pegawai.pengajuan-lembur')
                ->with('error', 'Data pegawai Anda belum lengkap. Lengkapi data terlebih dahulu.');
        }

        $unitOptions = $user->unitsLembur();

        $validated = $request->validate([
            'unit_kerja' => [count($unitOptions) > 1 ? 'required' : 'nullable', 'string', Rule::in($unitOptions)],
            // Lupa mengajukan: boleh dibuat paling lambat 3 hari setelah hari lembur; paling jauh 14 hari ke depan.
            'tanggal' => ['required', 'date', 'after_or_equal:' . now()->subDays(self::MAKS_HARI_MUNDUR)->toDateString(), 'before_or_equal:' . now()->addDays(self::MAKS_HARI_KE_DEPAN)->toDateString()],
            'uraian_tugas' => ['required', 'string', 'max:1000'],
            'jam_mulai' => ['required', 'date_format:H:i'],
            'jam_selesai' => ['required', 'date_format:H:i', 'after:jam_mulai'],
        ], [
            'unit_kerja.required' => 'Pilih bagian tempat Anda lembur.',
            'unit_kerja.in' => 'Bagian tidak valid untuk pengajuan lembur Anda.',
            'tanggal.required' => 'Tanggal wajib diisi.',
            'tanggal.after_or_equal' => 'Pengajuan lembur paling lambat dibuat ' . self::MAKS_HARI_MUNDUR . ' hari setelah hari lembur.',
            'tanggal.before_or_equal' => 'Lembur paling jauh diajukan ' . self::MAKS_HARI_KE_DEPAN . ' hari ke depan.',
            'uraian_tugas.required' => 'Uraian tugas wajib diisi.',
            'jam_mulai.required' => 'Jam mulai wajib diisi.',
            'jam_selesai.required' => 'Jam selesai wajib diisi.',
            'jam_selesai.after' => 'Jam selesai harus setelah jam mulai.',
        ]);

        if ($error = $this->cekWaktu($pegawai, $validated['tanggal'], $validated['jam_mulai'], $validated['jam_selesai'])) {
            return redirect()->back()->withInput()->with('error', $error);
        }

        $mulai = Carbon::createFromFormat('H:i', $validated['jam_mulai']);
        $selesai = Carbon::createFromFormat('H:i', $validated['jam_selesai']);
        $jumlahJam = round($mulai->diffInMinutes($selesai) / 60, 2);

        $unit = $validated['unit_kerja'] ?? $unitOptions[0];

        if ($error = PengajuanWorkflow::cekApprover($user, $unit, lembur: true)) {
            return redirect()->back()->withInput()->with('error', $error);
        }

        $pengajuan = $pegawai->pengajuanLemburs()->create([
            'unit_kerja' => $unit,
            'tanggal' => $validated['tanggal'],
            'hari' => Carbon::parse($validated['tanggal'])->translatedFormat('l'),
            'uraian_tugas' => $validated['uraian_tugas'],
            'jam_mulai' => $validated['jam_mulai'],
            'jam_selesai' => $validated['jam_selesai'],
            'jumlah_jam' => $jumlahJam,
            'status' => PengajuanLembur::STATUS_PENDING,
            ...PengajuanWorkflow::kolomAwal($user, $unit, lembur: true),
        ]);
        Notifikasi::pengajuanMenunggu($pengajuan);

        return redirect()
            ->route('pegawai.pengajuan-lembur')
            ->with('success', 'Pengajuan lembur berhasil dikirim. Menunggu persetujuan ' . $pengajuan->currentLevelLabel() . '.');
    }
}
