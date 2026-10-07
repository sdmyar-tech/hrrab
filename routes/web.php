<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\JabatanController as AdminJabatanController;
use App\Http\Controllers\Admin\KandidatInternalController;
use App\Http\Controllers\Admin\LamaranController as AdminLamaranController;
use App\Http\Controllers\Admin\LowonganController as AdminLowonganController;
use App\Http\Controllers\Admin\PegawaiController as AdminPegawaiController;
use App\Http\Controllers\Admin\PengajuanCutiController as AdminPengajuanCutiController;
use App\Http\Controllers\Admin\PengajuanFormasiController as AdminPengajuanFormasiController;
use App\Http\Controllers\Admin\PengajuanLemburController as AdminPengajuanLemburController;
use App\Http\Controllers\Admin\PenugasanController as AdminPenugasanController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\InterviewJadwalController;
use App\Http\Controllers\JadwalKandidatController;
use App\Http\Controllers\NotifikasiController;
use App\Http\Controllers\PengumumanController;
use App\Http\Controllers\Pegawai\DashboardController as PegawaiDashboardController;
use App\Http\Controllers\Pegawai\PengajuanCutiController as PegawaiPengajuanCutiController;
use App\Http\Controllers\Pegawai\PengajuanLemburController as PegawaiPengajuanLemburController;
use App\Http\Controllers\Pegawai\ProfilController;
use App\Http\Controllers\Pelamar\DataPelamarController;
use App\Http\Controllers\Pelamar\LamaranController as PelamarLamaranController;
use App\Http\Controllers\Public\LowonganController as PublicLowonganController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('lowongan.index');
});

