<?php

use App\Http\Controllers\AbsenAcaraController;
use App\Http\Controllers\AbsensiSayaController;
use App\Http\Controllers\Admin\AbsensiController as AdminAbsensiController;
use App\Http\Controllers\Admin\AbsensiImportController as AdminAbsensiImportController;
use App\Http\Controllers\Admin\AbsensiPengaturanController as AdminAbsensiPengaturanController;
use App\Http\Controllers\Admin\AlurPegawaiController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\EditorDataController as AdminEditorDataController;
use App\Http\Controllers\Admin\EvaluasiKontrakController as AdminEvaluasiKontrakController;
use App\Http\Controllers\Admin\IzinAbsensiController as AdminIzinAbsensiController;
use App\Http\Controllers\Admin\IzinKeluarController as AdminIzinKeluarController;
use App\Http\Controllers\Admin\JabatanController as AdminJabatanController;
use App\Http\Controllers\Admin\KandidatInternalController;
use App\Http\Controllers\Admin\LamaranController as AdminLamaranController;
use App\Http\Controllers\Admin\LogAktivitasController as AdminLogAktivitasController;
use App\Http\Controllers\Admin\LowonganController as AdminLowonganController;
use App\Http\Controllers\Admin\PegawaiController as AdminPegawaiController;
use App\Http\Controllers\Admin\PengajuanCutiController as AdminPengajuanCutiController;
use App\Http\Controllers\Admin\PengajuanFormasiController as AdminPengajuanFormasiController;
use App\Http\Controllers\Admin\PengajuanLemburController as AdminPengajuanLemburController;
use App\Http\Controllers\Admin\PenugasanController as AdminPenugasanController;
use App\Http\Controllers\Admin\SuratPeringatanController as AdminSuratPeringatanController;
use App\Http\Controllers\Admin\UnitController as AdminUnitController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\EksporController;
use App\Http\Controllers\InterviewJadwalController;
use App\Http\Controllers\JadwalKandidatController;
use App\Http\Controllers\NotifikasiController;
use App\Http\Controllers\Pegawai\DashboardController as PegawaiDashboardController;
use App\Http\Controllers\Pegawai\IzinKeluarController as PegawaiIzinKeluarController;
use App\Http\Controllers\Pegawai\PengajuanCutiController as PegawaiPengajuanCutiController;
use App\Http\Controllers\Pegawai\PengajuanLemburController as PegawaiPengajuanLemburController;
use App\Http\Controllers\Pegawai\ProfilController;
use App\Http\Controllers\Pelamar\DataPelamarController;
use App\Http\Controllers\Pelamar\LamaranController as PelamarLamaranController;
use App\Http\Controllers\PengumumanController;
use App\Http\Controllers\Public\LowonganController as PublicLowonganController;
use App\Http\Controllers\WajibPasswordController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('lowongan.index');
});

// Tujuan tombol "Beranda" di halaman error: dashboard sesuai role bila login, selain itu halaman lowongan.
Route::get('/beranda', function () {
    $user = auth()->user();

    return redirect()->route($user ? $user->dashboardRoute() : 'lowongan.index');
})->name('beranda');

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

