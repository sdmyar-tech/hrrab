<?php

namespace App\Support;

use App\Models\DataPegawai;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

/**
 * Pengelompokan pegawai yang dipakai dashboard (grafik komposisi) DAN filter Data Pegawai,
 * supaya angka di grafik selalu sama dengan jumlah baris saat grafik diklik.
 */
class KelompokPegawai
{
    public const MASA = ['< 1 th', '1–3 th', '3–5 th', '5–10 th', '≥ 10 th', 'Belum diisi'];

    public const USIA = ['< 25', '25–34', '35–44', '45–54', '≥ 55', 'Belum diisi'];

    public const PENDIDIKAN = ['SD', 'SMP', 'SMA/SMK', 'Diploma', 'S1/D4', 'S2', 'S3', 'Profesi', 'Belum diisi'];

    public const STATUS_KERJA = ['Tetap', 'Kontrak', 'Harian Lepas', 'Probation', 'Magang', 'Belum diisi'];

    public const GENDER = ['Laki-laki', 'Perempuan', 'Belum diisi'];

    /** Label filter untuk ditampilkan sebagai chip di Data Pegawai. */
    public const LABEL = [
        'status_kerja' => 'Status kerja', 'jk' => 'Jenis kelamin', 'masa' => 'Masa kerja', 'usia' => 'Usia',
        'pendidikan' => 'Pendidikan', 'lokasi' => 'Area kerja', 'masuk' => 'Masuk bulan', 'keluar' => 'Keluar bulan', 'kontrak' => 'Kontrak berakhir',
    ];

    public static function masa(DataPegawai $p): string
    {
        $masuk = $p->kepegawaian?->tanggal_masuk;

        return match (true) {
            ! $masuk => 'Belum diisi',
            ($t = $masuk->diffInYears(now())) < 1 => '< 1 th',
            $t < 3 => '1–3 th',
            $t < 5 => '3–5 th',
            $t < 10 => '5–10 th',
            default => '≥ 10 th',
        };
    }

    public static function usia(DataPegawai $p): string
    {
        return match (true) {
            ! $p->tanggal_lahir => 'Belum diisi',
            ($u = $p->tanggal_lahir->age) < 25 => '< 25',
            $u < 35 => '25–34',
            $u < 45 => '35–44',
            $u < 55 => '45–54',
            default => '≥ 55',
        };
    }

    public static function jenjang(?string $teks): string
    {
        $t = mb_strtoupper(trim((string) $teks));

        return match (true) {
            $t === '' => 'Belum diisi',
            (bool) preg_match('/^(S3|STRATA 3|STRATA TIGA|DOKTOR)/', $t) => 'S3',
            (bool) preg_match('/^(S2|STRATA 2|STRATA DUA|MAGISTER)/', $t) => 'S2',
            (bool) preg_match('/^PROFESI/', $t) => 'Profesi',
            (bool) preg_match('/^(S1|D4|STRATA 1|STRATA SATU|SARJANA)|UNIVERSITAS|INSTITUT/', $t) => 'S1/D4',
            (bool) preg_match('/^D[123]\b|^DIPLOMA/', $t) => 'Diploma',
            (bool) preg_match('/^(SMA|SMK|MA\b|MAN|SLTA|STM|SMEA|PAKET C)/', $t) => 'SMA/SMK',
            (bool) preg_match('/^(SMP|MTS|SLTP|PAKET B)/', $t) => 'SMP',
            (bool) preg_match('/^(SD|MI\b|PAKET A)/', $t) => 'SD',
            default => 'Belum diisi',
        };
    }

    /**
     * Terapkan filter dari dashboard ke query Data Pegawai. Mengembalikan filter yang benar-benar dipakai (untuk chip).
     *
     * @return array<string, string>
     */
    public static function terapkan(Builder $query, array $input): array
    {
        $pakai = [];
        $kepeg = fn (callable $f) => $query->whereHas('kepegawaian', $f);

        if (in_array($v = $input['status_kerja'] ?? null, self::STATUS_KERJA, true)) {
            $v === 'Belum diisi'
                ? $query->where(fn ($q) => $q->whereDoesntHave('kepegawaian')->orWhereHas('kepegawaian', fn ($k) => $k->whereNull('status_kerja')->orWhere('status_kerja', '')))
                : $kepeg(fn ($k) => $k->where('status_kerja', $v));
            $pakai['status_kerja'] = $v;
        }
        if (in_array($v = $input['jk'] ?? null, self::GENDER, true)) {
            $v === 'Belum diisi' ? $query->where(fn ($q) => $q->whereNull('jenis_kelamin')->orWhere('jenis_kelamin', '')) : $query->where('jenis_kelamin', $v);
            $pakai['jk'] = $v;
        }
        if (is_string($v = $input['lokasi'] ?? null) && $v !== '') {
            $v === 'Belum diisi'
                ? $query->where(fn ($q) => $q->whereDoesntHave('kepegawaian')->orWhereHas('kepegawaian', fn ($k) => $k->whereNull('lokasi_kerja')->orWhere('lokasi_kerja', '')))
                : $kepeg(fn ($k) => $k->where('lokasi_kerja', $v));
            $pakai['lokasi'] = $v;
        }
        foreach (['masuk' => 'tanggal_masuk', 'keluar' => 'tanggal_keluar'] as $kunci => $kolom) {
            if (is_string($v = $input[$kunci] ?? null) && preg_match('/^(\d{4})-(0[1-9]|1[0-2])$/', $v, $m)) {
                $kepeg(fn ($k) => $k->whereYear($kolom, (int) $m[1])->whereMonth($kolom, (int) $m[2]));
                $pakai[$kunci] = Carbon::createFromFormat('Y-m', $v)->translatedFormat('F Y');
            }
        }
        if (($v = (int) ($input['kontrak'] ?? 0)) > 0 && $v <= 366) {
            $kepeg(fn ($k) => $k->whereBetween('tanggal_habis_kontrak', [now()->startOfDay(), now()->addDays($v)->endOfDay()]));
            $pakai['kontrak'] = "≤ {$v} hari";
        }

        // Kelompok hitungan (masa kerja, usia, pendidikan): dicocokkan dengan fungsi yang sama seperti grafik dashboard.
        $kelompok = array_filter([
            'masa' => in_array($input['masa'] ?? null, self::MASA, true) ? $input['masa'] : null,
            'usia' => in_array($input['usia'] ?? null, self::USIA, true) ? $input['usia'] : null,
            'pendidikan' => in_array($input['pendidikan'] ?? null, self::PENDIDIKAN, true) ? $input['pendidikan'] : null,
        ]);
        if ($kelompok) {
            $ids = DataPegawai::with('kepegawaian:id,data_pegawai_id,tanggal_masuk')->get(['id', 'tanggal_lahir', 'pendidikan_terakhir'])
                ->filter(fn (DataPegawai $p) => (! isset($kelompok['masa']) || self::masa($p) === $kelompok['masa'])
                    && (! isset($kelompok['usia']) || self::usia($p) === $kelompok['usia'])
                    && (! isset($kelompok['pendidikan']) || self::jenjang($p->pendidikan_terakhir) === $kelompok['pendidikan']))
                ->pluck('id');
            $query->whereIn('data_pegawais.id', $ids);
            $pakai += $kelompok;
        }

        return $pakai;
    }
}
