<?php

namespace App\Support\Import;

/** Merapikan format tulisan data pegawai hasil form (kapitalisasi, gelar, nomor, alamat, dsb). */
class FormatTeks
{
    private const GELAR_DEPAN = [
        'drs' => 'Drs.', 'dra' => 'Dra.', 'dr' => 'Dr.', 'ir' => 'Ir.', 'apt' => 'apt.', 'ns' => 'Ns.',
        'h' => 'H.', 'hj' => 'Hj.', 'prof' => 'Prof.',
    ];

    private const GELAR_BELAKANG = [
        'ssi' => 'S.Si', 'spd' => 'S.Pd', 'spdi' => 'S.Pd.I', 'sfarm' => 'S.Farm', 'sfar' => 'S.Farm',
        'skom' => 'S.Kom', 'sikom' => 'S.I.Kom', 'spsi' => 'S.Psi', 'se' => 'S.E', 'st' => 'S.T', 'sh' => 'S.H',
        'sag' => 'S.Ag', 'sak' => 'S.Ak', 'skep' => 'S.Kep', 'ssos' => 'S.Sos', 'sm' => 'S.M', 'shi' => 'S.H.I',
        'sthi' => 'S.Th.I', 'sked' => 'S.Ked', 'sgz' => 'S.Gz', 'sap' => 'S.AP', 'ssi.' => 'S.Si', 'sei' => 'S.E.I',
        'mm' => 'M.M', 'mpd' => 'M.Pd', 'mag' => 'M.Ag', 'mkom' => 'M.Kom', 'msi' => 'M.Si', 'map' => 'M.AP',
        'mak' => 'M.Ak', 'mh' => 'M.H', 'mpdi' => 'M.Pd.I', 'me' => 'M.E', 'mkes' => 'M.Kes',
        'lc' => 'Lc', 'ak' => 'Ak', 'gr' => 'Gr', 'cht' => 'C.Ht',
        'amd' => 'A.Md', 'amdkep' => 'A.Md.Kep', 'amdak' => 'A.Md.Ak', 'amdkeb' => 'A.Md.Keb', 'amdfarm' => 'A.Md.Farm',
        'amk' => 'AMK', 'amak' => 'AMAK', 'siq' => 'S.IQ',
        'skm' => 'S.KM', 'ss' => 'S.S', 'sst' => 'S.ST', 'sud' => 'S.Ud', 'sip' => 'S.IP', 'strkep' => 'S.Tr.Kep',
        'ners' => 'Ners', 'sgz' => 'S.Gz', 'mkm' => 'M.KM', 'spt' => 'S.Pt', 'sp' => 'S.P', 'phd' => 'Ph.D',
    ];

    private const KATA_KECIL = ['bin', 'binti', 'dan', 'di', 'ke', 'dari'];

    public static function kosong(?string $nilai): bool
    {
        $v = mb_strtolower(trim((string) $nilai));

        return in_array($v, ['', '-', '--', '0', '0.0', 'tidak ada', 'tidak', 'belum ada', 'tdk ada', 'null', 'n/a', 'nan'], true);
    }

    public static function digit(?string $nilai): array
    {
        if (self::kosong($nilai)) {
            return [null, false];
        }

        $v = trim((string) $nilai);
        if (preg_match('/^\d+(\.\d+)?E\+?\d+$/i', $v) || preg_match('/^\d+\.0+$/', $v)) {
            $digit = sprintf('%.0f', (float) $v);

            return [$digit, strlen($digit) > 15];
        }

        $digit = preg_replace('/\D/', '', $v);

        return [$digit === '' ? null : $digit, false];
    }

    private const SINGKATAN = [
        'UIN', 'UIR', 'UNRI', 'UNP', 'UMRI', 'IAIN', 'STAI', 'STIKES', 'STIE', 'STKIP', 'STMIK', 'UT', 'UNAND', 'UNIMED',
        'SD', 'SMP', 'SMA', 'SMK', 'MAN', 'MTS', 'MI', 'TK', 'SMAN', 'SMKN', 'SMPN', 'SDN', 'PGSD', 'PAI', 'IPA', 'IPS',
        'PAUD', 'PGMI', 'TKJ', 'RT', 'RW', 'KM', 'PT', 'CV', 'TIC', 'STCH', 'TGH', 'FK', 'AIS', 'UKS', 'SDM', 'GA', 'II', 'III', 'IV',
    ];

