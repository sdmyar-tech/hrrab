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

/** Izin pegawai: Izin Keluar (bertiket untuk security gerbang), Izin Telat Masuk, dan Izin Tidak Masuk (tercatat di absensi). */
class IzinKeluarController extends Controller
{
    /** Izin bisa diajukan paling jauh sekian hari ke depan. */
    private const MAKS_HARI_KE_DEPAN = 14;

    /** Izin telat boleh diajukan belakangan, paling lambat sekian jam setelah jam tiba. */
    private const MAKS_JAM_MUNDUR = 12;

    /** Izin keluar: kelonggaran menit untuk waktu mengisi formulir (jam keluar paling awal = saat ini). */
    private const KELONGGARAN_MENIT = 5;

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
            'kelonggaranMenit' => self::KELONGGARAN_MENIT,
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

        $jenis = $request->input('jenis');
        $data = match ($jenis) {
            PengajuanIzinKeluar::JENIS_HARIAN => $this->dataHarian($request, $pegawai),
            PengajuanIzinKeluar::JENIS_TELAT => $this->dataTelat($request, $pegawai),
            default => $this->dataJam($request, $pegawai),
        };
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

        return redirect()->route('pegawai.izin-keluar')->with('success', 'Izin dikirim. Menunggu persetujuan ' . $izin->currentLevelLabel() . '.' . match ($izin->jenis) {
            PengajuanIzinKeluar::JENIS_HARIAN => ' Setelah disetujui, hari izin tercatat di absensi.',
            PengajuanIzinKeluar::JENIS_TELAT => ' Setelah disetujui, keterlambatan sampai jam tiba tercatat "Telat Izin" di absensi.',
            default => ' Tiket keluar muncul setelah disetujui.',
        });
    }

    /** Aturan keperluan menurut jenis (Sakit hanya untuk telat masuk & tidak masuk). */
    private function aturanKeperluan(string $jenis): array
    {
        return ['required', Rule::in(array_keys(PengajuanIzinKeluar::keperluanUntuk($jenis)))];
    }

    private const PESAN_KEPERLUAN = [
        'keperluan.required' => 'Pilih keperluan.',
        'keperluan.in' => 'Keperluan ini tidak tersedia untuk jenis izin yang dipilih.',
        'alasan.required' => 'Alasan wajib diisi.',
    ];

    /**
     * Izin Keluar saat jam kerja (bertiket). Diajukan sebelum keluar: jam keluar paling awal = saat ini.
     *
     * @return array<string, mixed>|string  kolom siap simpan, atau pesan galat
     */
    private function dataJam(Request $request, DataPegawai $pegawai): array|string
    {
        $tidakKembali = $request->boolean('tidak_kembali');
        $validated = $request->validate([
            'tanggal' => ['required', 'date', 'after_or_equal:today', 'before_or_equal:' . now()->addDays(self::MAKS_HARI_KE_DEPAN)->toDateString()],
            'jam_keluar' => ['required', 'date_format:H:i'],
            'jam_kembali' => [$tidakKembali ? 'nullable' : 'required', 'date_format:H:i', 'after:jam_keluar'],
            'keperluan' => $this->aturanKeperluan(PengajuanIzinKeluar::JENIS_JAM),
            'alasan' => ['required', 'string', 'max:500'],
        ], [
            'tanggal.after_or_equal' => 'Izin keluar tidak bisa untuk tanggal yang sudah lewat.',
            'tanggal.before_or_equal' => 'Izin paling jauh diajukan ' . self::MAKS_HARI_KE_DEPAN . ' hari ke depan.',
            'jam_keluar.required' => 'Jam keluar wajib diisi.',
            'jam_kembali.required' => 'Isi perkiraan jam kembali, atau centang "Tidak kembali lagi hari ini".',
            'jam_kembali.after' => 'Jam kembali harus setelah jam keluar.',
        ] + self::PESAN_KEPERLUAN);
        $jamKembali = $tidakKembali ? null : $validated['jam_kembali'];

        // Jam keluar paling awal = jam saat ini (kelonggaran beberapa menit untuk waktu mengisi formulir).
        $palingAwal = now()->subMinutes(self::KELONGGARAN_MENIT)->startOfMinute();
        if (Carbon::parse("{$validated['tanggal']} {$validated['jam_keluar']}")->lt($palingAwal)) {
            throw ValidationException::withMessages(['jam_keluar' => 'Jam keluar paling awal jam saat ini (' . now()->format('H:i') . '). Izin keluar diajukan sebelum keluar.']);
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
     * Izin Telat Masuk: jam_kembali = (perkiraan) jam tiba. Boleh dilaporkan belakangan paling lambat 12 jam
     * setelah jam tiba (mis. ban pecah di jalan, baru sempat izin setelah sampai kantor).
     *
     * @return array<string, mixed>|string  kolom siap simpan, atau pesan galat
     */
    private function dataTelat(Request $request, DataPegawai $pegawai): array|string
    {
        $batasMundur = now()->subHours(self::MAKS_JAM_MUNDUR);
        $validated = $request->validate([
            'tanggal' => ['required', 'date', 'after_or_equal:' . $batasMundur->toDateString(), 'before_or_equal:' . now()->addDays(self::MAKS_HARI_KE_DEPAN)->toDateString()],
            'jam_tiba' => ['required', 'date_format:H:i'],
            'keperluan' => $this->aturanKeperluan(PengajuanIzinKeluar::JENIS_TELAT),
            'alasan' => ['required', 'string', 'max:500'],
        ], [
            'tanggal.after_or_equal' => 'Izin telat paling lambat diajukan ' . self::MAKS_JAM_MUNDUR . ' jam setelah jam tiba.',
            'tanggal.before_or_equal' => 'Izin paling jauh diajukan ' . self::MAKS_HARI_KE_DEPAN . ' hari ke depan.',
            'jam_tiba.required' => 'Isi jam tiba (atau perkiraan jam tiba) di tempat kerja.',
        ] + self::PESAN_KEPERLUAN);

        if (Carbon::parse("{$validated['tanggal']} {$validated['jam_tiba']}")->lt($batasMundur)) {
            throw ValidationException::withMessages(['jam_tiba' => 'Izin telat paling lambat diajukan ' . self::MAKS_JAM_MUNDUR
                . ' jam setelah jam tiba (paling awal ' . $batasMundur->translatedFormat('d M Y H:i') . ').']);
        }
        // Satu izin telat per hari, dan tidak di hari yang sudah izin tidak masuk.
        $bentrok = $pegawai->pengajuanIzinKeluars()->bertumpuk($validated['tanggal'], $validated['tanggal'])
            ->whereIn('jenis', [PengajuanIzinKeluar::JENIS_TELAT, PengajuanIzinKeluar::JENIS_HARIAN])->exists();
        if ($bentrok) {
            return 'Sudah ada izin telat atau izin tidak masuk pada tanggal itu.';
        }

        return [
            'jenis' => PengajuanIzinKeluar::JENIS_TELAT,
            'tanggal' => $validated['tanggal'],
            'jam_kembali' => $validated['jam_tiba'],
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
            'keperluan' => $this->aturanKeperluan(PengajuanIzinKeluar::JENIS_HARIAN),
            'alasan' => ['required', 'string', 'max:500'],
        ], [
            'tanggal.after_or_equal' => $request->input('keperluan') === 'sakit'
                ? 'Izin sakit paling lama diajukan ' . self::MAKS_HARI_MUNDUR_SAKIT . ' hari setelah tanggal sakit.'
                : 'Tanggal izin tidak boleh sebelum hari ini.',
            'tanggal.before_or_equal' => 'Izin paling jauh diajukan ' . self::MAKS_HARI_KE_DEPAN . ' hari ke depan.',
            'tanggal_selesai.required' => 'Tanggal selesai wajib diisi.',
            'tanggal_selesai.after_or_equal' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
            'tanggal_selesai.before_or_equal' => 'Rentang izin tidak masuk paling panjang ' . self::MAKS_RENTANG_HARIAN . ' hari. Untuk lebih lama, ajukan cuti.',
        ] + self::PESAN_KEPERLUAN);

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
                ->with('error', ! $izin->isKeluar()
                    ? $izin->jenisLabel() . ' tidak memakai tiket keluar.'
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
