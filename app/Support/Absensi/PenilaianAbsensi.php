<?php

namespace App\Support\Absensi;

use App\Models\Absensi;
use App\Models\DataPegawai;
use App\Models\IzinAbsensi;
use App\Models\PengajuanCuti;
use App\Models\PengajuanIzinKeluar;
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
        // Izin tidak masuk & izin telat masuk (menu Izin) yang sudah disetujui akhir.
        $izinPegawai = PengajuanIzinKeluar::whereIn('data_pegawai_id', $ids)
            ->whereIn('jenis', [PengajuanIzinKeluar::JENIS_HARIAN, PengajuanIzinKeluar::JENIS_TELAT])
            ->where('status', PengajuanIzinKeluar::STATUS_APPROVED)
            ->whereDate('tanggal', '<=', $sampai)->whereRaw('COALESCE(tanggal_selesai, tanggal) >= ?', [$dari->toDateString()])
            ->get()->groupBy('data_pegawai_id');
        // Cuti disetujui yang menunjuk pegawai ini sebagai pengganti tugas (dasar lembur pengganti).
        $pengganti = PengajuanCuti::with('dataPegawai.jadwalKerja')->where('status', PengajuanCuti::STATUS_APPROVED)
            ->whereIn('pengganti_nama', $pegawais->pluck('nama_lengkap')->filter()->all())
            ->whereDate('tanggal_mulai', '<=', $sampai)->whereDate('tanggal_selesai', '>=', $dari)
            ->get()->groupBy('pengganti_nama');
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
                    ($izinPegawai[$p->id] ?? collect())->filter(fn ($z) => $z->tanggal->lte($t) && $z->tanggalAkhir()->gte($t)),
                    self::menitPengganti($p, $t, $pengganti[$p->nama_lengkap] ?? collect()),
                );
            }
        }

        return $hasil;
    }

    /**
     * @param  array{0: ?string, 1: ?string}  $cakupan  tanggal data mesin paling awal & paling akhir
     * @param  ?Collection<int, PengajuanIzinKeluar>  $izinPegawai  izin tidak masuk / telat masuk disetujui yang mencakup tanggal ini
     */
    public static function nilai(DataPegawai $p, CarbonInterface $tanggal, ?Absensi $a, ?PengajuanCuti $cuti, Collection $lembur, Collection $izin, array $cakupan = [null, null], ?Collection $izinPegawai = null, int $gantiMenit = 0): array
    {
        $izinHarian = $izinPegawai?->first(fn ($z) => $z->isHarian());
        $izinTelatPegawai = $izinPegawai?->first(fn ($z) => $z->isTelat() && $z->jamTiba());
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
            'lembur_ganti_menit' => 0,
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
        } elseif ($izinHarian && $hariKerja) {
            $h['status'] = $izinHarian->statusAbsensi();
            $h['catatan'][] = 'Izin tidak masuk (' . $izinHarian->keperluanLabel() . '): ' . $izinHarian->alasan;
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
            // Izin Telat Masuk pegawai yang disetujui: telat sampai jam tiba yang diizinkan.
            if ($izinTelatPegawai) {
                $izinTelat = max($izinTelat ?? 0, self::menit($izinTelatPegawai->jamTiba()) - $jamMasuk);
            }

            $h['telat_menit'] = $telat;
            $h['status'] = match (true) {
                $telat <= $jadwal->toleransi() => 'on_time',
                $izinTelat !== null && $telat <= $izinTelat => 'telat_izin',
                default => 'telat',
            };
            if ($h['status'] === 'telat_izin') {
                $alasan = $izin->where('jenis', IzinAbsensi::JENIS_TELAT)->pluck('alasan');
                if ($izinTelatPegawai) {
                    $alasan->push($izinTelatPegawai->keperluanLabel() . ' — ' . $izinTelatPegawai->alasan);
                }
                $h['catatan'][] = 'Izin telat: ' . $alasan->unique()->implode(', ');
            } elseif ($h['status'] === 'telat' && $izinTelatPegawai) {
                $h['catatan'][] = 'Ada izin telat sampai ' . $izinTelatPegawai->jamTiba() . ', tetapi scan masuk lebih lambat dari itu.';
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

        // Lembur pengganti cuti: dihitung dari TOTAL jam kerja, bukan dari jadwal — jam kerja orang yang diganti
        // dikurangi jam kerja jadwal sendiri (mis. 9 jam − 4 jam = 5 jam lembur). Dihitung bila pengganti hadir.
        if ($gantiMenit > 0 && $h['status'] !== 'cuti') {
            if ($masuk !== null) {
                $h['lembur_ganti_menit'] = $gantiMenit;
                $h['lembur_menit'] += $gantiMenit;
                $h['lembur_status'] ??= 'pengganti';
                $h['catatan'][] = 'Pengganti tugas cuti: lembur ' . self::durasi($gantiMenit) . ' (jam kerja yang digantikan dikurangi jam kerja jadwal sendiri).';
            } elseif ($tanggal->lte($hariIni)) {
                $h['catatan'][] = 'Ditunjuk sebagai pengganti tugas cuti (lembur ' . self::durasi($gantiMenit) . ') — dihitung setelah hadir.';
            }
        }

        [$h['label'], $h['badge']] = self::STATUS[$h['status']];

        return $h;
    }

    /**
     * Menit lembur pengganti pada satu tanggal: total jam kerja jadwal orang yang digantikan − total jam kerja jadwal sendiri.
     *
     * @param  Collection<int, PengajuanCuti>  $cutiDigantikan  cuti disetujui yang menunjuk pegawai ini sebagai pengganti
     */
    private static function menitPengganti(DataPegawai $pengganti, CarbonInterface $tanggal, Collection $cutiDigantikan): int
    {
        if ($cutiDigantikan->isEmpty() || in_array($tanggal->toDateString(), config('hrrab.hari_libur', []), true)) {
            return 0;
        }
        $total = 0;
        foreach ($cutiDigantikan as $c) {
            if ($c->tanggal_mulai->gt($tanggal) || $c->tanggal_selesai->lt($tanggal)) {
                continue;
            }
            $total += self::totalMenitKerja($c->dataPegawai?->jadwalKerja?->jamPada($tanggal));
        }

        return max(0, $total - self::totalMenitKerja($pengganti->jadwalKerja?->jamPada($tanggal)));
    }

    /** @param  array{masuk: string, pulang: string}|null  $jam */
    private static function totalMenitKerja(?array $jam): int
    {
        return $jam ? max(0, (self::menit($jam['pulang']) ?? 0) - (self::menit($jam['masuk']) ?? 0)) : 0;
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
