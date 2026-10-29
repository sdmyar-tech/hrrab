<?php

namespace App\Support\Absensi;

use App\Models\Absensi;
use App\Models\DataPegawai;
use App\Models\IzinAbsensi;
use App\Models\PengajuanCuti;
use App\Models\PengajuanLembur;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Menilai absensi harian berdasarkan jam finger + aturan HRRAB.
 * (Kolom Late/Early/OT dari mesin tidak dipakai sama sekali.)
 *
 * Status utama:
 *  on_time    masuk ≤ jam masuk + toleransi (bawaan 15 menit, bisa per pegawai)
 *  telat      lewat toleransi
 *  telat_izin lewat toleransi tapi masih dalam batas izin telat hari itu (bawaan 60 menit)
 *  hadir      ada scan tapi jadwal belum diatur / koreksi manual "Hadir"
 *  alpa       hari kerja tanpa scan (bukan cuti/libur)
 *  cuti | libur | izin | sakit | dinas_luar | belum_scan (hari ini) | belum (tanggal mendatang)
 * Tanda tambahan: pulang cepat, tidak scan pulang, lembur (hanya bila ada surat lembur disetujui).
 */
class PenilaianAbsensi
{
    public const STATUS = [
        'on_time' => ['On Time', 'text-bg-success'],
        'telat' => ['Telat', 'text-bg-warning'],
        'telat_izin' => ['Telat Izin', 'text-bg-info'],
        'hadir' => ['Hadir', 'text-bg-success'],
        'alpa' => ['Alpa', 'text-bg-danger'],
        'cuti' => ['Cuti', 'text-bg-info'],
        'libur' => ['Libur', 'text-bg-light border'],
        'izin' => ['Izin', 'text-bg-info'],
        'sakit' => ['Sakit', 'text-bg-info'],
        'dinas_luar' => ['Dinas Luar', 'text-bg-info'],
        'belum_scan' => ['Belum scan', 'text-bg-light border'],
        'belum' => ['Belum ada data', 'text-bg-light border text-muted'],
    ];

    /**
     * Nilai absensi beberapa pegawai pada rentang tanggal.
     *
     * @param  Collection<int, DataPegawai>  $pegawais
     * @return array<int, array<string, array>> [pegawai_id][Y-m-d] => hasil
     */
    public static function rentang(Collection $pegawais, CarbonInterface $dari, CarbonInterface $sampai): array
    {
        // Pastikan koleksi Eloquent (bisa dipanggil dengan collect([$pegawai])) agar relasi bisa dimuat sekaligus.
        $pegawais = (new \Illuminate\Database\Eloquent\Collection($pegawais->all()))->loadMissing(['jadwalKerja', 'kepegawaian']);
        $ids = $pegawais->pluck('id');
        $dari = Carbon::parse($dari)->startOfDay();
        $sampai = Carbon::parse($sampai)->startOfDay();

        $absensi = Absensi::whereIn('data_pegawai_id', $ids)->whereBetween('tanggal', [$dari->toDateString(), $sampai->toDateString()])
            ->get()->groupBy('data_pegawai_id')->map(fn ($g) => $g->keyBy(fn ($a) => $a->tanggal->toDateString()));
        $cuti = PengajuanCuti::whereIn('data_pegawai_id', $ids)->where('status', PengajuanCuti::STATUS_APPROVED)
            ->whereDate('tanggal_mulai', '<=', $sampai)->whereDate('tanggal_selesai', '>=', $dari)
            ->get()->groupBy('data_pegawai_id');
        $lembur = PengajuanLembur::whereIn('data_pegawai_id', $ids)->where('status', PengajuanLembur::STATUS_APPROVED)
            ->whereBetween('tanggal', [$dari->toDateString(), $sampai->toDateString()])
            ->get()->groupBy(fn ($l) => $l->data_pegawai_id . '|' . $l->tanggal->toDateString());
        $izin = IzinAbsensi::whereBetween('tanggal', [$dari->toDateString(), $sampai->toDateString()])
            ->get()->groupBy(fn ($z) => $z->tanggal->toDateString());
        // Rentang tanggal yang sudah ada data mesinnya: di luar ini hari tanpa scan BUKAN Alpa (data belum diimport).
        $cakupan = [Absensi::whereNotNull('finger_id')->min('tanggal'), Absensi::whereNotNull('finger_id')->max('tanggal')];

        $hasil = [];
        foreach ($pegawais as $p) {
            $unit = $p->kepegawaian?->unit_kerja;
            for ($t = $dari->copy(); $t->lte($sampai); $t->addDay()) {
                $tgl = $t->toDateString();
                $hasil[$p->id][$tgl] = self::nilai(
                    $p,
                    $t->copy(),
                    $absensi[$p->id][$tgl] ?? null,
                    ($cuti[$p->id] ?? collect())->first(fn ($c) => $c->tanggal_mulai->lte($t) && $c->tanggal_selesai->gte($t)),
                    $lembur["{$p->id}|{$tgl}"] ?? collect(),
                    ($izin[$tgl] ?? collect())->filter(fn ($z) => $z->berlakuUntuk($p->id, $unit)),
                    $cakupan,
                );
            }
        }

        return $hasil;
    }

