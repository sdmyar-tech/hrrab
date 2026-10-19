<?php

/*
 * Formulir penilaian evaluasi kontrak (diisi atasan). Nomor butir 1–50 menjadi kunci nilai yang disimpan,
 * jadi JANGAN mengubah nomor butir yang sudah dipakai; menambah butir baru cukup dengan nomor berikutnya.
 */
return [
    'skala' => [4 => 'Sangat Baik', 3 => 'Baik', 2 => 'Sedang', 1 => 'Kurang'],

    'bagian' => [
        ['kode' => 'A', 'judul' => 'RAB Value', 'kelompok' => [
            ['judul' => 'Rabbani', 'butir' => [
                1 => 'Sholat tepat waktu',
                2 => "Membaca Alqur'an",
                3 => 'Berpakaian sesuai dengan syariat keislaman',
                4 => 'Semangat dalam menambah ilmu',
                5 => 'Menjaga lisan dari perkataan buruk, bohong, fitnah, dan ghibah',
            ]],
            ['judul' => 'Amanah', 'butir' => [
                6 => 'Bekerja sesuai dengan jadwal, datang tepat waktu',
                7 => 'Menyelesaikan pekerjaan dan tanggung jawab sesuai dengan waktu yang ditentukan',
                8 => 'Menyelesaikan pekerjaan dengan tuntas dan baik',
                9 => 'Komitmen dengan aturan yang berlaku',
                10 => 'Menjaga rahasia unit/ divisi/ lembaga/ yayasan',
            ]],
            ['judul' => 'Beradab', 'butir' => [
                11 => 'Bisa memberi teladan baik di tempat kerja',
                12 => "Sabar dan sederhana (tawadhu')",
                13 => 'Memuliakan teman sejawat dan bawahan',
                14 => 'Menghargai atasan',
                15 => 'Mengucapkan dan menjawab salam',
            ]],
        ]],
        ['kode' => 'B', 'judul' => 'Kepribadian', 'kelompok' => [['judul' => null, 'butir' => [
            16 => 'Bertindak sesuai dengan norma agama, hukum, sosial dan kebudayaan nasional Indonesia',
            17 => 'Menunjukkan pribadi yang dewasa dan teladan yang baik',
            18 => 'Etos kerja, tanggung jawab yang tinggi, dan rasa bangga menjadi Karyawan',
            19 => 'Terbuka menerima saran (kritik) dan mau memperbaiki diri',
            20 => 'Jujur, mau mengakui kesalahan dan meminta maaf',
        ]]]],
        ['kode' => 'C', 'judul' => 'Sosial', 'kelompok' => [['judul' => null, 'butir' => [
            21 => 'Bersikap inklusif/ terbuka dan menghormati perbedaan',
            22 => 'Komunikasi dengan sesama karyawan dan masyarakat',
            23 => 'Bersikap objektif dan tidak diskriminatif',
            24 => 'Mau membantu pekerjaan rekan kerja dengan tidak mengabaikan tanggung jawab utamanya',
        ]]]],
        ['kode' => 'D', 'judul' => 'Profesional', 'kelompok' => [['judul' => null, 'butir' => [
            25 => 'Penguasaan terhadap pekerjaan sesuai dengan kompetensi dan keahlian',
            26 => 'Memiliki kemampuan analisis yang baik terhadap masalah/ tantangan pekerjaan',
            27 => 'Memahami batasan profesionalitas dalam bekerja dan interaksi dengan pihak internal dan eksternal lembaga/ yayasan',
        ]]]],
        ['kode' => 'E', 'judul' => 'Kualitas Kerja', 'kelompok' => [['judul' => null, 'butir' => [
            28 => 'Teliti dan akurat dalam bekerja',
            29 => 'Menyelesaikan pekerjaan sesuai target waktu yang telah disepakati',
            30 => 'Fokus pada tujuan yang ingin dicapai dalam bekerja',
        ]]]],
        ['kode' => 'F', 'judul' => 'Keandalan', 'kelompok' => [['judul' => null, 'butir' => [
            31 => 'Memastikan rekan kerja dan/atau bawahan tahu dan memahami resiko setiap pekerjaan',
            32 => 'Memberikan tanggapan positif terhadap berita-berita terkait Perusahaan, seperti kebijakan/aturan yang ditetapkan dan pencapaian prestasi kerja berdasarkan hasil kinerja',
        ]]]],
        ['kode' => 'G', 'judul' => 'Pemecahan Masalah', 'kelompok' => [['judul' => null, 'butir' => [
            33 => 'Menyampaikan segala sesuatu terkait dengan jobdesk',
            34 => 'Mengeluarkan ide/konsep baru dengan tujuan perbaikan di Unit Kerja',
        ]]]],
        ['kode' => 'H', 'judul' => 'Inisiatif', 'kelompok' => [['judul' => null, 'butir' => [
            35 => 'Memberikan solusi terhadap masalah-masalah yang dihadapi',
            36 => 'Mengambil inisiatif dalam bertindak sebelum diminta terkait dengan pekerjaan yang menjadi tanggung jawab',
            37 => 'Meningkatkan kompetensi dan kualitas diri',
        ]]]],
        ['kode' => 'I', 'judul' => 'Implementasi Budaya Insan Mulia', 'kelompok' => [['judul' => null, 'butir' => [
            38 => 'Mengakui kesalahan terkait dengan pekerjaan',
            39 => 'Bersemangat mengerjakan pekerjaan, termasuk pekerjaan tambahan',
        ]]]],
        ['kode' => 'K', 'judul' => 'Pelayanan', 'kelompok' => [['judul' => null, 'butir' => [
            40 => 'Mengutamakan kepentingan perusahaan dibandingkan dengan kepentingan pribadi',
            41 => 'Memberikan pelayanan terbaik dan berusaha melampaui harapan dalam bekerja',
        ]]]],
        ['kode' => 'L', 'judul' => 'Kerjasama', 'kelompok' => [['judul' => null, 'butir' => [
            42 => 'Bekerjasama dan berkoordinasi antar rekan kerja atau unit lain',
            43 => 'Menolong rekan kerja yang dalam kesulitan',
            44 => 'Mendorong dan memotivasi rekan kerja dalam proses perbaikan di Unit Kerja',
            45 => 'Berpartisipasi dan ikut serta untuk terlaksananya program budaya kerja',
            46 => 'Mengembangkan dan mengimplementasikan ide/konsep termasuk mengajak rekan kerja/bawahan untuk berpartisipasi mewujudkannya',
        ]]]],
        ['kode' => 'M', 'judul' => 'Komunikasi', 'kelompok' => [['judul' => null, 'butir' => [
            47 => 'Mengemukakan pendapat, fakta yang benar dan apa adanya',
            48 => 'Menyampaikan hal-hal yang harus diketahui oleh rekan kerja dan/atau bawahan secara terbuka',
            49 => 'Menegur rekan kerja yang berbuat salah dengan cara yang baik',
            50 => 'Tidak mengeluh ketika diminta untuk memperbaiki hasil kerja',
        ]]]],
    ],
];
