<?php

namespace App\Console\Commands;

use App\Models\DataPegawai;
use App\Models\LokasiKerja;
use App\Models\User;
use App\Support\Import\FormatTeks as F;
use App\Support\Import\XlsxReader;
use App\Support\PenugasanService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ImportPegawai extends Command
{
    protected $signature = 'pegawai:import
        {file : Path file .xlsx}
        {--jalankan : Benar-benar menulis ke database (tanpa ini hanya pemeriksaan)}
        {--bersihkan-rekrutmen : Ikut hapus data rekrutmen uji coba (lowongan, lamaran, pelamar, formasi)}';

    protected $description = 'Import data pegawai & akun dari Excel Pengkinian Data (default: pemeriksaan saja)';

    private array $kol;

    public function handle(): int
    {
        $this->kol = config('import_pegawai.kolom');
        $file = $this->argument('file');

        if (! is_file($file)) {
            $this->error("File tidak ditemukan: {$file}");

            return self::FAILURE;
        }

        $sheets = XlsxReader::baca($file);
        $pegawai = $this->siapkanPegawai($sheets);
        $akun = $this->pilihAkun($pegawai);

        $this->tulisLaporan($pegawai, $akun, dirname($file));
        $this->ringkasan($pegawai, $akun);

        if (! $this->option('jalankan')) {
            $this->newLine();
            $this->warn('Mode PEMERIKSAAN: database tidak diubah. Jalankan dengan --jalankan untuk mengimpor.');

            return self::SUCCESS;
        }

        $this->jalankan($pegawai, $akun);

        return self::SUCCESS;
    }

    private function siapkanPegawai(array $sheets): Collection
    {
        $keluar = $this->daftarKeluar($sheets[config('import_pegawai.sheet_keluar')] ?? []);
        $hasil = collect();

        $utama = config('import_pegawai.sheet');
        foreach ($sheets[$utama] as $no => $row) {
            if ($no < 2 || F::kosong($row[$this->kol['nama']] ?? null)) {
                continue;
            }
            $hasil->push([...$this->rapikan($no, $row, $keluar), 'sumber' => "{$utama}:{$no}"]);
        }

        $namaAda = $hasil->map(fn ($r) => $this->kunciNama($r['nama_asli']))->flip();
        $nikAda = $hasil->pluck('pegawai.nik')->filter()->flip();
        foreach (config('import_pegawai.sheet_pelengkap', []) as $i => $sheet) {
            foreach ($sheets[$sheet] ?? [] as $no => $row) {
                if ($no < 2 || F::kosong($row[$this->kol['nama']] ?? null)) {
                    continue;
                }
                $r = $this->rapikan($no, $row, $keluar);
                if (isset($namaAda[$this->kunciNama($r['nama_asli'])]) || ($r['pegawai']['nik'] && isset($nikAda[$r['pegawai']['nik']]))) {
                    continue;
                }
                $r['baris'] = ($i + 1) * 100000 + $no;
                $r['sumber'] = "{$sheet}:{$no}";
                $r['catatan'][] = "hanya ada di sheet {$sheet}";
                $hasil->push($r);
            }
        }

        $kepalaManual = collect(config('import_pegawai.kepala_manual', []))->filter(fn ($m) => isset($m['nama_excel']));

        return $this->bersihkanDuplikat($hasil)->map(function ($r) use ($kepalaManual) {
            foreach ($kepalaManual as $unit => $m) {
                if (str_contains($this->kunciNama($r['nama_asli']), mb_strtoupper($m['nama_excel']))) {
                    $r['kepegawaian']['unit_kerja'] = $unit;
                    $r['kepegawaian']['jabatan'] = $m['jabatan'] ?? $r['kepegawaian']['jabatan'];
                    $r['kepegawaian']['jabatan_tambahan'] = $m['jabatan_tambahan'] ?? $r['kepegawaian']['jabatan_tambahan'];
                    $r['pegawai']['email'] ??= $m['email'] ?? null;
                    $r['catatan'] = array_values(array_filter($r['catatan'], fn ($c) => ! str_contains($c, 'belum ditentukan')));
                }
            }

            return $r;
        });
    }

    private function daftarKeluar(array $rows): array
    {
        $daftar = [];
        foreach ($rows as $no => $r) {
            if ($no >= 6 && ! F::kosong($r['B'] ?? null)) {
                $daftar[$this->kunciNama($r['B'])] = ['tanggal' => $r['F'] ?? null, 'alasan' => F::judul($r['G'] ?? null)];
            }
        }

        return $daftar;
    }

    private function kunciNama(?string $nama): string
    {
        [$rapi] = F::nama($nama);
        $tanpaGelar = preg_replace('/^((Drs?|Dra|Ir|apt|Ns|H|Hj|Prof)\.\s*)+/i', '', explode(',', (string) $rapi)[0]);

        return mb_strtoupper(preg_replace('/[^\p{L} ]/u', '', $tanpaGelar));
    }

    private function rapikan(int $no, array $r, array $keluar): array
    {
        $k = $this->kol;
        $v = fn (string $kolom) => $r[$k[$kolom]] ?? null;
        $catatan = [];

        [$nama, $cekNama] = F::nama(config('import_pegawai.koreksi_nama')[$v('nama')] ?? $v('nama'));
        if ($cekNama) {
            $catatan[] = 'format nama/gelar perlu dicek';
        }

        [$nik, $nikPresisi] = F::digit($v('nik'));
        if ($nik !== null && ! preg_match('/^\d{16}$/', $nik)) {
            $catatan[] = "NIK tidak valid ({$v('nik')}), dikosongkan";
            $nik = null;
        } elseif ($nikPresisi) {
            $catatan[] = 'NIK tersimpan sbg angka di Excel, digit akhir mungkin salah';
        }
        if ($nik === null && ! str_contains(implode(' ', $catatan), 'NIK')) {
            $catatan[] = 'NIK kosong';
        }

        [$nip] = F::digit($v('nip'));
        if ($nip === null) {
            $catatan[] = 'NIP kosong';
        }

        [$kk, $kkPresisi] = F::digit($v('no_kk'));
        if ($kkPresisi) {
            $catatan[] = 'No. KK tersimpan sbg angka di Excel, digit terakhir kemungkinan salah';
        }
        [$npwp] = F::digit($v('npwp'));
        [$bpjsTk] = F::digit($v('bpjs_tk'));
        [$bpjsKes] = F::digit($v('bpjs_kes'));

        [$goldar, $goldarAsumsi] = F::golonganDarah($v('golongan_darah'));
        if ($goldarAsumsi) {
            $catatan[] = "golongan darah \"{$v('golongan_darah')}\" diasumsikan {$goldar}";
        }

        $jabatanList = array_values(array_filter(array_map([F::class, 'jabatan'], explode(',', (string) $v('jabatan')))));
        $jabatan = $jabatanList[0] ?? null;
        $tambahan = collect(array_slice($jabatanList, 1))
            ->merge(array_map([F::class, 'jabatan'], explode(',', (string) $v('jabatan_tambahan'))))
            ->filter()->reject(fn ($j) => $j === $jabatan)->unique()->implode(', ') ?: null;

        $unitExcel = mb_strtoupper(trim((string) $v('unit')));
        $unit = $this->tentukanUnit($unitExcel, (string) $jabatan, mb_strtoupper((string) $v('lokasi')));
        if ($unit === null) {
            $catatan[] = "bagian untuk jabatan \"{$jabatan}\" ({$unitExcel}) belum ditentukan";
        }

        [$statusKerja, $habisKontrak] = $this->statusKerja($v('status_kerja'));
        if ($statusKerja === null) {
            $catatan[] = 'status kerja kosong/tidak dikenal, diisi Kontrak';
            $statusKerja = 'Kontrak';
        }

        $tglLahir = $this->tanggal($v('tanggal_lahir'));
        if (! $tglLahir) {
            $catatan[] = 'tanggal lahir kosong (password akun tidak bisa dibuat)';
        }

        $infoKeluar = $keluar[$this->kunciNama($v('nama'))] ?? null;
        $tglMasuk = $this->tanggal($v('tanggal_masuk'));
        $aktif = ! $infoKeluar || ($tglMasuk && $infoKeluar['tanggal'] && $tglMasuk > $infoKeluar['tanggal']);
        if (! $aktif) {
            $catatan[] = 'tercatat di sheet PEGAWAI KELUAR' . ($infoKeluar['alasan'] ? " ({$infoKeluar['alasan']})" : '') . ', diimpor nonaktif';
        }

        $alamatKtp = F::alamat($v('alamat_ktp'));
        $alamatDom = F::alamat($v('alamat_domisili'));

        return [
            'baris' => $no,
            'waktu_isi' => $this->tanggal($v('waktu_isi')),
            'nama_asli' => $v('nama'),
            'catatan' => $catatan,
            'aktif' => $aktif,
            'unit_excel' => $unitExcel,
            'pegawai' => [
                'email' => F::email($v('email')),
                'nip' => $nip,
                'nama_lengkap' => $nama,
                'nik' => $nik,
                'npwp' => $npwp,
                'no_kk' => $kk,
                'no_hp' => F::hp($v('no_hp')),
                'tempat_lahir' => F::judul($v('tempat_lahir')),
                'tanggal_lahir' => $tglLahir,
                'jenis_kelamin' => F::jenisKelamin($v('jenis_kelamin')),
                'agama' => F::agama($v('agama')),
                'golongan_darah' => $goldar,
                'status_pernikahan' => F::statusPernikahan($v('status_pernikahan')),
                'jumlah_tanggungan' => is_numeric($v('jumlah_tanggungan')) ? (int) $v('jumlah_tanggungan') : 0,
                'nama_ibu_kandung' => F::nama($v('nama_ibu'))[0],
                'pendidikan_terakhir' => F::pendidikan($v('pendidikan')),
                'alamat_ktp' => $alamatKtp,
                'alamat_domisili' => $alamatDom && $alamatDom !== $alamatKtp ? $alamatDom : null,
            ],
            'kepegawaian' => [
                'jabatan' => $jabatan,
                'jabatan_tambahan' => $tambahan,
                'unit_kerja' => $unit,
                'lokasi_kerja' => F::jabatan($v('lokasi')) ?? $unitExcel,
                'tanggal_masuk' => $tglMasuk,
                'status_rekrutmen' => $aktif ? 'Aktif' : 'Tidak Aktif',
                'status_kerja' => $statusKerja,
                'tanggal_habis_kontrak' => $habisKontrak,
            ],
            'kontak' => [
                'no_hp_kerabat' => F::hp($v('hp_kerabat')),
                'nama_kerabat' => F::nama($v('nama_kerabat'))[0],
                'hubungan_kerabat' => F::hubunganKerabat($v('hubungan_kerabat')),
            ],
            'bpjs' => [
                'no_bpjs_ketenagakerjaan' => $bpjsTk,
                'no_bpjs_kesehatan' => $bpjsKes,
                'faskes_tingkat_1' => F::judul($v('faskes')),
            ],
        ];
    }

    private function tentukanUnit(string $unitExcel, string $jabatan, string $lokasi): ?string
    {
        if (in_array($unitExcel, config('import_pegawai.unit_sekolah'), true)) {
            return $unitExcel;
        }

        foreach (config('import_pegawai.jabatan_ke_unit') as $pola => $unit) {
            if (preg_match($pola, $jabatan)) {
                return $this->pecahPerLokasi($unit, $lokasi);
            }
        }

        return null;
    }

    private function pecahPerLokasi(string $unit, string $lokasi): string
    {
        $aturan = config("import_pegawai.pecah_unit_lokasi.{$unit}");
        if (! $aturan) {
            return $unit;
        }

        foreach ($aturan as $pola => $hasil) {
            if ($pola !== '*' && preg_match($pola, $lokasi)) {
                return $hasil;
            }
        }

        return $aturan['*'] ?? $unit;
    }

    private function statusKerja(?string $nilai): array
    {
        $v = mb_strtoupper(trim((string) $nilai));

        return match (true) {
            $v === 'TETAP' => ['Tetap', null],
            in_array($v, ['THL', 'HARIAN LEPAS'], true) => ['Harian Lepas', null],
            (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) => ['Kontrak', $v],
            str_contains($v, 'KONTRAK') => ['Kontrak', null],
            str_contains($v, 'MAGANG') => ['Magang', null],
            str_contains($v, 'PROBATION') => ['Probation', null],
            default => [null, null],
        };
    }

    private function tanggal(?string $nilai): ?string
    {
        $v = trim((string) $nilai);
        if ($v === '') {
            return null;
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $v)) {
            return $v;
        }
        try {
            return Carbon::createFromFormat('d/m/Y', $v)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function bersihkanDuplikat(Collection $rows): Collection
    {
        $rows = $rows->sortByDesc(fn ($r) => ($r['waktu_isi'] ?? '') . sprintf('%05d', $r['baris']))->values();
        $sudah = ['nik' => [], 'email' => [], 'nip' => []];
        $hasil = collect();

        foreach ($rows as $r) {
            $p = $r['pegawai'];
            $orangSama = ($p['nik'] && isset($sudah['nik'][$p['nik']]) && $sudah['nik'][$p['nik']] === $this->kunciNama($r['nama_asli']))
                || ($p['email'] && isset($sudah['email'][$p['email']]) && $sudah['email'][$p['email']] === $this->kunciNama($r['nama_asli']));
            if ($orangSama) {
                continue;
            }

            foreach (['nik' => 'NIK', 'email' => 'Email', 'nip' => 'NIP'] as $kolom => $label) {
                if ($p[$kolom] && isset($sudah[$kolom][$p[$kolom]])) {
                    $r['catatan'][] = "{$label} sama dengan pegawai lain ({$p[$kolom]}), dikosongkan";
                    $r['pegawai'][$kolom] = null;
                } elseif ($p[$kolom]) {
                    $sudah[$kolom][$p[$kolom]] = $this->kunciNama($r['nama_asli']);
                }
            }
            $hasil->push($r);
        }

        return $hasil->sortBy(fn ($r) => $r['pegawai']['nama_lengkap'])->values();
    }

    private function pilihAkun(Collection $pegawai): Collection
    {
        $akun = collect();
        $aktif = $pegawai->filter(fn ($r) => $r['aktif']);
        $dipakai = [];

        foreach (config('import_pegawai.petinggi') as $p) {
            $baris = isset($p['nama_excel']) ? $this->cariBaris($aktif, $p['nama_excel']) : null;
            $akun->push([
                'role' => User::ROLE_GM, 'nama' => $p['nama'], 'baris' => $baris['baris'] ?? null,
                'email' => $baris['pegawai']['email'] ?? null, 'email_lama' => $p['email_lama'] ?? null,
                'tanggal_lahir' => $baris['pegawai']['tanggal_lahir'] ?? null, 'unit' => implode(' + ', $p['bidang']),
                'bidang' => $p['bidang'],
                'kepala_unit' => $this->unitRangkapan($p['nama_excel'] ?? null),
            ]);
            if ($baris) {
                $dipakai[$baris['baris']] = true;
            }
        }

        $manual = config('import_pegawai.kepala_manual', []);
        $rangkapPerOrang = collect($manual)->filter(fn ($m) => isset($m['rangkap']))
            ->map(fn ($m, $unit) => ['nama' => $m['rangkap'], 'unit' => $unit])
            ->groupBy('nama');
        foreach ($rangkapPerOrang as $nama => $daftar) {
            $r = $this->cariBaris($aktif, $nama);
            if ($r && ! isset($dipakai[$r['baris']])) {
                $units = $daftar->pluck('unit')->values()->all();
                $akun->push([...$this->akunPegawai($r, User::ROLE_KEPALA_BAGIAN), 'unit' => implode(' + ', $units), 'kepala_unit' => $units]);
                $dipakai[$r['baris']] = true;
            }
        }

        $kepalaSdm = $aktif->first(fn ($r) => $r['kepegawaian']['jabatan'] === 'KEPALA SDM');
        if ($kepalaSdm) {
            $akun->push($this->akunPegawai($kepalaSdm, User::ROLE_KEPALA_SDM));
            $dipakai[$kepalaSdm['baris']] = true;
        }

        $polaKepala = config('import_pegawai.pola_kepala');
        foreach ($aktif->groupBy(fn ($r) => $r['kepegawaian']['unit_kerja'] ?? '') as $unit => $anggota) {
            if ($unit === '') {
                continue;
            }

            $m = $manual[$unit] ?? [];
            $kepalaAda = isset($m['rangkap']) || isset($m['baru']);

            $kepala = match (true) {
                $unit === 'SDM' => $kepalaSdm,
                $kepalaAda => null,
                isset($m['nama_excel']) => $this->cariBaris($anggota, $m['nama_excel']),
                default => $anggota
                    ->reject(fn ($r) => isset($dipakai[$r['baris']]))
                    ->map(fn ($r) => [$r, $this->peringkatKepala($r, $polaKepala)])
                    ->filter(fn ($x) => $x[1] !== null)
                    ->sortBy(fn ($x) => sprintf('%03d', $x[1]) . ($x[0]['kepegawaian']['tanggal_masuk'] ?? '9999'))
                    ->first()[0] ?? null,
            };

            if (! $kepala && ! $kepalaAda) {
                continue;
            }
            if ($kepala && $unit !== 'SDM') {
                $akun->push($this->akunPegawai($kepala, User::ROLE_KEPALA_BAGIAN));
                $dipakai[$kepala['baris']] = true;
            }

            $jumlahStaff = in_array($unit, config('kepegawaian.unit_tanpa_pengajuan', []), true)
                ? 0
                : ($m['staff'] ?? config('import_pegawai.staff_per_bagian'));
            $anggota->reject(fn ($r) => isset($dipakai[$r['baris']]))
                ->filter(fn ($r) => $r['pegawai']['email'] && $r['pegawai']['tanggal_lahir'] && $this->peringkatKepala($r, $polaKepala) === null)
                ->sortBy(fn ($r) => $r['kepegawaian']['tanggal_masuk'] ?? '9999')
                ->take(max(0, $jumlahStaff - ($unit === 'SDM' ? count(config('import_pegawai.akun_tambahan')) : 0)))
                ->each(function ($r) use ($akun, &$dipakai) {
                    $akun->push($this->akunPegawai($r, User::ROLE_STAFF));
                    $dipakai[$r['baris']] = true;
                });
        }

        foreach (config('import_pegawai.akun_tambahan') as $t) {
            $akun->push(['role' => User::ROLE_STAFF, 'nama' => $t['nama'], 'baris' => null, 'email' => $t['email'],
                'tanggal_lahir' => null, 'unit' => $t['unit'], 'tambahan' => $t]);
        }

        foreach ($manual as $unit => $m) {
            if (isset($m['baru'])) {
                $akun->push(['role' => User::ROLE_KEPALA_BAGIAN, 'nama' => $m['baru']['nama'], 'baris' => null,
                    'email' => $m['baru']['email'], 'tanggal_lahir' => null, 'unit' => $unit,
                    'tambahan' => ['nama' => $m['baru']['nama'], 'email' => $m['baru']['email'], 'unit' => $unit, 'jabatan' => $m['baru']['jabatan']]]);
            }
        }

        return $akun;
    }

    private function cariBaris(Collection $rows, string $nama): ?array
    {
        return $rows->first(fn ($r) => str_contains($this->kunciNama($r['nama_asli']), mb_strtoupper($nama)));
    }

    /**
     * @return list<string>|null
     */
    private function unitRangkapan(?string $namaExcel): ?array
    {
        if (! $namaExcel) {
            return null;
        }

        $units = collect(config('import_pegawai.kepala_manual', []))
            ->filter(fn ($m) => isset($m['rangkap']) && mb_strtoupper($m['rangkap']) === mb_strtoupper($namaExcel))
            ->keys()->values()->all();

        return $units ?: null;
    }

    private function peringkatKepala(array $r, array $pola): ?int
    {
        foreach ([$r['kepegawaian']['jabatan'], ...explode(', ', (string) $r['kepegawaian']['jabatan_tambahan'])] as $urut => $j) {
            foreach ($pola as $i => $p) {
                if ($j && preg_match($p, $j)) {
                    return $i * 10 + min($urut, 1);
                }
            }
        }

        return null;
    }

    private function akunPegawai(array $r, string $role): array
    {
        return ['role' => $role, 'nama' => $r['pegawai']['nama_lengkap'], 'baris' => $r['baris'], 'email' => $r['pegawai']['email'],
            'tanggal_lahir' => $r['pegawai']['tanggal_lahir'], 'unit' => $r['kepegawaian']['unit_kerja'],
            'jabatan' => $r['kepegawaian']['jabatan']];
    }

    private function password(?string $tanggalLahir): ?string
    {
        return $tanggalLahir ? Carbon::parse($tanggalLahir)->format('dmY') : null;
    }

    private function tulisLaporan(Collection $pegawai, Collection $akun, string $folder): void
    {
        $akunPerBaris = $akun->whereNotNull('baris')->keyBy('baris');

        $this->csv("{$folder}/laporan-import.csv", [
            'Sumber (sheet:baris)', 'Nama (asli)', 'Nama (dirapikan)', 'Status', 'NIP', 'NIK', 'Email', 'No. HP', 'Unit Excel',
            'Bagian (sistem)', 'Jabatan', 'Jabatan Tambahan', 'Lokasi', 'Status Kerja', 'Habis Kontrak', 'Pendidikan',
            'Akun Login', 'Catatan',
        ], $pegawai->map(fn ($r) => [
            $r['sumber'] ?? $r['baris'], $r['nama_asli'], $r['pegawai']['nama_lengkap'], $r['aktif'] ? 'Aktif' : 'Nonaktif',
            $r['pegawai']['nip'], $r['pegawai']['nik'], $r['pegawai']['email'], $r['pegawai']['no_hp'], $r['unit_excel'],
            $r['kepegawaian']['unit_kerja'], $r['kepegawaian']['jabatan'], $r['kepegawaian']['jabatan_tambahan'],
            $r['kepegawaian']['lokasi_kerja'], $r['kepegawaian']['status_kerja'], $r['kepegawaian']['tanggal_habis_kontrak'],
            $r['pegawai']['pendidikan_terakhir'],
            isset($akunPerBaris[$r['baris']]) ? User::roleLabelFor($akunPerBaris[$r['baris']]['role']) : '',
            implode('; ', $r['catatan']),
        ]));

        $this->csv("{$folder}/akun-login.csv", ['Role', 'Nama', 'Bagian / Bidang', 'Email Login', 'Password Awal', 'Catatan'],
            $akun->map(fn ($a) => [
                User::roleLabelFor($a['role']), $a['nama'], $a['unit'], $a['email'] ?? $a['email_lama'] ?? '(tidak ada email)',
                $this->password($a['tanggal_lahir']) ?? ($a['email_lama'] ?? false ? '(password lama tetap)' : 'password123'),
                ! $a['email'] && ! ($a['email_lama'] ?? null) ? 'tidak bisa login: email kosong' : '',
            ]));
    }

    private function csv(string $path, array $header, Collection $rows): void
    {
        $f = fopen($path, 'w');
        fwrite($f, "\xEF\xBB\xBF");
        fputcsv($f, $header, ';');
        foreach ($rows as $row) {
            fputcsv($f, $row, ';');
        }
        fclose($f);
        $this->line("Laporan: {$path}");
    }

    private function ringkasan(Collection $pegawai, Collection $akun): void
    {
        $this->newLine();
        $this->info('RINGKASAN');
        $this->line('Pegawai: ' . $pegawai->count() . ' (aktif ' . $pegawai->where('aktif', true)->count() . ', nonaktif ' . $pegawai->where('aktif', false)->count() . ')');
        $this->line('Bagian belum ditentukan: ' . $pegawai->whereNull('kepegawaian.unit_kerja')->count());
        $this->line('Baris dengan catatan: ' . $pegawai->filter(fn ($r) => $r['catatan'])->count());

        $this->newLine();
        $this->table(['Bagian', 'Aktif', 'Kepala', 'Akun Staff'], $pegawai->where('aktif', true)
            ->groupBy(fn ($r) => $r['kepegawaian']['unit_kerja'] ?? '(belum ditentukan)')
            ->map(fn ($rows, $unit) => [
                $unit, $rows->count(),
                $akun->first(fn ($a) => in_array($unit, (array) ($a['kepala_unit'] ?? []), true)
                    || ($a['unit'] === $unit && in_array($a['role'], [User::ROLE_KEPALA_BAGIAN, User::ROLE_KEPALA_SDM], true)))['nama'] ?? '-',
                $akun->where('unit', $unit)->where('role', User::ROLE_STAFF)->pluck('nama')->implode(', ') ?: '-',
            ])->sortKeys()->values()->all());

        $this->line('Akun login: ' . $akun->count() . ' ' . json_encode($akun->countBy(fn ($a) => User::roleLabelFor($a['role']))));
    }

    private function jalankan(Collection $pegawai, Collection $akun): void
    {
        $emailLama = $akun->pluck('email_lama')->filter()->all();

        DB::transaction(function () use ($pegawai, $akun, $emailLama) {
            if ($this->option('bersihkan-rekrutmen')) {
                DB::table('pengajuan_formasis')->delete();
                DB::table('lowongans')->delete();
                DB::table('lamarans')->delete();
                DB::table('data_pelamars')->delete();
                User::where('role', User::ROLE_PELAMAR)->delete();
            }

            DB::table('data_pegawais')->delete();

            User::whereNotIn('role', [User::ROLE_ADMIN, User::ROLE_PEMBINA, User::ROLE_PELAMAR])
                ->whereNotIn('email', $emailLama)
                ->delete();

            $idPerBaris = [];
            foreach ($pegawai as $r) {
                $model = DataPegawai::create($r['pegawai']);
                $model->kepegawaian()->create($r['kepegawaian']);
                if ($r['kepegawaian']['unit_kerja'] ?? null) {
                    PenugasanService::buatUtama($model, $r['kepegawaian']['unit_kerja'], $r['kepegawaian']['jabatan'] ?? null,
                        min($r['kepegawaian']['tanggal_masuk'] ?? now()->toDateString(), now()->toDateString()));
                }
                if (array_filter($r['kontak'])) {
                    $model->kontakDarurat()->create($r['kontak']);
                }
                if (array_filter($r['bpjs'])) {
                    $model->bpjs()->create($r['bpjs']);
                }
                $idPerBaris[$r['baris']] = $model->id;
            }

            foreach ($akun as $a) {
                $this->simpanAkun($a, $idPerBaris);
            }

            LokasiKerja::daftarkan($pegawai->pluck('kepegawaian.lokasi_kerja'));
        });

        $this->newLine();
        $this->info('Import selesai: ' . DataPegawai::count() . ' pegawai, ' . User::count() . ' akun.');
    }

    private function simpanAkun(array $a, array $idPerBaris): void
    {
        $dataPegawaiId = $a['baris'] ? ($idPerBaris[$a['baris']] ?? null) : null;

        if (isset($a['tambahan'])) {
            $t = $a['tambahan'];
            $pegawai = DataPegawai::create(['nama_lengkap' => $t['nama'], 'email' => $t['email']]);
            $pegawai->kepegawaian()->create([
                'jabatan' => $t['jabatan'], 'unit_kerja' => $t['unit'], 'tanggal_masuk' => now()->toDateString(),
                'status_rekrutmen' => 'Aktif', 'status_kerja' => 'Kontrak',
            ]);
            PenugasanService::buatUtama($pegawai, $t['unit'], $t['jabatan']);
            $dataPegawaiId = $pegawai->id;
        }

        $user = isset($a['email_lama']) ? User::where('email', $a['email_lama'])->first() : null;
        $email = $a['email'] ?? $a['email_lama'] ?? null;
        if (! $email) {
            return;
        }

        $data = [
            'name' => $a['nama'],
            'email' => $email,
            'role' => $a['role'],
            'data_pegawai_id' => $dataPegawaiId,
            'nip' => $dataPegawaiId ? DataPegawai::find($dataPegawaiId)?->nip : null,
            'divisi' => $a['role'] === User::ROLE_GM
                ? collect($a['bidang'])->flatMap(fn ($b) => config("kepegawaian.gm_bidang.{$b}", []))->unique()->values()->all()
                : null,
        ];
        if ($pw = $this->password($a['tanggal_lahir'])) {
            $data['password'] = Hash::make($pw);
        } elseif (! $user) {
            $data['password'] = Hash::make('password123');
        }

        $user ? $user->update($data) : $user = User::create($data);

        $pegawai = $dataPegawaiId ? DataPegawai::with('kepegawaian')->find($dataPegawaiId) : null;
        if ($pegawai && in_array($a['role'], [User::ROLE_GM, User::ROLE_KEPALA_BAGIAN, User::ROLE_KEPALA_SDM], true)) {
            $units = $a['kepala_unit'] ?? ($a['role'] === User::ROLE_GM ? [] : array_filter([$pegawai->kepegawaian?->unit_kerja]));
            PenugasanService::aturKepala($pegawai, array_values($units));
        }
    }
}
