<?php

namespace App\Providers;

use App\Models\HariLibur;
use App\Models\LokasiKerja;
use App\Models\Pengumuman;
use App\Models\Unit;
use App\Models\User;
use App\Support\Izin;
use App\Support\PencatatLog;
use App\Support\SidebarHitung;
use App\Support\TugasNotifikasi;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // Master Unit, Lokasi Kerja & Kalender menggantikan daftar di config: semua pemakai config('kepegawaian.unit_kerja'),
        // config('kepegawaian.lokasi_kerja') dan config('hrrab.hari_libur') (hari kerja, absensi, cuti, jadwal rekrutmen) ikut database.
        try {
            config([
                'kepegawaian.unit_kerja' => Unit::daftar(),
                'kepegawaian.lokasi_kerja' => LokasiKerja::daftar(),
                'hrrab.hari_libur' => HariLibur::daftarTanggal(),
            ]);
        } catch (\Throwable) {
            // Basis data belum siap (mis. saat instalasi/migrasi pertama): tetap pakai daftar di config.
        }

        // Log aktivitas: login, logout, dan percobaan login gagal (akun selain superadmin).
        Event::listen(Login::class, fn (Login $e) => $e->user instanceof User
            && PencatatLog::tulis($e->user, 'login', 'Akun', 'Login ke sistem' . (request()->routeIs('google.*') ? ' (Google)' : '')));
        Event::listen(Logout::class, fn (Logout $e) => $e->user instanceof User
            && PencatatLog::tulis($e->user, 'logout', 'Akun', 'Logout dari sistem'));
        Event::listen(Failed::class, fn (Failed $e) => $e->user instanceof User
            && PencatatLog::tulis($e->user, 'login', 'Akun', 'Percobaan masuk gagal (kata sandi salah)', 'gagal'));

        // Setiap permission di katalog = satu gate (route can:..., @can, $user->can()). Sumbernya hanya centang di database.
        foreach ([...Izin::semua(), User::ABILITY_AJUKAN_CUTI, User::ABILITY_AJUKAN_LEMBUR, User::ABILITY_AJUKAN_IZIN_KELUAR] as $ability) {
            Gate::define($ability, fn (User $user) => $user->hasAbility($ability));
        }

        View::composer(['layouts.admin.sidebar', 'layouts.pegawai.sidebar'], function ($view) {
            $user = Auth::user();
            $view->with('hitung', $user instanceof User && in_array($user->role, User::INTERNAL_ROLES, true) ? SidebarHitung::untuk($user) : []);
        });

        View::composer('layouts.pelamar.sidebar', function ($view) {
            $user = Auth::user();
            if ($user instanceof User) {
                TugasNotifikasi::rapikan($user);
            }
            $view->with('hitungPelamar', $user instanceof User ? ['notifikasi' => $user->unreadNotifications()->count()] : []);
        });

        View::composer('layouts.partials.navbar', function ($view) {
            $user = Auth::user();
            if ($user instanceof User) {
                TugasNotifikasi::rapikan($user);
            }

            if (! $user instanceof User) {
                $view->with('lonceng', null);

                return;
            }

            // Pelamar hanya menerima notifikasi pribadi (tidak ada pengumuman internal).
            if (! in_array($user->role, User::INTERNAL_ROLES, true)) {
                $view->with('lonceng', [
                    'notifikasi' => $user->notifications()->latest()->take(6)->get(),
                    'notifikasiBelum' => $user->unreadNotifications()->count(),
                    'pengumuman' => null,
                    'pengumumanBelum' => 0,
                ]);

                return;
            }

            $pengumuman = Pengumuman::untuk($user)->berlaku()
                ->withExists(['pembaca as sudah_dibaca' => fn ($q) => $q->where('users.id', $user->id)])
                ->orderByDesc('penting')->latest()->take(6)->get();

            $view->with('lonceng', [
                'notifikasi' => $user->notifications()->latest()->take(6)->get(),
                'notifikasiBelum' => $user->unreadNotifications()->count(),
                'pengumuman' => $pengumuman,
                'pengumumanBelum' => Pengumuman::untuk($user)->berlaku()->belumDibaca($user)->count(),
            ]);
        });
    }
}
