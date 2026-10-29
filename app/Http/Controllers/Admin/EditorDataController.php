<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\EditorData;
use App\Support\ExcelExporter;
use App\Support\FilterTanggal;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Editor Data — khusus Admin. Mengubah/menghapus baris data apa pun secara langsung:
 * tanpa aturan alur, tanpa batas tanggal, tanpa notifikasi. Setiap perubahan tetap tercatat di Log Aktivitas.
 */
class EditorDataController extends Controller
{
    public function index(): View
    {
        $this->hanyaAdmin();

        $grup = collect(EditorData::entitas())
            ->map(fn ($k, $kunci) => [...$k, 'kunci' => $kunci, 'jumlah' => EditorData::model($k)->newQuery()->count()])
            ->groupBy('grup');

        return view('admin.editor-data.index', ['grup' => $grup]);
    }

    public function daftar(Request $request, string $entitas): View
    {
        $this->hanyaAdmin();
        $k = EditorData::konfig($entitas);

        return view('admin.editor-data.daftar', [
            'entitas' => $entitas,
            'k' => $k,
            'baris' => $this->saringTanggal(EditorData::query($k, $request->query('q')), $k, $request)->paginate(25)->withQueryString(),
        ]);
    }

    /** Ekspor satu jenis data (sesuai pencarian ?q=), semua kolom. */
    public function export(Request $request, string $entitas): BinaryFileResponse
    {
        $this->hanyaAdmin();
        $k = EditorData::konfig($entitas);

        return ExcelExporter::downloadMultiSheet(ExcelExporter::namaFile('data-' . $entitas),
            [EditorData::lembarEkspor($k, $this->saringTanggal(EditorData::query($k, $request->query('q')), $k, $request)->reorder()->orderBy(EditorData::model($k)->getKeyName())->lazy())]);
    }

    /** Ekspor seluruh data aplikasi: satu lembar per jenis data. */
    public function exportSemua(): BinaryFileResponse
    {
        $this->hanyaAdmin();
        @set_time_limit(300);

        $lembar = collect(EditorData::entitas())->map(fn ($k) => EditorData::lembarEkspor($k,
            EditorData::query($k, null)->reorder()->orderBy(EditorData::model($k)->getKeyName())->lazy()))->values()->all();

        return ExcelExporter::downloadMultiSheet(ExcelExporter::namaFile('semua-data-hrrab'), $lembar);
    }

    public function edit(Request $request, string $entitas, string $id): View
    {
        $this->hanyaAdmin();
        $k = EditorData::konfig($entitas);
        $model = $this->cari($k, $id);

        return view('admin.editor-data.edit', [
            'entitas' => $entitas,
            'k' => $k,
            'model' => $model,
            'kolom' => EditorData::kolom($model),
            'dampak' => EditorData::dampakHapus($model),
            'kembali' => EditorData::kembaliAman($request->query('kembali')),
        ]);
    }

    /** Form tambah data baru (semua kolom, nilai awal = bawaan database). */
    public function create(Request $request, string $entitas): View
    {
        $this->hanyaAdmin();
        $k = EditorData::konfig($entitas);
        abort_unless($k['tambah'] ?? true, 404, 'Data jenis ini dibuat lewat halamannya sendiri.');
        $model = EditorData::model($k);

        return view('admin.editor-data.edit', [
            'entitas' => $entitas,
            'k' => $k,
            'model' => $model,
            'kolom' => EditorData::kolom($model),
            'dampak' => [],
            'kembali' => EditorData::kembaliAman($request->query('kembali')),
        ]);
    }

    public function store(Request $request, string $entitas): RedirectResponse
    {
        $this->hanyaAdmin();
        $k = EditorData::konfig($entitas);
        abort_unless($k['tambah'] ?? true, 404);
        $model = EditorData::model($k);
        $masukan = (array) $request->input('d', []);
        $kosongkan = (array) $request->input('null', []);
        $isi = [];
        $galat = [];

        foreach (EditorData::kolom($model) as $kol) {
            $nama = $kol['nama'];
            if ($kol['baca']) {
                continue;
            }
            $v = isset($kosongkan[$nama]) ? null : trim((string) ($masukan[$nama] ?? ''));
            if ($v === null || $v === '') {
                if ($kol['wajib']) {
                    $galat["d.{$nama}"] = "Kolom {$nama} wajib diisi.";
                }

                continue; // kosong: pakai bawaan database / NULL
            }
            try {
                $isi[$nama] = $this->konversi($model, $kol, $v);
            } catch (\InvalidArgumentException $e) {
                $galat["d.{$nama}"] = $e->getMessage();
            }
        }

        if ($galat) {
            throw ValidationException::withMessages($galat);
        }

        try {
            $model->forceFill($isi)->save();
        } catch (QueryException $e) {
            return back()->withInput()->with('error', 'Gagal disimpan: ' . $this->pesanDb($e));
        }

        $tujuan = EditorData::kembaliAman($request->input('kembali'));

        return ($tujuan ? redirect($tujuan) : redirect()->route('admin.data.edit', [$entitas, $model->getKey()]))
            ->with('success', "{$k['label']} baru #{$model->getKey()} dibuat.");
    }