    /** @param array{0: ?string, 1: ?string} $cakupan  tanggal data mesin paling awal & paling akhir */
    public static function nilai(DataPegawai $p, CarbonInterface $tanggal, ?Absensi $a, ?PengajuanCuti $cuti, Collection $lembur, Collection $izin, array $cakupan = [null, null]): array
    {
        $jadwal = $p->jadwalKerja;
        $libur = in_array($tanggal->toDateString(), config('hrrab.hari_libur', []), true);
        $jam = $jadwal?->jamPada($tanggal);
        $hariKerja = ! $libur && ($jadwal ? $jam !== null : in_array($tanggal->dayOfWeekIso, config('hrrab.hari_kerja', [1, 2, 3, 4, 5]), true));
        if (! $hariKerja) {
            $jam = null;
        }

        $masuk = self::menit($a?->jam_masuk);
        $keluar = self::menit($a?->jam_keluar);
        $catatan = [];

        // Scan keluar terlalu dekat dengan scan masuk (mis. 07:53 & 07:54) = tidak scan pulang.
        if ($masuk !== null && $keluar !== null && ! $a->dikoreksi && $keluar - $masuk < (int) config('hrrab.absensi.min_selisih_keluar', 60)) {
            $catatan[] = 'Scan keluar ' . self::jam($keluar) . ' terlalu dekat dengan scan masuk — dianggap tidak scan pulang.';
            $keluar = null;
        }

        $h = [
            'tanggal' => Carbon::parse($tanggal),
            'status' => 'belum',
            'masuk' => self::jam($masuk),
            'keluar' => self::jam($keluar),
            'scan_masuk' => $a?->scan_masuk ? substr($a->scan_masuk, 0, 5) : null,
            'scan_keluar' => $a?->scan_keluar ? substr($a->scan_keluar, 0, 5) : null,
            'jadwal' => $jam,
            'hari_kerja' => $hariKerja,
            'telat_menit' => 0,
            'pulang_cepat_menit' => 0,
            'pulang_cepat_izin' => false,
            'tanpa_scan_pulang' => false,
            'lembur_menit' => 0,
            'lembur_surat_menit' => 0,
            'lembur_status' => null,
            'dikoreksi' => (bool) $a?->dikoreksi,
            'absensi' => $a,
            'catatan' => $catatan,
        ];

        $hariIni = now()->startOfDay();
        $manual = $a?->status_manual;

        if ($manual && $manual !== Absensi::MANUAL_HADIR) {
            $h['status'] = $manual;
        } elseif ($cuti) {
            $h['status'] = 'cuti';
        } elseif (! $hariKerja) {
            $h['status'] = 'libur';
        } elseif ($masuk === null) {
            $tgl = $tanggal->toDateString();
            $masukKerja = $p->kepegawaian?->tanggal_masuk;
            $belumAdaData = ! $cakupan[0] || $tgl < $cakupan[0] || $tgl > $cakupan[1] || ($masukKerja && $tanggal->lt($masukKerja));
            $h['status'] = match (true) {
                $tanggal->gt($hariIni) => 'belum',
                $tanggal->equalTo($hariIni) => 'belum_scan',
                $belumAdaData => 'belum',   // data mesin tanggal ini belum diimport / pegawai belum bekerja
                default => 'alpa',
            };
        } elseif ($jam === null) {
            $h['status'] = 'hadir';
            $h['catatan'][] = 'Jadwal kerja pegawai belum diatur — keterlambatan tidak dinilai.';
        } else {
            $jamMasuk = self::menit($jam['masuk']);
            $jamPulang = self::menit($jam['pulang']);
            $telat = max(0, $masuk - $jamMasuk);
            $izinTelat = $izin->where('jenis', IzinAbsensi::JENIS_TELAT)->max('menit');

            $h['telat_menit'] = $telat;
            $h['status'] = match (true) {
                $telat <= $jadwal->toleransi() => 'on_time',
                $izinTelat !== null && $telat <= $izinTelat => 'telat_izin',
                default => 'telat',
            };
            if ($h['status'] === 'telat_izin') {
                $h['catatan'][] = 'Izin telat: ' . $izin->where('jenis', IzinAbsensi::JENIS_TELAT)->pluck('alasan')->unique()->implode(', ');
            }

            if ($keluar !== null && $keluar < $jamPulang) {
                $h['pulang_cepat_menit'] = $jamPulang - $keluar;
                $izinPulang = $izin->where('jenis', IzinAbsensi::JENIS_PULANG_CEPAT)->max('menit');
                $h['pulang_cepat_izin'] = $izinPulang !== null && $h['pulang_cepat_menit'] <= $izinPulang;
            }
            if ($keluar === null && $tanggal->lt($hariIni)) {
                $h['tanpa_scan_pulang'] = true;
            }
        }

        if ($manual === Absensi::MANUAL_HADIR && $jam === null && $masuk !== null) {
            $h['status'] = 'hadir';
        }

        self::lembur($h, $lembur, $masuk, $keluar, $jam, $a);
        [$h['label'], $h['badge']] = self::STATUS[$h['status']];

        return $h;
    }

