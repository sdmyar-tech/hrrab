<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\EksporController;
use App\Models\DataPegawai;
use App\Models\InterviewJadwal;
use App\Models\KontrakJadwal;
use App\Models\Lamaran;
use App\Models\Lowongan;
use App\Models\Penugasan;
use App\Models\ScreeningOnboarding;
use App\Models\User;
use App\Support\ExcelExporter;
use App\Support\FilterTanggal;
use App\Support\InterviewFlow;
use App\Support\Kalender;
use App\Support\Notifikasi;
use App\Support\OnboardingFlow;
use App\Support\PenugasanService;
use App\Support\RekrutmenAccess;
use App\Support\Urutan;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LamaranController extends Controller
{
    public function index(Request $request): View
    {
        /** @var User $user */
        $user = Auth::user();

        $query = $this->filteredQuery($request, $user);
        // Nama kandidat: pelamar umum (data_pelamars) atau kandidat internal (data_pegawais).
        $namaKandidat = 'coalesce((select nama_lengkap from data_pelamars where data_pelamars.id = lamarans.data_pelamar_id), '
            . Urutan::namaPegawai('lamarans') . ')';
        $urut = Urutan::terapkan($query, [
            ...Urutan::tanggal('masuk', 'Lamaran masuk', 'lamarans.created_at'),
            ...Urutan::tanggal('diperbarui', 'Diperbarui', 'lamarans.updated_at'),
            ...Urutan::abjad('nama', 'Nama', $namaKandidat),
            ...Urutan::abjad('jabatan', 'Jabatan', 'lamarans.jabatan_tujuan'),
            ...Urutan::abjad('unit', 'Bagian', 'lamarans.unit_kerja_tujuan'),
        ]);
        $lamarans = $query->paginate(15)->withQueryString();

        return view('admin.lamaran.index', [
            'lamarans' => $lamarans,
            'canReview' => RekrutmenAccess::canReview($user),
            ...$urut,
        ]);
    }

    public function export(Request $request): BinaryFileResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $lamarans = $this->filteredQuery($request, $user)
            ->with(['interviewJadwals', 'diverifikasiOleh', 'diputuskanOleh'])->orderBy('id')->get();

        $headers = [
            'Tanggal Melamar', 'Nama Kandidat', 'Email', 'No. HP', 'NIK', 'Jenis Kelamin', 'Pendidikan Terakhir',
            'Jalur', 'Posisi', 'Bagian Tujuan', 'Status', 'Tahap', 'Interview Lulus',
            'Diverifikasi Oleh', 'Diputuskan Oleh', 'Tanggal Keputusan', 'Catatan',
        ];

        $rows = $lamarans->map(function (Lamaran $l) {
            $orang = $l->dataPegawai ?? $l->dataPelamar;

            return [
                optional($l->created_at)->format('d-m-Y H:i'),
                $l->namaKandidat(),
                $l->emailKandidat(),
                $orang?->no_hp,
                $orang?->nik,
                $orang?->jenis_kelamin,
                $orang?->pendidikan_terakhir,
                $l->isJalurInternal() ? 'Jalur Langsung' : 'Lowongan',
                $l->judulPosisi(),
                $l->unitKerjaTujuan(),
                $l->statusLabel(),
                $l->tahapLabel(),
                $l->interviewJadwals->where('hasil_status', InterviewJadwal::HASIL_LULUS)->count(),
                $l->diverifikasiOleh?->name,
                $l->diputuskanOleh?->name,
                optional($l->diputuskan_pada)->format('d-m-Y'),
                $l->catatan_admin,
            ];
        });

        return ExcelExporter::download(ExcelExporter::namaFile('lamaran-masuk'), $headers, $rows, 'Lamaran Masuk');
    }

    /** Semua jadwal rekrutmen (interview, onboarding, kontrak) dari lamaran sesuai filter aktif, satu sheet per jenis. */
    public function exportJadwal(Request $request): BinaryFileResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $lamarans = $this->filteredQuery($request, $user)
            ->with(['interviewJadwals.interviewer', 'screeningOnboardings', 'kontrakJadwals'])->orderBy('id')->get();

        return ExcelExporter::downloadMultiSheet(ExcelExporter::namaFile('jadwal-rekrutmen'), EksporController::sheetJadwal($lamarans));
    }

    private function filteredQuery(Request $request, User $user)
    {
        $query = Lamaran::with([
            'dataPelamar.user',
            'dataPelamar.lamarans' => fn ($q) => $q->with('lowongan')->latest(),
            'dataPegawai.kepegawaian',
            'lowongan',
        ]);

        if (! RekrutmenAccess::canLihatSemua($user)) {
            $units = $user->unitsDipimpinUntukQuery();
            $query->where(function ($q) use ($units) {
                $q->whereHas('lowongan', fn ($l) => $l->whereIn('unit_kerja', $units))
                    ->orWhere(fn ($t) => $t->whereNull('lowongan_id')->whereIn('unit_kerja_tujuan', $units));
            });
        }

        if ($request->filled('jalur')) {
            $query->where('jalur', $request->string('jalur'));
        }

        if ($search = $request->query('q')) {
            $cari = function ($q) use ($search) {
                $q->where('nama_lengkap', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            };
            $query->where(fn ($q) => $q->whereHas('dataPelamar', $cari)->orWhereHas('dataPegawai', $cari));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('lowongan_id')) {
            $query->where('lowongan_id', $request->integer('lowongan_id'));
        }

        // Rentang tanggal lamaran masuk.
        FilterTanggal::terapkan($query, 'lamarans.created_at', $request);

        return $query;
    }

    public function show(Lamaran $lamaran): View
    {
        $this->authorizeAccess($lamaran);

        $lamaran->load(['dataPelamar.riwayatPekerjaan', 'dataPelamar.riwayatPendidikan', 'dataPegawai.kepegawaian', 'lowongan', 'interviewJadwals.interviewer',
            'screeningOnboardings.pengawas', 'screeningOnboardings.kepala', 'screeningOnboardings.konfirmasiSdmOleh', 'screeningOnboardings.konfirmasiKepalaOleh', 'kontrakJadwals']);

        /** @var User $user */
        $user = Auth::user();

        $tahapBerikutnya = InterviewFlow::tahapBerikutnya($lamaran);
        $onboardingLulus = OnboardingFlow::onboardingLulus($lamaran);

        $pengawasOptions = User::whereIn('role', [User::ROLE_STAFF, User::ROLE_KEPALA_BAGIAN, User::ROLE_KEPALA_SDM, User::ROLE_ADMIN, User::ROLE_GM])
            ->orderBy('name')
            ->get(['id', 'name', 'role']);

        return view('admin.lamaran.show', [
            'lamaran' => $lamaran,
            'canReview' => RekrutmenAccess::canReview($user),
            'tahapBerikutnya' => $tahapBerikutnya,
            'interviewerBerikutnya' => $tahapBerikutnya ? InterviewFlow::interviewerUntukTahap($lamaran, $tahapBerikutnya) : null,
            'bisaTambahLanjutan' => InterviewFlow::bisaTambahLanjutan($lamaran),
            'opsiInterviewerLanjutan' => InterviewFlow::opsiInterviewerLanjutan($lamaran),
            'bisaKonfirmasiInterview' => InterviewFlow::bisaKonfirmasiSelesai($lamaran),
            'jumlahInterviewLulus' => InterviewFlow::jumlahLulus($lamaran),
            'pengawasOptions' => $pengawasOptions,
            'kepalaTerkait' => OnboardingFlow::kepalaTerkait($lamaran),
            'onboardingLulus' => $onboardingLulus,
            'tanggalKontrakMinimal' => $onboardingLulus ? OnboardingFlow::tanggalKontrakMinimal($onboardingLulus) : null,
            'admin' => $user->isAdmin(),
            'opsiInterviewerAdmin' => $user->isAdmin() ? InterviewFlow::opsiInterviewerAdmin() : collect(),
        ]);
    }

    public function verifikasiTerima(Lamaran $lamaran): RedirectResponse
    {
        $this->authorizeReview();

        if (! $lamaran->isMenungguVerifikasi()) {
            return redirect()->back()->with('error', 'Lamaran ini sudah diverifikasi.');
        }

        $lamaran->update([
            'status' => Lamaran::STATUS_PROSES,
            'tahap' => Lamaran::TAHAP_INTERVIEW,
            'diverifikasi_oleh' => Auth::id(),
            'diverifikasi_pada' => now(),
        ]);

        return redirect()->back()->with('success', 'Data pelamar diverifikasi. Silakan jadwalkan Interview 1 (Kepala SDM).');
    }

    public function verifikasiTolak(Request $request, Lamaran $lamaran): RedirectResponse
    {
        $this->authorizeReview();

        if (! $lamaran->isMenungguVerifikasi()) {
            return redirect()->back()->with('error', 'Lamaran ini sudah diverifikasi.');
        }

        $validated = $request->validate([
            'catatan_admin' => ['required', 'string', 'max:1000'],
        ], [
            'catatan_admin.required' => 'Alasan penolakan wajib diisi.',
        ]);

        $lamaran->update([
            'status' => Lamaran::STATUS_DITOLAK,
            'keputusan' => Lamaran::KEPUTUSAN_TOLAK_ADMIN,
            'tahap' => Lamaran::TAHAP_SELESAI,
            'catatan_admin' => $validated['catatan_admin'],
            'diverifikasi_oleh' => Auth::id(),
            'diverifikasi_pada' => now(),
            'diputuskan_oleh' => Auth::id(),
            'diputuskan_pada' => now(),
        ]);

        return redirect()->back()->with('success', 'Lamaran ditolak pada tahap verifikasi. Pelamar kembali ke mode Guest dan bisa melamar ulang.');
    }

    public function interviewStore(Request $request, Lamaran $lamaran): RedirectResponse
    {
        $this->authorizeReview();

        // Form "Tambah Interview (Admin)" mengirim tahap_interview; form biasa tetap mengikuti alur normal.
        if ($this->adminBebas() && $request->has('tahap_interview')) {
            return $this->interviewStoreAdmin($request, $lamaran);
        }

        if ($lamaran->tahap !== Lamaran::TAHAP_INTERVIEW) {
            return redirect()->back()->with('error', 'Lamaran belum berada di tahap interview.');
        }

        $lamaran->load('lowongan', 'interviewJadwals');
        $tahap = InterviewFlow::tahapBerikutnya($lamaran);
        $lanjutan = ! $tahap && InterviewFlow::bisaTambahLanjutan($lamaran);

        if (! $tahap && ! $lanjutan) {
            return redirect()->back()->with('error', 'Tidak ada interview yang bisa dijadwalkan saat ini.');
        }

        $validated = $request->validate([
            'interviewer_id' => [$lanjutan ? 'required' : 'prohibited', Rule::in(InterviewFlow::opsiInterviewerLanjutan($lamaran)->pluck('id'))],
            'tanggal' => ['required', 'date', ...$this->aturanTanggal()],
            'waktu_mulai' => ['required', 'date_format:H:i', $this->aturanJamBelumLewat()],
            'waktu_selesai' => ['nullable', 'date_format:H:i', 'after:waktu_mulai'],
            'lokasi' => ['nullable', 'string', 'max:255'],
            'catatan' => ['nullable', 'string', 'max:1000'],
        ], [
            'interviewer_id.required' => 'Pilih interviewer untuk interview lanjutan.',
            'waktu_selesai.after' => 'Jam selesai harus setelah jam mulai.',
            'tanggal.after_or_equal' => 'Tanggal interview tidak boleh di masa lalu.',
        ]);

        $interviewer = $lanjutan
            ? User::find($validated['interviewer_id'])
            : InterviewFlow::interviewerUntukTahap($lamaran, $tahap);

        if (! $interviewer) {
            return redirect()->back()->with('error', 'Belum ada akun Kepala SDM untuk menjadi interviewer tahap 1.');
        }

        unset($validated['interviewer_id']);
        $jadwal = $this->buatJadwal($lamaran, $tahap ?? InterviewJadwal::TAHAP_LANJUTAN, $interviewer, $validated);

        return redirect()->back()->with('success', "Jadwal {$jadwal->tahapLabel()} berhasil dibuat dengan interviewer {$interviewer->name}.");
    }

    /** Admin: tambah interview kapan saja, tahap & interviewer bebas (tanpa urutan tahap). */
    private function interviewStoreAdmin(Request $request, Lamaran $lamaran): RedirectResponse
    {
        $v = $request->validate([
            'tahap_interview' => ['required', Rule::in([InterviewJadwal::TAHAP_KEPALA_SDM, InterviewJadwal::TAHAP_KEPALA_BAGIAN, InterviewJadwal::TAHAP_LANJUTAN])],
            'interviewer_id' => ['required', Rule::in(InterviewFlow::opsiInterviewerAdmin()->pluck('id'))],
            'tanggal' => ['required', 'date'],
            'waktu_mulai' => ['required', 'date_format:H:i'],
            'waktu_selesai' => ['nullable', 'date_format:H:i', 'after:waktu_mulai'],
            'lokasi' => ['nullable', 'string', 'max:255'],
            'catatan' => ['nullable', 'string', 'max:1000'],
        ], [
            'interviewer_id.required' => 'Pilih interviewer.',
            'waktu_selesai.after' => 'Jam selesai harus setelah jam mulai.',
        ]);

        $interviewer = User::find($v['interviewer_id']);
        $tahap = (int) $v['tahap_interview'];
        unset($v['interviewer_id'], $v['tahap_interview']);
        $jadwal = $this->buatJadwal($lamaran, $tahap, $interviewer, $v);

        return redirect()->back()->with('success', "{$jadwal->tahapLabel()} dijadwalkan dengan {$interviewer->name} (Mode Admin).");
    }

    /** Admin: hapus jadwal interview (status apa pun). */
    public function interviewDestroy(Lamaran $lamaran, InterviewJadwal $interview): RedirectResponse
    {
        abort_unless($this->adminBebas(), 403);
        abort_if($interview->lamaran_id !== $lamaran->id, 404);

        $label = $interview->tahapLabel();
        $interview->delete();

        return redirect()->back()->with('success', "Jadwal {$label} dihapus.");
    }

    private function buatJadwal(Lamaran $lamaran, int $tahap, User $interviewer, array $jadwal): InterviewJadwal
    {
        return tap($lamaran->interviewJadwals()->create([
            ...$jadwal,
            'interviewer_id' => $interviewer->id,
            'pertemuan_ke' => $lamaran->interviewJadwals()->max('pertemuan_ke') + 1,
            'tahap_interview' => $tahap,
            'status' => InterviewJadwal::STATUS_TERJADWAL,
            'dijadwalkan_oleh' => Auth::id(),
        ]), fn (InterviewJadwal $j) => Notifikasi::interviewDijadwalkan($j));
    }

    public function interviewUpdate(Request $request, Lamaran $lamaran, InterviewJadwal $interview): RedirectResponse
    {
        if ($interview->lamaran_id !== $lamaran->id) {
            abort(404);
        }

        /** @var User $user */
        $user = Auth::user();

        if (! InterviewFlow::bisaUbahJadwal($user, $interview)) {
            abort(403, 'Jadwal ini sudah selesai/dibatalkan atau Anda tidak berwenang mengubahnya.');
        }

        // Aturan tanggal/jam hanya berlaku bila jadwalnya dipindah (ubah lokasi/catatan saja tetap boleh).
        $dipindah = $request->input('tanggal') !== $interview->tanggal->toDateString()
            || $request->input('waktu_mulai') !== substr((string) $interview->waktu_mulai, 0, 5);

        $validated = $request->validate([
            'tanggal' => ['required', 'date', ...($dipindah ? $this->aturanTanggal() : [])],
            'waktu_mulai' => ['required', 'date_format:H:i', ...($dipindah ? [$this->aturanJamBelumLewat()] : [])],
            'waktu_selesai' => ['nullable', 'date_format:H:i', 'after:waktu_mulai'],
            'lokasi' => ['nullable', 'string', 'max:255'],
            'catatan' => ['nullable', 'string', 'max:1000'],
            // Khusus Admin: ganti interviewer, tahap, dan status (mis. buka lagi interview yang sudah selesai).
            ...($this->adminBebas() ? [
                'interviewer_id' => ['sometimes', Rule::in(InterviewFlow::opsiInterviewerAdmin()->pluck('id'))],
                'tahap_interview' => ['sometimes', Rule::in([InterviewJadwal::TAHAP_KEPALA_SDM, InterviewJadwal::TAHAP_KEPALA_BAGIAN, InterviewJadwal::TAHAP_LANJUTAN])],
                'status' => ['sometimes', Rule::in([InterviewJadwal::STATUS_TERJADWAL, InterviewJadwal::STATUS_SELESAI, InterviewJadwal::STATUS_DIBATALKAN])],
            ] : []),
        ], [
            'waktu_selesai.after' => 'Jam selesai harus setelah jam mulai.',
            'tanggal.after_or_equal' => 'Tanggal interview tidak boleh di masa lalu.',
        ]);

        // Kembali ke Terjadwal = hasil dikosongkan (interview diulang).
        if (($validated['status'] ?? null) === InterviewJadwal::STATUS_TERJADWAL && $interview->status !== InterviewJadwal::STATUS_TERJADWAL) {
            $validated += ['hasil_status' => null, 'catatan_hasil' => null, 'diisi_oleh' => null, 'diisi_pada' => null];
        }
        $interview->update($validated);

        return redirect()->back()->with('success', "Jadwal {$interview->tahapLabel()} berhasil diperbarui.");
    }

    public function interviewCancel(Request $request, Lamaran $lamaran, InterviewJadwal $interview): RedirectResponse
    {
        $this->authorizeReview();

        if ($interview->lamaran_id !== $lamaran->id) {
            abort(404);
        }

        $validated = $request->validate([
            'catatan' => ['required', 'string', 'max:1000'],
        ], [
            'catatan.required' => 'Alasan pembatalan wajib diisi.',
        ]);

        $interview->update([
            'status' => InterviewJadwal::STATUS_DIBATALKAN,
            'catatan' => $validated['catatan'],
        ]);

        return redirect()->back()->with('success', 'Jadwal interview dibatalkan.');
    }

    public function interviewHasil(Request $request, Lamaran $lamaran, InterviewJadwal $interview): RedirectResponse
    {
        if ($interview->lamaran_id !== $lamaran->id) {
            abort(404);
        }

        /** @var User $user */
        $user = Auth::user();

        if (! InterviewFlow::bisaIsiHasil($user, $interview)) {
            abort(403, 'Hanya interviewer jadwal ini atau Kepala SDM/GM yang bisa mengisi hasil interview.');
        }

        $validated = $request->validate([
            'hasil_status' => ['required', 'in:lulus,dipertimbangkan,tidak_lulus'],
            'catatan_hasil' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($interview->tanggal->isAfter(now()->endOfDay()) && ! $this->adminBebas()) {
            return redirect()->back()->with('error', 'Hasil interview baru bisa diisi pada hari interview ('
                . $interview->tanggal->translatedFormat('l, d M Y') . '). Ubah jadwalnya bila interview dimajukan.');
        }

        $tidakLulus = $validated['hasil_status'] === InterviewJadwal::HASIL_TIDAK_LULUS;
        $hasilLama = $interview->hasil_status;
        // Admin mengubah hasil Tidak Lulus → lamaran yang ditolak karena interview ini dibuka kembali.
        $bukaLagi = $this->adminBebas() && $hasilLama === InterviewJadwal::HASIL_TIDAK_LULUS && ! $tidakLulus
            && $lamaran->status === Lamaran::STATUS_DITOLAK && str_starts_with((string) $lamaran->catatan_admin, 'Tidak lulus ' . $interview->tahapLabel());

        DB::transaction(function () use ($interview, $lamaran, $validated, $user, $tidakLulus, $bukaLagi) {
            if ($bukaLagi) {
                $lamaran->update(['status' => Lamaran::STATUS_PROSES, 'tahap' => Lamaran::TAHAP_INTERVIEW, 'keputusan' => null,
                    'catatan_admin' => null, 'diputuskan_oleh' => null, 'diputuskan_pada' => null]);
            }

            $interview->update([
                ...$validated,
                'status' => InterviewJadwal::STATUS_SELESAI,
                'diisi_oleh' => $user->id,
                'diisi_pada' => now(),
            ]);

            if ($tidakLulus && ! $lamaran->isSelesai()) {
                $catatan = $validated['catatan_hasil'] ?? null;
                $alasan = "Tidak lulus {$interview->tahapLabel()}" . (filled($catatan) ? ": {$catatan}" : '.');

                $lamaran->update([
                    'status' => Lamaran::STATUS_DITOLAK,
                    'keputusan' => Lamaran::KEPUTUSAN_TOLAK_ADMIN,
                    'tahap' => Lamaran::TAHAP_SELESAI,
                    'catatan_admin' => $alasan,
                    'diputuskan_oleh' => $user->id,
                    'diputuskan_pada' => now(),
                ]);
            }
        });

        if ($tidakLulus) {
            return redirect()->back()->with('success', "Hasil {$interview->tahapLabel()}: Tidak Lulus. Lamaran otomatis ditolak.");
        }
        if ($bukaLagi) {
            $lamaran->refresh();
        }

        if ($validated['hasil_status'] !== InterviewJadwal::HASIL_LULUS || $lamaran->tahap !== Lamaran::TAHAP_INTERVIEW) {
            return redirect()->back()->with('success', 'Hasil interview berhasil disimpan.');
        }

        $lamaran->load('lowongan', 'interviewJadwals');

        if ($interview->tahap_interview === InterviewJadwal::TAHAP_KEPALA_SDM
            && InterviewFlow::tahapBerikutnya($lamaran) === InterviewJadwal::TAHAP_KEPALA_BAGIAN) {
            $interviewer2 = InterviewFlow::interviewerTahap2($lamaran);

            // Interview 2 dijadwalkan hari kerja berikutnya (jam sama) agar tidak bentrok dengan slot Interview 1.
            $this->buatJadwal($lamaran, InterviewJadwal::TAHAP_KEPALA_BAGIAN, $interviewer2, [
                'tanggal' => Kalender::hariKerjaSetelah($interview->tanggal->copy()->max(now()->startOfDay()))->toDateString(),
                'waktu_mulai' => $interview->waktu_mulai,
                'waktu_selesai' => $interview->waktu_selesai,
                'lokasi' => $interview->lokasi,
                'catatan' => 'Lanjutan otomatis dari Interview 1.',
            ]);

            return redirect()->back()->with('success', "Interview 1 lulus. Interview 2 otomatis dijadwalkan hari kerja berikutnya dengan {$interviewer2->name} (bisa diubah).");
        }

        return redirect()->back()->with('success', InterviewFlow::bisaKonfirmasiSelesai($lamaran)
            ? "{$interview->tahapLabel()} lulus. Tambah interview lanjutan bila perlu, atau klik \"Konfirmasi Interview Selesai\" untuk lanjut ke onboarding."
            : "{$interview->tahapLabel()} lulus.");
    }

    public function interviewSelesai(Lamaran $lamaran): RedirectResponse
    {
        $this->authorizeReview();

        $lamaran->load('lowongan', 'interviewJadwals');

        if (! InterviewFlow::bisaKonfirmasiSelesai($lamaran) && ! $this->adminBebas()) {
            return redirect()->back()->with('error', 'Interview belum bisa dikonfirmasi selesai: minimal ' . InterviewFlow::MIN_LULUS
                . ' interview Lulus (termasuk Interview 1) dan tidak ada jadwal yang masih berjalan.');
        }

        $lamaran->update(['tahap' => Lamaran::TAHAP_SCREENING]);

        return redirect()->back()->with('success', 'Interview dikonfirmasi selesai (' . InterviewFlow::jumlahLulus($lamaran) . ' interview lulus). Lanjut ke screening/onboarding.');
    }

    public function screeningStore(Request $request, Lamaran $lamaran): RedirectResponse
    {
        $this->authorizeReview();

        if ($lamaran->tahap !== Lamaran::TAHAP_SCREENING && ! $this->adminBebas()) {
            return redirect()->back()->with('error', 'Lamaran belum berada di tahap screening/onboarding.');
        }

        if ($lamaran->screeningOnboardings()->where('status', ScreeningOnboarding::STATUS_BERJALAN)->exists() && ! $this->adminBebas()) {
            return redirect()->back()->with('error', 'Masih ada masa onboarding yang berjalan.');
        }

        $validated = $request->validate([
            'pengawas_id' => ['required', 'exists:users,id'],
            'tanggal_mulai' => ['required', 'date', ...$this->aturanTanggal()],
            'durasi_hari' => ['required', 'integer', 'min:' . OnboardingFlow::DURASI_MIN, 'max:' . ($this->adminBebas() ? 365 : OnboardingFlow::DURASI_MAX)],
            'lokasi' => ['nullable', 'string', 'max:255'],
            'catatan' => ['nullable', 'string', 'max:1000'],
        ], [
            'pengawas_id.required' => 'Pilih pengawas terlebih dahulu.',
        ]);

        $tanggalMulai = Carbon::parse($validated['tanggal_mulai']);
        $kepala = OnboardingFlow::kepalaTerkait($lamaran->loadMissing('lowongan'));

        $screening = $lamaran->screeningOnboardings()->create([
            ...$validated,
            'kepala_id' => $kepala?->id,
            'tanggal_mulai' => $tanggalMulai,
            'tanggal_selesai' => OnboardingFlow::tanggalSelesai($tanggalMulai, (int) $validated['durasi_hari']),
            'status' => ScreeningOnboarding::STATUS_BERJALAN,
            'dijadwalkan_oleh' => Auth::id(),
        ]);
        Notifikasi::onboardingDimulai($screening);

        return redirect()->back()->with('success', "Onboarding {$validated['durasi_hari']} hari dijadwalkan mulai {$tanggalMulai->translatedFormat('d M Y')}. "
            . 'Konfirmasi hasil oleh Kepala SDM' . ($kepala ? " & {$kepala->name} (Kepala Bagian)" : '') . '.');
    }

    public function screeningHasil(Request $request, Lamaran $lamaran, ScreeningOnboarding $screening): RedirectResponse
    {
        if ($screening->lamaran_id !== $lamaran->id) {
            abort(404);
        }

        $validated = $request->validate([
            'sisi' => ['required', Rule::in([OnboardingFlow::SISI_SDM, OnboardingFlow::SISI_KEPALA])],
            'hasil_status' => ['required', Rule::in([ScreeningOnboarding::HASIL_LULUS, ScreeningOnboarding::HASIL_TIDAK_LULUS])],
            'catatan_hasil' => ['nullable', 'string', 'max:1000'],
        ]);

        /** @var User $user */
        $user = Auth::user();
        $screening->setRelation('lamaran', $lamaran);

        if (! OnboardingFlow::bisaKonfirmasi($user, $screening, $validated['sisi'])) {
            abort(403, 'Anda tidak berwenang mengonfirmasi onboarding ini, atau konfirmasinya sudah diisi.');
        }

        if ($validated['hasil_status'] === ScreeningOnboarding::HASIL_LULUS && ! OnboardingFlow::bolehKonfirmasiLulus($screening) && ! $this->adminBebas()) {
            return redirect()->back()->with('error', 'Hasil Lulus baru bisa dikonfirmasi mulai hari terakhir onboarding ('
                . $screening->tanggal_selesai->translatedFormat('l, d M Y') . '). Tidak Lulus bisa dikonfirmasi kapan saja.');
        }

        $pesan = OnboardingFlow::konfirmasi($screening, $validated['sisi'], $validated['hasil_status'], $validated['catatan_hasil'] ?? null, $user);

        return redirect()->back()->with('success', $pesan);
    }

    public function kontrakStore(Request $request, Lamaran $lamaran): RedirectResponse
    {
        $this->authorizeReview();

        $onboarding = OnboardingFlow::onboardingLulus($lamaran->load('screeningOnboardings'));

        if ((! $onboarding || $lamaran->tahap !== Lamaran::TAHAP_KONTRAK) && ! $this->adminBebas()) {
            return redirect()->back()->with('error', 'Jadwal kontrak hanya bisa dibuat setelah onboarding dinyatakan Lulus.');
        }

        if ($lamaran->kontrakJadwals()->where('status', KontrakJadwal::STATUS_TERJADWAL)->exists() && ! $this->adminBebas()) {
            return redirect()->back()->with('error', 'Sudah ada jadwal kontrak. Ubah jadwal yang ada.');
        }

        $validated = $this->validasiKontrak($request, $onboarding);

        $lamaran->kontrakJadwals()->create([
            ...$validated,
            'status' => KontrakJadwal::STATUS_TERJADWAL,
            'dijadwalkan_oleh' => Auth::id(),
        ]);

        return redirect()->back()->with('success', 'Jadwal tanda tangan kontrak berhasil dibuat.');
    }

    public function kontrakUpdate(Request $request, Lamaran $lamaran, KontrakJadwal $kontrak): RedirectResponse
    {
        $this->authorizeReview();

        if ($kontrak->lamaran_id !== $lamaran->id) {
            abort(404);
        }

        $onboarding = OnboardingFlow::onboardingLulus($lamaran->load('screeningOnboardings'));

        if (($kontrak->status !== KontrakJadwal::STATUS_TERJADWAL || ! $onboarding || $lamaran->isSelesai()) && ! $this->adminBebas()) {
            return redirect()->back()->with('error', 'Jadwal kontrak ini tidak bisa diubah lagi.');
        }

        $data = $this->validasiKontrak($request, $onboarding);
        if ($this->adminBebas() && $request->filled('status')) {
            $data['status'] = $request->validate(['status' => [Rule::in([KontrakJadwal::STATUS_TERJADWAL, KontrakJadwal::STATUS_SELESAI, KontrakJadwal::STATUS_DIBATALKAN])]])['status'];
        }
        $kontrak->update($data);

        return redirect()->back()->with('success', 'Jadwal tanda tangan kontrak diperbarui.');
    }

    /** Admin: hapus jadwal kontrak. */
    public function kontrakDestroy(Lamaran $lamaran, KontrakJadwal $kontrak): RedirectResponse
    {
        abort_unless($this->adminBebas(), 403);
        abort_if($kontrak->lamaran_id !== $lamaran->id, 404);
        $kontrak->delete();

        return redirect()->back()->with('success', 'Jadwal kontrak dihapus.');
    }

    /** Admin: ubah onboarding apa pun (tanggal, durasi, pengawas, kepala, status, lokasi, arahan). */
    public function screeningUpdate(Request $request, Lamaran $lamaran, ScreeningOnboarding $screening): RedirectResponse
    {
        abort_unless($this->adminBebas(), 403);
        abort_if($screening->lamaran_id !== $lamaran->id, 404);

        $v = $request->validate([
            'pengawas_id' => ['required', 'exists:users,id'],
            'kepala_id' => ['nullable', 'exists:users,id'],
            'tanggal_mulai' => ['required', 'date'],
            'durasi_hari' => ['required', 'integer', 'min:1', 'max:365'],
            'status' => ['required', Rule::in([ScreeningOnboarding::STATUS_BERJALAN, ScreeningOnboarding::STATUS_SELESAI, ScreeningOnboarding::STATUS_DIBATALKAN])],
            'lokasi' => ['nullable', 'string', 'max:255'],
            'catatan' => ['nullable', 'string', 'max:1000'],
        ]);
        $mulai = Carbon::parse($v['tanggal_mulai']);
        $screening->update([...$v, 'tanggal_mulai' => $mulai, 'tanggal_selesai' => OnboardingFlow::tanggalSelesai($mulai, (int) $v['durasi_hari'])]);

        return redirect()->back()->with('success', 'Onboarding diperbarui: ' . $screening->tanggal_mulai->translatedFormat('d M') . ' – ' . $screening->tanggal_selesai->translatedFormat('d M Y') . '.');
    }

    /** Batalkan onboarding: Kepala SDM/GM saat berjalan; Admin kapan saja. */
    public function screeningCancel(Request $request, Lamaran $lamaran, ScreeningOnboarding $screening): RedirectResponse
    {
        $this->authorizeReview();
        abort_if($screening->lamaran_id !== $lamaran->id, 404);

        if ($screening->status !== ScreeningOnboarding::STATUS_BERJALAN && ! $this->adminBebas()) {
            return redirect()->back()->with('error', 'Hanya onboarding yang sedang berjalan yang bisa dibatalkan.');
        }
        $alasan = $request->validate(['catatan' => ['required', 'string', 'max:1000']], ['catatan.required' => 'Alasan pembatalan wajib diisi.'])['catatan'];
        $screening->update(['status' => ScreeningOnboarding::STATUS_DIBATALKAN, 'catatan_hasil' => 'Dibatalkan: ' . $alasan]);

        return redirect()->back()->with('success', 'Onboarding dibatalkan.');
    }

    /** Admin: hapus onboarding. */
    public function screeningDestroy(Lamaran $lamaran, ScreeningOnboarding $screening): RedirectResponse
    {
        abort_unless($this->adminBebas(), 403);
        abort_if($screening->lamaran_id !== $lamaran->id, 404);
        $screening->delete();

        return redirect()->back()->with('success', 'Data onboarding dihapus.');
    }

    private function validasiKontrak(Request $request, ?ScreeningOnboarding $onboarding): array
    {
        // Admin bebas memilih tanggal; selain admin minimal di hari terakhir onboarding.
        $minimal = $onboarding && ! $this->adminBebas() ? OnboardingFlow::tanggalKontrakMinimal($onboarding) : null;

        return $request->validate([
            'tanggal' => ['required', 'date', ...($minimal ? ['after_or_equal:' . $minimal->toDateString()] : []), ...$this->aturanTanggal(false)],
            // Kontrak kini bisa di hari yang sama: jam yang sudah lewat ditolak.
            'waktu' => ['nullable', 'date_format:H:i', $this->aturanJamBelumLewat()],
            'lokasi' => ['nullable', 'string', 'max:255'],
            'catatan' => ['nullable', 'string', 'max:1000'],
        ], [
            'tanggal.after_or_equal' => 'Tanggal kontrak paling cepat ' . $minimal?->translatedFormat('d M Y') . ' (hari terakhir onboarding).',
        ]);
    }

    public function rekrut(Request $request, Lamaran $lamaran): RedirectResponse
    {
        $this->authorizeReview();

        if ($lamaran->status === Lamaran::STATUS_DITERIMA || ($lamaran->isSelesai() && ! $this->adminBebas())) {
            return redirect()->back()->with('error', 'Lamaran ini sudah memiliki keputusan akhir.');
        }

        $lamaran->load(['dataPelamar.user', 'dataPegawai.kepegawaian', 'lowongan', 'screeningOnboardings']);

        $onboarding = OnboardingFlow::onboardingLulus($lamaran);

        if (! $onboarding && ! $this->adminBebas()) {
            return redirect()->back()->with('error', 'Rekrut hanya bisa dilakukan setelah onboarding dinyatakan Lulus oleh Kepala SDM & Kepala Bagian.');
        }

        if (! $this->adminBebas()) {
            // Rekrut = setelah tanda tangan kontrak: tidak boleh sebelum tanggal kontrak yang dijadwalkan.
            $kontrak = $lamaran->kontrakJadwals()->where('status', KontrakJadwal::STATUS_TERJADWAL)->orderBy('tanggal')->first();
            if ($kontrak && $kontrak->tanggal->gt(now()->startOfDay())) {
                return redirect()->back()->with('error', 'Rekrut dilakukan setelah tanda tangan kontrak. Jadwal kontrak: '
                    . $kontrak->tanggal->translatedFormat('l, d F Y') . '. Majukan jadwal kontrak bila perlu.');
            }

            // Kuota lowongan tidak boleh terlampaui.
            if ($lamaran->lowongan?->isKuotaPenuh()) {
                return redirect()->back()->with('error', 'Kuota lowongan sudah terpenuhi (' . $lamaran->lowongan->jumlah_terisi . '/' . $lamaran->lowongan->jumlah_dibutuhkan
                    . '). Tambah jumlah kebutuhan pada lowongan terlebih dahulu, atau tolak kandidat ini.');
            }
        }

        // Tanggal masuk = hari pertama onboarding; Admin tanpa onboarding mengisi manual.
        if ($onboarding) {
            $request->merge(['tanggal_masuk' => $onboarding->tanggal_mulai->toDateString()]);
        }

        if ($lamaran->isKandidatPegawai()) {
            return $this->tetapkanPosisiPegawai($request, $lamaran);
        }

        $pelamar = $lamaran->dataPelamar;

        if (! $pelamar->nik) {
            return redirect()->back()->with('error', 'Pelamar belum melengkapi NIK pada data pelamar. Minta pelamar melengkapi datanya terlebih dahulu sebelum direkrut.');
        }

        // Mantan pegawai (NIK sama, sudah keluar) melamar lagi: data pegawai lamanya diaktifkan kembali, bukan dibuat baru.
        $mantan = DataPegawai::with('kepegawaian')->where('nik', $pelamar->nik)->first();
        if ($mantan && $mantan->kepegawaian?->status_rekrutmen !== 'Tidak Aktif') {
            return redirect()->back()->with('error', "NIK pelamar ini sudah terdaftar sebagai pegawai aktif ({$mantan->nama_lengkap}, NIP {$mantan->nip}).");
        }

        $validated = $request->validate([
            'nip' => ['required', 'string', 'max:50', Rule::unique('data_pegawais', 'nip')->ignore($mantan?->id)],
            'tanggal_masuk' => ['required', 'date'],
            'status_kerja' => ['required', 'in:Tetap,Kontrak,Magang,Harian Lepas,Probation'],
            'masa_kontrak_bulan' => ['nullable', 'integer', 'in:3,6,9,12,15,18,21,24,27,30,33,36'],
        ], [
            'nip.unique' => 'NIP sudah digunakan pegawai lain.',
        ]);

        $tanggalHabisKontrak = ! empty($validated['masa_kontrak_bulan'])
            ? Kalender::habisKontrak(Carbon::parse($validated['tanggal_masuk']), (int) $validated['masa_kontrak_bulan'])
            : null;

        DB::transaction(function () use ($validated, $lamaran, $pelamar, $tanggalHabisKontrak, $mantan) {
            $biodata = [
                'email' => $pelamar->email,
                'nip' => $validated['nip'],
                'nama_lengkap' => $pelamar->nama_lengkap,
                'nik' => $pelamar->nik,
                'npwp' => $pelamar->npwp,
                'no_kk' => $pelamar->no_kk,
                'no_hp' => $pelamar->no_hp,
                'tempat_lahir' => $pelamar->tempat_lahir,
                'tanggal_lahir' => $pelamar->tanggal_lahir,
                'jenis_kelamin' => $pelamar->jenis_kelamin,
                'agama' => $pelamar->agama,
                'golongan_darah' => $pelamar->golongan_darah,
                'status_pernikahan' => $pelamar->status_pernikahan,
                'jumlah_tanggungan' => $pelamar->jumlah_tanggungan ?? 0,
                'nama_ibu_kandung' => $pelamar->nama_ibu_kandung,
                'pendidikan_terakhir' => $pelamar->pendidikan_terakhir,
                'alamat_ktp' => $pelamar->alamat_ktp,
                'alamat_domisili' => $pelamar->alamat_domisili,
            ];
            $kepegawaian = [
                'jabatan' => $lamaran->jabatanTujuan(),
                'unit_kerja' => $lamaran->unitKerjaTujuan(),
                'lokasi_kerja' => $lamaran->lokasiKerjaTujuan() ?? $lamaran->unitKerjaTujuan(),
                'tanggal_masuk' => $validated['tanggal_masuk'],
                'status_rekrutmen' => 'Aktif',
                'status_kerja' => $validated['status_kerja'],
                'tanggal_habis_kontrak' => $tanggalHabisKontrak,
            ];

            if ($mantan) {
                // Email pegawai lain tidak ditimpa (kolom unik); status absensi dikembalikan seperti sebelum keluar.
                if (DataPegawai::where('email', $biodata['email'])->whereKeyNot($mantan->id)->exists()) {
                    unset($biodata['email']);
                }
                $ikutAbsensi = (bool) ($mantan->kepegawaian?->keluar_snapshot['ikut_absensi'] ?? false);
                $mantan->update($biodata);
                $mantan->forceFill(['ikut_absensi' => $ikutAbsensi])->save();
                $mantan->kepegawaian()->updateOrCreate(['data_pegawai_id' => $mantan->id], $kepegawaian);
                $pegawai = $mantan;
            } else {
                $pegawai = DataPegawai::create($biodata);
                $pegawai->kepegawaian()->create($kepegawaian);
            }
            PenugasanService::buatUtama($pegawai, $lamaran->unitKerjaTujuan(), $lamaran->jabatanTujuan(), $validated['tanggal_masuk'], oleh: Auth::user());

            if ($pelamar->user) {
                $pelamar->user->update([
                    'role' => User::ROLE_STAFF,
                    'nip' => $validated['nip'],
                    'data_pegawai_id' => $pegawai->id,
                ]);
            }

            $this->tandaiDiterima($lamaran);
        });

        return redirect()
            ->route('admin.lamaran.index')
            ->with('success', 'Pelamar berhasil direkrut dan telah masuk ke Data Pegawai.');
    }

    private function tetapkanPosisiPegawai(Request $request, Lamaran $lamaran): RedirectResponse
    {
        $pegawai = $lamaran->dataPegawai;

        $validated = $request->validate([
            'tanggal_masuk' => ['required', 'date'],
            'status_kerja' => ['required', 'in:Tetap,Kontrak,Magang,Harian Lepas,Probation'],
            'masa_kontrak_bulan' => ['nullable', 'integer', 'in:3,6,9,12,15,18,21,24,27,30,33,36'],
        ]);

        $tanggalHabisKontrak = ! empty($validated['masa_kontrak_bulan'])
            ? Kalender::habisKontrak(Carbon::parse($validated['tanggal_masuk']), (int) $validated['masa_kontrak_bulan'])
            : null;

        try {
            $peringatan = DB::transaction(function () use ($lamaran, $pegawai, $validated, $tanggalHabisKontrak) {
                $pegawai->kepegawaian()->updateOrCreate(['data_pegawai_id' => $pegawai->id], [
                    'lokasi_kerja' => $lamaran->lokasiKerjaTujuan() ?? $pegawai->kepegawaian?->lokasi_kerja ?? $lamaran->unitKerjaTujuan(),
                    'status_kerja' => $validated['status_kerja'],
                    'tanggal_habis_kontrak' => $tanggalHabisKontrak,
                ]);

                $peringatan = PenugasanService::mutasi($pegawai, [
                    'unit_kerja' => $lamaran->unitKerjaTujuan(),
                    'jabatan' => $lamaran->jabatanTujuan(),
                    'peran' => $pegawai->penugasanAktif->firstWhere('is_utama', true)?->peran ?? Penugasan::PERAN_ANGGOTA,
                    'tanggal_efektif' => $validated['tanggal_masuk'],
                    'keterangan' => "Hasil seleksi jalur langsung (lamaran #{$lamaran->id})",
                ], Auth::user());

                $this->tandaiDiterima($lamaran);

                return $peringatan;
            });
        } catch (ValidationException $e) {
            return redirect()->back()->withInput()->with('error', collect($e->errors())->flatten()->first());
        }

        return redirect()
            ->route('admin.lamaran.index')
            ->with('success', "{$pegawai->nama_lengkap} ditetapkan pada posisi {$lamaran->jabatanTujuan()} ({$lamaran->unitKerjaTujuan()})."
                . ($peringatan ? ' ' . implode(' ', $peringatan) : ''));
    }

    private function tandaiDiterima(Lamaran $lamaran): void
    {
        $lamaran->update([
            'status' => Lamaran::STATUS_DITERIMA,
            'keputusan' => Lamaran::KEPUTUSAN_REKRUT,
            'tahap' => Lamaran::TAHAP_SELESAI,
            'diputuskan_oleh' => Auth::id(),
            'diputuskan_pada' => now(),
        ]);
        // Kontrak sudah ditandatangani: jadwal kontrak yang masih terjadwal ditandai selesai (tidak muncul lagi di agenda).
        $lamaran->kontrakJadwals()->where('status', KontrakJadwal::STATUS_TERJADWAL)->update(['status' => KontrakJadwal::STATUS_SELESAI, 'updated_at' => now()]);

        if (! $lamaran->lowongan) {
            return;
        }

        $lamaran->lowongan()->increment('jumlah_terisi');
        $lamaran->lowongan->refresh();

        if ($lamaran->lowongan->isKuotaPenuh()) {
            $lamaran->lowongan()->update(['status' => Lowongan::STATUS_DITUTUP]);
        }
    }

    /** Admin: pindahkan tahap/status lamaran langsung tanpa mengikuti alur. */
    public function ubahAdmin(Request $request, Lamaran $lamaran): RedirectResponse
    {
        abort_unless($this->adminBebas(), 403);

        $v = $request->validate([
            'tahap' => ['required', Rule::in([Lamaran::TAHAP_VERIFIKASI, Lamaran::TAHAP_INTERVIEW, Lamaran::TAHAP_SCREENING, Lamaran::TAHAP_KONTRAK, Lamaran::TAHAP_SELESAI])],
            'status' => ['required', Rule::in([Lamaran::STATUS_MENUNGGU_VERIFIKASI, Lamaran::STATUS_PROSES, Lamaran::STATUS_DITERIMA, Lamaran::STATUS_DITOLAK, Lamaran::STATUS_DIBATALKAN])],
            'catatan_admin' => ['nullable', 'string', 'max:1000'],
        ]);

        $final = in_array($v['status'], [Lamaran::STATUS_DITERIMA, Lamaran::STATUS_DITOLAK, Lamaran::STATUS_DIBATALKAN], true);

        $lamaran->update([
            ...$v,
            'diputuskan_oleh' => $final ? Auth::id() : null,
            'diputuskan_pada' => $final ? now() : null,
        ]);

        return redirect()->back()->with('success', "Lamaran dipindah ke tahap {$lamaran->tahapLabel()} dengan status {$lamaran->statusLabel()}.");
    }

    /**
     * Aturan tanggal jadwal rekrutmen: hari kerja (bukan akhir pekan/libur) dan tidak di masa lalu. Admin bebas.
     *
     * @return list<mixed>
     */
    private function aturanTanggal(bool $mulaiHariIni = true): array
    {
        if ($this->adminBebas()) {
            return [];
        }

        return [
            ...($mulaiHariIni ? ['after_or_equal:today'] : []),
            function (string $atribut, mixed $nilai, \Closure $gagal) {
                if (strtotime((string) $nilai) !== false && ! Kalender::isHariKerja(Carbon::parse($nilai))) {
                    $gagal('Tanggal ' . Carbon::parse($nilai)->translatedFormat('l, d M Y') . ' bukan hari kerja (' . Kalender::namaHariKerja() . ', di luar hari libur).');
                }
            },
        ];
    }

    /** Untuk jadwal hari ini, jam mulai tidak boleh sudah lewat. */
    private function aturanJamBelumLewat(): \Closure
    {
        return function (string $atribut, mixed $nilai, \Closure $gagal) {
            $tanggal = request('tanggal');
            if (! $this->adminBebas() && $tanggal && Carbon::parse($tanggal)->isToday() && (string) $nilai <= now()->format('H:i')) {
                $gagal('Jam mulai sudah lewat untuk jadwal hari ini (sekarang ' . now()->format('H:i') . ' WIB).');
            }
        };
    }

    /** Admin (super admin) bebas dari aturan urutan tahap rekrutmen. */
    private function adminBebas(): bool
    {
        return (bool) Auth::user()?->isAdmin();
    }

    public function tolak(Request $request, Lamaran $lamaran): RedirectResponse
    {
        $this->authorizeReview();

        if ($lamaran->isSelesai() && ! $this->adminBebas()) {
            return redirect()->back()->with('error', 'Lamaran ini sudah memiliki keputusan akhir.');
        }

        $validated = $request->validate([
            'catatan_admin' => ['required', 'string', 'max:1000'],
        ], [
            'catatan_admin.required' => 'Alasan penolakan wajib diisi.',
        ]);

        $lamaran->update([
            'status' => Lamaran::STATUS_DITOLAK,
            'keputusan' => Lamaran::KEPUTUSAN_TOLAK_ADMIN,
            'tahap' => Lamaran::TAHAP_SELESAI,
            'catatan_admin' => $validated['catatan_admin'],
            'diputuskan_oleh' => Auth::id(),
            'diputuskan_pada' => now(),
        ]);

        return redirect()->back()->with('success', 'Lamaran ditolak.');
    }

    private function authorizeAccess(Lamaran $lamaran): void
    {
        /** @var User $user */
        $user = Auth::user();

        if (RekrutmenAccess::canLihatSemua($user)) {
            return;
        }

        $lamaran->loadMissing('lowongan');

        $terlibat = $lamaran->interviewJadwals()->where('interviewer_id', $user->id)->exists()
            || $lamaran->screeningOnboardings()->where('kepala_id', $user->id)->exists();

        if (! $terlibat && ! $user->memimpinUnit($lamaran->unitKerjaTujuan())) {
            abort(403, 'Anda hanya bisa melihat lamaran divisi Anda sendiri.');
        }
    }

    private function authorizeReview(): void
    {
        /** @var User $user */
        $user = Auth::user();

        if (! RekrutmenAccess::canReview($user)) {
            abort(403, 'Hanya Kepala SDM/GM yang berwenang melakukan aksi ini.');
        }
    }
}