    public function update(Request $request, string $entitas, string $id): RedirectResponse
    {
        $this->hanyaAdmin();
        $k = EditorData::konfig($entitas);
        $model = $this->cari($k, $id);
        $masukan = (array) $request->input('d', []);
        $kosongkan = (array) $request->input('null', []);
        $galat = [];
        $ubah = [];

        foreach (EditorData::kolom($model) as $kol) {
            $nama = $kol['nama'];
            if ($kol['baca'] || (! array_key_exists($nama, $masukan) && ! isset($kosongkan[$nama]))) {
                continue;
            }
            $lama = EditorData::nilaiInput($model, $kol);
            $baru = isset($kosongkan[$nama]) ? null : (string) ($masukan[$nama] ?? '');
            if ($baru === $lama || ($baru === null && $lama === '')) {
                continue;
            }

            try {
                $nilai = $this->konversi($model, $kol, $baru);
            } catch (\InvalidArgumentException $e) {
                $galat["d.{$nama}"] = $e->getMessage();

                continue;
            }
            // Bandingkan setelah dinormalkan (mis. 08:00 = 08:00:00, JSON beda spasi) agar yang tidak berubah tidak ikut tersimpan.
            try {
                $nilaiLama = $lama === '' ? null : $this->konversi($model, [...$kol, 'wajib' => false], $lama);
            } catch (\InvalidArgumentException) {
                $nilaiLama = $lama;
            }
            if ($nilai !== null && $nilai == $nilaiLama && gettype($nilai) === gettype($nilaiLama)) {
                continue;
            }
            $ubah[$nama] = $nilai;
        }

        if ($galat) {
            throw ValidationException::withMessages($galat);
        }
        if (! $ubah) {
            return back()->with('info', 'Tidak ada perubahan.');
        }

        if ($model instanceof User && array_key_exists('role', $ubah) && $model->role === User::ROLE_ADMIN
            && $ubah['role'] !== User::ROLE_ADMIN && User::where('role', User::ROLE_ADMIN)->count() <= 1) {
            return back()->withInput()->with('error', 'Ini satu-satunya akun Admin — role-nya tidak boleh diganti agar sistem tidak terkunci.');
        }

        try {
            $model->forceFill($ubah)->save();
        } catch (QueryException $e) {
            return back()->withInput()->with('error', 'Gagal disimpan: ' . $this->pesanDb($e));
        }

        $tujuan = EditorData::kembaliAman($request->input('kembali'));

        return ($tujuan ? redirect($tujuan) : back())
            ->with('success', "{$k['label']} #{$model->getKey()} diperbarui (" . count($ubah) . ' kolom: ' . implode(', ', array_keys($ubah)) . ').');
    }

    public function destroy(Request $request, string $entitas, string $id): RedirectResponse
    {
        $this->hanyaAdmin();
        $k = EditorData::konfig($entitas);
        $model = $this->cari($k, $id);

        if ($model instanceof User) {
            if ($model->id === Auth::id()) {
                return back()->with('error', 'Akun yang sedang Anda pakai tidak bisa dihapus.');
            }
            if ($model->role === User::ROLE_ADMIN && User::where('role', User::ROLE_ADMIN)->count() <= 1) {
                return back()->with('error', 'Ini satu-satunya akun Admin — tidak bisa dihapus.');
            }
        }

        try {
            $model->delete();
        } catch (QueryException $e) {
            return back()->with('error', 'Gagal dihapus: ' . $this->pesanDb($e));
        }

        $tujuan = EditorData::kembaliAman($request->input('kembali'));

        return ($tujuan ? redirect($tujuan) : redirect()->route('admin.data.daftar', $entitas))
            ->with('success', "{$k['label']} #{$id} dihapus.");
    }

