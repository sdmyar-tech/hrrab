<?php

namespace App\Support;

use App\Models\DataPegawai;
use App\Models\Kepegawaian;
use App\Models\Lowongan;
use App\Models\PengajuanCuti;
use App\Models\PengajuanFormasi;
use App\Models\PengajuanIzinKeluar;
use App\Models\PengajuanLembur;
use App\Models\User;
use Carbon\CarbonInterface;

/**
 * Ringkasan untuk penyetuju (cuti, lembur, izin keluar, formasi): detail pengajuan, angka penting, peringatan,
 * riwayat singkat, dan alur persetujuan. Semua tipe memakai bentuk array yang sama agar satu partial
 * (admin.pengajuan._ringkasan) bisa menampilkannya.
 *
 * Bentuk: judul, orang{nama, sub, meta}, detail[[label, nilai]], angka[{label, nilai, catatan, nada}],
 * peringatan[string], riwayatJudul, riwayat[{utama, sub, status, kelas}], alur[{label, oleh, waktu, catatan, status}].
 * Status alur: selesai | berjalan | menunggu | ditolak | lewati.
 */
class RingkasanPengajuan
{
    private const URUTAN_TAHAP = [PengajuanWorkflow::LEVEL_KEPALA_DIVISI, PengajuanWorkflow::LEVEL_GM, PengajuanWorkflow::LEVEL_KEPALA_SDM];

    public static function cuti(PengajuanCuti $c): array
    {
        $c->loadMissing('dataPegawai.kepegawaian', 'kepalaApprovedBy', 'gmApprovedBy', 'approvedBy');
        $tahun = $c->tanggal_mulai->year;
        $unit = PengajuanWorkflow::unitPengajuan($c);
        $sisa = PengajuanCuti::sisaCutiTahunan($c->dataPegawai, $tahun);
        $setelah = $sisa - $c->lama_cuti;
        $tahunan = $c->jenis_cuti === PengajuanCuti::JENIS_TAHUNAN;
        $milikPegawai = PengajuanCuti::where('data_pegawai_id', $c->data_pegawai_id)->whereKeyNot($c->id);

        // Rekan satu unit yang cuti (menunggu/disetujui) di rentang tanggal yang sama.
        $bersamaan = PengajuanCuti::with('dataPegawai:id,nama_lengkap')
            ->where('unit_kerja', $unit)->where('data_pegawai_id', '!=', $c->data_pegawai_id)
            ->whereIn('status', [PengajuanCuti::STATUS_PENDING, PengajuanCuti::STATUS_APPROVED])
            ->whereDate('tanggal_mulai', '<=', $c->tanggal_selesai)->whereDate('tanggal_selesai', '>=', $c->tanggal_mulai)
            ->get();

        $peringatan = [];
        if ($c->isPending() && $tahunan && $setelah < 0) {
            $peringatan[] = "Melebihi sisa cuti tahunan ({$sisa} hari tersisa, diajukan {$c->lama_cuti} hari).";
        }
        if ($bersamaan->isNotEmpty()) {
            $peringatan[] = 'Rekan bagian ' . $unit . ' cuti di tanggal yang sama: '
                . $bersamaan->map(fn ($b) => ($b->dataPegawai?->nama_lengkap ?? '-') . ($b->isPending() ? ' (menunggu)' : ''))->take(4)->implode(', ')
                . ($bersamaan->count() > 4 ? ', +' . ($bersamaan->count() - 4) . ' lainnya' : '') . '.';
        }

        return [
            'judul' => $c->jenisLabel() . ' · ' . $c->lama_cuti . ' hari',
            'orang' => self::orang($c->dataPegawai, $unit),
            'detail' => array_filter([
                ['Jenis', $c->jenisLabel() . ($c->kategoriBesarLabel() ? ' — ' . $c->kategoriBesarLabel() : '')],
                ['Tanggal', self::rentang($c->tanggal_mulai, $c->tanggal_selesai) . " ({$c->lama_cuti} hari)"],
                ['Pengganti', $c->pengganti_nama ?: '-'],
                $c->keterangan ? ['Keterangan', $c->keterangan] : null,
                ['Diajukan', self::waktu($c->created_at)],
            ]),
            'angka' => [
                ['label' => 'Sisa cuti tahunan ' . $tahun, 'nilai' => "{$sisa}/" . PengajuanCuti::ANNUAL_QUOTA . ' hari',
                    'catatan' => $c->isPending() && $tahunan ? 'jadi ' . max(0, $setelah) . ' hari bila disetujui' : null,
                    'nada' => $tahunan && $c->isPending() ? ($setelah < 0 ? 'bahaya' : ($setelah <= 2 ? 'waspada' : null)) : null],
                ['label' => 'Pengajuan tahun ini', 'nilai' => PengajuanCuti::jumlahPengajuanTahunIni($c->dataPegawai, $tahun) . '/' . PengajuanCuti::MAX_PENGAJUAN_PER_TAHUN . ' kali', 'catatan' => null, 'nada' => null],
                ['label' => 'Cuti disetujui ' . $tahun, 'nilai' => (clone $milikPegawai)->where('status', PengajuanCuti::STATUS_APPROVED)->whereYear('tanggal_mulai', $tahun)->sum('lama_cuti') . ' hari', 'catatan' => null, 'nada' => null],
                ['label' => 'Rekan cuti bersamaan', 'nilai' => $bersamaan->count() . ' orang', 'catatan' => null, 'nada' => $bersamaan->isNotEmpty() ? 'waspada' : null],
            ],
            'peringatan' => $peringatan,
            'riwayatJudul' => 'Riwayat cuti terakhir',
            'riwayat' => (clone $milikPegawai)->latest('tanggal_mulai')->limit(5)->get()->map(fn (PengajuanCuti $r) => [
                'utama' => self::rentang($r->tanggal_mulai, $r->tanggal_selesai) . ' · ' . $r->jenisLabel(),
                'sub' => $r->lama_cuti . ' hari' . ($r->keterangan ? ' · ' . str($r->keterangan)->limit(40) : ''),
                'status' => $r->statusLabel(), 'kelas' => $r->badgeClass(),
            ])->all(),
            'alur' => self::alurBerjenjang($c, false),
        ];
    }

