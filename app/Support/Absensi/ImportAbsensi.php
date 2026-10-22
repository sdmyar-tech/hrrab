<?php

namespace App\Support\Absensi;

use App\Models\Absensi;
use App\Models\AbsensiImport;
use App\Models\DataPegawai;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Import hasil baca file mesin ke tabel absensis.
 *  - Kunci unik: finger_id + tanggal → import ulang file/periode yang sama tidak membuat data dobel.
 *  - Hari yang sudah dikoreksi SDM/Admin TIDAK ditimpa.
 *  - Semua ID disimpan (termasuk yang belum dipetakan / pegawai tidak ikut absensi) agar bisa dinilai kemudian.
 */
class ImportAbsensi
{
    /** Analisis tanpa menyimpan, untuk ditampilkan sebelum SDM menekan Simpan. */
    public static function pratinjau(array $hasilBaca): array
    {
        $baris = self::gabungDobel($hasilBaca['baris']);
        [$pegawai, $ada] = self::muat($baris);

        $ringkas = [
            'format' => $hasilBaca['format'],
            'periode_dari' => $baris->min('tanggal'),
            'periode_sampai' => $baris->max('tanggal'),
            'total' => $baris->count(),
            'baru' => 0, 'diperbarui' => 0, 'sama' => 0, 'dikoreksi' => 0,
            'kosong' => $hasilBaca['kosong'],
            'tidak_valid' => $hasilBaca['tidak_valid'],
            'pegawai_dinilai' => [],
            'tidak_ikut' => [],
            'belum_dipetakan' => [],
        ];

        foreach ($baris as $b) {
            $p = $pegawai[$b['finger_id']] ?? null;
            $lama = self::cariLama($ada, $b, $p);
            $ringkas[self::jenis($lama, $b)]++;

            $kunci = $b['finger_id'];
            if (! $p) {
                $ringkas['belum_dipetakan'][$kunci] ??= ['finger_id' => $kunci, 'nama' => $b['nama'], 'hari' => 0];
                $ringkas['belum_dipetakan'][$kunci]['hari']++;
            } elseif (! $p->ikut_absensi) {
                $ringkas['tidak_ikut'][$kunci] ??= ['finger_id' => $kunci, 'nama' => $p->nama_lengkap, 'hari' => 0];
                $ringkas['tidak_ikut'][$kunci]['hari']++;
            } else {
                $ringkas['pegawai_dinilai'][$p->id] = true;
            }
        }

        $ringkas['pegawai_dinilai'] = count($ringkas['pegawai_dinilai']);
        $ringkas['belum_dipetakan'] = array_values($ringkas['belum_dipetakan']);
        $ringkas['tidak_ikut'] = array_values($ringkas['tidak_ikut']);

        return $ringkas;
    }

    public static function simpan(array $hasilBaca, string $namaFile, User $oleh): AbsensiImport
    {
        $ringkas = self::pratinjau($hasilBaca);
        $baris = self::gabungDobel($hasilBaca['baris']);

        return DB::transaction(function () use ($baris, $ringkas, $namaFile, $oleh) {
            $import = AbsensiImport::create([
                'nama_file' => mb_substr($namaFile, 0, 250),
                'periode_dari' => $ringkas['periode_dari'],
                'periode_sampai' => $ringkas['periode_sampai'],
                'jumlah_baris' => $ringkas['total'],
                'jumlah_baru' => $ringkas['baru'],
                'jumlah_diperbarui' => $ringkas['diperbarui'],
                'jumlah_dilewati' => $ringkas['sama'] + $ringkas['dikoreksi'] + count($ringkas['tidak_valid']),
                'ringkasan' => collect($ringkas)->except('tidak_valid')->all() + ['tidak_valid' => count($ringkas['tidak_valid'])],
                'diimport_oleh' => $oleh->id,
            ]);

            [$pegawai, $ada] = self::muat($baris);
            $sekarang = now();
            $sisip = [];

            foreach ($baris as $b) {
                $p = $pegawai[$b['finger_id']] ?? null;
                $lama = self::cariLama($ada, $b, $p);
                $jenis = self::jenis($lama, $b);

                if ($jenis === 'baru') {
                    $sisip[] = [
                        'data_pegawai_id' => $p?->id, 'finger_id' => $b['finger_id'], 'nama_mesin' => $b['nama'], 'tanggal' => $b['tanggal'],
                        'scan_masuk' => $b['masuk'], 'scan_keluar' => $b['keluar'], 'jam_masuk' => $b['masuk'], 'jam_keluar' => $b['keluar'],
                        'absensi_import_id' => $import->id, 'created_at' => $sekarang, 'updated_at' => $sekarang,
                    ];
                } elseif ($jenis === 'diperbarui') {
                    // Query builder (tanpa event model) agar import ribuan baris tetap ringan.
                    DB::table('absensis')->where('id', $lama->id)->update([
                        'data_pegawai_id' => $p?->id ?? $lama->data_pegawai_id, 'finger_id' => $b['finger_id'], 'nama_mesin' => $b['nama'] ?? $lama->nama_mesin,
                        'scan_masuk' => $b['masuk'], 'scan_keluar' => $b['keluar'], 'jam_masuk' => $b['masuk'], 'jam_keluar' => $b['keluar'],
                        'absensi_import_id' => $import->id, 'updated_at' => $sekarang,
                    ]);
                }
            }

            foreach (array_chunk($sisip, 500) as $potong) {
                DB::table('absensis')->insert($potong);
            }

            return $import;
        });
    }

