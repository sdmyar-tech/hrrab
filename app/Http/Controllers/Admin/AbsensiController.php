<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Absensi;
use App\Models\DataPegawai;
use App\Models\User;
use App\Support\Absensi\AksesAbsensi;
use App\Support\Absensi\EksporAbsensi;
use App\Support\Absensi\PenilaianAbsensi;
use App\Support\Absensi\TampilanAbsensi;
use App\Support\Absensi\TanggalAbsensi;
use App\Support\ExcelExporter;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AbsensiController extends Controller
{
    /** Kelompok filter di halaman Harian (kartu angka di atas tabel). */
    private const KELOMPOK_HARIAN = [
        'on_time' => [self::class, 'kOnTime'],
        'telat' => [self::class, 'kTelat'],
        'telat_izin' => [self::class, 'kTelatIzin'],
        'alpa' => [self::class, 'kAlpa'],
        'cuti_izin' => [self::class, 'kCutiIzin'],
        'perlu_cek' => [self::class, 'kPerluCek'],
    ];

    public static function kOnTime(array $h): bool
    {
        return $h['status'] === 'on_time' || $h['status'] === 'hadir';
    }

    public static function kTelat(array $h): bool
    {
        return $h['status'] === 'telat';
    }

    public static function kTelatIzin(array $h): bool
    {
        return $h['status'] === 'telat_izin';
    }

    public static function kAlpa(array $h): bool
    {
        return $h['status'] === 'alpa';
    }

    public static function kCutiIzin(array $h): bool
    {
        return in_array($h['status'], ['cuti', 'izin', 'sakit', 'dinas_luar'], true);
    }

    /** Perlu ditindak SDM: tidak scan pulang, lembur belum terverifikasi, pulang cepat tanpa izin. */
    public static function kPerluCek(array $h): bool
    {
        return $h['tanpa_scan_pulang'] || $h['lembur_status'] === 'belum' || ($h['pulang_cepat_menit'] > 0 && ! $h['pulang_cepat_izin']);
    }

    /** Pilihan urutan halaman Harian. */
    public const URUT_HARIAN = [
        'nama' => 'Nama A–Z',
        'masuk_awal' => 'Masuk paling pagi',
        'masuk_akhir' => 'Masuk paling siang',
        'telat' => 'Telat paling lama',
        'unit' => 'Unit A–Z',
    ];

    /** Pilihan urutan halaman Rekap (juga dipakai ekspor). */
    public const URUT_REKAP = [
        'nama' => 'Nama A–Z',
        'unit' => 'Unit A–Z',
        'telat' => 'Telat terbanyak',
        'telat_menit' => 'Menit telat terbanyak',
        'alpa' => 'Alpa terbanyak',
        'hadir_sedikit' => 'Hadir paling sedikit',
        'tanpa_scan' => 'Tidak scan pulang terbanyak',
        'lembur' => 'Lembur terbanyak',
    ];

    /** Absensi semua pegawai pada satu tanggal. */
    public function harian(Request $request): View
    {
        $user = $this->user();
        $d = $this->dataHarian($request, $user);
        $bisaKelola = AksesAbsensi::bisaKelola($user);

        return view('admin.absensi.harian', [
            'layout' => $user->panelLayout(),
            'tanggal' => $d['tanggal'],
            'tanggalOtomatis' => ! $d['pilihan'] && ! $d['pesanTanggal'] && ! $d['tanggal']->isToday(),
            'pesanTanggal' => $d['pesanTanggal'],
            'urutOpsi' => self::URUT_HARIAN,
            'urutAktif' => $d['urut'],
            'baris' => $d['baris'],
            'hitung' => $d['hitung'],
            'jumlahPegawai' => $d['jumlahPegawai'],
            'unitOpsi' => $this->unitOpsi($user),
            'bisaKelola' => $bisaKelola,
            'dataTerakhir' => $d['dataTerakhir'],
            'setup' => $bisaKelola ? TampilanAbsensi::langkahSetup() : null,
        ]);
    }

    /** Data halaman Harian (dipakai halaman & ekspor): tanggal terpilih + baris pegawai setelah filter & urut. */
    private function dataHarian(Request $request, User $user): array
    {
        $dataTerakhir = Absensi::whereNotNull('finger_id')->max('tanggal');
        $diminta = $request->query('tanggal');
        $pilihan = TanggalAbsensi::tanggal($diminta);
        $pesanTanggal = null;
        if (filled($diminta) && ! $pilihan) {
            $pesanTanggal = 'Tanggal yang dimasukkan tidak valid, jadi ditampilkan tanggal bawaan.';
        } elseif ($pilihan?->gt(now()->startOfDay())) {
            [$pilihan, $pesanTanggal] = [now()->startOfDay(), 'Absensi tanggal mendatang belum ada, jadi ditampilkan hari ini.'];
        }
        // Tanpa tanggal: tampilkan hari ini, atau tanggal data mesin terakhir bila hari ini belum diimport.
        $tanggal = $pilihan ?? ($dataTerakhir && $dataTerakhir < now()->toDateString() ? Carbon::parse($dataTerakhir) : now()->startOfDay());
        $pegawais = $this->pegawaiTerfilter($request, $user)->get();
        $nilai = PenilaianAbsensi::rentang($pegawais, $tanggal, $tanggal);

        $baris = $pegawais->map(fn (DataPegawai $p) => ['pegawai' => $p, 'h' => $nilai[$p->id][$tanggal->toDateString()]]);
        $kelompok = self::KELOMPOK_HARIAN;
        $hitung = collect($kelompok)->map(fn ($cocok) => $baris->filter(fn ($b) => $cocok($b['h']))->count());
        $status = $request->query('status');
        if ($status && isset($kelompok[$status])) {
            $baris = $baris->filter(fn ($b) => $kelompok[$status]($b['h']));
        }
        $urut = array_key_exists((string) $request->query('urut'), self::URUT_HARIAN) ? $request->query('urut') : 'nama';
        $baris = self::urutkanHarian($baris, $urut);

        return [
            'tanggal' => $tanggal,
            'pilihan' => $pilihan,
            'pesanTanggal' => $pesanTanggal,
            'urut' => $urut,
            'baris' => $baris->values(),
            'hitung' => $hitung,
            'jumlahPegawai' => $pegawais->count(),
            'dataTerakhir' => $dataTerakhir,
        ];
    }

    /** Rekap per pegawai pada rentang tanggal. */
    public function rekap(Request $request): View
    {
        $user = $this->user();
        [$dari, $sampai, $pesanRentang] = $this->rentang($request);
        [$baris, $urut] = $this->barisRekap($request, $user, $dari, $sampai);

        return view('admin.absensi.rekap', [
            'layout' => $user->panelLayout(),
            'dari' => $dari,
            'sampai' => $sampai,
            'pesanRentang' => $pesanRentang,
            'baris' => $baris,
            'unitOpsi' => $this->unitOpsi($user),
            'preset' => $this->presetPeriode(),
            'urutOpsi' => self::URUT_REKAP,
            'urutAktif' => $urut,
        ]);
    }

    public function rekapExport(Request $request): BinaryFileResponse
    {
        $user = $this->user();
        [$dari, $sampai] = $this->rentang($request);
        [$barisRekap, , $nilai] = $this->barisRekap($request, $user, $dari, $sampai);
        $pegawais = $barisRekap->pluck('pegawai'); // urutan ekspor = urutan yang dipilih di layar

        $rekap = $barisRekap->map(fn (array $b) => EksporAbsensi::rekap($b['pegawai'], $b['r']));

        $detail = [];
        foreach ($pegawais as $p) {
            foreach ($nilai[$p->id] ?? [] as $h) {
                $detail[] = EksporAbsensi::detail($p, $h);
            }
        }

        return ExcelExporter::downloadMultiSheet('rekap-absensi-' . $dari->format('Ymd') . '-' . $sampai->format('Ymd'), [
            ['name' => 'Rekap', 'headers' => EksporAbsensi::HEADER_REKAP, 'rows' => $rekap],
            ['name' => 'Detail Harian', 'headers' => EksporAbsensi::HEADER_DETAIL, 'rows' => $detail],
        ]);
    }

    /** Ekspor halaman Harian: tanggal, filter unit/cari, kelompok status, dan urutan sama dengan layar. */
    public function harianExport(Request $request): BinaryFileResponse
    {
        ['tanggal' => $tanggal, 'baris' => $baris] = $this->dataHarian($request, $this->user());

        return ExcelExporter::download('absensi-harian-' . $tanggal->format('Ymd'), EksporAbsensi::HEADER_DETAIL,
            $baris->map(fn (array $b) => EksporAbsensi::detail($b['pegawai'], $b['h'])), 'Absensi ' . $tanggal->format('d-m-Y'));
    }

    /** Ekspor riwayat absensi satu pegawai pada rentang yang dilihat. */
    public function pegawaiExport(Request $request, DataPegawai $pegawai): BinaryFileResponse
    {
        $pegawai->load(['kepegawaian', 'jadwalKerja']);
        abort_unless(AksesAbsensi::bolehLihatPegawai($this->user(), $pegawai), 403, 'Pegawai ini di luar unit Anda.');

        [$dari, $sampai] = $this->rentang($request);
        $hari = PenilaianAbsensi::rentang(collect([$pegawai]), $dari, $sampai)[$pegawai->id] ?? [];

        return ExcelExporter::downloadMultiSheet('absensi-' . Str::slug($pegawai->nama_lengkap) . '-' . $dari->format('Ymd') . '-' . $sampai->format('Ymd'),
            EksporAbsensi::satuPegawai($pegawai, $hari));
    }

    /** Riwayat absensi satu pegawai (dengan form koreksi per hari). */
    public function pegawai(Request $request, DataPegawai $pegawai): View
    {
        $user = $this->user();
        $pegawai->load(['kepegawaian', 'jadwalKerja']);
        abort_unless(AksesAbsensi::bolehLihatPegawai($user, $pegawai), 403, 'Pegawai ini di luar unit Anda.');

        [$dari, $sampai, $pesanRentang] = $this->rentang($request);
        $hari = PenilaianAbsensi::rentang(collect([$pegawai]), $dari, $sampai)[$pegawai->id] ?? [];

        return view('admin.absensi.pegawai', [
            'layout' => $user->panelLayout(),
            'pegawai' => $pegawai,
            'dari' => $dari,
            'sampai' => $sampai,
            'hari' => array_reverse($hari),
            'rekap' => PenilaianAbsensi::rekap($hari),
            'preset' => $this->presetPeriode(),
            'pesanRentang' => $pesanRentang,
            'bisaKelola' => AksesAbsensi::bisaKelola($user),
        ]);
    }

    /**
     * Koreksi satu hari oleh SDM/Admin: ubah jam, status (izin/sakit/dinas luar/alpa/hadir), atau lembur manual.
     * Jam asli mesin tetap tersimpan di scan_masuk/scan_keluar; alasan wajib.
     */
    public function koreksi(Request $request, DataPegawai $pegawai): RedirectResponse
    {
        $v = $request->validate([
            // Admin boleh mengisi tanggal ke depan (mis. dinas luar/izin yang sudah diketahui).
            'tanggal' => ['required', 'date', ...(Auth::user()->isAdmin() ? [] : ['before_or_equal:today'])],
            'aksi' => ['nullable', Rule::in(['simpan', 'reset'])],
            'jam_masuk' => ['nullable', 'date_format:H:i'],
            'jam_keluar' => ['nullable', 'date_format:H:i'],
            'status_manual' => ['nullable', Rule::in(array_keys(Absensi::STATUS_MANUAL))],
            'lembur_manual' => ['nullable', 'regex:/^\d{1,2}:\d{2}$/'],
            'alasan' => ['required_unless:aksi,reset', 'nullable', 'string', 'max:500'],
        ], [
            'alasan.required_unless' => 'Alasan koreksi wajib diisi.',
            'lembur_manual.regex' => 'Format jam lembur JJ:MM, mis. 02:30.',
            'tanggal.before_or_equal' => 'Absensi tanggal mendatang belum bisa dikoreksi.',
        ]);

        $tanggal = Carbon::parse($v['tanggal'])->toDateString();
        $absen = Absensi::where('data_pegawai_id', $pegawai->id)->whereDate('tanggal', $tanggal)->first()
            ?? new Absensi(['data_pegawai_id' => $pegawai->id, 'tanggal' => $tanggal]);

        if (($v['aksi'] ?? 'simpan') === 'reset') {
            if (! $absen->exists) {
                return back()->with('success', 'Tidak ada koreksi untuk dikembalikan.');
            }
            if (! $absen->scan_masuk && ! $absen->scan_keluar) {
                $absen->delete(); // baris manual murni (tanpa data mesin) dihapus

                return back()->with('success', 'Koreksi dihapus.');
            }
            $absen->update([
                'jam_masuk' => $absen->scan_masuk, 'jam_keluar' => $absen->scan_keluar, 'status_manual' => null, 'lembur_manual_menit' => null,
                'dikoreksi' => false, 'alasan_koreksi' => null, 'dikoreksi_oleh' => null, 'dikoreksi_pada' => null,
            ]);

            return back()->with('success', 'Absensi ' . $pegawai->nama_lengkap . ' dikembalikan ke data mesin.');
        }

        $status = $v['status_manual'] ?? null;
        if ($status === Absensi::MANUAL_HADIR && empty($v['jam_masuk'])) {
            throw ValidationException::withMessages(['jam_masuk' => 'Isi jam masuk untuk status Hadir.']);
        }
        if (! empty($v['jam_masuk']) && ! empty($v['jam_keluar']) && $v['jam_keluar'] <= $v['jam_masuk']) {
            throw ValidationException::withMessages(['jam_keluar' => 'Jam keluar harus setelah jam masuk.']);
        }

        $lembur = null;
        if (! empty($v['lembur_manual'])) {
            [$j, $m] = array_map('intval', explode(':', $v['lembur_manual']));
            $lembur = $j * 60 + $m;
        }

        $absen->fill([
            'jam_masuk' => $v['jam_masuk'] ?? null,
            'jam_keluar' => $v['jam_keluar'] ?? null,
            'status_manual' => $status,
            'lembur_manual_menit' => $lembur,
            'dikoreksi' => true,
            'alasan_koreksi' => $v['alasan'],
            'dikoreksi_oleh' => Auth::id(),
            'dikoreksi_pada' => now(),
        ])->save();

        return back()->with('success', 'Absensi ' . $pegawai->nama_lengkap . ' tanggal ' . Carbon::parse($tanggal)->translatedFormat('d M Y') . ' dikoreksi.');
    }

    private function pegawaiTerfilter(Request $request, User $user)
    {
        return AksesAbsensi::pegawai($user)
            ->with(['kepegawaian', 'jadwalKerja'])
            ->when($request->filled('unit'), fn ($q) => $q->whereHas('kepegawaian', fn ($k) => $k->where('unit_kerja', $request->query('unit'))))
            ->when($request->filled('q'), function ($q) use ($request) {
                $kata = '%' . $request->query('q') . '%';
                $q->where(fn ($w) => $w->where('nama_lengkap', 'like', $kata)->orWhere('nip', 'like', $kata)->orWhere('finger_id', $request->query('q'))
                    ->orWhereHas('kepegawaian', fn ($k) => $k->where('unit_kerja', 'like', $kata)->orWhere('jabatan', 'like', $kata)));
            })
            ->orderBy('nama_lengkap');
    }

    /** @return array{0: Carbon, 1: Carbon, 2: list<string>} rentang aman + pesan penyesuaian */
    private function rentang(Request $request): array
    {
        return TanggalAbsensi::rentang($request->query('dari'), $request->query('sampai'));
    }

    /**
     * Baris rekap per pegawai yang sudah difilter & diurutkan.
     *
     * @return array{0: Collection, 1: string, 2: array}
     */
    private function barisRekap(Request $request, User $user, Carbon $dari, Carbon $sampai): array
    {
        $pegawais = $this->pegawaiTerfilter($request, $user)->get();
        $nilai = PenilaianAbsensi::rentang($pegawais, $dari, $sampai);
        $baris = $pegawais->map(fn (DataPegawai $p) => ['pegawai' => $p, 'r' => PenilaianAbsensi::rekap($nilai[$p->id] ?? [])]);
        $urut = array_key_exists((string) $request->query('urut'), self::URUT_REKAP) ? $request->query('urut') : 'nama';

        // Angka terbesar di atas; bila sama, urut nama agar stabil.
        $angka = ['telat' => 'telat', 'telat_menit' => 'telat_menit', 'alpa' => 'alpa', 'tanpa_scan' => 'tanpa_scan_pulang', 'lembur' => 'lembur_menit'];
        $baris = match (true) {
            isset($angka[$urut]) => self::urutBanyak($baris, [[fn ($b) => $b['r'][$angka[$urut]], 'desc'], [fn ($b) => $b['pegawai']->nama_lengkap, 'asc']]),
            $urut === 'hadir_sedikit' => self::urutBanyak($baris, [[fn ($b) => $b['r']['hadir_total'], 'asc'], [fn ($b) => $b['pegawai']->nama_lengkap, 'asc']]),
            $urut === 'unit' => self::urutBanyak($baris, [[fn ($b) => (string) $b['pegawai']->kepegawaian?->unit_kerja, 'asc'], [fn ($b) => $b['pegawai']->nama_lengkap, 'asc']]),
            default => $baris,
        };

        return [$baris->values(), $urut, $nilai];
    }

    /**
     * Urutkan dengan beberapa kunci: [[fn ($baris) => nilai, 'asc'|'desc'], ...]. Urutan stabil.
     * (Collection::sortBy([...]) milik Laravel memperlakukan closure sebagai pembanding 2 argumen, jadi tidak dipakai.)
     */
    private static function urutBanyak(Collection $baris, array $kunci): Collection
    {
        $daftar = $baris->values()->all();
        $indeks = array_keys($daftar);
        usort($indeks, function ($i, $j) use ($daftar, $kunci) {
            foreach ($kunci as [$ambil, $arah]) {
                [$a, $b] = [$ambil($daftar[$i]), $ambil($daftar[$j])];
                $c = is_string($a) && is_string($b) ? strcasecmp($a, $b) : ($a <=> $b);
                if ($c !== 0) {
                    return $arah === 'desc' ? -$c : $c;
                }
            }

            return $i <=> $j;
        });

        return collect(array_map(fn ($i) => $daftar[$i], $indeks));
    }

    /** Urutkan baris Harian. Pegawai tanpa scan selalu di bawah untuk urutan berdasarkan jam. */
    private static function urutkanHarian(Collection $baris, string $urut): Collection
    {
        $menit = fn ($b) => PenilaianAbsensi::menit($b['h']['masuk']);
        $nama = [fn ($b) => $b['pegawai']->nama_lengkap, 'asc'];

        return match ($urut) {
            'masuk_awal' => self::urutBanyak($baris, [[fn ($b) => $menit($b) === null ? 1 : 0, 'asc'], [fn ($b) => $menit($b) ?? 0, 'asc'], $nama]),
            'masuk_akhir' => self::urutBanyak($baris, [[fn ($b) => $menit($b) === null ? 1 : 0, 'asc'], [fn ($b) => $menit($b) ?? 0, 'desc'], $nama]),
            'telat' => self::urutBanyak($baris, [[fn ($b) => in_array($b['h']['status'], ['telat', 'telat_izin'], true) ? $b['h']['telat_menit'] : -1, 'desc'], $nama]),
            'unit' => self::urutBanyak($baris, [[fn ($b) => (string) $b['pegawai']->kepegawaian?->unit_kerja, 'asc'], $nama]),
            default => $baris,
        };
    }

    /** Pilihan cepat periode: bulan ini, bulan lalu, periode gaji (21–20) berjalan & sebelumnya. */
    private function presetPeriode(): array
    {
        $awal = (int) config('hrrab.absensi.periode_mulai', 21);
        $mulaiGaji = now()->day >= $awal ? now()->setDay($awal) : now()->subMonthNoOverflow()->setDay($awal);

        $hariIni = now()->startOfDay();
        $batas = fn (Carbon $c) => $c->copy()->startOfDay()->min($hariIni); // periode berjalan: sampai hari ini saja

        return [
            'Bulan ini' => [now()->startOfMonth(), $hariIni->copy()],
            'Bulan lalu' => [now()->subMonthNoOverflow()->startOfMonth(), now()->subMonthNoOverflow()->endOfMonth()],
            "Periode {$awal}–" . ($awal - 1) . ' berjalan' => [$mulaiGaji->copy()->startOfDay(), $batas($mulaiGaji->copy()->addMonthNoOverflow()->subDay())],
            "Periode {$awal}–" . ($awal - 1) . ' lalu' => [$mulaiGaji->copy()->subMonthNoOverflow()->startOfDay(), $mulaiGaji->copy()->subDay()->startOfDay()],
        ];
    }

    private function unitOpsi(User $user): array
    {
        $unit = AksesAbsensi::unitTerlihat($user);

        return AksesAbsensi::pegawai($user)->join('kepegawaians', 'kepegawaians.data_pegawai_id', '=', 'data_pegawais.id')
            ->when($unit !== null, fn ($q) => $q->whereIn('kepegawaians.unit_kerja', $unit ?: ['__tidak_ada__']))
            ->distinct()->orderBy('kepegawaians.unit_kerja')->pluck('kepegawaians.unit_kerja')->filter()->values()->all();
    }

    private function user(): User
    {
        /** @var User $user */
        $user = Auth::user();

        return $user;
    }
}