    public static function lembur(PengajuanLembur $l): array
    {
        $l->loadMissing('dataPegawai.kepegawaian', 'kepalaApprovedBy', 'gmApprovedBy', 'approvedBy');
        $unit = PengajuanWorkflow::unitPengajuan($l);
        $bulan = $l->tanggal->copy()->startOfMonth();
        $milikPegawai = PengajuanLembur::where('data_pegawai_id', $l->data_pegawai_id)->whereKeyNot($l->id);
        $diBulan = fn (CarbonInterface $b, string $status) => (clone $milikPegawai)->where('status', $status)
            ->whereBetween('tanggal', [$b->toDateString(), $b->copy()->endOfMonth()->toDateString()]);
        $jamDisetujui = (float) $diBulan($bulan, PengajuanLembur::STATUS_APPROVED)->sum('jumlah_jam');
        $kaliDisetujui = $diBulan($bulan, PengajuanLembur::STATUS_APPROVED)->count();
        $jamMenunggu = (float) $diBulan($bulan, PengajuanLembur::STATUS_PENDING)->sum('jumlah_jam');
        $jamBulanLalu = (float) $diBulan($bulan->copy()->subMonth(), PengajuanLembur::STATUS_APPROVED)->sum('jumlah_jam');
        $namaBulan = $bulan->translatedFormat('F');

        $peringatan = [];
        $hariSama = (clone $milikPegawai)->whereDate('tanggal', $l->tanggal)->where('status', '!=', PengajuanLembur::STATUS_REJECTED)->get();
        if ($hariSama->isNotEmpty()) {
            $peringatan[] = 'Ada pengajuan lembur lain di tanggal yang sama: '
                . $hariSama->map(fn ($h) => substr((string) $h->jam_mulai, 0, 5) . '–' . substr((string) $h->jam_selesai, 0, 5) . ' (' . $h->statusLabel() . ')')->implode(', ') . '.';
        }

        return [
            'judul' => 'Lembur · ' . self::jam($l->jumlah_jam),
            'orang' => self::orang($l->dataPegawai, $unit),
            'detail' => [
                ['Tanggal', ($l->hari ? $l->hari . ', ' : '') . $l->tanggal->translatedFormat('d M Y')],
                ['Jam', substr((string) $l->jam_mulai, 0, 5) . '–' . substr((string) $l->jam_selesai, 0, 5) . ' (' . self::jam($l->jumlah_jam) . ')'],
                ['Uraian tugas', $l->uraian_tugas ?: '-'],
                ['Diajukan', self::waktu($l->created_at)],
            ],
            'angka' => [
                ['label' => "Disetujui {$namaBulan}", 'nilai' => self::jam($jamDisetujui), 'catatan' => "{$kaliDisetujui} kali", 'nada' => null],
                ['label' => 'Total bila disetujui', 'nilai' => self::jam($jamDisetujui + ($l->isPending() ? (float) $l->jumlah_jam : 0)), 'catatan' => $l->isPending() ? '+' . self::jam($l->jumlah_jam) . ' pengajuan ini' : 'sudah diproses', 'nada' => null],
                ['label' => 'Menunggu lainnya', 'nilai' => self::jam($jamMenunggu), 'catatan' => $namaBulan, 'nada' => $jamMenunggu > 0 ? 'waspada' : null],
                ['label' => 'Bulan lalu', 'nilai' => self::jam($jamBulanLalu), 'catatan' => 'disetujui', 'nada' => null],
            ],
            'peringatan' => $peringatan,
            'riwayatJudul' => 'Riwayat lembur terakhir',
            'riwayat' => (clone $milikPegawai)->latest('tanggal')->limit(5)->get()->map(fn (PengajuanLembur $r) => [
                'utama' => $r->tanggal->translatedFormat('d M Y') . ' · ' . substr((string) $r->jam_mulai, 0, 5) . '–' . substr((string) $r->jam_selesai, 0, 5),
                'sub' => self::jam($r->jumlah_jam) . ($r->uraian_tugas ? ' · ' . str($r->uraian_tugas)->limit(40) : ''),
                'status' => $r->statusLabel(), 'kelas' => $r->badgeClass(),
            ])->all(),
            'alur' => self::alurBerjenjang($l, true),
        ];
    }