    /**
     * Lembur dihitung HANYA bila ada surat lembur disetujui:
     * menit kehadiran (scan) di dalam jendela surat & di luar jam kerja → maksimal sebesar surat.
     */
    private static function lembur(array &$h, Collection $surat, ?int $masuk, ?int $keluar, ?array $jam, ?Absensi $a): void
    {
        $jamMasuk = $jam ? self::menit($jam['masuk']) : null;
        $jamPulang = $jam ? self::menit($jam['pulang']) : null;

        if ($surat->isEmpty()) {
            // Info saja: hadir ≥ 1 jam di luar jam kerja tanpa surat lembur (tidak dihitung lembur).
            if ($jam && $masuk !== null && $keluar !== null && ($jamMasuk - $masuk >= 60 || $keluar - $jamPulang >= 60)) {
                $h['catatan'][] = 'Hadir di luar jam kerja tanpa surat lembur — tidak dihitung lembur.';
            }

            return;
        }

        $totalSurat = 0;
        $terverifikasi = 0;
        foreach ($surat as $s) {
            $mulai = self::menit($s->jam_mulai);
            $selesai = self::menit($s->jam_selesai);
            if ($mulai === null || $selesai === null) {
                continue;
            }
            if ($selesai <= $mulai) {
                $selesai = 1440; // lembur lewat tengah malam: dihitung sampai 24:00, sisanya dikoreksi SDM
            }
            $totalSurat += $selesai - $mulai;

            if ($masuk !== null && $keluar !== null) {
                $hadir = self::irisan($masuk, $keluar, $mulai, $selesai);
                $diJamKerja = $jam ? self::irisan(max($masuk, $mulai), min($keluar, $selesai), $jamMasuk, $jamPulang) : 0;
                $terverifikasi += max(0, $hadir - $diJamKerja);
            }
        }

        $h['lembur_surat_menit'] = $totalSurat;
        if ($a?->lembur_manual_menit !== null) {
            $h['lembur_menit'] = $a->lembur_manual_menit;
            $h['lembur_status'] = 'manual';
        } elseif ($terverifikasi > 0) {
            $h['lembur_menit'] = min($terverifikasi, $totalSurat);
            $h['lembur_status'] = 'terverifikasi';
        } else {
            $h['lembur_status'] = 'belum';
            $h['catatan'][] = 'Ada surat lembur, tetapi jam finger tidak mencakup jam lembur — perlu verifikasi SDM.';
        }
    }

    /** Ringkasan rekap untuk satu pegawai dari hasil rentang(). */
    public static function rekap(array $hariHari): array
    {
        $r = array_fill_keys(array_keys(self::STATUS), 0) + [
            'hari_kerja' => 0, 'telat_menit' => 0, 'pulang_cepat' => 0, 'tanpa_scan_pulang' => 0,
            'lembur_menit' => 0, 'lembur_surat_menit' => 0, 'lembur_belum' => 0, 'dikoreksi' => 0,
        ];
        foreach ($hariHari as $h) {
            $r[$h['status']]++;
            $r['hari_kerja'] += $h['hari_kerja'] && $h['status'] !== 'belum' ? 1 : 0;
            $r['telat_menit'] += $h['status'] === 'telat' ? $h['telat_menit'] : 0;
            $r['pulang_cepat'] += $h['pulang_cepat_menit'] > 0 && ! $h['pulang_cepat_izin'] ? 1 : 0;
            $r['tanpa_scan_pulang'] += $h['tanpa_scan_pulang'] ? 1 : 0;
            $r['lembur_menit'] += $h['lembur_menit'];
            $r['lembur_surat_menit'] += $h['lembur_surat_menit'];
            $r['lembur_belum'] += $h['lembur_status'] === 'belum' ? 1 : 0;
            $r['dikoreksi'] += $h['dikoreksi'] ? 1 : 0;
        }
        $r['hadir_total'] = $r['on_time'] + $r['telat'] + $r['telat_izin'] + $r['hadir'] + $r['dinas_luar'];

        return $r;
    }

    public static function menit(?string $jam): ?int
    {
        if ($jam === null || $jam === '') {
            return null;
        }
        [$j, $m] = array_map('intval', explode(':', $jam) + [1 => 0]);

        return $j * 60 + $m;
    }

    public static function jam(?int $menit): ?string
    {
        return $menit === null ? null : sprintf('%02d:%02d', intdiv($menit, 60), $menit % 60);
    }

    /** Format durasi menit → "1 j 30 m". */
    public static function durasi(int $menit): string
    {
        if ($menit <= 0) {
            return '0 m';
        }

        return trim((intdiv($menit, 60) ? intdiv($menit, 60) . ' j ' : '') . ($menit % 60 ? ($menit % 60) . ' m' : ''));
    }

    private static function irisan(int $a1, int $a2, int $b1, int $b2): int
    {
        return max(0, min($a2, $b2) - max($a1, $b1));
    }
}