    /**
     * Petakan finger_id ke pegawai dan hubungkan semua absensi lama dengan ID tersebut.
     *
     * @return int jumlah hari absensi yang ikut terhubung
     */
    public static function petakan(DataPegawai $pegawai, ?string $fingerId): int
    {
        return DB::transaction(function () use ($pegawai, $fingerId) {
            $lama = $pegawai->finger_id;
            $pegawai->forceFill(['finger_id' => $fingerId])->save();

            if ($lama && $lama !== $fingerId) {
                // Absensi dari ID lama yang belum dikoreksi dilepas dari pegawai ini.
                Absensi::where('finger_id', $lama)->where('data_pegawai_id', $pegawai->id)->update(['data_pegawai_id' => null]);
            }
            if (! $fingerId) {
                return 0;
            }

            // Baris manual (tanpa finger) pegawai ini pada tanggal yang sama menang; baris mesin pada tanggal itu dibiarkan tidak terhubung.
            $tanggalManual = Absensi::where('data_pegawai_id', $pegawai->id)->whereNull('finger_id')->pluck('tanggal')->map->toDateString();

            return Absensi::where('finger_id', $fingerId)
                ->where(fn ($q) => $q->whereNull('data_pegawai_id')->orWhere('data_pegawai_id', '!=', $pegawai->id))
                ->whereNotIn('tanggal', $tanggalManual)
                ->update(['data_pegawai_id' => $pegawai->id]);
        });
    }

    /** Baris ganda (ID + tanggal sama dalam satu file) digabung: scan paling awal & paling akhir. */
    private static function gabungDobel(array $baris): Collection
    {
        return collect($baris)->groupBy(fn ($b) => $b['finger_id'] . '|' . $b['tanggal'])->map(function (Collection $g) {
            $scan = $g->flatMap(fn ($b) => [$b['masuk'], $b['keluar']])->filter()->sort()->values();
            $b = $g->first();
            $b['masuk'] = $scan->first();
            $b['keluar'] = $scan->count() > 1 ? $scan->last() : null;

            return $b;
        })->values();
    }

    /** @return array{0: Collection<string, DataPegawai>, 1: array{finger: Collection, pegawai: Collection}} */
    private static function muat(Collection $baris): array
    {
        $pegawai = DataPegawai::whereIn('finger_id', $baris->pluck('finger_id')->unique())->get(['id', 'finger_id', 'nama_lengkap', 'ikut_absensi'])->keyBy('finger_id');
        $dari = $baris->min('tanggal');
        $sampai = $baris->max('tanggal');

        $ada = Absensi::whereBetween('tanggal', [$dari, $sampai])
            ->where(fn ($q) => $q->whereIn('finger_id', $baris->pluck('finger_id')->unique())->orWhereIn('data_pegawai_id', $pegawai->pluck('id')))
            ->get(['id', 'finger_id', 'data_pegawai_id', 'nama_mesin', 'tanggal', 'scan_masuk', 'scan_keluar', 'dikoreksi']);

        return [$pegawai, [
            'finger' => $ada->whereNotNull('finger_id')->keyBy(fn ($a) => $a->finger_id . '|' . $a->tanggal->toDateString()),
            'pegawai' => $ada->whereNotNull('data_pegawai_id')->keyBy(fn ($a) => $a->data_pegawai_id . '|' . $a->tanggal->toDateString()),
        ]];
    }

    private static function cariLama(array $ada, array $b, ?DataPegawai $p): ?Absensi
    {
        return $ada['finger'][$b['finger_id'] . '|' . $b['tanggal']]
            ?? ($p ? $ada['pegawai'][$p->id . '|' . $b['tanggal']] ?? null : null);
    }

    /** baru | diperbarui | sama | dikoreksi */
    private static function jenis(?Absensi $lama, array $b): string
    {
        if (! $lama) {
            return 'baru';
        }
        if ($lama->dikoreksi) {
            return 'dikoreksi';
        }

        return substr((string) $lama->scan_masuk, 0, 5) === (string) $b['masuk'] && substr((string) $lama->scan_keluar, 0, 5) === (string) $b['keluar']
            ? 'sama' : 'diperbarui';
    }
}
