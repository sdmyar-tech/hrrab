<?php

namespace App\Support;

use App\Models\DataPegawai;
use App\Models\EvaluasiKontrak;
use App\Models\Kepegawaian;
use App\Models\KontrakPegawai;
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
        // Catatan manual (PKWT n) menjadi patokan bila ada: kontrak ke-N = nomor PKWT yang memuat tanggal habis evaluasi itu.
        $manual = KontrakPegawai::tersedia()
            ? KontrakPegawai::whereIn('data_pegawai_id', $semua->pluck('data_pegawai_id')->unique())->get()->groupBy('data_pegawai_id')
            : collect();

        foreach ($semua->groupBy('data_pegawai_id') as $idPegawai => $milikPegawai) {
            $urut = $milikPegawai->sortBy(fn ($e) => $e->tanggal_habis_kontrak->timestamp);
            $pkwt = ($manual[$idPegawai] ?? collect())->filter(fn (KontrakPegawai $r) => $r->dihitung());

            if ($pkwt->isEmpty()) {
                $ke = 1;
                foreach ($urut as $e) {
                    $nomor[$e->id] = $ke;
                    if ($e->keputusan === EvaluasiKontrak::PERPANJANG) {
                        $ke++;
                    }
                }

                continue;
            }

            $tambahan = 0; // perpanjangan di luar periode yang dicatat manual
            foreach ($urut as $e) {
                $habis = $e->tanggal_habis_kontrak;
                $memuat = $pkwt->first(fn (KontrakPegawai $r) => $habis->gte($r->tanggal_mulai) && (! $r->tanggal_selesai || $habis->lte($r->tanggal_selesai)));
                if ($memuat) {
                    $nomor[$e->id] = $memuat->nomorPkwt();

                    continue;
                }
                $dasar = (int) $pkwt->filter(fn (KontrakPegawai $r) => $r->tanggal_mulai->lte($habis))->max(fn (KontrakPegawai $r) => $r->nomorPkwt());
                $nomor[$e->id] = $dasar + 1 + $tambahan;
                if ($e->keputusan === EvaluasiKontrak::PERPANJANG) {
                    $tambahan++;
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

        $manual = KontrakPegawai::tersedia() ? $pegawai->kontrak()->get() : collect();
        if ($manual->isNotEmpty()) {
            return self::dariCatatan($manual, $evaluasi, $k);
        }

        $baris = [];
        $mulai = $k?->tanggal_masuk?->copy();
        $ke = 1;

        foreach ($evaluasi as $e) {
            $habis = $e->tanggal_habis_kontrak->copy();
            [$keterangan, $tone] = self::keteranganEvaluasi($e);
            $baris[] = [
                'label' => 'Kontrak ke-' . $ke,
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
                'label' => 'Kontrak ke-' . $ke,
                'ke' => $ke, 'mulai' => $mulai ?? $k->tanggal_masuk?->copy(), 'habis' => $habisSekarang,
                'bulan' => self::bulan($mulai ?? $k->tanggal_masuk, $habisSekarang), 'status' => 'berjalan', 'evaluasi' => null,
                'keterangan' => 'Kontrak sedang berjalan; evaluasi dibuka ' . EvaluasiKontrak::HARI_SEBELUM . ' hari sebelum habis ('
                    . $habisSekarang->copy()->subDays(EvaluasiKontrak::HARI_SEBELUM)->translatedFormat('d M Y') . ').',
                'tone' => 'info', 'catatan' => null,
            ];
        }

        return $baris;
    }

    /**
     * Riwayat dari catatan manual (Masa Training, PKWT n, PKWTT …); hasil evaluasi yang periodenya cocok ditempelkan
     * ke barisnya. Evaluasi di luar periode catatan & kontrak yang sedang berjalan ditambahkan di belakang.
     *
     * @param  Collection<int, KontrakPegawai>  $manual
     */
    private static function dariCatatan(Collection $manual, Collection $evaluasi, ?Kepegawaian $k): array
    {
        $baris = [];
        $terpakai = [];

        foreach ($manual as $r) {
            $akhir = $r->tanggal_selesai?->copy();
            $e = $r->dihitung()
                ? $evaluasi->first(fn (EvaluasiKontrak $x) => $x->tanggal_habis_kontrak->gte($r->tanggal_mulai) && (! $akhir || $x->tanggal_habis_kontrak->lte($akhir)))
                : null;
            if ($e) {
                $terpakai[$e->id] = true;
            }
            $ketEvaluasi = $e ? self::keteranganEvaluasi($e)[0] : null;
            $keterangan = trim(($r->keterangan ?: '') . ' ' . ($ketEvaluasi ?? ''));
            $baris[] = [
                'label' => $r->jenis, 'ke' => $r->nomorPkwt(), 'mulai' => $r->tanggal_mulai->copy(), 'habis' => $akhir, 'bulan' => self::bulan($r->tanggal_mulai, $akhir),
                'status' => $r->berlaku() ? 'berjalan' : 'catatan', 'evaluasi' => $e,
                'keterangan' => $keterangan !== '' ? $keterangan : ($r->jenis === KontrakPegawai::PKWTT ? 'Pegawai tetap (PKWTT).' : 'Tercatat manual.'),
                'tone' => $r->warna(), 'catatan' => $r->nomor_kontrak ? 'No. ' . $r->nomor_kontrak : null,
            ];
        }

        // Evaluasi yang tidak termasuk periode catatan mana pun: lanjutkan penomoran dari PKWT tertinggi.
        $ke = (int) $manual->filter(fn (KontrakPegawai $r) => $r->dihitung())->max(fn (KontrakPegawai $r) => $r->nomorPkwt()) + 1;
        $terakhir = $manual->max(fn (KontrakPegawai $r) => ($r->tanggal_selesai ?? $r->tanggal_mulai)->timestamp);
        foreach ($evaluasi as $e) {
            if (isset($terpakai[$e->id])) {
                continue;
            }
            [$keterangan, $tone] = self::keteranganEvaluasi($e);
            $mulai = $e->periode_mulai ?? $e->periodeBawaan()[0];
            $baris[] = [
                'label' => 'Kontrak ke-' . $ke, 'ke' => $ke, 'mulai' => $mulai?->copy(), 'habis' => $e->tanggal_habis_kontrak->copy(),
                'bulan' => self::bulan($mulai, $e->tanggal_habis_kontrak), 'status' => $e->status === EvaluasiKontrak::STATUS_SELESAI ? 'selesai' : 'evaluasi',
                'evaluasi' => $e, 'keterangan' => $keterangan, 'tone' => $tone, 'catatan' => $e->catatan_sdm ?: $e->catatan,
            ];
            if ($e->keputusan === EvaluasiKontrak::PERPANJANG) {
                $ke++;
            }
        }

        // Kontrak berjalan menurut data kepegawaian yang belum terwakili baris mana pun.
        $habisSekarang = $k?->tanggal_habis_kontrak?->copy();
        $terwakili = $habisSekarang && collect($baris)->contains(fn ($b) => $b['habis'] && $b['habis']->gte($habisSekarang));
        if ($habisSekarang && $k->status_kerja !== 'Tetap' && ! $terwakili && $habisSekarang->timestamp >= ($terakhir ?? 0)) {
            $baris[] = [
                'label' => 'Kontrak ke-' . $ke, 'ke' => $ke, 'mulai' => null, 'habis' => $habisSekarang, 'bulan' => null, 'status' => 'berjalan', 'evaluasi' => null,
                'keterangan' => 'Kontrak sedang berjalan (menurut Data Pegawai); evaluasi dibuka ' . EvaluasiKontrak::HARI_SEBELUM . ' hari sebelum habis ('
                    . $habisSekarang->copy()->subDays(EvaluasiKontrak::HARI_SEBELUM)->translatedFormat('d M Y') . ').',
                'tone' => 'info', 'catatan' => null,
            ];
        }

        usort($baris, fn ($a, $b) => ($a['mulai']?->timestamp ?? PHP_INT_MAX) <=> ($b['mulai']?->timestamp ?? PHP_INT_MAX));

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
