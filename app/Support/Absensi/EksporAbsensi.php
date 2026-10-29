<?php

namespace App\Support\Absensi;

use App\Models\DataPegawai;

/** Kolom ekspor absensi yang sama untuk Rekap, Harian, per Pegawai, dan Absensi Saya. */
class EksporAbsensi
{
    public const HEADER_REKAP = [
        'Nama', 'NIP', 'Finger ID', 'Bagian', 'Jadwal', 'Hari Kerja', 'Hadir', 'On Time', 'Telat (kali)', 'Telat (menit)', 'Telat Izin',
        'Pulang Cepat', 'Tidak Scan Pulang', 'Alpa', 'Cuti', 'Izin', 'Sakit', 'Dinas Luar',
        'Lembur Terverifikasi (jam)', 'Lembur Surat (jam)', 'Lembur Belum Terverifikasi (hari)', 'Hari Dikoreksi',
    ];

    public const HEADER_DETAIL = [
        'Tanggal', 'Hari', 'Nama', 'Finger ID', 'Bagian', 'Jadwal', 'Masuk', 'Keluar', 'Status', 'Telat (menit)', 'Pulang Cepat (menit)',
        'Tidak Scan Pulang', 'Lembur (jam)', 'Status Lembur', 'Dikoreksi', 'Alasan Koreksi', 'Catatan',
    ];

    /** Satu baris rekap (hasil PenilaianAbsensi::rekap) untuk satu pegawai. */
    public static function rekap(DataPegawai $p, array $r): array
    {
        return [
            $p->nama_lengkap, $p->nip, $p->finger_id, $p->kepegawaian?->unit_kerja, $p->jadwalKerja?->ringkas() ?? 'Belum diatur',
            $r['hari_kerja'], $r['hadir_total'], $r['on_time'], $r['telat'], $r['telat_menit'], $r['telat_izin'],
            $r['pulang_cepat'], $r['tanpa_scan_pulang'], $r['alpa'], $r['cuti'], $r['izin'], $r['sakit'], $r['dinas_luar'],
            self::jam($r['lembur_menit']), self::jam($r['lembur_surat_menit']), $r['lembur_belum'], $r['dikoreksi'],
        ];
    }

    /** Satu baris hari (hasil PenilaianAbsensi::rentang) untuk satu pegawai. */
    public static function detail(DataPegawai $p, array $h): array
    {
        return [
            $h['tanggal']->format('d-m-Y'), $h['tanggal']->translatedFormat('l'), $p->nama_lengkap, $p->finger_id, $p->kepegawaian?->unit_kerja,
            $h['jadwal'] ? $h['jadwal']['masuk'] . '-' . $h['jadwal']['pulang'] : '-', $h['masuk'], $h['keluar'],
            $h['label'], $h['telat_menit'] ?: null, $h['pulang_cepat_menit'] ?: null, $h['tanpa_scan_pulang'] ? 'Ya' : null,
            $h['lembur_menit'] ? self::jam($h['lembur_menit']) : null, $h['lembur_status'], $h['dikoreksi'] ? 'Ya' : null,
            $h['absensi']?->alasan_koreksi, implode(' ', $h['catatan']),
        ];
    }

    /** Ekspor satu pegawai: lembar ringkasan + detail per hari (urut tanggal naik). */
    public static function satuPegawai(DataPegawai $p, array $hari): array
    {
        return [
            ['name' => 'Ringkasan', 'headers' => self::HEADER_REKAP, 'rows' => [self::rekap($p, PenilaianAbsensi::rekap($hari))]],
            ['name' => 'Detail Harian', 'headers' => self::HEADER_DETAIL, 'rows' => array_map(fn (array $h) => self::detail($p, $h), array_values($hari))],
        ];
    }

    private static function jam(int $menit): float|int
    {
        return $menit ? round($menit / 60, 2) : 0;
    }
}