Route::get('/' . config('hrrab.staff_login_path'), [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/' . config('hrrab.staff_login_path'), [LoginController::class, 'login'])->name('login.submit')->middleware('throttle:6,1');

Route::middleware('bukan-pelamar')->group(function () {
    Route::get('/pelamar/login', [LoginController::class, 'showPelamarLoginForm'])->name('pelamar.login');
    Route::post('/pelamar/login', [LoginController::class, 'loginPelamar'])->name('pelamar.login.submit')->middleware('throttle:6,1');

    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register'])->name('register.submit')->middleware('throttle:6,1');

    Route::get('/auth/google/redirect', [GoogleController::class, 'redirect'])->name('google.redirect');
    Route::get('/auth/google/callback', [GoogleController::class, 'callback'])->name('google.callback')->middleware('throttle:10,1');
});

Route::get('/jadwal-interview/{token}', [JadwalKandidatController::class, 'show'])
    ->name('jadwal-kandidat.show')->where('token', '[A-Za-z0-9]{40}')->middleware('throttle:30,1');

Route::prefix('lowongan')->name('lowongan.')->group(function () {
    Route::get('/', [PublicLowonganController::class, 'index'])->name('index');
    Route::get('/{lowongan}', [PublicLowonganController::class, 'show'])->name('show');
});

Route::middleware(['auth', 'nocache'])->group(function () {
    Route::prefix('admin')->name('admin.')->middleware('internal')->group(function () {
        Route::middleware('can:lihat-tabel-karyawan')->group(function () {
            Route::get('/pegawai', [AdminPegawaiController::class, 'index'])->name('pegawai.index');
            Route::get('/pegawai/export', [AdminPegawaiController::class, 'export'])->name('pegawai.export');
        });
        Route::middleware('can:kelola-pegawai')->group(function () {
            Route::get('/pegawai/create', [AdminPegawaiController::class, 'create'])->name('pegawai.create');
            Route::post('/pegawai', [AdminPegawaiController::class, 'store'])->name('pegawai.store');
            Route::get('/pegawai/{pegawai}/edit', [AdminPegawaiController::class, 'edit'])->name('pegawai.edit');
            Route::put('/pegawai/{pegawai}', [AdminPegawaiController::class, 'update'])->name('pegawai.update');
            Route::delete('/pegawai/{pegawai}', [AdminPegawaiController::class, 'destroy'])->name('pegawai.destroy');
        });
        Route::get('/pegawai/{pegawai}', [AdminPegawaiController::class, 'show'])->name('pegawai.show')->middleware('can:lihat-tabel-karyawan');
    });
    Route::prefix('admin')->name('admin.')->middleware('admin')->group(function () {
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

        Route::prefix('users')->name('users.')->middleware('can:kelola-sistem')->group(function () {
            Route::get('/', [AdminUserController::class, 'index'])->name('index');
            Route::get('/export', [AdminUserController::class, 'export'])->name('export');
            Route::get('/create', [AdminUserController::class, 'create'])->name('create');
            Route::post('/', [AdminUserController::class, 'store'])->name('store');
            Route::get('/{user}/edit', [AdminUserController::class, 'edit'])->name('edit');
            Route::put('/{user}', [AdminUserController::class, 'update'])->name('update');
            Route::delete('/{user}', [AdminUserController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('jabatan')->name('jabatan.')->middleware('can:kelola-sistem')->group(function () {
            Route::get('/', [AdminJabatanController::class, 'index'])->name('index');
            Route::get('/export', [AdminJabatanController::class, 'export'])->name('export');
            Route::post('/', [AdminJabatanController::class, 'store'])->name('store');
            Route::put('/{jabatan}', [AdminJabatanController::class, 'update'])->name('update');
            Route::delete('/{jabatan}', [AdminJabatanController::class, 'destroy'])->name('destroy');
        });

Route::middleware('can:kelola-penugasan')->group(function () {
            Route::get('/penugasan', [AdminPenugasanController::class, 'index'])->name('penugasan.index');
            Route::get('/penugasan/export', [AdminPenugasanController::class, 'export'])->name('penugasan.export');
            Route::get('/pegawai/{pegawai}/penugasan/create', [AdminPenugasanController::class, 'create'])->name('penugasan.create');
            Route::post('/pegawai/{pegawai}/penugasan', [AdminPenugasanController::class, 'store'])->name('penugasan.store');
            Route::post('/penugasan/{penugasan}/akhiri', [AdminPenugasanController::class, 'akhiri'])->name('penugasan.akhiri');
            Route::delete('/penugasan/{penugasan}', [AdminPenugasanController::class, 'destroy'])->name('penugasan.destroy');
            Route::middleware('can:kelola-sistem')->group(function () {
                Route::get('/penugasan/{penugasan}/edit', [AdminPenugasanController::class, 'edit'])->name('penugasan.edit');
                Route::put('/penugasan/{penugasan}', [AdminPenugasanController::class, 'update'])->name('penugasan.update');
            });
        });

        Route::prefix('pengajuan-lembur')->name('pengajuan-lembur.')->middleware('can:lihat-laporan')->group(function () {
            Route::get('/', [AdminPengajuanLemburController::class, 'index'])->name('index');
            Route::get('/export', [AdminPengajuanLemburController::class, 'export'])->name('export');
            Route::get('/{pengajuanLembur}', [AdminPengajuanLemburController::class, 'show'])->name('show');
            Route::post('/{pengajuanLembur}/approve', [AdminPengajuanLemburController::class, 'approve'])->name('approve')->middleware('can:approve');
            Route::post('/{pengajuanLembur}/reject', [AdminPengajuanLemburController::class, 'reject'])->name('reject')->middleware('can:approve');
            Route::middleware('can:kelola-sistem')->group(function () {
                Route::put('/{pengajuanLembur}/ubah-admin', [AdminPengajuanLemburController::class, 'ubahAdmin'])->name('ubah-admin');
                Route::delete('/{pengajuanLembur}', [AdminPengajuanLemburController::class, 'destroy'])->name('destroy');
            });
        });

        Route::prefix('pengajuan-cuti')->name('pengajuan-cuti.')->middleware('can:lihat-laporan')->group(function () {
            Route::get('/', [AdminPengajuanCutiController::class, 'index'])->name('index');
            Route::get('/export', [AdminPengajuanCutiController::class, 'export'])->name('export');
            Route::get('/{pengajuanCuti}', [AdminPengajuanCutiController::class, 'show'])->name('show');
            Route::post('/{pengajuanCuti}/approve', [AdminPengajuanCutiController::class, 'approve'])->name('approve')->middleware('can:approve');
            Route::post('/{pengajuanCuti}/reject', [AdminPengajuanCutiController::class, 'reject'])->name('reject')->middleware('can:approve');
            Route::middleware('can:kelola-sistem')->group(function () {
                Route::put('/{pengajuanCuti}/ubah-admin', [AdminPengajuanCutiController::class, 'ubahAdmin'])->name('ubah-admin');
                Route::delete('/{pengajuanCuti}', [AdminPengajuanCutiController::class, 'destroy'])->name('destroy');
            });
        });

Route::prefix('pengajuan-formasi')->name('pengajuan-formasi.')->group(function () {
            Route::get('/', [AdminPengajuanFormasiController::class, 'index'])->name('index');
            Route::get('/export', [AdminPengajuanFormasiController::class, 'export'])->name('export');
            Route::get('/create', [AdminPengajuanFormasiController::class, 'create'])->name('create');
            Route::post('/', [AdminPengajuanFormasiController::class, 'store'])->name('store');
            Route::post('/{pengajuanFormasi}/approve', [AdminPengajuanFormasiController::class, 'approve'])->name('approve');
            Route::post('/{pengajuanFormasi}/reject', [AdminPengajuanFormasiController::class, 'reject'])->name('reject');
            Route::post('/{pengajuanFormasi}/batalkan', [AdminPengajuanFormasiController::class, 'batalkan'])->name('batalkan');
            Route::delete('/{pengajuanFormasi}', [AdminPengajuanFormasiController::class, 'destroy'])->name('destroy')->middleware('can:kelola-sistem');
        });

        Route::prefix('lowongan')->name('lowongan.')->group(function () {
            Route::get('/', [AdminLowonganController::class, 'index'])->name('index');
            Route::get('/export', [AdminLowonganController::class, 'export'])->name('export');
            Route::get('/create', [AdminLowonganController::class, 'create'])->name('create');
            Route::post('/', [AdminLowonganController::class, 'store'])->name('store');
            Route::get('/{lowongan}/edit', [AdminLowonganController::class, 'edit'])->name('edit');
            Route::put('/{lowongan}', [AdminLowonganController::class, 'update'])->name('update');
            Route::post('/{lowongan}/publish', [AdminLowonganController::class, 'publish'])->name('publish');
            Route::post('/{lowongan}/close', [AdminLowonganController::class, 'close'])->name('close');
            Route::post('/{lowongan}/reopen', [AdminLowonganController::class, 'reopen'])->name('reopen');
        });

        Route::prefix('lamaran')->name('lamaran.')->group(function () {
            Route::get('/', [AdminLamaranController::class, 'index'])->name('index');
            Route::get('/export', [AdminLamaranController::class, 'export'])->name('export');

            Route::get('/internal/create', [KandidatInternalController::class, 'create'])->name('internal.create');
            Route::post('/internal', [KandidatInternalController::class, 'store'])->name('internal.store');

            Route::get('/{lamaran}', [AdminLamaranController::class, 'show'])->name('show');
            Route::post('/{lamaran}/verifikasi/terima', [AdminLamaranController::class, 'verifikasiTerima'])->name('verifikasi.terima');
            Route::post('/{lamaran}/verifikasi/tolak', [AdminLamaranController::class, 'verifikasiTolak'])->name('verifikasi.tolak');
            Route::post('/{lamaran}/interview', [AdminLamaranController::class, 'interviewStore'])->name('interview.store');
            Route::put('/{lamaran}/interview/{interview}', [AdminLamaranController::class, 'interviewUpdate'])->name('interview.update');
            Route::post('/{lamaran}/interview/{interview}/hasil', [AdminLamaranController::class, 'interviewHasil'])->name('interview.hasil');
            Route::post('/{lamaran}/interview/{interview}/cancel', [AdminLamaranController::class, 'interviewCancel'])->name('interview.cancel');
            Route::post('/{lamaran}/interview-selesai', [AdminLamaranController::class, 'interviewSelesai'])->name('interview.selesai');
            Route::post('/{lamaran}/screening', [AdminLamaranController::class, 'screeningStore'])->name('screening.store');
            Route::post('/{lamaran}/screening/{screening}/hasil', [AdminLamaranController::class, 'screeningHasil'])->name('screening.hasil');
            Route::post('/{lamaran}/kontrak', [AdminLamaranController::class, 'kontrakStore'])->name('kontrak.store');
            Route::put('/{lamaran}/kontrak/{kontrak}', [AdminLamaranController::class, 'kontrakUpdate'])->name('kontrak.update');
            Route::post('/{lamaran}/rekrut', [AdminLamaranController::class, 'rekrut'])->name('rekrut');
            Route::post('/{lamaran}/tolak', [AdminLamaranController::class, 'tolak'])->name('tolak');
            Route::put('/{lamaran}/ubah-admin', [AdminLamaranController::class, 'ubahAdmin'])->name('ubah-admin')->middleware('can:kelola-sistem');
        });
    });

    Route::prefix('pegawai')->name('pegawai.')->middleware('can:mengajukan')->group(function () {
        Route::middleware('can:ajukan-lembur')->group(function () {
            Route::get('/pengajuan-lembur', [PegawaiPengajuanLemburController::class, 'index'])->name('pengajuan-lembur');
            Route::post('/pengajuan-lembur', [PegawaiPengajuanLemburController::class, 'store'])->name('pengajuan-lembur.store');
        });

        Route::middleware('can:ajukan-cuti')->group(function () {
            Route::get('/pengajuan-cuti', [PegawaiPengajuanCutiController::class, 'index'])->name('pengajuan-cuti');
            Route::post('/pengajuan-cuti', [PegawaiPengajuanCutiController::class, 'store'])->name('pengajuan-cuti.store');
        });
    });

Route::prefix('pegawai')->name('pegawai.')->middleware('pegawai')->group(function () {
        Route::get('/dashboard', [PegawaiDashboardController::class, 'index'])->name('dashboard');
        Route::get('/data-pegawai', [ProfilController::class, 'dataPegawai'])->name('data-pegawai');
        Route::get('/pengkinian-data', [ProfilController::class, 'pengkinianData'])->name('pengkinian-data');
        Route::post('/pengkinian-data', [ProfilController::class, 'storePengkinianData'])->name('pengkinian-data.store');
    });

Route::prefix('pelamar')->name('pelamar.')->middleware('pelamar')->group(function () {
        Route::get('/data-pelamar', [DataPelamarController::class, 'edit'])->name('data-pelamar.edit');
        Route::post('/data-pelamar', [DataPelamarController::class, 'update'])->name('data-pelamar.update');

        Route::get('/lamaran', [PelamarLamaranController::class, 'index'])->name('lamaran.index');
        Route::get('/lamaran/{lamaran}', [PelamarLamaranController::class, 'show'])->name('lamaran.show');
        Route::post('/lowongan/{lowongan}/apply', [PelamarLamaranController::class, 'apply'])->name('lamaran.apply')->middleware('throttle:20,1');
        Route::post('/lamaran/{lamaran}/tolak', [PelamarLamaranController::class, 'tolak'])->name('lamaran.tolak');
    });

    Route::get('/interview-saya', [InterviewJadwalController::class, 'milikSaya'])->name('interview.saya');

    Route::middleware('internal')->group(function () {
        Route::get('/pengumuman', [PengumumanController::class, 'index'])->name('pengumuman.index');
        Route::post('/pengumuman/baca-semua', [PengumumanController::class, 'bacaSemua'])->name('pengumuman.baca-semua');
        Route::middleware('can:kelola-pengumuman')->group(function () {
            Route::get('/pengumuman/create', [PengumumanController::class, 'create'])->name('pengumuman.create');
            Route::get('/pengumuman/export', [PengumumanController::class, 'export'])->name('pengumuman.export');
            Route::post('/pengumuman', [PengumumanController::class, 'store'])->name('pengumuman.store');
            Route::get('/pengumuman/{pengumuman}/edit', [PengumumanController::class, 'edit'])->name('pengumuman.edit');
            Route::put('/pengumuman/{pengumuman}', [PengumumanController::class, 'update'])->name('pengumuman.update');
            Route::delete('/pengumuman/{pengumuman}', [PengumumanController::class, 'destroy'])->name('pengumuman.destroy');
        });

        Route::middleware('can:kirim-notifikasi')->group(function () {
            Route::get('/notifikasi/kirim', [NotifikasiController::class, 'kirimForm'])->name('notifikasi.kirim');
            Route::post('/notifikasi/kirim', [NotifikasiController::class, 'kirim'])->name('notifikasi.kirim.store')->middleware('throttle:10,1');
        });
    });

    // Notifikasi pribadi: semua akun login (internal & pelamar).
    Route::get('/notifikasi', [NotifikasiController::class, 'index'])->name('notifikasi.index');
    Route::get('/notifikasi/ringkas', [NotifikasiController::class, 'ringkas'])->name('notifikasi.ringkas');
    Route::post('/notifikasi/baca-semua', [NotifikasiController::class, 'bacaSemua'])->name('notifikasi.baca-semua');
    Route::get('/notifikasi/{id}', [NotifikasiController::class, 'buka'])->name('notifikasi.buka');

    Route::get('/profile', [LoginController::class, 'profile'])->name('profile');
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
});