    public static function formasi(PengajuanFormasi $f): array
    {
        $f->loadMissing('diajukanOleh.dataPegawai.kepegawaian', 'direviewOleh', 'gmPenyetuju', 'ownerPenyetuju');
        $unit = $f->unit_kerja;
        // Hitungan sama dengan bagan unit (penugasan aktif termasuk rangkap, kepala dihitung di unit).
        $bagan = RekrutmenAccess::baganUnit($unit);
        $aktifDi = fn (array $orang) => collect($orang)->where('aktif', true)->count();
        $setahun = now()->subYear()->toDateString();
        $lainUnit = PengajuanFormasi::where('unit_kerja', $unit)->whereKeyNot($f->id);

        $peringatan = [];
        $lowonganAktif = Lowongan::where('unit_kerja', $unit)->where('jabatan', $f->jabatan)
            ->where('status', Lowongan::STATUS_DIPUBLIKASIKAN)
            // Lowongan manual (tanpa formasi) ikut dihitung; "!=" saja akan membuang baris NULL.
            ->where(fn ($q) => $q->whereNull('pengajuan_formasi_id')->orWhere('pengajuan_formasi_id', '!=', $f->id))->get();
        foreach ($lowonganAktif as $low) {
            $peringatan[] = "Masih ada lowongan aktif \"{$low->judul}\" (terisi {$low->jumlah_terisi}/{$low->jumlah_dibutuhkan}).";
        }
        if ($dobel = (clone $lainUnit)->where('jabatan', $f->jabatan)->where('status', PengajuanFormasi::STATUS_PENDING)->count()) {
            $peringatan[] = "Ada {$dobel} pengajuan formasi lain untuk {$f->jabatan} di bagian ini yang masih menunggu.";
        }

        return [
            'judul' => $f->jabatan . ' · ' . $f->jumlahLabel(),
            'orang' => ['nama' => $f->diajukanOleh?->dataPegawai?->nama_lengkap ?? $f->diajukanOleh?->name ?? '-',
                'sub' => 'Pengaju · bagian ' . $unit, 'meta' => $f->diajukanOleh?->roleLabel()],
            'detail' => array_filter([
                ['Kebutuhan', $f->jenisLabel()],
                ['Jabatan', $f->jabatan],
                ['Jumlah', $f->jumlahLabel()],
                ['Dibutuhkan', $f->target_waktu?->translatedFormat('d M Y') ?? '-'],
                ['Persyaratan', collect([$f->min_pendidikan ? 'min. ' . $f->min_pendidikan : null, $f->min_usia ? "usia ≥ {$f->min_usia}" : null])->filter()->implode(', ') ?: '-'],
                $f->kualifikasi ? ['Keahlian', str($f->kualifikasi)->limit(160)->toString()] : null,
                $f->tugas ? ['Tugas', str($f->tugas)->limit(160)->toString()] : null,
                $f->alasan ? ['Keterangan', $f->alasan] : null,
                ['Diajukan', self::waktu($f->created_at)],
            ]),
            'angka' => [
                ['label' => 'Pegawai aktif bagian', 'nilai' => ($aktifDi(collect($bagan['grup'])->flatten(1)->all()) + count($bagan['kepala'])) . ' orang', 'catatan' => $unit, 'nada' => null],
                ['label' => 'Aktif di jabatan ini', 'nilai' => $aktifDi($bagan['grup'][mb_strtoupper($f->jabatan)] ?? []) . ' orang', 'catatan' => $f->jabatan, 'nada' => null],
                ['label' => 'Keluar 12 bulan', 'nilai' => Kepegawaian::where('unit_kerja', $unit)->whereDate('tanggal_keluar', '>=', $setahun)->count() . ' orang', 'catatan' => null, 'nada' => null],
                ['label' => 'Masuk 12 bulan', 'nilai' => Kepegawaian::where('unit_kerja', $unit)->whereDate('tanggal_masuk', '>=', $setahun)->count() . ' orang', 'catatan' => null, 'nada' => null],
            ],
            'peringatan' => $peringatan,
            'riwayatJudul' => 'Formasi terakhir bagian ' . $unit,
            'riwayat' => (clone $lainUnit)->latest()->limit(5)->get()->map(fn (PengajuanFormasi $r) => [
                'utama' => $r->jabatan . ' · ' . $r->jumlah_dibutuhkan . ' orang',
                'sub' => ($r->jenis_kebutuhan ? (PengajuanFormasi::JENIS_LABEL[$r->jenis_kebutuhan] ?? '') . ' · ' : '') . $r->created_at?->translatedFormat('d M Y'),
                'status' => $r->statusLabel(), 'kelas' => $r->badgeClass(),
            ])->all(),
            'alur' => self::alurFormasi($f),
            'formasi' => $f,   // untuk bagan mini (digantikan / posisi baru)
            'bagan' => self::baganFormasi($bagan, $f),
        ];
    }

