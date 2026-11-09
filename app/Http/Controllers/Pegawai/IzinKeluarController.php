<?php

namespace App\Http\Controllers\Pegawai;

use App\Http\Controllers\Controller;
use App\Models\DataPegawai;
use App\Models\PengajuanCuti;
use App\Models\PengajuanIzinKeluar;
use App\Models\User;
use App\Support\Kalender;
use App\Support\Notifikasi;
use App\Support\PengajuanWorkflow;
use App\Support\RingkasanPengajuan;
use App\Support\Urutan;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/** Izin pegawai: beberapa jam (bertiket untuk security gerbang) atau tidak masuk harian (tercatat di absensi). */
class IzinKeluarController extends Controller
{
    /** Izin bisa diajukan paling jauh sekian hari ke depan. */
    private const MAKS_HARI_KE_DEPAN = 14;

    /** Izin beberapa jam boleh diajukan belakangan, paling lambat sekian jam setelah jam keluar. */
    private const MAKS_JAM_MUNDUR = 12;

    /** Izin sakit boleh diajukan paling lambat sekian hari setelah tanggal sakit. */
    private const MAKS_HARI_MUNDUR_SAKIT = 3;

    /** Rentang terpanjang satu izin tidak masuk (hari kalender); lebih dari itu lewat cuti. */
    private const MAKS_RENTANG_HARIAN = 14;

