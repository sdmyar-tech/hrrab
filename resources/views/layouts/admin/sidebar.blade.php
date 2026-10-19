@php
    $u = Auth::user();
    $penyetuju = $u->can('approve');
    $ik = \App\Support\Absensi\TampilanAbsensi::IKON;
    $menu = [
        ['bagian' => null, 'item' => [
            ['Dashboard', 'bi-speedometer2', 'admin.dashboard', 'admin.dashboard', true, null],
        ]],
        // Penyetuju melihat antrean persetujuan; Admin & Pembina melihat rekap data cuti/lembur.
        ['bagian' => $penyetuju ? 'Persetujuan & Tugas' : 'Cuti & Lembur', 'ikon' => 'bi-check2-square', 'item' => [
            [$penyetuju ? 'Persetujuan Cuti' : 'Data Cuti', 'bi-calendar2-check', 'admin.pengajuan-cuti.index', 'admin.pengajuan-cuti.*', $u->can('lihat-laporan'), $hitung['cuti'] ?? null],
            [$penyetuju ? 'Persetujuan Lembur' : 'Data Lembur', 'bi-clock-history', 'admin.pengajuan-lembur.index', 'admin.pengajuan-lembur.*', $u->can('lihat-laporan'), $hitung['lembur'] ?? null],
            ['Jadwal Interview Saya', 'bi-calendar2-week', 'interview.saya', 'interview.saya', $u->bisaMenginterview(), $hitung['interview'] ?? null],
        ]],
        ['bagian' => 'Pengajuan Saya', 'ikon' => 'bi-send-check', 'item' => [
            ['Ajukan Cuti', 'bi-calendar-plus', 'pegawai.pengajuan-cuti', 'pegawai.pengajuan-cuti*', $u->can('ajukan-cuti'), null],
            ['Ajukan Lembur', 'bi-stopwatch', 'pegawai.pengajuan-lembur', 'pegawai.pengajuan-lembur*', $u->can('ajukan-lembur'), null],
        ]],
        ['bagian' => 'Kepegawaian', 'ikon' => 'bi-people', 'item' => [
            ['Data Pegawai', 'bi-people', 'admin.pegawai.index', 'admin.pegawai.*', $u->can('lihat-tabel-karyawan'), null],
            ['Mutasi & Rangkap', 'bi-arrow-left-right', 'admin.penugasan.index', 'admin.penugasan.*', $u->can('kelola-penugasan'), null],
            ['Evaluasi Kontrak', 'bi-clipboard-check', 'admin.evaluasi-kontrak.index', 'admin.evaluasi-kontrak.*', \App\Support\EvaluasiKontrakAlur::bolehMembuka($u), $hitung['evaluasi_kontrak'] ?? null],
        ]],
        // Izin telat/pulang cepat diakses dari tab modul & tombol di halaman Harian (menu tetap ringkas).
        ['bagian' => 'Absensi', 'ikon' => $ik['modul'], 'item' => [
            ['Absensi Harian', $ik['harian'], 'admin.absensi.harian', ['admin.absensi.harian', 'admin.absensi.pegawai', 'admin.absensi.izin*'], $u->can('lihat-absensi'), null],
            ['Rekap Absensi', $ik['rekap'], 'admin.absensi.rekap', 'admin.absensi.rekap*', $u->can('lihat-absensi'), null],
            ['Import Data Mesin', $ik['import'], 'admin.absensi.import', 'admin.absensi.import*', $u->can('kelola-absensi'), null],
            ['Pengaturan Absensi', $ik['pengaturan'], 'admin.absensi.pengaturan', 'admin.absensi.pengaturan*', $u->can('kelola-absensi'), null],
            ['Absensi Saya', $ik['saya'], 'absensi.saya', 'absensi.saya', (bool) $u->dataPegawai?->ikut_absensi, null],
        ]],
        ['bagian' => 'Rekrutmen', 'ikon' => 'bi-person-plus', 'item' => [
            ['Pengajuan Formasi', 'bi-person-plus', 'admin.pengajuan-formasi.index', 'admin.pengajuan-formasi.*', true, $hitung['formasi'] ?? null],
            ['Lamaran Masuk', 'bi-inbox', 'admin.lamaran.index', 'admin.lamaran.*', true, $hitung['lamaran'] ?? null],
            ['Lowongan', 'bi-briefcase', 'admin.lowongan.index', 'admin.lowongan.*', true, null],
        ]],
        ['bagian' => 'Informasi', 'ikon' => 'bi-info-circle', 'item' => [
            ['Pengumuman', 'bi-megaphone', 'pengumuman.index', 'pengumuman.*', true, $hitung['pengumuman'] ?? null],
            ['Notifikasi', 'bi-bell', 'notifikasi.index', 'notifikasi.index', true, $hitung['notifikasi'] ?? null],
            ['Kirim Notifikasi', 'bi-send', 'notifikasi.kirim', 'notifikasi.kirim*', $u->can('kirim-notifikasi'), null],
        ]],
        ['bagian' => 'Sistem', 'ikon' => 'bi-gear', 'item' => [
            ['Kelola Akun', 'bi-person-lock', 'admin.users.index', 'admin.users.*', $u->can('kelola-sistem'), null],
            ['Master Jabatan', 'bi-diagram-3', 'admin.jabatan.index', 'admin.jabatan.*', $u->can('kelola-sistem'), null],
            ['Editor Data', 'bi-database-gear', 'admin.data.index', 'admin.data.*', $u->isAdmin(), null],
            ['Log Aktivitas', 'bi-journal-text', 'admin.log.index', 'admin.log.*', $u->can('kelola-sistem'), null],
        ]],
    ];
@endphp
@include('layouts.partials.sidebar', ['menu' => $menu, 'beranda' => 'admin.dashboard'])