    /** Bagan unit untuk rincian formasi (hanya baca): yang digantikan & posisi baru (dengan jenis kelamin) ditandai. */
    private static function baganFormasi(array $bagan, PengajuanFormasi $f): array
    {
        // Jenis kelamin posisi baru mengikuti urutan isian: laki-laki, perempuan, lalu bebas.
        $jkSlot = fn (int $i) => $i < $f->jumlah_laki ? 'L' : ($i < $f->jumlah_laki + $f->jumlah_perempuan ? 'P' : 'L/P');
        $baru = collect($f->slot_tambahan ?? [])->map(fn ($ket, $i) => [
            'nama' => ($ket ?: 'Posisi baru #' . ($i + 1)) . ' · ' . $jkSlot($i), 'judul' => 'Posisi baru yang diajukan',
        ])->values()->all();

        return BaganStatis::susun($bagan, $f->jabatan, $f->diganti ?? [], $baru);
    }

    public static function izinKeluar(PengajuanIzinKeluar $i): array
    {
        $i->loadMissing('dataPegawai.kepegawaian', 'kepalaApprovedBy', 'gmApprovedBy', 'approvedBy');
        $unit = PengajuanWorkflow::unitPengajuan($i);
        $bulan = $i->tanggal->copy()->startOfMonth();
        $milikPegawai = PengajuanIzinKeluar::where('data_pegawai_id', $i->data_pegawai_id)->whereKeyNot($i->id);
        $disetujuiBulanIni = (clone $milikPegawai)->where('status', PengajuanIzinKeluar::STATUS_APPROVED)
            ->whereBetween('tanggal', [$bulan->toDateString(), $bulan->copy()->endOfMonth()->toDateString()])->count();
        $namaBulan = $bulan->translatedFormat('F');

        $peringatan = [];
        $hariSama = (clone $milikPegawai)->whereDate('tanggal', $i->tanggal)->where('status', '!=', PengajuanIzinKeluar::STATUS_REJECTED)->get();
        if ($hariSama->isNotEmpty()) {
            $peringatan[] = 'Ada izin keluar lain di tanggal yang sama: ' . $hariSama->map(fn ($h) => $h->jamLabel() . ' (' . $h->statusLabel() . ')')->implode(', ') . '.';
        }
        if ($i->tidakKembali()) {
            $peringatan[] = 'Pegawai tidak kembali lagi ke tempat kerja setelah keluar hari itu.';
        }
        // Rekan satu unit yang juga izin keluar di hari yang sama (menunggu/disetujui).
        $rekan = PengajuanIzinKeluar::with('dataPegawai:id,nama_lengkap')->where('unit_kerja', $unit)
            ->where('data_pegawai_id', '!=', $i->data_pegawai_id)->whereDate('tanggal', $i->tanggal)
            ->where('status', '!=', PengajuanIzinKeluar::STATUS_REJECTED)->get();

        return [
            'judul' => 'Izin keluar · ' . $i->jamLabel(),
            'orang' => self::orang($i->dataPegawai, $unit),
            'detail' => array_filter([
                ['Tanggal', $i->tanggal->translatedFormat('l, d M Y')],
                ['Jam', $i->jamLabel()],
                ['Keperluan', $i->keperluanLabel()],
                ['Alasan', $i->alasan ?: '-'],
                $i->kode_tiket ? ['Kode tiket', $i->kode_tiket] : null,
                ['Diajukan', self::waktu($i->created_at)],
            ]),
            'angka' => [
                ['label' => "Izin keluar {$namaBulan}", 'nilai' => $disetujuiBulanIni . ' kali', 'catatan' => 'disetujui, selain ini', 'nada' => $disetujuiBulanIni >= 4 ? 'waspada' : null],
                ['label' => 'Rekan bagian keluar hari itu', 'nilai' => $rekan->count() . ' orang', 'catatan' => $rekan->pluck('dataPegawai.nama_lengkap')->take(2)->implode(', ') ?: null, 'nada' => $rekan->isNotEmpty() ? 'waspada' : null],
            ],
            'peringatan' => $peringatan,
            'riwayatJudul' => 'Riwayat izin keluar terakhir',
            'riwayat' => (clone $milikPegawai)->latest('tanggal')->limit(5)->get()->map(fn (PengajuanIzinKeluar $r) => [
                'utama' => $r->tanggal->translatedFormat('d M Y') . ' · ' . $r->jamLabel(),
                'sub' => $r->keperluanLabel() . ($r->alasan ? ' · ' . str($r->alasan)->limit(40) : ''),
                'status' => $r->statusLabel(), 'kelas' => $r->badgeClass(),
            ])->all(),
            'alur' => self::alurBerjenjang($i, false),
        ];
    }