    /** Ubah teks dari form ke nilai kolom. Melempar InvalidArgumentException bila tidak valid. */
    private function konversi(Model $model, array $kol, ?string $v): mixed
    {
        $wajib = fn () => throw new \InvalidArgumentException("Kolom {$kol['nama']} wajib diisi (tidak boleh kosong).");

        if ($v === null || ($v === '' && $kol['jenis'] !== 'teks' && $kol['jenis'] !== 'teks_panjang')) {
            return $kol['wajib'] ? $wajib() : null;
        }

        return match ($kol['jenis']) {
            'angka', 'fk' => is_numeric($v) ? $v + 0 : throw new \InvalidArgumentException("Kolom {$kol['nama']} harus berupa angka."),
            'boolean' => in_array($v, ['0', '1'], true) ? (int) $v : throw new \InvalidArgumentException("Kolom {$kol['nama']} harus Ya/Tidak."),
            'enum' => in_array($v, $kol['opsi'], true) ? $v : throw new \InvalidArgumentException("Pilihan {$kol['nama']} tidak valid."),
            // Dropdown konstanta: nilai harus salah satu pilihan (atau nilai lama yang dipertahankan).
            'pilihan' => array_key_exists($v, $kol['pilihan']) || $v === EditorData::nilaiInput($model, $kol)
                ? (is_numeric($v) && ctype_digit($v) && str_contains($kol['tipe'], 'int') ? (int) $v : $v)
                : throw new \InvalidArgumentException("Pilihan {$kol['nama']} tidak valid."),
            'date' => $this->tanggal($v, 'Y-m-d', $kol['nama'])->toDateString(),
            'datetime' => $this->tanggal($v, null, $kol['nama'])->format('Y-m-d H:i:s'),
            'time' => preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $v) ? (strlen($v) === 5 ? "{$v}:00" : $v) : throw new \InvalidArgumentException("Kolom {$kol['nama']} harus jam (HH:MM)."),
            'json' => $this->json($model, $kol['nama'], $v),
            default => $v,
        };
    }

    private function tanggal(string $v, ?string $format, string $nama): Carbon
    {
        $salah = fn () => throw new \InvalidArgumentException("Kolom {$nama} bukan tanggal yang valid.");
        // Ketat: tanggal yang "meluap" (mis. 30 Feb jadi 2 Mar) ditolak, bukan digeser diam-diam.
        if (! preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $v, $m) || ! checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
            $salah();
        }
        try {
            return $format ? Carbon::createFromFormat($format, $v)->startOfDay() : Carbon::parse($v);
        } catch (\Throwable) {
            return $salah();
        }
    }

    private function json(Model $model, string $nama, string $v): mixed
    {
        $data = json_decode($v, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException("Kolom {$nama} harus JSON yang valid (" . json_last_error_msg() . ').');
        }

        // Kolom ber-cast array/json diisi array; selain itu disimpan sebagai teks JSON.
        return $model->hasCast($nama, ['array', 'json', 'collection', 'object']) ? $data : json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function pesanDb(QueryException $e): string
    {
        $info = $e->errorInfo[1] ?? null;

        return match ($info) {
            1451 => 'data ini masih dipakai oleh data lain. Hapus/ubah data terkait lebih dulu.',
            1452 => 'ID yang dirujuk tidak ada.',
            1062 => 'nilai sudah dipakai data lain (harus unik).',
            1048 => 'ada kolom wajib yang kosong.',
            1406 => 'isian terlalu panjang untuk kolom ini.',
            1366, 1292 => 'format isian tidak sesuai jenis kolom.',
            default => 'kesalahan database (' . ($e->errorInfo[2] ?? $e->getMessage()) . ').',
        };
    }

    private function cari(array $k, string $id): Model
    {
        $model = EditorData::model($k);

        return $model->newQuery()->whereKey($id)->firstOrFail();
    }

    /** Rentang tanggal dibuat (?tanggal_dari/?tanggal_sampai) — hanya untuk data yang punya kolom waktu dibuat. */
    private function saringTanggal(Builder $q, array $k, Request $request): Builder
    {
        $model = EditorData::model($k);

        return $model->usesTimestamps() && $model->getCreatedAtColumn()
            ? FilterTanggal::terapkan($q, $model->getTable() . '.' . $model->getCreatedAtColumn(), $request)
            : $q;
    }

    private function hanyaAdmin(): void
    {
        /** @var User $user */
        $user = Auth::user();
        abort_unless($user?->isAdmin(), 403, 'Editor Data khusus Admin.');
    }
}
