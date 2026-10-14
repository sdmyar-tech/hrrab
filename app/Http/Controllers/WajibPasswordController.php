<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Wajib ganti password:
 * - Kepala SDM/Admin menyalakan/mematikan penanda pada akun pegawai.
 * - Pemilik akun yang ditandai hanya bisa membuka halaman ganti password (dijaga middleware PaksaGantiPassword).
 */
class WajibPasswordController extends Controller
{
    public function halaman(): View|RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();
        if (! $user->wajib_ganti_password) {
            return redirect()->to($this->beranda($user));
        }

        return view('akun.wajib-ganti-password', ['user' => $user]);
    }

    public function simpan(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();
        if (! $user->wajib_ganti_password) {
            return redirect()->to($this->beranda($user));
        }

        $request->validate([
            'password_lama' => ['required', 'string', 'current_password'],
            'password' => ['required', 'string', 'confirmed', Password::min(8)->letters()->numbers(), 'different:password_lama'],
        ], [
            'password_lama.required' => 'Isi password saat ini.',
            'password_lama.current_password' => 'Password saat ini salah.',
            'password.required' => 'Isi password baru.',
            'password.confirmed' => 'Konfirmasi password baru tidak sama.',
            'password.different' => 'Password baru harus berbeda dari password lama.',
            'password.min' => 'Password baru minimal 8 karakter.',
            'password.letters' => 'Password baru harus mengandung huruf.',
            'password.numbers' => 'Password baru harus mengandung angka.',
        ]);

        $user->forceFill([
            'password' => $request->input('password'),
            'wajib_ganti_password' => false,
            'password_diganti_pada' => now(),
        ])->save();

        $request->session()->regenerate();

        return redirect()->to($this->beranda($user))->with('success', 'Password berhasil diganti. Silakan lanjutkan.');
    }

    /** Kepala SDM/Admin: nyalakan/matikan wajib ganti password untuk satu akun. */
    public function atur(Request $request, User $user): RedirectResponse
    {
        /** @var User $pengatur */
        $pengatur = Auth::user();
        $v = $request->validate(['wajib' => ['required', 'boolean']]);

        $tolak = match (true) {
            $user->id === $pengatur->id => 'Tidak bisa untuk akun Anda sendiri.',
            $user->isPelamar() => 'Hanya untuk akun pegawai, bukan akun pelamar.',
            $user->isAdmin() && ! $pengatur->isAdmin() => 'Hanya Admin yang bisa mengatur akun Admin.',
            default => null,
        };
        $kembali = \App\Support\EditorData::kembaliAman($request->input('kembali'));
        $balik = $kembali ? redirect()->to($kembali) : back();

        if ($tolak) {
            return $balik->with('error', $tolak);
        }

        $user->forceFill(['wajib_ganti_password' => (bool) $v['wajib']])->save();

        return $balik->with('success', $v['wajib']
            ? "{$user->name} wajib mengganti password saat membuka sistem berikutnya."
            : "Kewajiban ganti password {$user->name} dimatikan.");
    }

    private function beranda(User $user): string
    {
        return match (true) {
            $user->isPelamar() => route('pelamar.lamaran.index'),
            $user->isStaff() => route('pegawai.dashboard'),
            default => route('admin.dashboard'),
        };
    }
}