    /** Alur cuti/lembur/izin keluar: diajukan → tahap rute (Kepala Bagian / GM / Kepala SDM) lengkap dengan status tiap tahap. */
    private static function alurBerjenjang(PengajuanCuti|PengajuanLembur|PengajuanIzinKeluar $p, bool $lembur): array
    {
        $langkah = [['label' => 'Diajukan', 'oleh' => $p->dataPegawai?->nama_lengkap, 'waktu' => self::waktu($p->created_at), 'catatan' => null, 'status' => 'selesai']];

        // Tahap yang seharusnya dilalui + tahap yang benar-benar tercatat, diurutkan baku.
        $pengaju = PengajuanWorkflow::submitterUser($p);
        $rute = collect($pengaju ? PengajuanWorkflow::rute($pengaju, PengajuanWorkflow::unitPengajuan($p), $lembur) : [])->keyBy('level');
        $level = collect(self::URUTAN_TAHAP)->filter(fn ($lv) => $rute->has($lv) || $p->current_level === $lv
            || ($lv === PengajuanWorkflow::LEVEL_KEPALA_DIVISI && $p->kepala_approved_by)
            || ($lv === PengajuanWorkflow::LEVEL_GM && $p->gm_approved_by))->values();
        if ($level->isEmpty()) {
            $level = collect([PengajuanWorkflow::LEVEL_KEPALA_SDM]);
        }

        $sudah = [
            PengajuanWorkflow::LEVEL_KEPALA_DIVISI => $p->kepala_approved_by ? [$p->kepalaApprovedBy?->name, $p->kepala_approved_at] : null,
            PengajuanWorkflow::LEVEL_GM => $p->gm_approved_by ? [$p->gmApprovedBy?->name . ($p->gm_pengganti ? ' (GM pengganti)' : ''), $p->gm_approved_at] : null,
        ];
        $ditolakDicatat = false;
        foreach ($level as $i => $lv) {
            $akhir = $i === $level->count() - 1;
            $selesai = $sudah[$lv] ?? null;
            if (! $selesai && $akhir && $p->status === $p::STATUS_APPROVED) {
                $selesai = [$p->approvedBy?->name, $p->approved_at];
            }
            $calon = $rute->get($lv)['approvers'] ?? collect();
            $calonTeks = $calon->isEmpty() ? null : $calon->take(2)->pluck('name')->implode(', ') . ($calon->count() > 2 ? ' +' . ($calon->count() - 2) : '');

            $langkah[] = match (true) {
                (bool) $selesai => ['label' => PengajuanWorkflow::levelLabel($lv), 'oleh' => $selesai[0], 'waktu' => self::waktu($selesai[1]), 'catatan' => null, 'status' => 'selesai'],
                $p->status === $p::STATUS_REJECTED && ! $ditolakDicatat && ($ditolakDicatat = true) => ['label' => PengajuanWorkflow::levelLabel($lv), 'oleh' => $p->approvedBy?->name, 'waktu' => self::waktu($p->approved_at), 'catatan' => $p->catatan_admin, 'status' => 'ditolak'],
                $p->isPending() && $p->current_level === $lv => ['label' => PengajuanWorkflow::levelLabel($lv), 'oleh' => $calonTeks, 'waktu' => null, 'catatan' => 'sedang menunggu', 'status' => 'berjalan'],
                $p->isPending() => ['label' => PengajuanWorkflow::levelLabel($lv), 'oleh' => $calonTeks, 'waktu' => null, 'catatan' => null, 'status' => 'menunggu'],
                default => ['label' => PengajuanWorkflow::levelLabel($lv), 'oleh' => null, 'waktu' => null, 'catatan' => 'dilewati', 'status' => 'lewati'],
            };
        }

        return $langkah;
    }

