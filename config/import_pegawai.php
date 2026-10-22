<?php

return [

    'sheet' => 'PEGAWAI 2026',
    'sheet_keluar' => 'PEGAWAI KELUAR',
    'sheet_pelengkap' => ['PEGAWAI AKTIF'],

    'kolom' => [
        'waktu_isi' => 'A', 'email' => 'B', 'nip' => 'C', 'nama' => 'D', 'nik' => 'E', 'npwp' => 'F', 'no_kk' => 'G',
        'no_hp' => 'H', 'tempat_lahir' => 'I', 'tanggal_lahir' => 'J', 'jenis_kelamin' => 'K', 'agama' => 'L',
        'golongan_darah' => 'M', 'jabatan' => 'N', 'jabatan_tambahan' => 'O', 'unit' => 'P', 'lokasi' => 'Q',
        'tanggal_masuk' => 'R', 'jenis_rekrutmen' => 'T', 'status' => 'U', 'status_kerja' => 'V',
        'alamat_ktp' => 'W', 'alamat_domisili' => 'X', 'status_pernikahan' => 'Y', 'jumlah_tanggungan' => 'Z',
        'hp_kerabat' => 'AA', 'nama_kerabat' => 'AB', 'hubungan_kerabat' => 'AC', 'nama_ibu' => 'AD',
        'pendidikan' => 'AE', 'bpjs_tk' => 'AF', 'bpjs_kes' => 'AG', 'faskes' => 'AN',
    ],

    'unit_sekolah' => ['SMA', 'SMP', 'SMK'],

    'jabatan_ke_unit' => [
        '/\bSDM\b/' => 'SDM',
        '/PURCHASING/' => 'PURCHASING',
        '/KEUANGAN|PAJAK|KASIR/' => 'KEUANGAN YAR/YAYASAN',
        '/LAUNDRY/' => 'LAUNDRY',
        '/DEPOT/' => 'DEPOT',
        '/CLEANING SERVICE/' => 'CS',
        '/DRIVER/' => 'DRIVER',
        '/DAPUR|GIZI/' => 'DAPUR AIS',
        '/SECURITY/' => 'SECURITY',
        '/NAHKODA|NAKHODA|KAPAL|DERMAGA/' => 'DERMAGA',
        '/MAINTENANCE AC|HELPER AC/' => 'MAINTENANCE AC',
        '/LISTRIK/' => 'LISTRIK',
        '/MAINTENANCE|PEMBANGUNAN|SARANA|SARPRAS|MECHANICAL/' => 'PEMBANGUNAN',
        '/PERAWAT/' => 'UKS',
        '/MEDIA CENTER/' => 'MEDIA CENTER',
        '/HUMAS/' => 'STCH',
        '/\bGA\b|ASSET|KEBUN|SAMPAH/' => 'GA',
        '/MARKETING/' => 'UNIT BISNIS',
        '/ISLAMIZATION|MENTOR KAJIAN/' => 'CINTAI RAB',
        '/SEKRETARIS|DEWAN PENGARAH/' => 'SEKRETARIAT',
    ],

    'pecah_unit_lokasi' => [
        'LISTRIK' => ['/UNIVERSITAS/' => 'LISTRIK UNIV', '*' => 'LISTRIK YAYASAN'],
        'KEUANGAN YAR/YAYASAN' => ['/UNIVERSITAS/' => 'KEUANGAN UNIV'],
    ],

    'pola_kepala' => ['/^KEPALA SEKOLAH$/', '/^KEPALA\b/', '/^(KOORDINATOR|KORDINATOR)\b/', '/^LEADER\b/'],

    'staff_per_bagian' => 2,

    'kepala_manual' => [
        'DAPUR AIS' => ['nama_excel' => 'Neneng Sumarni', 'jabatan' => 'KEPALA DAPUR AIS', 'jabatan_tambahan' => 'AHLI GIZI'],
        'UKS' => ['rangkap' => 'Luluk Elvitaria'],
        'MEDIA CENTER' => ['rangkap' => 'Luluk Elvitaria'],
        'GA' => ['rangkap' => 'Nurfahmi'],
        'CS' => ['rangkap' => 'Nurfahmi'],
        'LISTRIK YAYASAN' => ['nama_excel' => 'Taufik Herman'],
        'LISTRIK UNIV' => ['nama_excel' => 'Ade Satria'],
        'KEUANGAN YAR/YAYASAN' => ['nama_excel' => 'Kurnia Wijaya'],
        'KEUANGAN UNIV' => ['nama_excel' => 'Tommy Minardo'],
        'STCH' => ['nama_excel' => 'Evona Arson', 'jabatan' => 'KEPALA STCH', 'jabatan_tambahan' => 'KEPALA HUMAS, PROTOKOLER IBU PEMBINA YAYASAN'],
        'CINTAI RAB' => ['nama_excel' => 'Ali Rakhman', 'jabatan' => 'KEPALA CINTAI RAB', 'jabatan_tambahan' => 'MENTOR', 'email' => 'ali.rakhman@yayasanabdurrab.test'],
        'DERMAGA' => ['nama_excel' => 'Jamal Idris', 'email' => 'jamal.idris@yayasanabdurrab.test'],
        'UNIT BISNIS' => ['rangkap' => 'Dinda Asyrofi Ana', 'staff' => 0],
    ],

    'petinggi' => [
        ['nama' => 'Dr. Soraya Medina Ruza, M.M', 'email_lama' => 'gm@example.com', 'bidang' => ['GM SDM & Purchasing']],
        ['nama' => 'Ahmad Wildan, M.M', 'email_lama' => 'gm.umum-ga@example.com', 'bidang' => ['GM Umum & GA']],
        ['nama' => 'Luluk Elvitaria, M.Kom', 'email_lama' => 'gm.sekretariat@example.com', 'nama_excel' => 'Luluk Elvitaria', 'bidang' => ['GM Sekretariat', 'Dewan Pengarah']],
        ['nama' => 'Annisa Mutia Ruza, S.M', 'email_lama' => 'gm.stch-unit-bisnis@example.com', 'bidang' => ['GM STCH & Unit Bisnis']],
        ['nama' => 'Drs. Gamal Lubis, M.Pd', 'nama_excel' => 'Gamal Lubis', 'bidang' => ['Dewan Pengarah']],
    ],

    'koreksi_nama' => [
        'Ns.Abdur Rahman.S,Kep' => 'Ns. Abdur Rahman, S.Kep',
    ],

    'akun_tambahan' => [
        ['nama' => 'Taufiq', 'email' => 'taufiq.sdm@yayasanabdurrab.test', 'unit' => 'SDM', 'jabatan' => 'STAFF SDM'],
    ],
];
