<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AbsensiImport;
use App\Models\User;
use App\Support\Absensi\ImportAbsensi;
use App\Support\Absensi\PembacaFileAbsen;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Import file absensi dari flashdisk mesin: unggah → pratinjau → simpan.
 * File sementara disimpan di storage privat (local disk) dan dihapus setelah disimpan/kedaluwarsa.
 */
class AbsensiImportController extends Controller
{
    private const FOLDER = 'absensi-import';

    public function index(): View
    {
        return view('admin.absensi.import', [
            'layout' => $this->user()->panelLayout(),
            'riwayat' => AbsensiImport::with('diimportOleh')->latest()->paginate(10),
        ]);
    }

    public function pratinjau(Request $request): View|RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:20480', 'extensions:xls,xlsx,csv,dat,txt'],
        ], [
            'file.extensions' => 'Format file harus .xls, .xlsx, .csv, .dat, atau .txt (hasil unduhan mesin).',
            'file.max' => 'Ukuran file maksimal 20 MB.',
        ]);

        $this->bersihkanFileLama();
        $file = $request->file('file');
        $token = Str::random(32);
        $path = $file->storeAs(self::FOLDER, $token . '.' . strtolower($file->getClientOriginalExtension()), 'local');

        try {
            $hasil = PembacaFileAbsen::baca(Storage::disk('local')->path($path), $file->getClientOriginalName());
        } catch (\RuntimeException $e) {
            Storage::disk('local')->delete($path);

            return back()->with('error', $e->getMessage());
        }

        if ($hasil['baris'] === []) {
            Storage::disk('local')->delete($path);

            return back()->with('error', 'Tidak ada data scan yang bisa dibaca di file ini.');
        }

        session(["absensi_import.{$token}" => ['path' => $path, 'nama' => $file->getClientOriginalName()]]);

        return view('admin.absensi.pratinjau', [
            'layout' => $this->user()->panelLayout(),
            'token' => $token,
            'namaFile' => $file->getClientOriginalName(),
            'r' => ImportAbsensi::pratinjau($hasil),
        ]);
    }

    public function simpan(Request $request): RedirectResponse
    {
        $token = (string) $request->input('token');
        $info = session("absensi_import.{$token}");

        if (! $info || ! Storage::disk('local')->exists($info['path'])) {
            return redirect()->route('admin.absensi.import')->with('error', 'Sesi pratinjau kedaluwarsa. Silakan unggah ulang file.');
        }

        $hasil = PembacaFileAbsen::baca(Storage::disk('local')->path($info['path']), $info['nama']);
        $import = ImportAbsensi::simpan($hasil, $info['nama'], $this->user());

        Storage::disk('local')->delete($info['path']);
        session()->forget("absensi_import.{$token}");

        // Langkah 3 "Selesai": ringkasan + tombol lanjutan ditampilkan di halaman import.
        return redirect()->route('admin.absensi.import')->with('hasil_import', [
            'baru' => $import->jumlah_baru,
            'diperbarui' => $import->jumlah_diperbarui,
            'dilewati' => $import->jumlah_dilewati,
            'dari' => $import->periode_dari?->toDateString(),
            'sampai' => $import->periode_sampai?->toDateString(),
            'belum_dipetakan' => count($import->ringkasan['belum_dipetakan'] ?? []),
        ]);
    }

    /** File pratinjau yang tidak jadi disimpan dihapus setelah 1 hari. */
    private function bersihkanFileLama(): void
    {
        foreach (Storage::disk('local')->files(self::FOLDER) as $f) {
            if (Storage::disk('local')->lastModified($f) < now()->subDay()->getTimestamp()) {
                Storage::disk('local')->delete($f);
            }
        }
    }

    private function user(): User
    {
        /** @var User $user */
        $user = Auth::user();

        return $user;
    }
}
