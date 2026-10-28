<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\Berkas;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Membuka dokumen pribadi (KTP, KK, CV, ijazah, foto) hanya untuk pemilik & pihak yang berhak. */
class BerkasController extends Controller
{
    public function lihat(string $path): StreamedResponse
    {
        /** @var User $user */
        $user = Auth::user();
        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('public');

        abort_if(str_contains($path, '..') || str_contains($path, '\\') || ! Berkas::dilindungi($path), 404);
        abort_unless($disk->exists($path), 404);
        abort_unless(Berkas::bolehLihat($user, $path), 403);

        return $disk->response($path, basename($path), [
            // Foto boleh di-cache browser (tampil berulang di daftar); dokumen lain tidak disimpan di cache.
            'Cache-Control' => Berkas::foto($path) ? 'private, max-age=3600' : 'private, no-store, max-age=0',
            // Unggahan dibatasi PDF/JPG/PNG/DOC: nosniff mencegah browser menafsirkannya sebagai HTML/skrip.
            // (Tanpa CSP "sandbox": penampil PDF bawaan Chrome menolak dibuka di konteks sandbox.)
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
