<?php

namespace App\Support\Absensi;

use App\Models\Absensi;
use App\Models\DataPegawai;

/** Satu sumber ikon & panduan langkah modul Absensi, agar tampilan konsisten di semua akun & halaman. */
class TampilanAbsensi
{
    public const IKON = [
        'modul' => 'bi-calendar2-week',
        'harian' => 'bi-calendar-day',
        'rekap' => 'bi-bar-chart-line',
        'import' => 'bi-cloud-arrow-up',
        'izin' => 'bi-umbrella',
        'pengaturan' => 'bi-sliders',
        'saya' => 'bi-person-check',
        'koreksi' => 'bi-pencil-square',
        'mesin' => 'bi-upc-scan',
        'lembur' => 'bi-clock-history',
    ];

    /** Alasan koreksi yang paling sering, untuk diklik cepat. */
    public const ALASAN_CEPAT = [
        'Lupa finger masuk',
        'Lupa finger pulang',
        'Mesin absen bermasalah',
        'Tugas di luar kantor',
        'Sakit (ada surat dokter)',
        'Izin keperluan keluarga',
    ];

    /** Pilihan cepat jam kerja yang umum dipakai di yayasan. */
    public const JAM_CEPAT = [
        ['07:30', '16:00'],
        ['07:00', '15:00'],
        ['08:00', '16:00'],
        ['08:00', '17:00'],
    ];

    /**
     * Langkah persiapan absensi untuk pengelola (SDM/Admin).
     *
     * @return array{langkah: list<array{judul: string, teks: string, selesai: bool, url: string, tombol: string}>, selesai: int, total: int}
     */
    public static function langkahSetup(): array
    {
        $ikut = AksesAbsensi::aktif(DataPegawai::where('ikut_absensi', true));
        $jumlahIkut = (clone $ikut)->count();
        $tanpaFinger = (clone $ikut)->whereNull('finger_id')->count();
        $tanpaJadwal = (clone $ikut)->doesntHave('jadwalKerja')->count();
        $adaImport = Absensi::whereNotNull('finger_id')->exists();
        $belumDipetakan = Absensi::whereNull('data_pegawai_id')->whereNotNull('finger_id')->distinct('finger_id')->count('finger_id');

        $langkah = [
            [
                'judul' => 'Pilih pegawai yang diabsen',
                'teks' => $jumlahIkut ? "{$jumlahIkut} pegawai ikut absensi." : 'Belum ada pegawai yang dicentang ikut absensi.',
                'selesai' => $jumlahIkut > 0,
                'url' => route('admin.absensi.pengaturan', ['tab' => 'daftar']),
                'tombol' => 'Pilih pegawai',
            ],
            [
                'judul' => 'Atur jam kerja',
                'teks' => $tanpaJadwal ? "{$tanpaJadwal} pegawai belum punya jam kerja." : 'Semua pegawai sudah punya jam kerja.',
                'selesai' => $jumlahIkut > 0 && $tanpaJadwal === 0,
                'url' => route('admin.absensi.pengaturan', ['masalah' => 'tanpa_jadwal']),
                'tombol' => 'Atur jam kerja',
            ],
            [
                'judul' => 'Import data dari mesin',
                'teks' => $adaImport ? 'Data mesin sudah masuk.' : 'Unggah file dari flashdisk mesin absen.',
                'selesai' => $adaImport,
                'url' => route('admin.absensi.import'),
                'tombol' => 'Import file',
            ],
            [
                'judul' => 'Hubungkan ID mesin',
                'teks' => match (true) {
                    $belumDipetakan > 0 => "{$belumDipetakan} ID dari mesin belum dikenali.",
                    $tanpaFinger > 0 => "{$tanpaFinger} pegawai belum punya ID mesin.",
                    default => 'Semua ID mesin sudah terhubung.',
                },
                'selesai' => $adaImport && $belumDipetakan === 0 && $tanpaFinger === 0,
                'url' => route('admin.absensi.pengaturan', ['tab' => 'pemetaan']),
                'tombol' => 'Hubungkan ID',
            ],
        ];

        $selesai = count(array_filter($langkah, fn ($l) => $l['selesai']));

        return ['langkah' => $langkah, 'selesai' => $selesai, 'total' => count($langkah)];
    }
}