// Tujuan QR absen acara. Di luar grup auth agar tamu melihat pesan 'masuk dulu' (bukan 404).
Route::get('/acara/{pengumuman}/absen', [AbsenAcaraController::class, 'scan'])->name('acara.absen')->middleware('throttle:30,1');

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
        Route::get('/pegawai/{pegawai}/export', [AdminPegawaiController::class, 'exportDetail'])->name('pegawai.export-detail')->middleware('can:lihat-tabel-karyawan');

        // Absensi (mesin fingerprint): lihat = Kepala Bagian (unitnya) & pengelola; kelola = Kepala SDM, Admin, staf SDM.
        Route::prefix('absensi')->name('absensi.')->group(function () {
            Route::middleware('can:lihat-absensi')->group(function () {
                Route::get('/', [AdminAbsensiController::class, 'harian'])->name('harian');
                Route::get('/rekap', [AdminAbsensiController::class, 'rekap'])->name('rekap');
                Route::get('/rekap/export', [AdminAbsensiController::class, 'rekapExport'])->name('rekap.export');
                Route::get('/pegawai/{pegawai}', [AdminAbsensiController::class, 'pegawai'])->name('pegawai');
            });
            Route::middleware('can:kelola-absensi')->group(function () {
                Route::post('/pegawai/{pegawai}/koreksi', [AdminAbsensiController::class, 'koreksi'])->name('koreksi');
                Route::get('/import', [AdminAbsensiImportController::class, 'index'])->name('import');
                Route::post('/import/pratinjau', [AdminAbsensiImportController::class, 'pratinjau'])->name('import.pratinjau');
                Route::post('/import/simpan', [AdminAbsensiImportController::class, 'simpan'])->name('import.simpan');
                Route::delete('/import/{import}', [AdminAbsensiImportController::class, 'hapus'])->name('import.hapus');
                Route::get('/pengaturan', [AdminAbsensiPengaturanController::class, 'index'])->name('pengaturan');
                Route::post('/pengaturan/massal', [AdminAbsensiPengaturanController::class, 'massal'])->name('pengaturan.massal');
                Route::put('/pengaturan/pegawai/{pegawai}', [AdminAbsensiPengaturanController::class, 'updatePegawai'])->name('pengaturan.pegawai');
                Route::post('/pengaturan/pegawai/{pegawai}/ikut', [AdminAbsensiPengaturanController::class, 'ikut'])->name('pengaturan.ikut');
                Route::post('/pengaturan/petakan', [AdminAbsensiPengaturanController::class, 'petakan'])->name('pengaturan.petakan');
                Route::post('/pengaturan/aktifkan-daftar', [AdminAbsensiPengaturanController::class, 'aktifkanDaftar'])->name('pengaturan.aktifkan-daftar');
                Route::get('/izin', [AdminIzinAbsensiController::class, 'index'])->name('izin');
                Route::post('/izin', [AdminIzinAbsensiController::class, 'store'])->name('izin.store');
                Route::delete('/izin/{izin}', [AdminIzinAbsensiController::class, 'destroy'])->name('izin.destroy');
            });
        });
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

        Route::prefix('log-aktivitas')->name('log.')->middleware('can:kelola-sistem')->group(function () {
            Route::get('/', [AdminLogAktivitasController::class, 'index'])->name('index');
            Route::get('/export', [AdminLogAktivitasController::class, 'export'])->name('export');
            Route::get('/arsip/{nama}', [AdminLogAktivitasController::class, 'unduhArsip'])->name('arsip')->where('nama', '[A-Za-z0-9\-\.]+');
        });

        // Editor Data: Admin bisa mengubah/menghapus data apa pun langsung (tercatat di log).
        Route::prefix('data')->name('data.')->middleware('can:kelola-sistem')->group(function () {
            Route::get('/', [AdminEditorDataController::class, 'index'])->name('index');
            Route::get('/{entitas}', [AdminEditorDataController::class, 'daftar'])->name('daftar')->where('entitas', '[a-z_]+');
            Route::get('/{entitas}/tambah', [AdminEditorDataController::class, 'create'])->name('create')->where('entitas', '[a-z_]+');
            Route::post('/{entitas}', [AdminEditorDataController::class, 'store'])->name('store')->where('entitas', '[a-z_]+');
            Route::get('/{entitas}/{id}/edit', [AdminEditorDataController::class, 'edit'])->name('edit')->where('entitas', '[a-z_]+');
            Route::put('/{entitas}/{id}', [AdminEditorDataController::class, 'update'])->name('update')->where('entitas', '[a-z_]+');
            Route::delete('/{entitas}/{id}', [AdminEditorDataController::class, 'destroy'])->name('destroy')->where('entitas', '[a-z_]+');
        });

        // Master Unit: Kepala SDM & Admin mengatur koordinator (terhubung ke rangkap); tambah/ubah/hapus unit khusus Admin.
        Route::prefix('unit')->name('unit.')->middleware('can:kelola-penugasan')->group(function () {
            Route::get('/', [AdminUnitController::class, 'index'])->name('index');
            Route::post('/{unit}/koordinator', [AdminUnitController::class, 'koordinator'])->name('koordinator');
            Route::middleware('can:kelola-sistem')->group(function () {
                Route::post('/', [AdminUnitController::class, 'store'])->name('store');
                Route::put('/{unit}', [AdminUnitController::class, 'update'])->name('update');
                Route::delete('/{unit}', [AdminUnitController::class, 'destroy'])->name('destroy');
            });
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
            Route::post('/{pengajuanLembur}/approve', [AdminPengajuanLemburController::class, 'approve'])->name('approve')->middleware('can:proses-pengajuan');
            Route::post('/{pengajuanLembur}/reject', [AdminPengajuanLemburController::class, 'reject'])->name('reject')->middleware('can:proses-pengajuan');
            Route::middleware('can:kelola-sistem')->group(function () {
                Route::put('/{pengajuanLembur}/ubah-admin', [AdminPengajuanLemburController::class, 'ubahAdmin'])->name('ubah-admin');
                Route::delete('/{pengajuanLembur}', [AdminPengajuanLemburController::class, 'destroy'])->name('destroy');
            });
        });

        // Izin keluar saat jam kerja: alur persetujuan sama dengan cuti (atasan langsung → Kepala SDM).
        Route::prefix('izin-keluar')->name('izin-keluar.')->middleware('can:lihat-laporan')->group(function () {
            Route::get('/', [AdminIzinKeluarController::class, 'index'])->name('index');
            Route::get('/export', [AdminIzinKeluarController::class, 'export'])->name('export');
            Route::get('/{izinKeluar}', [AdminIzinKeluarController::class, 'show'])->name('show');
            Route::post('/{izinKeluar}/approve', [AdminIzinKeluarController::class, 'approve'])->name('approve')->middleware('can:proses-pengajuan');
            Route::post('/{izinKeluar}/reject', [AdminIzinKeluarController::class, 'reject'])->name('reject')->middleware('can:proses-pengajuan');
            Route::middleware('can:kelola-sistem')->group(function () {
                Route::put('/{izinKeluar}/ubah-admin', [AdminIzinKeluarController::class, 'ubahAdmin'])->name('ubah-admin');
                Route::delete('/{izinKeluar}', [AdminIzinKeluarController::class, 'destroy'])->name('destroy');
            });
        });

        Route::prefix('pengajuan-cuti')->name('pengajuan-cuti.')->middleware('can:lihat-laporan')->group(function () {
            Route::get('/', [AdminPengajuanCutiController::class, 'index'])->name('index');
            Route::get('/export', [AdminPengajuanCutiController::class, 'export'])->name('export');
            Route::get('/{pengajuanCuti}', [AdminPengajuanCutiController::class, 'show'])->name('show');
            Route::post('/{pengajuanCuti}/approve', [AdminPengajuanCutiController::class, 'approve'])->name('approve')->middleware('can:proses-pengajuan');
            Route::post('/{pengajuanCuti}/reject', [AdminPengajuanCutiController::class, 'reject'])->name('reject')->middleware('can:proses-pengajuan');
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

        // Evaluasi kontrak (H-30): atasan mengisi, Kepala SDM memutuskan. Hak akses dicek di controller.
        Route::prefix('evaluasi-kontrak')->name('evaluasi-kontrak.')->group(function () {
            Route::get('/', [AdminEvaluasiKontrakController::class, 'index'])->name('index');
            Route::get('/{evaluasiKontrak}', [AdminEvaluasiKontrakController::class, 'show'])->name('show');
            Route::post('/{evaluasiKontrak}/isi', [AdminEvaluasiKontrakController::class, 'isi'])->name('isi');
            Route::post('/{evaluasiKontrak}/putuskan', [AdminEvaluasiKontrakController::class, 'putuskan'])->name('putuskan');
            Route::post('/{evaluasiKontrak}/jadwal-ttd', [AdminEvaluasiKontrakController::class, 'jadwalTtd'])->name('jadwal-ttd');
        });

        // Surat peringatan bulanan (SP1–SP3 per bulan). Hak akses dicek di controller.
        Route::prefix('surat-peringatan')->name('surat-peringatan.')->group(function () {
            Route::get('/', [AdminSuratPeringatanController::class, 'index'])->name('index');
            Route::post('/', [AdminSuratPeringatanController::class, 'store'])->name('store');
            Route::delete('/{suratPeringatan}', [AdminSuratPeringatanController::class, 'destroy'])->name('destroy');
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
            Route::get('/export-jadwal', [AdminLamaranController::class, 'exportJadwal'])->name('export-jadwal');

            Route::get('/internal/create', [KandidatInternalController::class, 'create'])->name('internal.create');
            Route::get('/internal/bagan', [KandidatInternalController::class, 'bagan'])->name('internal.bagan');
            Route::post('/internal', [KandidatInternalController::class, 'store'])->name('internal.store');

            Route::get('/{lamaran}', [AdminLamaranController::class, 'show'])->name('show');
            Route::post('/{lamaran}/verifikasi/terima', [AdminLamaranController::class, 'verifikasiTerima'])->name('verifikasi.terima');
            Route::post('/{lamaran}/verifikasi/tolak', [AdminLamaranController::class, 'verifikasiTolak'])->name('verifikasi.tolak');
            Route::post('/{lamaran}/interview', [AdminLamaranController::class, 'interviewStore'])->name('interview.store');
            Route::put('/{lamaran}/interview/{interview}', [AdminLamaranController::class, 'interviewUpdate'])->name('interview.update');
            Route::post('/{lamaran}/interview/{interview}/hasil', [AdminLamaranController::class, 'interviewHasil'])->name('interview.hasil');
            Route::post('/{lamaran}/interview/{interview}/cancel', [AdminLamaranController::class, 'interviewCancel'])->name('interview.cancel');
            Route::delete('/{lamaran}/interview/{interview}', [AdminLamaranController::class, 'interviewDestroy'])->name('interview.destroy');
            Route::post('/{lamaran}/interview-selesai', [AdminLamaranController::class, 'interviewSelesai'])->name('interview.selesai');
            Route::post('/{lamaran}/screening', [AdminLamaranController::class, 'screeningStore'])->name('screening.store');
            Route::post('/{lamaran}/screening/{screening}/hasil', [AdminLamaranController::class, 'screeningHasil'])->name('screening.hasil');
            Route::put('/{lamaran}/screening/{screening}', [AdminLamaranController::class, 'screeningUpdate'])->name('screening.update');
            Route::post('/{lamaran}/screening/{screening}/cancel', [AdminLamaranController::class, 'screeningCancel'])->name('screening.cancel');
            Route::delete('/{lamaran}/screening/{screening}', [AdminLamaranController::class, 'screeningDestroy'])->name('screening.destroy');
            Route::post('/{lamaran}/kontrak', [AdminLamaranController::class, 'kontrakStore'])->name('kontrak.store');
            Route::put('/{lamaran}/kontrak/{kontrak}', [AdminLamaranController::class, 'kontrakUpdate'])->name('kontrak.update');
            Route::delete('/{lamaran}/kontrak/{kontrak}', [AdminLamaranController::class, 'kontrakDestroy'])->name('kontrak.destroy');
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

        Route::middleware('can:ajukan-izin-keluar')->group(function () {
            Route::get('/izin-keluar', [PegawaiIzinKeluarController::class, 'index'])->name('izin-keluar');
            Route::post('/izin-keluar', [PegawaiIzinKeluarController::class, 'store'])->name('izin-keluar.store');
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

    Route::get('/absensi-saya', [AbsensiSayaController::class, 'index'])->name('absensi.saya')->middleware('internal');
    // Tiket izin keluar: pemilik, atau penyetuju/Admin yang berhak melihat (dicek di controller).
    Route::get('/izin-keluar/{izin}/tiket', [PegawaiIzinKeluarController::class, 'tiket'])->name('pegawai.izin-keluar.tiket')->middleware('internal');
    Route::get('/interview-saya', [InterviewJadwalController::class, 'milikSaya'])->name('interview.saya');
    Route::get('/interview-saya/export', [EksporController::class, 'jadwalInterviewSaya'])->name('interview.saya.export');
    Route::get('/pegawai/pengajuan-cuti/export', [EksporController::class, 'riwayatCuti'])->name('pegawai.pengajuan-cuti.export');
    Route::get('/pegawai/pengajuan-lembur/export', [EksporController::class, 'riwayatLembur'])->name('pegawai.pengajuan-lembur.export');
    Route::get('/pelamar/lamaran-export', [EksporController::class, 'lamaranSaya'])->name('pelamar.lamaran.export')->middleware('pelamar');

    Route::middleware('internal')->group(function () {
        Route::get('/pengumuman', [PengumumanController::class, 'index'])->name('pengumuman.index');
        Route::post('/pengumuman/baca-semua', [PengumumanController::class, 'bacaSemua'])->name('pengumuman.baca-semua');

        // Absen acara — hak akses (SDM/Admin/GM) dicek di controller.
        Route::post('/acara/{pengumuman}/absen', [AbsenAcaraController::class, 'simpan'])->name('acara.absen.simpan')->middleware('throttle:10,1');
        Route::get('/pengumuman/{pengumuman}/absen', [AbsenAcaraController::class, 'pantau'])->name('pengumuman.absen');
        Route::get('/pengumuman/{pengumuman}/absen/export', [AbsenAcaraController::class, 'export'])->name('pengumuman.absen.export');
        Route::get('/pengumuman/{pengumuman}/absen/qr', [AbsenAcaraController::class, 'qr'])->name('pengumuman.absen.qr');
        Route::get('/pengumuman/{pengumuman}/absen/qr/token', [AbsenAcaraController::class, 'qrToken'])->name('pengumuman.absen.qr-token');
        Route::post('/pengumuman/{pengumuman}/absen/ingatkan', [AbsenAcaraController::class, 'ingatkan'])->name('pengumuman.absen.ingatkan')->middleware('throttle:6,1');
        Route::post('/pengumuman/{pengumuman}/absen/{peserta}', [AbsenAcaraController::class, 'tandai'])->name('pengumuman.absen.tandai');
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
    Route::get('/notifikasi/export', [EksporController::class, 'notifikasi'])->name('notifikasi.export');
    Route::post('/notifikasi/baca-semua', [NotifikasiController::class, 'bacaSemua'])->name('notifikasi.baca-semua');
    Route::get('/notifikasi/{id}', [NotifikasiController::class, 'buka'])->name('notifikasi.buka');

    Route::get('/profile', [LoginController::class, 'profile'])->name('profile');
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    // Wajib ganti password (satu-satunya halaman yang terbuka selama penanda aktif).
    Route::get('/akun/ganti-password', [WajibPasswordController::class, 'halaman'])->name('akun.ganti-password');
    Route::post('/akun/ganti-password', [WajibPasswordController::class, 'simpan'])->middleware('throttle:10,1')->name('akun.ganti-password.simpan');

    // Kepala SDM & Admin: alur persetujuan/atasan pegawai + paksa ganti password akun.
    Route::prefix('admin')->name('admin.')->middleware(['internal', 'can:atur-akun-pegawai'])->group(function () {
        Route::put('/pegawai/{pegawai}/alur', [AlurPegawaiController::class, 'update'])->name('pegawai.alur.update');
        Route::delete('/pegawai/{pegawai}/alur', [AlurPegawaiController::class, 'destroy'])->name('pegawai.alur.destroy');
        Route::post('/akun/{user}/wajib-password', [WajibPasswordController::class, 'atur'])->name('akun.wajib-password');
    });
});