    /** Alur formasi: penambahan bertahap GM unit → Owner; replacement cukup Kepala SDM/GM (unit langsung Pembina: Pembina). */
    private static function alurFormasi(PengajuanFormasi $f): array
    {
        $langkah = [['label' => 'Diajukan', 'oleh' => $f->diajukanOleh?->name, 'waktu' => self::waktu($f->created_at), 'catatan' => null, 'status' => 'selesai']];
        $ditolak = $f->status === PengajuanFormasi::STATUS_REJECTED;
        $tahap = RekrutmenAccess::tahapPersetujuan($f);

        if ($f->butuhDuaPersetujuan()) {
            $daftar = array_filter([
                'gm' => RekrutmenAccess::perluGm($f) ? ['GM bagian', $f->gmPenyetuju, $f->gm_disetujui_pada, RekrutmenAccess::gmFormasi($f)->pluck('name')] : null,
                'owner' => ['Owner', $f->ownerPenyetuju, $f->owner_disetujui_pada, User::where('role', User::ROLE_PEMBINA)->pluck('name')],
            ]);
        } else {
            $pembina = in_array($f->unit_kerja, config('kepegawaian.unit_langsung_pembina', []), true);
            $daftar = ['akhir' => [$pembina ? 'Pembina' : 'Kepala SDM / GM', $f->status === PengajuanFormasi::STATUS_APPROVED ? $f->direviewOleh : null, $f->direview_pada, collect()]];
            $tahap = $f->isPending() ? 'akhir' : null;
        }

        $ditolakDicatat = false;
        foreach ($daftar as $kode => [$label, $oleh, $pada, $calon]) {
            $langkah[] = match (true) {
                (bool) $oleh => ['label' => $label, 'oleh' => $oleh->name, 'waktu' => self::waktu($pada), 'catatan' => null, 'status' => 'selesai'],
                $ditolak && ! $ditolakDicatat && ($ditolakDicatat = true) => ['label' => $label, 'oleh' => $f->direviewOleh?->name, 'waktu' => self::waktu($f->direview_pada), 'catatan' => $f->catatan_admin, 'status' => 'ditolak'],
                $f->isPending() && $tahap === $kode => ['label' => $label, 'oleh' => $calon->take(2)->implode(', ') ?: null, 'waktu' => null, 'catatan' => 'sedang menunggu', 'status' => 'berjalan'],
                $f->isPending() => ['label' => $label, 'oleh' => $calon->take(2)->implode(', ') ?: null, 'waktu' => null, 'catatan' => null, 'status' => 'menunggu'],
                default => ['label' => $label, 'oleh' => null, 'waktu' => null, 'catatan' => $f->status === PengajuanFormasi::STATUS_DIBATALKAN ? 'dibatalkan' : 'dilewati', 'status' => 'lewati'],
            };
        }

        return $langkah;
    }

