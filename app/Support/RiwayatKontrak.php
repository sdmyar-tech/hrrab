<?php

namespace App\Support;

use App\Models\DataPegawai;
use App\Models\EvaluasiKontrak;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Riwayat kontrak pegawai: kontrak ke berapa, periodenya, dan keterangannya (hasil evaluasi & keputusan).
 *
 * Sumber data = evaluasi kontrak. Kontrak pertama dimulai di TMT (tanggal masuk); tiap keputusan "perpanjang"
 * melahirkan kontrak berikutnya yang mulai sehari setelah kontrak lama habis. Perpanjangan SEBELUM sistem ini
 * dipakai tidak tercatat, jadi hitungannya dimulai dari data yang ada di sistem.
 */
class RiwayatKontrak
{
    /**
     * Nomor kontrak tiap evaluasi: [id evaluasi => kontrak ke-N]. Satu kali jalan untuk banyak pegawai (tanpa query per baris).
     *
     * @param  Collection<int, EvaluasiKontrak>  $semua  evaluasi dari satu atau banyak pegawai
     * @return array<int, int>
     */
    public static function nomorPerEvaluasi(Collection $semua): array
    {
        $nomor = [];
        foreach ($semua->groupBy('data_pegawai_id') as $milikPegawai) {
            $ke = 1;
            foreach ($milikPegawai->sortBy(fn ($e) => $e->tanggal_habis_kontrak->timestamp) as $e) {
                $nomor[$e->id] = $ke;
                if ($e->keputusan === EvaluasiKontrak::PERPANJANG) {
                    $ke++;
                }
            }
        }

        return $nomor;
    }

    /**
     * Daftar kontrak pegawai dari yang pertama sampai yang sedang berjalan.
     *
     * @return list<array{ke:int, mulai:?Carbon, habis:?Carbon, bulan:?int, status:string, evaluasi:?EvaluasiKontrak, keterangan:string, tone:string, catatan:?string}>
     */
    public static function untuk(DataPegawai $pegawai): array
    {
        $k = $pegawai->kepegawaian;
        $evaluasi = EvaluasiKontrak::with('pemutus')->where('data_pegawai_id', $pegawai->id)->orderBy('tanggal_habis_kontrak')->get();
        $baris = [];
        $mulai = $k?->tanggal_masuk?->copy();
        $ke = 1;

        foreach ($evaluasi as $e) {
            $habis = $e->tanggal_habis_kontrak->copy();
            [$keterangan, $tone] = self::keteranganEvaluasi($e);
            $baris[] = [
                'ke' => $ke, 'mulai' => $mulai, 'habis' => $habis, 'bulan' => self::bulan($mulai, $habis),
                'status' => $e->status === EvaluasiKontrak::STATUS_SELESAI ? 'selesai' : 'evaluasi',
                'evaluasi' => $e, 'keterangan' => $keterangan, 'tone' => $tone, 'catatan' => $e->catatan_sdm ?: $e->catatan,
            ];
            if ($e->keputusan === EvaluasiKontrak::PERPANJANG) {
                $ke++;
                $mulai = $habis->copy()->addDay();
            } else {
                $mulai = null; // tidak ada kontrak lanjutan (tetap / tidak diperpanjang / belum diputuskan)
                if ($e->status !== EvaluasiKontrak::STATUS_SELESAI) {
                    return $baris;
                }
            }
        }

        // Kontrak yang sedang berjalan (belum sampai tahap evaluasi) = kontrak terakhir menurut data kepegawaian.
        $habisSekarang = $k?->tanggal_habis_kontrak?->copy();
        if ($habisSekarang && $k->status_kerja !== 'Tetap' && ($evaluasi->isEmpty() || $mulai) && (! $mulai || $habisSekarang->gte($mulai))) {
            $baris[] = [
                'ke' => $ke, 'mulai' => $mulai ?? $k->tanggal_masuk?->copy(), 'habis' => $habisSekarang,
                'bulan' => self::bulan($mulai ?? $k->tanggal_masuk, $habisSekarang), 'status' => 'berjalan', 'evaluasi' => null,
                'keterangan' => 'Kontrak sedang berjalan; evaluasi dibuka ' . EvaluasiKontrak::HARI_SEBELUM . ' hari sebelum habis ('
                    . $habisSekarang->copy()->subDays(EvaluasiKontrak::HARI_SEBELUM)->translatedFormat('d M Y') . ').',
                'tone' => 'info', 'catatan' => null,
            ];
        }

        return $baris;
    }

    /** @return array{0: string, 1: string} [keterangan, warna badge] */
    private static function keteranganEvaluasi(EvaluasiKontrak $e): array
    {
        if ($e->status === EvaluasiKontrak::STATUS_MENUNGGU_ATASAN) {
            return ['Evaluasi berjalan: menunggu penilaian atasan.', 'warning'];
        }
        if ($e->status === EvaluasiKontrak::STATUS_MENUNGGU_SDM) {
            return ['Sudah dinilai (' . EvaluasiKontrak::predikat($e->total_nilai) . '); menunggu keputusan.', 'warning'];
        }

        $nilai = $e->total_nilai !== null ? ' Nilai ' . EvaluasiKontrak::predikat($e->total_nilai) . ' (' . $e->total_nilai . ').' : '';

        return match ($e->keputusan) {
            EvaluasiKontrak::PERPANJANG => ['Diperpanjang ' . $e->keputusan_lama_bulan . ' bulan' . ($e->keputusan_naik_gaji ? ', naik gaji' . ($e->keputusan_naik_gaji_nominal ? ' ' . EvaluasiKontrak::rupiah($e->keputusan_naik_gaji_nominal) : '') : '') . '.' . $nilai, 'success'],
            EvaluasiKontrak::TETAP => ['Diangkat menjadi pegawai tetap.' . ($e->keputusan_naik_gaji ? ' Naik gaji' . ($e->keputusan_naik_gaji_nominal ? ' ' . EvaluasiKontrak::rupiah($e->keputusan_naik_gaji_nominal) : '') . '.' : '') . $nilai, 'success'],
            EvaluasiKontrak::TIDAK => ['Tidak diperpanjang; kontrak berakhir.' . $nilai, 'danger'],
            default => ['Selesai.' . $nilai, 'neutral'],
        };
    }

    private static function bulan(?Carbon $mulai, ?Carbon $habis): ?int
    {
        return $mulai && $habis && $habis->gte($mulai) ? max(1, (int) round($mulai->diffInDays($habis->copy()->addDay()) / 30.4375)) : null;
    }
}
