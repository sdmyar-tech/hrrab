<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Absensi;
use App\Models\DataPegawai;
use App\Models\User;
use App\Support\Absensi\AksesAbsensi;
use App\Support\Absensi\PenilaianAbsensi;
use App\Support\ExcelExporter;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

    public static function kOnTime(array $h): bool { return $h['status'] === 'on_time' || $h['status'] === 'hadir'; }
    public static function kTelat(array $h): bool { return $h['status'] === 'telat'; }
    public static function kTelatIzin(array $h): bool { return $h['status'] === 'telat_izin'; }
    public static function kAlpa(array $h): bool { return $h['status'] === 'alpa'; }
    public static function kCutiIzin(array $h): bool { return in_array($h['status'], ['cuti', 'izin', 'sakit', 'dinas_luar'], true); }

    /** Perlu ditindak SDM: tidak scan pulang, lembur belum terverifikasi, pulang cepat tanpa izin. */
    public static function kPerluCek(array $h): bool
    {
        return $h['tanpa_scan_pulang'] || $h['lembur_status'] === 'belum' || ($h['pulang_cepat_menit'] > 0 && ! $h['pulang_cepat_izin']);
    }

    /** Absensi semua pegawai pada satu tanggal. */
    public function harian(Request $request): View
    {
        $user = $this->user();
        $dataTerakhir = Absensi::whereNotNull('finger_id')->max('tanggal');
        $pilihan = $this->tanggal($request->query('tanggal'));
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

        $bisaKelola = AksesAbsensi::bisaKelola($user);

        return view('admin.absensi.harian', [
            'layout' => $user->panelLayout(),
            'tanggal' => $tanggal,
            'tanggalOtomatis' => ! $pilihan && ! $tanggal->isToday(),
            'baris' => $baris->values(),
            'hitung' => $hitung,
            'jumlahPegawai' => $pegawais->count(),
            'unitOpsi' => $this->unitOpsi($user),
            'bisaKelola' => $bisaKelola,
            'dataTerakhir' => $dataTerakhir,
            'setup' => $bisaKelola ? \App\Support\Absensi\TampilanAbsensi::langkahSetup() : null,
        ]);
    }

    /** Rekap per pegawai pada rentang tanggal. */
    public function rekap(Request $request): View
    {
        $user = $this->user();
        [$dari, $sampai] = $this->rentang($request);
        $pegawais = $this->pegawaiTerfilter($request, $user)->get();
        $nilai = PenilaianAbsensi::rentang($pegawais, $dari, $sampai);

        return view('admin.absensi.rekap', [
            'layout' => $user->panelLayout(),
            'dari' => $dari,
            'sampai' => $sampai,
            'baris' => $pegawais->map(fn (DataPegawai $p) => ['pegawai' => $p, 'r' => PenilaianAbsensi::rekap($nilai[$p->id] ?? [])]),
            'unitOpsi' => $this->unitOpsi($user),
            'preset' => $this->presetPeriode(),
        ]);
    }

    public function rekapExport(Request $request): BinaryFileResponse
    {
        $user = $this->user();
        [$dari, $sampai] = $this->rentang($request);
        $pegawais = $this->pegawaiTerfilter($request, $user)->get();
        $nilai = PenilaianAbsensi::rentang($pegawais, $dari, $sampai);
        $d = fn (int $m) => $m ? round($m / 60, 2) : 0;

        $rekap = $pegawais->map(function (DataPegawai $p) use ($nilai, $d) {
            $r = PenilaianAbsensi::rekap($nilai[$p->id] ?? []);

            return [
                $p->nama_lengkap, $p->nip, $p->finger_id, $p->kepegawaian?->unit_kerja, $p->jadwalKerja?->ringkas() ?? 'Belum diatur',
                $r['hari_kerja'], $r['hadir_total'], $r['on_time'], $r['telat'], $r['telat_menit'], $r['telat_izin'],
                $r['pulang_cepat'], $r['tanpa_scan_pulang'], $r['alpa'], $r['cuti'], $r['izin'], $r['sakit'], $r['dinas_luar'],
                $d($r['lembur_menit']), $d($r['lembur_surat_menit']), $r['lembur_belum'], $r['dikoreksi'],
            ];
        });

        $detail = [];
        foreach ($pegawais as $p) {
            foreach ($nilai[$p->id] ?? [] as $h) {
                $detail[] = [
                    $h['tanggal']->format('d-m-Y'), $h['tanggal']->translatedFormat('l'), $p->nama_lengkap, $p->finger_id, $p->kepegawaian?->unit_kerja,
                    $h['jadwal'] ? $h['jadwal']['masuk'] . '-' . $h['jadwal']['pulang'] : '-', $h['masuk'], $h['keluar'],
                    $h['label'], $h['telat_menit'] ?: null, $h['pulang_cepat_menit'] ?: null, $h['tanpa_scan_pulang'] ? 'Ya' : null,
                    $h['lembur_menit'] ? $d($h['lembur_menit']) : null, $h['lembur_status'], $h['dikoreksi'] ? 'Ya' : null,
                    $h['absensi']?->alasan_koreksi, implode(' ', $h['catatan']),
                ];
            }
        }

        return ExcelExporter::downloadMultiSheet('rekap-absensi-' . $dari->format('Ymd') . '-' . $sampai->format('Ymd'), [
            ['name' => 'Rekap', 'headers' => [
                'Nama', 'NIP', 'Finger ID', 'Unit', 'Jadwal', 'Hari Kerja', 'Hadir', 'On Time', 'Telat (kali)', 'Telat (menit)', 'Telat Izin',
                'Pulang Cepat', 'Tidak Scan Pulang', 'Alpa', 'Cuti', 'Izin', 'Sakit', 'Dinas Luar',
                'Lembur Terverifikasi (jam)', 'Lembur Surat (jam)', 'Lembur Belum Terverifikasi (hari)', 'Hari Dikoreksi',
            ], 'rows' => $rekap],
            ['name' => 'Detail Harian', 'headers' => [
                'Tanggal', 'Hari', 'Nama', 'Finger ID', 'Unit', 'Jadwal', 'Masuk', 'Keluar', 'Status', 'Telat (menit)', 'Pulang Cepat (menit)',
                'Tidak Scan Pulang', 'Lembur (jam)', 'Status Lembur', 'Dikoreksi', 'Alasan Koreksi', 'Catatan',
            ], 'rows' => $detail],
        ]);
    }

    /** Riwayat absensi satu pegawai (dengan form koreksi per hari). */
    public function pegawai(Request $request, DataPegawai $pegawai): View
    {
        $user = $this->user();
        $pegawai->load(['kepegawaian', 'jadwalKerja']);
        abort_unless(AksesAbsensi::bolehLihatPegawai($user, $pegawai), 403, 'Pegawai ini di luar unit Anda.');

        [$dari, $sampai] = $this->rentang($request);
        $hari = PenilaianAbsensi::rentang(collect([$pegawai]), $dari, $sampai)[$pegawai->id] ?? [];

        return view('admin.absensi.pegawai', [
            'layout' => $user->panelLayout(),
            'pegawai' => $pegawai,
            'dari' => $dari,
            'sampai' => $sampai,
            'hari' => array_reverse($hari),
            'rekap' => PenilaianAbsensi::rekap($hari),
            'preset' => $this->presetPeriode(),
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
            'tanggal' => ['required', 'date', 'before_or_equal:today'],
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
                $q->where(fn ($w) => $w->where('nama_lengkap', 'like', $kata)->orWhere('nip', 'like', $kata)->orWhere('finger_id', $request->query('q')));
            })
            ->orderBy('nama_lengkap');
    }

    /** @return array{0: Carbon, 1: Carbon} */
    private function rentang(Request $request): array
    {
        $dari = $this->tanggal($request->query('dari')) ?? now()->startOfMonth();
        $sampai = $this->tanggal($request->query('sampai')) ?? now()->startOfDay();
        if ($sampai->lt($dari)) {
            [$dari, $sampai] = [$sampai, $dari];
        }
        if ($dari->diffInDays($sampai) > 92) {
            $dari = $sampai->copy()->subDays(92); // batasi ±3 bulan agar halaman tetap ringan
        }

        return [$dari, $sampai];
    }

    private function tanggal(?string $nilai): ?Carbon
    {
        try {
            return $nilai ? Carbon::createFromFormat('Y-m-d', $nilai)->startOfDay() : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /** Pilihan cepat periode: bulan ini, bulan lalu, periode gaji (21–20) berjalan & sebelumnya. */
    private function presetPeriode(): array
    {
        $awal = (int) config('hrrab.absensi.periode_mulai', 21);
        $mulaiGaji = now()->day >= $awal ? now()->setDay($awal) : now()->subMonthNoOverflow()->setDay($awal);

        return [
            'Bulan ini' => [now()->startOfMonth(), now()],
            'Bulan lalu' => [now()->subMonthNoOverflow()->startOfMonth(), now()->subMonthNoOverflow()->endOfMonth()],
            "Periode {$awal}–" . ($awal - 1) . ' berjalan' => [$mulaiGaji->copy(), $mulaiGaji->copy()->addMonthNoOverflow()->subDay()],
            "Periode {$awal}–" . ($awal - 1) . ' lalu' => [$mulaiGaji->copy()->subMonthNoOverflow(), $mulaiGaji->copy()->subDay()],
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
