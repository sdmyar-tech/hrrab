<?php

// Libur nasional bertanggal tetap; libur berbasis kalender hijriah/imlek (Idulfitri, Iduladha, Nyepi, dll.)
// berubah tiap tahun — tambahkan manual ke 'hari_libur' sesuai SKB pemerintah.
$liburTetap = [];
foreach (range(2026, 2030) as $tahun) {
    foreach (['01-01', '05-01', '06-01', '08-17', '12-25'] as $tanggal) {
        $liburTetap[] = "{$tahun}-{$tanggal}";
    }
}

return [

    'staff_login_path' => trim(env('STAFF_LOGIN_PATH', 'portal-sdm'), '/'),

    // Hari kerja (ISO: 1 = Senin ... 7 = Minggu). Dipakai onboarding, jadwal kontrak, interview, dan hitungan cuti besar.
    'hari_kerja' => [1, 2, 3, 4, 5],

    // Tanggal libur (format Y-m-d) yang tidak dihitung sebagai hari kerja.
    // Saat aplikasi berjalan diganti daftar tanggal merah aktif dari menu Kalender (tabel hari_liburs); daftar ini hanya cadangan.
    'hari_libur' => $liburTetap,

    // Sumber sinkronisasi otomatis tanggal merah (dicoba berurutan; API hanya dipakai untuk sinkron, bukan dibaca langsung).
    'libur_api' => [
        'google_ics' => env('LIBUR_ICS_URL', 'https://calendar.google.com/calendar/ical/id.indonesian%23holiday%40group.v.calendar.google.com/public/basic.ics'),
        'nager' => env('LIBUR_NAGER_URL', 'https://date.nager.at/api/v3/PublicHolidays/{tahun}/ID'),
        'interval_hari' => 30,   // sinkron otomatis bila sinkron berhasil terakhir lebih lama dari ini
    ],

    // Masa simpan log aktivitas (hari). Lewat masa ini log diarsipkan ke Excel di storage lalu dihapus dari database.
    'retensi_log' => [
        'akses' => 365,   // login, logout, login gagal
        'ekspor' => 730,  // ekspor data
        'data' => 1825,   // tambah, ubah, hapus, setujui, tolak, kirim, dll.
    ],

    // Absensi (mesin fingerprint, import file flashdisk).
    'absensi' => [
        'toleransi_menit' => 15,      // batas On Time bawaan (bisa diubah per pegawai)
        'izin_telat_menit' => 60,     // bawaan izin telat (hujan, dll.), bisa diubah saat membuat izin
        'izin_pulang_menit' => 60,    // bawaan izin pulang cepat
        'min_selisih_keluar' => 60,   // scan keluar < N menit setelah masuk = dianggap tidak scan pulang
        'periode_mulai' => 21,        // pilihan cepat rekap "periode gaji" 21 s/d 20
    ],

];