    public static function judul(?string $nilai): ?string
    {
        if (self::kosong($nilai)) {
            return null;
        }

        $teks = preg_replace('/(\p{L}{2,})\.(?=\p{L})/u', '$1. ', self::rapikanSpasi($nilai));
        $teks = mb_convert_case(mb_strtolower($teks), MB_CASE_TITLE, 'UTF-8');
        $kata = explode(' ', $teks);
        foreach ($kata as $i => $k) {
            $polos = mb_strtoupper(preg_replace('/[^\p{L}]/u', '', $k));
            if (in_array($polos, self::SINGKATAN, true)) {
                $kata[$i] = mb_strtoupper($k);
            } elseif ($i > 0 && in_array(mb_strtolower($k), self::KATA_KECIL, true)) {
                $kata[$i] = mb_strtolower($k);
            }
        }

        return implode(' ', $kata);
    }

    /**
     * @return array{0: ?string, 1: bool} [nama, perluDicek]
     */
    public static function nama(?string $nilai): array
    {
        if (self::kosong($nilai)) {
            return [null, false];
        }

        $sisa = self::rapikanSpasi(preg_replace('/\([^)]*\)/', '', (string) $nilai));
        $depan = [];

        while (preg_match('/^([A-Za-z]{1,4})\.\s*(.+)$/u', $sisa, $m) && isset(self::GELAR_DEPAN[mb_strtolower($m[1])])) {
            $depan[] = self::GELAR_DEPAN[mb_strtolower($m[1])];
            $sisa = $m[2];
        }

        $bagian = array_map('trim', explode(',', $sisa));
        $inti = rtrim(array_shift($bagian), ' .');
        $belakang = [];

        $kata = explode(' ', $inti);
        while (count($kata) > 1) {
            $ketemu = false;
            for ($n = min(3, count($kata) - 1); $n >= 1; $n--) {
                if ($g = self::gelarBelakang(implode('', array_slice($kata, -$n)))) {
                    array_unshift($belakang, ...$g);
                    array_splice($kata, -$n);
                    $ketemu = true;
                    break;
                }
            }
            if (! $ketemu) {
                break;
            }
        }
        $inti = implode(' ', $kata);

        $perluDicek = false;
        foreach ($bagian as $segmen) {
            if ($g = self::gelarBelakang(str_replace(' ', '', $segmen), true)) {
                array_push($belakang, ...$g);
                continue;
            }
            foreach (preg_split('/\s+/', $segmen) as $sub) {
                if ($sub === '') {
                    continue;
                }
                if ($g = self::gelarBelakang($sub, true)) {
                    array_push($belakang, ...$g);
                } else {
                    $belakang[] = $sub;
                    $perluDicek = true;
                }
            }
        }

        $namaInti = self::judul(str_replace('.', '. ', $inti));
        $namaInti = preg_replace('/\b(\p{Lu})\b(?!\.)/u', '$1.', (string) $namaInti);
        $namaInti = self::rapikanSpasi((string) $namaInti);
        if (preg_match('/\.\s*\p{Ll}/u', $namaInti)) {
            $perluDicek = true;
        }

        $hasil = trim(implode(' ', $depan) . ' ' . $namaInti);
        if ($belakang) {
            $hasil .= ', ' . implode(', ', array_unique($belakang));
        }

        return [$hasil, $perluDicek];
    }

    /**
     * @return list<string>|null
     */
    private static function gelarBelakang(string $token, bool $longgar = false): ?array
    {
        $kunci = mb_strtolower(preg_replace('/[^A-Za-z]/', '', $token));
        $bentukGelar = $longgar || str_contains($token, '.') || mb_strlen($kunci) <= 3 || preg_match('/^[A-Z][a-z]?[A-Z]/', $token);

        if (! $bentukGelar || $kunci === '') {
            return null;
        }
        if (isset(self::GELAR_BELAKANG[$kunci])) {
            return [self::GELAR_BELAKANG[$kunci]];
        }

        $bagian = array_values(array_filter(explode('.', $token), fn ($b) => trim($b) !== ''));
        $hasil = [];
        for ($i = 0; $i < count($bagian);) {
            $cocok = false;
            for ($j = count($bagian); $j > $i; $j--) {
                $k = mb_strtolower(preg_replace('/[^A-Za-z]/', '', implode('', array_slice($bagian, $i, $j - $i))));
                if (isset(self::GELAR_BELAKANG[$k])) {
                    $hasil[] = self::GELAR_BELAKANG[$k];
                    $i = $j;
                    $cocok = true;
                    break;
                }
            }
            if (! $cocok) {
                return null;
            }
        }

        return count($hasil) > 1 ? $hasil : null;
    }

