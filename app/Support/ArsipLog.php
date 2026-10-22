<?php

namespace App\Support;

use App\Models\LogAktivitas;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\File;

/** Masa simpan bertingkat log aktivitas: arsipkan ke Excel di storage, lalu hapus dari database. */
class ArsipLog
{
    public const FOLDER = 'arsip-log';

    /** kategori => [label, daftar aksi (null = semua aksi lain)] */
    public const KATEGORI = [
        'akses' => ['Login & logout', ['login', 'logout']],
        'ekspor' => ['Ekspor data', ['ekspor']],
        'data' => ['Perubahan data', null],
    ];

    /** @return array<string, int> jumlah hari per kategori */
    public static function retensi(): array
    {
        return array_merge(['akses' => 365, 'ekspor' => 730, 'data' => 1825], config('hrrab.retensi_log', []));
    }

    /**
     * Arsipkan lalu hapus log yang melewati masa simpan.
     *
     * @return array<string, array{jumlah: int, file: ?string}>
     */
    public static function jalankan(): array
    {
        $hasil = [];

        foreach (self::KATEGORI as $kunci => [$label]) {
            $batas = now()->startOfDay()->subDays(self::retensi()[$kunci]);
            $query = self::query($kunci)->where('created_at', '<', $batas);
            $jumlah = (clone $query)->count();

            if ($jumlah === 0) {
                $hasil[$kunci] = ['jumlah' => 0, 'file' => null];

                continue;
            }

            // File arsip dibuat dulu; database baru dihapus kalau arsip berhasil ditulis.
            $file = self::tulisArsip($kunci, $label, (clone $query)->orderBy('id')->get());
            $hapus = (clone $query)->delete();

            $hasil[$kunci] = ['jumlah' => $hapus, 'file' => $file];
        }

        return $hasil;
    }

    /** @return list<array{nama: string, ukuran: int, waktu: int}> */
    public static function daftarArsip(): array
    {
        $folder = storage_path('app/' . self::FOLDER);

        return collect(File::isDirectory($folder) ? File::files($folder) : [])
            ->filter(fn ($f) => $f->getExtension() === 'xlsx')
            ->map(fn ($f) => ['nama' => $f->getFilename(), 'ukuran' => $f->getSize(), 'waktu' => $f->getMTime()])
            ->sortByDesc('waktu')->values()->all();
    }

    public static function pathArsip(string $nama): ?string
    {
        // Hanya nama file arsip yang valid, mencegah path traversal.
        if (! preg_match('/^log-aktivitas-[a-z]+-[0-9]{8}-[0-9]{6}\.xlsx$/', $nama)) {
            return null;
        }
        $path = storage_path('app/' . self::FOLDER . '/' . $nama);

        return is_file($path) ? $path : null;
    }

    private static function query(string $kategori): Builder
    {
        $aksiKhusus = array_merge(...array_values(array_filter(array_column(self::KATEGORI, 1))));
        $aksi = self::KATEGORI[$kategori][1];

        return $aksi === null
            ? LogAktivitas::whereNotIn('aksi', $aksiKhusus)
            : LogAktivitas::whereIn('aksi', $aksi);
    }

    private static function tulisArsip(string $kategori, string $label, iterable $logs): string
    {
        $rows = collect($logs)->map(fn (LogAktivitas $l) => [
            $l->created_at->format('d-m-Y H:i:s'),
            $l->nama_pengguna,
            User::roleLabelFor($l->role),
            $l->aksiInfo()[0],
            $l->modul,
            $l->deskripsi,
            $l->status === 'gagal' ? 'Gagal' : 'Berhasil',
            $l->perubahan ? json_encode($l->perubahan, JSON_UNESCAPED_UNICODE) : null,
            $l->ip,
            $l->perangkat,
        ]);

        $sementara = XlsxWriter::tulis([[
            'name' => $label,
            'headers' => ['Waktu', 'Pengguna', 'Role', 'Aksi', 'Modul', 'Deskripsi', 'Status', 'Perubahan (JSON)', 'IP', 'Perangkat'],
            'rows' => $rows,
        ]]);

        $folder = storage_path('app/' . self::FOLDER);
        File::ensureDirectoryExists($folder);
        $nama = "log-aktivitas-{$kategori}-" . now()->format('Ymd-His') . '.xlsx';
        File::move($sementara, "{$folder}/{$nama}");

        return $nama;
    }
}
