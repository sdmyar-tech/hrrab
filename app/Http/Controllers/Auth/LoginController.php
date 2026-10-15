<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function showLoginForm(): View
    {
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
            'email'    => ['required', 'email'],
            'password' => ['required', 'string', 'min:6'],
        ], [
            'email.required'    => 'Email wajib diisi.',
            'email.email'       => 'Format email tidak valid.',
            'password.required' => 'Password wajib diisi.',
            'password.min'      => 'Password minimal 6 karakter.',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return redirect()->back()
                ->withInput($request->only('email'))
                ->with('error', 'Email atau password yang Anda masukkan salah.');
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
        // Halaman pertama setelah login: submenu sidebar dimulai dalam keadaan tertutup.
        $request->session()->put('baru_masuk', true);

        // Datang dari scan QR absen acara sebelum login → kembali ke halaman absen itu.
        if (! $isGuest && ($tujuanAcara = $request->session()->pull('acara_tujuan'))) {
            return redirect($tujuanAcara)->with('success', 'Selamat datang, ' . $user->name . '! Lanjutkan absen acara.');
        }

        if ($user->isStaff()) {
            return redirect()
                ->route('pegawai.dashboard')
                ->with('success', 'Selamat datang, ' . $user->name . '! Anda login sebagai Staff.');
        }

        if (in_array($user->role, User::INTERNAL_ROLES, true)) {
            return redirect()
                ->route('admin.dashboard')
                ->with('success', 'Selamat datang, ' . $user->name . '! Anda login sebagai ' . $user->roleLabel() . '.');
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
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        $isGuest = (bool) $user?->isPelamar();

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route($isGuest ? 'pelamar.login' : 'login')
            ->with('success', 'Anda telah berhasil logout.');
    }

    public function profile(): View|RedirectResponse
    {
        if (! Auth::check()) {
            return redirect()->route('login');
        }

        /** @var \App\Models\User|null $user */
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
