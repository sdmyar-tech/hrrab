<?php

namespace App\Providers;

use App\Models\Pengumuman;
use App\Models\User;
use App\Support\PencatatLog;
use App\Support\SidebarHitung;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Log aktivitas: login, logout, dan percobaan login gagal (akun selain superadmin).
        Event::listen(Login::class, fn (Login $e) => $e->user instanceof User
            && PencatatLog::tulis($e->user, 'login', 'Akun', 'Login ke sistem' . (request()->routeIs('google.*') ? ' (Google)' : '')));
        Event::listen(Logout::class, fn (Logout $e) => $e->user instanceof User
            && PencatatLog::tulis($e->user, 'logout', 'Akun', 'Logout dari sistem'));
        Event::listen(Failed::class, fn (Failed $e) => $e->user instanceof User
            && PencatatLog::tulis($e->user, 'login', 'Akun', 'Percobaan login gagal (password salah)', 'gagal'));

        $abilities = array_unique([
            ...array_merge(...array_values(User::ABILITIES)),
            User::ABILITY_AJUKAN_CUTI,
            User::ABILITY_AJUKAN_LEMBUR,
        ]);

        foreach ($abilities as $ability) {
            Gate::define($ability, fn (User $user) => $user->hasAbility($ability));
        }
        // Menyetujui/menolak cuti & lembur: penyetuju sesuai tahap, atau Admin (boleh di tahap mana pun).
        Gate::define('proses-pengajuan', fn (User $user) => $user->hasAbility(User::ABILITY_APPROVE) || $user->isAdmin());
        // Atur alur persetujuan/atasan pegawai & paksa ganti password akun: hanya Kepala SDM dan Admin.
        Gate::define('atur-akun-pegawai', fn (User $user) => $user->isAdmin() || $user->isKepalaSdm());

        View::composer(['layouts.admin.sidebar', 'layouts.pegawai.sidebar'], function ($view) {
            $user = Auth::user();
            $view->with('hitung', $user instanceof User && in_array($user->role, User::INTERNAL_ROLES, true) ? SidebarHitung::untuk($user) : []);
        });

        View::composer('layouts.pelamar.sidebar', function ($view) {
            $user = Auth::user();
            $view->with('hitungPelamar', $user instanceof User ? ['notifikasi' => $user->unreadNotifications()->count()] : []);
        });

        View::composer('layouts.partials.navbar', function ($view) {
            $user = Auth::user();

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
