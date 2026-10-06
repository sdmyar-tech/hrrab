<?php

namespace App\Providers;

use App\Models\Pengumuman;
use App\Models\User;
use App\Support\SidebarHitung;
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
        $abilities = array_unique([
            ...array_merge(...array_values(User::ABILITIES)),
            User::ABILITY_AJUKAN_CUTI,
            User::ABILITY_AJUKAN_LEMBUR,
        ]);

        foreach ($abilities as $ability) {
            Gate::define($ability, fn (User $user) => $user->hasAbility($ability));
        }

        View::composer(['layouts.admin.sidebar', 'layouts.pegawai.sidebar'], function ($view) {
            $user = Auth::user();
            $view->with('hitung', $user instanceof User && in_array($user->role, User::INTERNAL_ROLES, true) ? SidebarHitung::untuk($user) : []);
        });

        View::composer('layouts.partials.navbar', function ($view) {
            $user = Auth::user();

            if (! $user instanceof User || ! in_array($user->role, User::INTERNAL_ROLES, true)) {
                $view->with('lonceng', null);

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
