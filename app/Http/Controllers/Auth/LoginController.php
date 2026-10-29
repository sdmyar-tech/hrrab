<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\PortalStaf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function showLoginForm(): View|RedirectResponse
    {
        // Sudah login: langsung ke panelnya (akun wajib ganti password tetap dihadang PaksaGantiPassword).
        /** @var User|null $user */
        $user = Auth::user();
        if ($user) {
            return redirect()->route($user->dashboardRoute());
        }

        return view('auth.login');
    }

    public function showPelamarLoginForm(): View
    {
        return view('auth.login-pelamar');
    }

    public function login(Request $request): RedirectResponse
    {
        return $this->attempt($request, staffPortal: true);
    }

    public function loginPelamar(Request $request): RedirectResponse
    {
        return $this->attempt($request, staffPortal: false);
    }

    private function attempt(Request $request, bool $staffPortal): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:6'],
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'password.required' => 'Kata sandi wajib diisi.',
            'password.min' => 'Kata sandi minimal 6 karakter.',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return redirect()->back()
                ->withInput($request->only('email'))
                ->with('error', 'Email atau kata sandi yang Anda masukkan salah.');
        }

        $isGuest = $user->isPelamar();

        if ($staffPortal && $isGuest) {
            return redirect()->back()
                ->withInput($request->only('email'))
                ->with('error', 'Akun pelamar wajib login lewat halaman Login Pelamar.');
        }

        if (! $staffPortal && ! $isGuest) {
            return redirect()->back()
                ->withInput($request->only('email'))
                ->with('error', 'Akun staff wajib login lewat halaman Login Sistem HR Admin.');
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        // Datang dari scan QR absen acara sebelum login → kembali ke halaman absen itu.
        if (! $isGuest && ($tujuanAcara = $request->session()->pull('acara_tujuan'))) {
            return redirect($tujuanAcara)->with('success', 'Selamat datang, ' . $user->name . '! Lanjutkan absen acara.');
        }

        if (in_array($user->role, User::INTERNAL_ROLES, true)) {
            PortalStaf::tandai();
            // Kembali ke halaman internal yang tadi dibuka saat sesi habis (bukan area pelamar).
            $tujuan = $request->session()->pull('url.intended');
            $tujuan = $tujuan && ! str_starts_with(trim((string) parse_url($tujuan, PHP_URL_PATH), '/'), 'pelamar') ? $tujuan : route($user->dashboardRoute());

            return redirect()->to($tujuan)
                ->with('success', 'Selamat datang, ' . $user->name . '! Anda login sebagai ' . ($user->isStaff() ? 'Staff' : $user->roleLabel()) . '.');
        }

        if ($isGuest) {
            $tujuan = $request->session()->pull('lowongan_tujuan');

            return redirect($tujuan ?? route('pelamar.lamaran.index'))
                ->with('success', 'Selamat datang, ' . $user->name . '!' . ($tujuan ? ' Silakan lanjutkan lamaran Anda.' : ''));
        }

        return redirect()->route('login')->with('error', 'Role tidak dikenali.');
    }

    public function logout(Request $request): RedirectResponse
    {
        /** @var User|null $user */
        $user = Auth::user();
        // Sesi sudah habis (tidak ada user): tujuan ditebak dari penanda portal/halaman asal.
        $tujuan = match (true) {
            $user === null => PortalStaf::halamanLogin($request),
            $user->isPelamar() => route('pelamar.login'),
            default => route('login'),
        };

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->to($tujuan)->with('success', 'Anda telah berhasil logout.');
    }

    public function profile(): View|RedirectResponse
    {
        if (! Auth::check()) {
            return redirect()->route('login');
        }

        /** @var User|null $user */
        $user = Auth::user();

        if (! $user) {
            return redirect()->route('login');
        }

        $user->load([
            'dataPegawai',
            'dataPegawai.kepegawaian',
            'dataPegawai.kontakDarurat',
            'dataPegawai.bpjs',
            'dataPegawai.dokumen',
        ]);

        return view('profile', compact('user'));
    }
}
