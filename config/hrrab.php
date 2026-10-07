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
    'hari_libur' => $liburTetap,

];