    /** Identitas singkat pegawai: nama, jabatan · unit, NIP · status kerja · masa kerja. */
    private static function orang(?DataPegawai $p, ?string $unit): array
    {
        $k = $p?->kepegawaian;
        $masa = $k?->tanggal_masuk ? $k->tanggal_masuk->diff(now()) : null;

        return [
            'nama' => $p?->nama_lengkap ?? '-',
            'sub' => collect([$k?->jabatan, $unit ?? $k?->unit_kerja])->filter()->implode(' · '),
            'meta' => collect([
                $p?->nip ? 'NIP ' . $p->nip : null,
                $k?->status_kerja,
                $masa ? 'masa kerja ' . ($masa->y ? $masa->y . ' th ' : '') . $masa->m . ' bl' : null,
            ])->filter()->implode(' · '),
        ];
    }

    private static function rentang(CarbonInterface $a, CarbonInterface $b): string
    {
        return $a->isSameDay($b) ? $a->translatedFormat('d M Y')
            : ($a->isSameMonth($b) ? $a->format('d') : $a->translatedFormat($a->isSameYear($b) ? 'd M' : 'd M Y')) . '–' . $b->translatedFormat('d M Y');
    }

    private static function waktu(?CarbonInterface $t): ?string
    {
        return $t?->translatedFormat('d M Y H:i');
    }

    private static function jam(float|string|null $n): string
    {
        return rtrim(rtrim(number_format((float) $n, 1, ',', ''), '0'), ',') . ' jam';
    }
}
