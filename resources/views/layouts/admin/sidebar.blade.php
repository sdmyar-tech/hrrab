@php
    $u = Auth::user();
    $penyetuju = $u->can('approve');
    $ik = \App\Support\Absensi\TampilanAbsensi::IKON;
    $evaluasi = \App\Support\EvaluasiKontrakAlur::bolehMembuka($u);
    // Bagian selalu terbuka; label dibuat pendek karena judul bagian sudah menjelaskan konteksnya.
    // Notifikasi & pengumuman dibaca lewat ikon lonceng di navbar (tidak diulang di sini).
    $menu = [
        ['bagian' => null, 'item' => [
            ['Dashboard', 'bi-speedometer2', 'admin.dashboard', 'admin.dashboard', true, null],
            ['Kalender', 'bi-calendar3', 'kalender.index', 'kalender.*', true, null],
        ]],
        // Penyetuju: antrean tugasnya di paling atas. Admin & Pembina: rekap data pengajuan.
        ['bagian' => $penyetuju ? 'Tugas Saya' : 'Data Pengajuan', 'item' => [
            [$penyetuju ? 'Persetujuan Cuti' : 'Cuti', 'bi-calendar2-check', 'admin.pengajuan-cuti.index', 'admin.pengajuan-cuti.*', $u->can('lihat-laporan'), $hitung['cuti'] ?? null],
            [$penyetuju ? 'Persetujuan Lembur' : 'Lembur', 'bi-clock-history', 'admin.pengajuan-lembur.index', 'admin.pengajuan-lembur.*', $u->can('lihat-laporan'), $hitung['lembur'] ?? null],
            [$penyetuju ? 'Persetujuan Izin Keluar' : 'Izin Keluar', 'bi-door-open', 'admin.izin-keluar.index', 'admin.izin-keluar.*', $u->can('lihat-laporan'), $hitung['izin_keluar'] ?? null],
            ['Jadwal Interview Saya', 'bi-calendar2-week', 'interview.saya', 'interview.saya', $u->bisaMenginterview(), $hitung['interview'] ?? null],
            ['Evaluasi Kontrak', 'bi-clipboard-check', 'admin.evaluasi-kontrak.index', 'admin.evaluasi-kontrak.*', $penyetuju && $evaluasi, $hitung['evaluasi_kontrak'] ?? null],
        ]],
        ['bagian' => 'Pengajuan Saya', 'item' => [
            ['Cuti', 'bi-calendar-plus', 'pegawai.pengajuan-cuti', 'pegawai.pengajuan-cuti*', $u->can('ajukan-cuti'), null],
            ['Lembur', 'bi-stopwatch', 'pegawai.pengajuan-lembur', 'pegawai.pengajuan-lembur*', $u->can('ajukan-lembur'), null],
            ['Izin Keluar', 'bi-door-open', 'pegawai.izin-keluar', 'pegawai.izin-keluar*', $u->can('ajukan-izin-keluar'), null],
        ]],
        ['bagian' => 'Kepegawaian', 'item' => [
            ['Data Pegawai', 'bi-people', 'admin.pegawai.index', 'admin.pegawai.*', $u->can('lihat-tabel-karyawan'), null],
            ['Mutasi & Rangkap', 'bi-arrow-left-right', 'admin.penugasan.index', 'admin.penugasan.*', $u->can('kelola-penugasan'), null],
            ['Unit & Koordinator', 'bi-buildings', 'admin.unit.index', 'admin.unit.*', $u->can('kelola-penugasan'), null],
            ['Evaluasi Kontrak', 'bi-clipboard-check', 'admin.evaluasi-kontrak.index', 'admin.evaluasi-kontrak.*', ! $penyetuju && $evaluasi, $hitung['evaluasi_kontrak'] ?? null],
            ['Surat Peringatan', 'bi-exclamation-octagon', 'admin.surat-peringatan.index', 'admin.surat-peringatan.*', \App\Support\SuratPeringatanAkses::bolehMembuka($u), null],
        ]],
        // Izin telat/pulang cepat diakses dari tab modul & tombol di halaman Harian (menu tetap ringkas).
        ['bagian' => 'Absensi', 'item' => [
            ['Absensi Harian', $ik['harian'], 'admin.absensi.harian', ['admin.absensi.harian', 'admin.absensi.pegawai', 'admin.absensi.izin*'], $u->can('lihat-absensi'), null],
            ['Rekap Absensi', $ik['rekap'], 'admin.absensi.rekap', 'admin.absensi.rekap*', $u->can('lihat-absensi'), null],
            ['Import Data Mesin', $ik['import'], 'admin.absensi.import', 'admin.absensi.import*', $u->can('kelola-absensi'), null],
            ['Pengaturan Absensi', $ik['pengaturan'], 'admin.absensi.pengaturan', 'admin.absensi.pengaturan*', $u->can('kelola-absensi'), null],
            ['Absensi Saya', $ik['saya'], 'absensi.saya', 'absensi.saya', (bool) $u->dataPegawai?->ikut_absensi, null],
        ]],
        ['bagian' => 'Rekrutmen', 'item' => [
            ['Pengajuan Formasi', 'bi-person-plus', 'admin.pengajuan-formasi.index', 'admin.pengajuan-formasi.*', true, $hitung['formasi'] ?? null],
            ['Lamaran Masuk', 'bi-inbox', 'admin.lamaran.index', 'admin.lamaran.*', true, $hitung['lamaran'] ?? null],
            ['Lowongan', 'bi-briefcase', 'admin.lowongan.index', 'admin.lowongan.*', true, null],
        ]],
        ['bagian' => 'Informasi', 'item' => [
            ['Kelola Pengumuman', 'bi-megaphone', 'pengumuman.index', 'pengumuman.*', $u->can('kelola-pengumuman'), null],
            ['Kirim Notifikasi', 'bi-send', 'notifikasi.kirim', 'notifikasi.kirim*', $u->can('kirim-notifikasi'), null],
        ]],
        ['bagian' => 'Sistem', 'item' => [
            ['Kelola Akun', 'bi-person-lock', 'admin.users.index', 'admin.users.*', $u->can('kelola-sistem'), null],
            ['Master Role', 'bi-shield-check', 'admin.role.index', 'admin.role.*', $u->can('kelola-sistem'), null],
            ['Master Jabatan', 'bi-diagram-3', 'admin.jabatan.index', 'admin.jabatan.*', $u->can('kelola-sistem'), null],
            ['Master Lokasi Kerja', 'bi-geo-alt', 'admin.lokasi-kerja.index', 'admin.lokasi-kerja.*', $u->can('kelola-sistem'), null],
            ['Editor Data', 'bi-database-gear', 'admin.data.index', 'admin.data.*', $u->isAdmin(), null],
            ['Log Aktivitas', 'bi-journal-text', 'admin.log.index', 'admin.log.*', $u->can('kelola-sistem'), null],
        ]],
    ];
@endphp
@include('layouts.partials.sidebar', ['menu' => $menu, 'beranda' => 'admin.dashboard'])