    public static function email(?string $nilai): ?string
    {
        $v = mb_strtolower(trim((string) $nilai));

        return filter_var($v, FILTER_VALIDATE_EMAIL) ? $v : null;
    }

    public static function hp(?string $nilai): ?string
    {
        if (self::kosong($nilai)) {
            return null;
        }

        $bagian = preg_split('/[\/,;]|\batau\b|\bdan\b/i', (string) $nilai);
        $nilai = collect($bagian)->first(fn ($b) => preg_match('/\d{6,}/', preg_replace('/\D/', '', $b))) ?? $nilai;

        [$d] = self::digit($nilai);
        if (! $d) {
            return null;
        }

        if (strlen($d) > 14 && preg_match('/^(0\d{9,12}?)(08\d{8,11})$/', $d, $m)) {
            $d = $m[1];
        }
        if (str_starts_with($d, '62')) {
            $d = '0' . substr($d, 2);
        } elseif (str_starts_with($d, '8')) {
            $d = '0' . $d;
        }

        return strlen($d) >= 9 && strlen($d) <= 15 ? $d : null;
    }

    public static function alamat(?string $nilai): ?string
    {
        $v = self::judul($nilai);
        if (! $v) {
            return null;
        }

        $ganti = [
            '/\bJl\b\.?|\bJln\b\.?/u' => 'Jl.', '/\bGg\b\.?/u' => 'Gg.', '/\bNo\b\.?/u' => 'No.', '/\bKec\b\.?/u' => 'Kec.',
            '/\bKab\b\.?/u' => 'Kab.', '/\bKel\b\.?/u' => 'Kel.', '/\bPerum\b\.?/u' => 'Perum.', '/\bRt\b\.?/u' => 'RT',
            '/\bRw\b\.?/u' => 'RW', '/\bKm\b\.?/u' => 'KM', '/\bDs\b\.?/u' => 'Ds.',
        ];
        $v = preg_replace(array_keys($ganti), array_values($ganti), $v);
        $v = preg_replace('/\s*,\s*/', ', ', $v);
        $v = preg_replace('/\b(Jl|Gg|No|Kec|Kab|Kel|Perum|Ds)\.(?=[\p{L}\d])/u', '$1. ', $v);
        $v = preg_replace('/\.\./', '.', $v);

        return self::rapikanSpasi(rtrim($v, ' ,.'));
    }

    public static function jenisKelamin(?string $nilai): ?string
    {
        $v = mb_strtoupper((string) $nilai);

        return match (true) {
            str_contains($v, 'LAKI') => 'Laki-laki',
            str_contains($v, 'PEREMPUAN') || str_contains($v, 'WANITA') => 'Perempuan',
            default => null,
        };
    }

    public static function agama(?string $nilai): ?string
    {
        $v = mb_strtoupper(trim((string) $nilai));

        return match ($v) {
            '' => null,
            'PROTESTAN', 'KRISTEN PROTESTAN', 'KRISTEN' => 'Kristen',
            default => self::judul($v),
        };
    }

    /** @return array{0: ?string, 1: bool} [golongan darah, diasumsikan] */
    public static function golonganDarah(?string $nilai): array
    {
        $v = mb_strtoupper(str_replace(' ', '', (string) $nilai));
        if ($v === '' || str_contains($v, 'BELUM')) {
            return ['Belum Diketahui', false];
        }
        if (in_array($v, ['O+', 'A+', 'B+', 'AB+', 'O-', 'A-', 'B-', 'AB-', 'O', 'A', 'B', 'AB'], true)) {
            return [$v, false];
        }

        return ['Belum Diketahui', true];
    }

    public static function statusPernikahan(?string $nilai): ?string
    {
        $v = mb_strtoupper(trim((string) $nilai));

        return match (true) {
            $v === '' => null,
            str_contains($v, 'BELUM') => 'Belum Menikah',
            str_contains($v, 'MENIKAH') || str_contains($v, 'KAWIN') => 'Menikah',
            in_array($v, ['JANDA', 'DUDA', 'CERAI', 'CERAI HIDUP'], true) => 'Cerai Hidup',
            str_contains($v, 'MATI') => 'Cerai Mati',
            default => null,
        };
    }