    public function index(): View
    {
        /** @var User $user */
        $user = Auth::user();
        $pegawai = $user->dataPegawai;

        $urut = ['urutOpsi' => [], 'urutAktif' => null];
        $riwayat = collect();
        if ($pegawai) {
            $query = $pegawai->pengajuanIzinKeluars();
            $urut = Urutan::terapkan($query, [
                ...Urutan::tanggal('tgl', 'Tanggal izin', 'pengajuan_izin_keluars.tanggal'),
                ...Urutan::tanggal('diajukan', 'Diajukan', 'pengajuan_izin_keluars.created_at'),
            ]);
            $riwayat = $query->get();
        }

        return view('pegawai.izin-keluar', [
            ...$urut,
            'riwayat' => $riwayat,
            'keperluanOptions' => PengajuanIzinKeluar::KEPERLUAN,
            'maksHari' => self::MAKS_HARI_KE_DEPAN,
            'maksMundurSakit' => self::MAKS_HARI_MUNDUR_SAKIT,
            'maksJamMundur' => self::MAKS_JAM_MUNDUR,
            'rute' => PengajuanWorkflow::rute($user, $user->unitKerja()),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $pegawai = $user->dataPegawai;

        if (! $pegawai) {
            return redirect()->route('pegawai.izin-keluar')->with('error', 'Data pegawai Anda belum tersedia. Hubungi bagian SDM.');
        }

        $harian = $request->input('jenis') === PengajuanIzinKeluar::JENIS_HARIAN;
        $data = $harian ? $this->dataHarian($request, $pegawai) : $this->dataJam($request, $pegawai);
        if (is_string($data)) {
            return back()->withInput()->with('error', $data);
        }

        $unit = $user->unitKerja();
        if ($error = PengajuanWorkflow::cekApprover($user, $unit)) {
            return back()->withInput()->with('error', $error);
        }

        $izin = $pegawai->pengajuanIzinKeluars()->create([
            ...$data,
            'unit_kerja' => $unit,
            'status' => PengajuanIzinKeluar::STATUS_PENDING,
            ...PengajuanWorkflow::kolomAwal($user, $unit),
        ]);
        Notifikasi::pengajuanMenunggu($izin);

        return redirect()->route('pegawai.izin-keluar')->with('success', 'Izin dikirim. Menunggu persetujuan ' . $izin->currentLevelLabel() . '.'
            . ($harian ? ' Setelah disetujui, hari izin tercatat di absensi.' : ' Tiket keluar muncul setelah disetujui.'));
    }

    /**
     * Izin beberapa jam (keluar saat jam kerja, bertiket).
     *
     * @return array<string, mixed>|string  kolom siap simpan, atau pesan galat
     */
    private function dataJam(Request $request, DataPegawai $pegawai): array|string
    {
        $tidakKembali = $request->boolean('tidak_kembali');
        $batasMundur = now()->subHours(self::MAKS_JAM_MUNDUR);
        $validated = $request->validate([
            'tanggal' => ['required', 'date', 'after_or_equal:' . $batasMundur->toDateString(), 'before_or_equal:' . now()->addDays(self::MAKS_HARI_KE_DEPAN)->toDateString()],
            'jam_keluar' => ['required', 'date_format:H:i'],
            'jam_kembali' => [$tidakKembali ? 'nullable' : 'required', 'date_format:H:i', 'after:jam_keluar'],
            'keperluan' => ['required', Rule::in(array_keys(PengajuanIzinKeluar::KEPERLUAN))],
            'alasan' => ['required', 'string', 'max:500'],
        ], [
            'tanggal.after_or_equal' => 'Izin beberapa jam paling lambat diajukan ' . self::MAKS_JAM_MUNDUR . ' jam setelah jam keluar.',
            'tanggal.before_or_equal' => 'Izin paling jauh diajukan ' . self::MAKS_HARI_KE_DEPAN . ' hari ke depan.',
            'jam_keluar.required' => 'Jam keluar wajib diisi.',
            'jam_kembali.required' => 'Isi perkiraan jam kembali, atau centang "Tidak kembali lagi hari ini".',
            'jam_kembali.after' => 'Jam kembali harus setelah jam keluar.',
            'keperluan.required' => 'Pilih keperluan.',
            'alasan.required' => 'Alasan wajib diisi.',
        ]);
        $jamKembali = $tidakKembali ? null : $validated['jam_kembali'];

        // Boleh dilaporkan belakangan (mis. ban pecah, baru sempat izin setelah kembali) paling lama 12 jam setelah jam keluar.
        if (Carbon::parse("{$validated['tanggal']} {$validated['jam_keluar']}")->lt($batasMundur)) {
            throw ValidationException::withMessages(['jam_keluar' => 'Izin beberapa jam paling lambat diajukan ' . self::MAKS_JAM_MUNDUR
                . ' jam setelah jam keluar (paling awal ' . $batasMundur->translatedFormat('d M Y H:i') . ').']);
        }

        // Tidak boleh tumpang tindih dengan izin lain (menunggu/disetujui): izin tidak masuk di tanggal itu, atau jam yang bertumpuk.
        $selesai = $jamKembali ?? '23:59';
        $bentrok = $pegawai->pengajuanIzinKeluars()->bertumpuk($validated['tanggal'], $validated['tanggal'])
            ->where(fn ($q) => $q->where('jenis', PengajuanIzinKeluar::JENIS_HARIAN)
                ->orWhere(fn ($j) => $j->where('jam_keluar', '<', $selesai)
                    ->where(fn ($k) => $k->whereNull('jam_kembali')->orWhere('jam_kembali', '>', $validated['jam_keluar']))))
            ->exists();
        if ($bentrok) {
            return 'Sudah ada izin lain (izin tidak masuk, atau jam yang bertumpuk) pada tanggal itu.';
        }

        return [
            'jenis' => PengajuanIzinKeluar::JENIS_JAM,
            'tanggal' => $validated['tanggal'],
            'jam_keluar' => $validated['jam_keluar'],
            'jam_kembali' => $jamKembali,
            'keperluan' => $validated['keperluan'],
            'alasan' => $validated['alasan'],
        ];
    }

    /**
     * Izin tidak masuk 1 hari atau lebih. Lama dihitung hari kerja (Sabtu–Minggu & tanggal merah dilewati, sama seperti cuti).
     *
     * @return array<string, mixed>|string  kolom siap simpan, atau pesan galat
     */
    private function dataHarian(Request $request, DataPegawai $pegawai): array|string
    {
        // Sakit biasanya baru dilaporkan setelah terjadi: boleh mundur beberapa hari.
        $palingAwal = $request->input('keperluan') === 'sakit' ? now()->subDays(self::MAKS_HARI_MUNDUR_SAKIT) : now();
        $validated = $request->validate([
            'tanggal' => ['required', 'date', 'after_or_equal:' . $palingAwal->toDateString(), 'before_or_equal:' . now()->addDays(self::MAKS_HARI_KE_DEPAN)->toDateString()],
            'tanggal_selesai' => ['required', 'date', 'after_or_equal:tanggal', 'before_or_equal:' . Carbon::parse((string) $request->input('tanggal'))->addDays(self::MAKS_RENTANG_HARIAN)->toDateString()],
            'keperluan' => ['required', Rule::in(array_keys(PengajuanIzinKeluar::KEPERLUAN))],
            'alasan' => ['required', 'string', 'max:500'],
        ], [
            'tanggal.after_or_equal' => $request->input('keperluan') === 'sakit'
                ? 'Izin sakit paling lama diajukan ' . self::MAKS_HARI_MUNDUR_SAKIT . ' hari setelah tanggal sakit.'
                : 'Tanggal izin tidak boleh sebelum hari ini.',
            'tanggal.before_or_equal' => 'Izin paling jauh diajukan ' . self::MAKS_HARI_KE_DEPAN . ' hari ke depan.',
            'tanggal_selesai.required' => 'Tanggal selesai wajib diisi.',
            'tanggal_selesai.after_or_equal' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
            'tanggal_selesai.before_or_equal' => 'Rentang izin tidak masuk paling panjang ' . self::MAKS_RENTANG_HARIAN . ' hari. Untuk lebih lama, ajukan cuti.',
            'keperluan.required' => 'Pilih keperluan.',
            'alasan.required' => 'Alasan wajib diisi.',
        ]);

        $lama = Kalender::jumlahHariKerja(Carbon::parse($validated['tanggal']), Carbon::parse($validated['tanggal_selesai']));
        if ($lama < 1) {
            return 'Rentang tanggal yang dipilih tidak berisi hari kerja (semuanya Sabtu–Minggu atau tanggal merah).';
        }
        if ($pegawai->pengajuanIzinKeluars()->bertumpuk($validated['tanggal'], $validated['tanggal_selesai'])->exists()) {
            return 'Sudah ada izin lain (menunggu/disetujui) pada rentang tanggal itu.';
        }
        $cutiBentrok = $pegawai->pengajuanCutis()->where('status', '!=', PengajuanCuti::STATUS_REJECTED)
            ->whereDate('tanggal_mulai', '<=', $validated['tanggal_selesai'])->whereDate('tanggal_selesai', '>=', $validated['tanggal'])
            ->exists();
        if ($cutiBentrok) {
            return 'Rentang tanggal itu bertumpuk dengan cuti Anda (menunggu/disetujui).';
        }

        return [
            'jenis' => PengajuanIzinKeluar::JENIS_HARIAN,
            'tanggal' => $validated['tanggal'],
            'tanggal_selesai' => $validated['tanggal_selesai'],
            'lama_hari' => $lama,
            'keperluan' => $validated['keperluan'],
            'alasan' => $validated['alasan'],
        ];
    }

    /** Tiket keluar (hanya izin yang sudah disetujui akhir): dibuka pemilik, atau penyetuju/Admin yang berhak melihatnya. */
    public function tiket(PengajuanIzinKeluar $izin): View|RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $milikSendiri = $user->data_pegawai_id && $user->data_pegawai_id === $izin->data_pegawai_id;
        if (! $milikSendiri) {
            $terlihat = PengajuanIzinKeluar::whereKey($izin->id);
            PengajuanWorkflow::scopeVisibleTo($terlihat, $user);
            abort_unless($terlihat->exists(), 403, 'Anda tidak berwenang melihat tiket ini.');
        }

        if (! $izin->punyaTiket()) {
            return redirect()->to($milikSendiri ? route('pegawai.izin-keluar') : route('admin.izin-keluar.show', $izin))
                ->with('error', $izin->isHarian()
                    ? 'Izin tidak masuk tidak memakai tiket keluar.'
                    : 'Tiket baru tersedia setelah izin disetujui sampai tahap akhir.');
        }

        $izin->load('dataPegawai.kepegawaian', 'dataPegawai.dokumen');
        // Penyetuju yang sudah menyetujui (atasan langsung / GM / Kepala SDM), dari alur yang sama dengan rincian.
        $penyetuju = collect(RingkasanPengajuan::izinKeluar($izin)['alur'])->skip(1)->where('status', 'selesai')->values();

        return view('pegawai.izin-keluar-tiket', [
            'izin' => $izin,
            'penyetuju' => $penyetuju,
            'milikSendiri' => $milikSendiri,
            'masaBerlaku' => $izin->masaBerlaku(),
        ]);
    }
}
