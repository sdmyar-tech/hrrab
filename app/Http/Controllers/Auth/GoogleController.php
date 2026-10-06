<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleController extends Controller
{
    public static function enabled(): bool
    {
        return filled(config('services.google.client_id')) && filled(config('services.google.client_secret'));
    }

    public function redirect(): RedirectResponse
    {
        abort_unless(self::enabled(), 404);

        return Socialite::driver('google')->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        abort_unless(self::enabled(), 404);

        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('pelamar.login')
                ->with('error', 'Gagal masuk dengan Google. Silakan coba lagi.');
        }

        if (! $googleUser->getEmail()) {
            return redirect()->route('pelamar.login')
                ->with('error', 'Akun Google Anda tidak memiliki email. Silakan daftar manual.');
        }

        $user = User::where('google_id', $googleUser->getId())->first()
            ?? User::where('email', $googleUser->getEmail())->first();

        if ($user && ! $user->isPelamar()) {
            return redirect()->route('pelamar.login')
                ->with('error', 'Email ini terdaftar sebagai akun internal, bukan akun pelamar.');
        }

        $isNew = ! $user;

        if ($isNew) {
            $user = User::create([
                'name' => $googleUser->getName() ?: Str::before($googleUser->getEmail(), '@'),
                'email' => $googleUser->getEmail(),
                'google_id' => $googleUser->getId(),
                'password' => Str::random(40),
                'role' => User::ROLE_PELAMAR,
            ]);
            $user->forceFill(['email_verified_at' => now()])->save();
        } elseif (! $user->google_id) {
            $user->forceFill(['google_id' => $googleUser->getId(), 'email_verified_at' => $user->email_verified_at ?? now()])->save();
        }

        Auth::login($user, true);
        $request->session()->regenerate();

        if ($isNew || ! $user->dataPelamar) {
            return redirect()->route('pelamar.data-pelamar.edit')
                ->with('success', $isNew
                    ? 'Akun berhasil dibuat dengan Google. Silakan lengkapi data pelamar Anda.'
                    : 'Selamat datang, ' . $user->name . '! Silakan lengkapi data pelamar Anda.');
        }

        return redirect($request->session()->pull('lowongan_tujuan') ?? route('pelamar.lamaran.index'))
            ->with('success', 'Selamat datang kembali, ' . $user->name . '!');
    }
}