    public static function hubunganKerabat(?string $nilai): ?string
    {
        if (self::kosong($nilai)) {
            return null;
        }
        $v = mb_strtoupper(preg_replace('/\s*\/\s*/', '/', self::rapikanSpasi((string) $nilai)));

        return match (true) {
            str_contains($v, 'ISTRI') || $v === 'SUAMI' || str_contains($v, 'CALON ISTRI') => 'Istri/Suami',
            (bool) preg_match('/^(AYAH|IBU|ORANG ?TUA|BAPAK)\b/', $v) => 'Ayah/Ibu',
            (bool) preg_match('/^(ADIK|KAKAK|ABANG)(\/KAKAK)?( KANDUNG)?$/', $v) => 'Adik/Kakak Kandung',
            $v === 'ANAK' => 'Anak',
            default => self::judul(str_replace('SODARA', 'SAUDARA', $v)),
        };
    }

    public static function pendidikan(?string $nilai): ?string
    {
        if (self::kosong($nilai)) {
            return null;
        }
        $v = mb_strtoupper(self::rapikanSpasi((string) $nilai));
        $v = preg_replace('/\s*\(\s*([^)]*)\s*\)/', '', $v);

        $jenjang = [
            '/^(STRATA\s*1\s*\/\s*S1|SARJANA\s*S1|SARJANA|S\s*[-.]?\s*1)\b/' => 'S1',
            '/^(STRATA\s*2|MAGISTER|S\s*[-.]?\s*2)\b/' => 'S2',
            '/^(STRATA\s*3|DOKTOR|S\s*[-.]?\s*3)\b/' => 'S3',
            '/^(D\s*[-.]?\s*III|D\s*[-.]?\s*3|DIPLOMA\s*3)\b/' => 'D3',
            '/^(D\s*[-.]?\s*IV|D\s*[-.]?\s*4)\b/' => 'D4',
            '/^(D\s*[-.]?\s*1)\b/' => 'D1',
            '/^(SLTA|SMA|SEKOLAH MENENGAH ATAS)\b(\s*\/?\s*SEDERAJAT)?/' => 'SMA',
            '/^(MAN|MA)\b/' => 'MAN',
            '/^(SMK|STM|SMEA)\b(\s*\/?\s*SEDERAJAT)?/' => 'SMK',
            '/^(SLTP|SMP|MTS)\b(\s*\/?\s*SEDERAJAT)?/' => 'SMP',
            '/^(SD|SEKOLAH DASAR|MI)\b(\s*\/?\s*SEDERAJAT)?/' => 'SD',
            '/^PROFESI\s+NERS|^NERS\b/' => 'Profesi Ners',
            '/^(S1\s*-\s*)?(PROFESI\s+)?APOTEKER\b/' => 'Profesi Apoteker',
            '/^(PONPES|PONDOK PESANTREN|ALUMNI? PONDOK|PONDOK)\b/' => 'Pondok Pesantren',
        ];

        foreach ($jenjang as $pola => $label) {
            if (preg_match($pola, $v, $m)) {
                $jurusan = trim(preg_replace($pola, '', $v, 1), " -/,.");

                return self::rapikanSpasi($label . ($jurusan !== '' ? ' ' . self::judul($jurusan) : ''));
            }
        }

        return self::judul($v);
    }

    public static function jabatan(?string $nilai): ?string
    {
        if (self::kosong($nilai)) {
            return null;
        }
        $v = mb_strtoupper(self::rapikanSpasi((string) $nilai));
        $ganti = [
            "/AL[ -]?QUR'? ?AN/" => 'AL-QURAN', '/^STAF\b/' => 'STAFF', '/\bKORDINATOR\b/' => 'KOORDINATOR',
            '/\bKOOR\.\s*/' => 'KOORDINATOR ', '/\s*&\s*/' => ' & ', '/\s+DAN\s+(?=MAINTENANCE|LAUNDRY)/' => ' & ',
            '/^STAFF GA \(GENERAL AFFAIR\)$/' => 'STAFF GA', '/^GA MASSENGER$/' => 'GA MESSENGER',
            '/^MUSYRIF ASRAMA$/' => 'MUSYRIF', '/\bKONTRUKSI\b/' => 'KONSTRUKSI',
        ];

        return self::rapikanSpasi(preg_replace(array_keys($ganti), array_values($ganti), $v));
    }

    public static function rapikanSpasi(string $teks): string
    {
        return trim(preg_replace('/\s+/u', ' ', $teks));
    }
}
