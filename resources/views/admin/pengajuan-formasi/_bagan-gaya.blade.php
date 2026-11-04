{{-- Gaya bagan unit (form & detail pengajuan formasi, ringkasan pengajuan, gambaran posisi kandidat).
     Hijau = aktif, abu = tidak aktif, merah = posisi kosong/baru. Warna, jarak, huruf memakai token ui.css (terang & gelap).
     --node (148px) dan --b-tulang (28px) = ukuran geometri bagan: HARUS sama dengan JS formulir (LEBAR_NODE, TULANG)
     dan App\Support\BaganStatis::NODE agar pembagian baris tepat. Pasangan garis (padding atas cabang/daun = tinggi garisnya)
     memakai token yang sama sehingga tetap bersambung. --}}
@once
<style>
    .bagan, .bagan-deret, .bagan-legenda {
        --b-hijau: var(--hr-success); --b-hijau-bg: var(--hr-success-soft); --b-hijau-teks: var(--hr-success-dark);
        --b-abu: var(--hr-neutral); --b-abu-bg: var(--hr-neutral-soft); --b-abu-garis: var(--admin-border-strong);
        --b-merah: var(--hr-danger); --b-merah-bg: var(--hr-danger-soft);
        --garis: var(--admin-border-strong); --node: 148px; --b-tulang: 28px;
    }
    html[data-theme="dark"] :is(.bagan, .bagan-deret, .bagan-legenda) {
        --b-hijau-teks: var(--hr-success);
        --garis: color-mix(in srgb, var(--hr-gray-400) 60%, var(--admin-surface));
    }
    .bagan {
        border: 1px solid var(--admin-border); border-radius: var(--hr-radius); background: var(--admin-surface-soft); overflow: hidden;
        transition: border-color var(--hr-durasi) var(--hr-easing), box-shadow var(--hr-durasi) var(--hr-easing); }
    /* Formulir: pilihan di bagan belum sesuai jumlah (validasi di halaman). */
    .bagan.is-invalid { border-color: var(--hr-danger); box-shadow: 0 0 0 3px color-mix(in srgb, var(--hr-danger) 16%, transparent); }
    .bagan-legenda { display: flex; flex-wrap: wrap; gap: var(--hr-space-1) var(--hr-space-4); font-size: var(--hr-text-xs); color: var(--admin-muted); margin-bottom: var(--hr-space-2); }
    .bagan-legenda span { display: inline-flex; align-items: center; gap: var(--hr-space-1); }
    .bagan-legenda i.kotak { width: var(--hr-space-3); height: var(--hr-space-3); border-radius: calc(var(--hr-radius-sm) / 2); display: inline-block; }
    .bagan-legenda .kotak.aktif { background: var(--b-hijau); }
    .bagan-legenda .kotak.nonaktif { background: var(--hr-gray-400); }
    .bagan-legenda .kotak.kosong { background: var(--b-merah); }

    .bagan-alat { display: flex; flex-wrap: wrap; align-items: center; gap: var(--hr-space-2); padding: var(--hr-space-2) var(--hr-space-3); border-bottom: 1px solid var(--admin-border); background: var(--admin-surface); }
    .bagan-alat input[type=search] { flex: 1 1 calc(var(--hr-space-8) * 3); max-width: calc(var(--hr-space-8) * 5); }
    .bagan-skala { min-width: var(--hr-space-7); text-align: center; font-size: var(--hr-text-xs); font-variant-numeric: tabular-nums; color: var(--admin-muted); }
    .bagan-layar { overflow: auto; max-height: 72vh; padding: var(--hr-space-4) var(--hr-space-3) var(--hr-space-5); cursor: grab; }
    .bagan-layar.geser { cursor: grabbing; user-select: none; }
    .bagan-layar:focus-visible { outline: 2px solid var(--hr-focus); outline-offset: -2px; }

    /* Pohon: 3 kolom (kiri | tulang punggung | kanan). Kolom kiri & kanan selalu sama lebar → tulang tepat di tengah bawah kepala. */
    .bagan-pohon { display: grid; grid-template-columns: 1fr var(--b-tulang) 1fr; width: max-content; min-width: 100%; margin: 0 auto; }
    .bagan-akar { grid-column: 1 / -1; display: flex; flex-direction: column; align-items: center; }
    .bagan-akar-label { font-size: var(--hr-text-xs); font-weight: var(--hr-weight-bold); letter-spacing: .06em; text-transform: uppercase; color: var(--admin-muted); margin-bottom: var(--hr-space-1); }
    .bagan-akar-deret { display: flex; flex-wrap: wrap; justify-content: center; gap: var(--hr-space-2); }
    .bagan-akar::after { content: ''; width: 2px; height: var(--hr-space-4); background: var(--garis); }
    .bagan-sisi { display: flex; align-items: flex-start; }
    .bagan-sisi.kiri { justify-content: flex-end; }
    .bagan-sisi.tunggal { grid-column: 1 / -1; justify-content: center; }
    .bagan-tulang { position: relative; }
    .bagan-tulang::before { content: ''; position: absolute; left: 50%; top: 0; bottom: 0; width: 2px; margin-left: -1px; background: var(--garis); }
    .bagan-tulang.akhir::before { display: none; }
    .bagan-tulang::after { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 2px; background: var(--garis); }
    .bagan-tulang.hanya-kiri::after { right: 50%; }

    /* Cabang = satu jabatan. Garis datar (bus) di atas + garis turun ke label jabatan. */
    .bagan-cabang { position: relative; padding: var(--hr-space-4) var(--hr-space-2) var(--hr-space-5); display: flex; flex-direction: column; align-items: center; }
    .bagan-cabang::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 2px; background: var(--garis); }
    .bagan-sisi.kiri .bagan-cabang:first-child::before { left: 50%; }
    .bagan-sisi.kanan .bagan-cabang:last-child::before { right: 50%; }
    .bagan-sisi.tunggal .bagan-cabang::before { display: none; }
    .bagan-cabang::after { content: ''; position: absolute; top: 0; left: 50%; width: 2px; margin-left: -1px; height: var(--hr-space-4); background: var(--garis); }
    .bagan-jabatan { max-width: calc(var(--node) + var(--hr-space-3)); padding: var(--hr-space-1) var(--hr-space-2); border: 1px solid var(--admin-border); border-radius: var(--hr-radius-md); background: var(--admin-surface);
        font-size: var(--hr-text-xs); font-weight: var(--hr-weight-bold); letter-spacing: .04em; line-height: var(--hr-leading-tight); color: var(--admin-muted); text-transform: uppercase; text-align: center; overflow-wrap: anywhere; }
    .bagan-cabang.terpilih .bagan-jabatan { border-color: var(--hr-focus); color: var(--hr-primary-soft-text); background: var(--hr-primary-soft); }
    .bagan-turun { width: 2px; height: var(--hr-space-3); background: var(--garis); }

    /* Anggota sejajar (≤ 4 orang): tiap orang punya garis sendiri dari bus. */
    .bagan-anak { display: flex; }
    .bagan-daun { position: relative; padding: var(--hr-space-3) var(--hr-space-1) 0; }
    .bagan-daun::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 2px; background: var(--garis); }
    .bagan-daun:first-child::before { left: 50%; }
    .bagan-daun:last-child::before { right: 50%; }
    .bagan-daun:only-child::before { display: none; }
    .bagan-daun::after { content: ''; position: absolute; top: 0; left: 50%; width: 2px; margin-left: -1px; height: var(--hr-space-3); background: var(--garis); }
    /* Banyak orang: rak grid rata (tetap sejajar per baris) + bisa dilipat. */
    .bagan-rak { display: grid; grid-template-columns: repeat(var(--kol, 4), var(--node)); gap: var(--hr-space-2); padding: var(--hr-space-2); border: 1.5px dashed var(--garis); border-radius: var(--hr-radius-md); background: color-mix(in srgb, var(--admin-surface) 60%, transparent); }
    .bagan-lagi { grid-column: 1 / -1; justify-self: center; font-size: var(--hr-text-xs); }

    .bagan-node { position: relative; display: flex; align-items: center; gap: var(--hr-space-2); width: var(--node); min-height: var(--hr-control); padding: var(--hr-space-1) var(--hr-space-2); border-radius: var(--hr-radius-md); border: 1.5px solid;
        font-size: var(--hr-text-xs); font-weight: var(--hr-weight-semibold); text-align: left; line-height: var(--hr-leading-tight); background: var(--admin-surface); color: var(--admin-text); }
    .bagan-node .bi { flex: none; font-size: var(--hr-text-base); }
    .bagan-node .nm { min-width: 0; overflow-wrap: anywhere; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    .bagan-node.aktif { border-color: var(--b-hijau); background: var(--b-hijau-bg); color: var(--b-hijau-teks); }
    .bagan-node.nonaktif { border-color: var(--b-abu-garis); background: var(--b-abu-bg); color: var(--b-abu); }
    /* Merah = posisi kosong. Putus-putus = kursi kosong yang bisa diklik untuk diajukan; garis penuh = posisi baru yang sudah diajukan. */
    .bagan-node.kosong { border-style: dashed; border-color: var(--b-merah); background: var(--b-merah-bg); color: var(--b-merah); }
    .bagan-node.kosong.slot { border-style: solid; }
    .bagan-node.tambah .sub { display: block; font-size: var(--hr-text-xs); font-weight: var(--hr-weight-medium); opacity: .8; }
    button.bagan-node.tambah:hover { background: color-mix(in srgb, var(--b-merah) 14%, var(--b-merah-bg)); }
    /* Kepala = pegawai aktif (hijau), sedikit lebih tegas. */
    .bagan-node.kepala { width: auto; min-width: calc(var(--node) + var(--hr-space-6)); max-width: calc(var(--node) * 1.75); justify-content: center; border-width: 2px; font-weight: var(--hr-weight-bold); }
    .bagan-node.kepala:disabled { opacity: 1; }
    /* Gerak mikro: sorot naik sedikit, tekan mengecil, fokus keyboard bercincin merek. */
    button.bagan-node { cursor: pointer; transition: transform var(--hr-durasi) var(--hr-easing), box-shadow var(--hr-durasi) var(--hr-easing), background-color var(--hr-durasi) var(--hr-easing); }
    button.bagan-node:hover:not(:disabled) { transform: translateY(-1px); box-shadow: var(--hr-shadow-md); }
    button.bagan-node:active:not(:disabled) { transform: scale(.98); box-shadow: var(--hr-shadow-xs); }
    button.bagan-node:focus-visible { outline: 2px solid var(--hr-focus); outline-offset: 2px; }
    button.bagan-node:disabled { cursor: default; opacity: .8; }
    .bagan-node.cocok { outline: 2px solid var(--hr-warning); outline-offset: 2px; }
    /* Lencana bulat di pojok: ganti (reverse) untuk pegawai yang digantikan, plus untuk posisi baru. */
    .bagan-lencana { position: absolute; top: calc(var(--hr-space-2) * -1); right: calc(var(--hr-space-2) * -1); width: var(--hr-icon); height: var(--hr-icon); border-radius: 50%; display: grid; place-items: center;
        color: var(--hr-primary-50); font-size: var(--hr-text-xs); box-shadow: 0 0 0 2px var(--admin-surface); }
    .bagan-node.diganti { box-shadow: 0 0 0 2px var(--hr-focus); }
    button.bagan-node.diganti:hover:not(:disabled) { box-shadow: 0 0 0 2px var(--hr-focus), var(--hr-shadow-md); }
    .bagan-node.diganti .bagan-lencana { background: var(--hr-primary); }
    .bagan-node.kosong .bagan-lencana { background: var(--b-merah); }
    html[data-theme="dark"] .bagan-node.kosong .bagan-lencana { color: var(--admin-surface); }
    .bagan-kosong-info { grid-column: 1 / -1; font-size: var(--hr-text-sm); color: var(--admin-muted); text-align: center; padding: var(--hr-space-3); }

    /* Ringkasan di pop-up detail: berjajar & membungkus. */
    .bagan-deret { display: flex; flex-wrap: wrap; gap: var(--hr-space-2); padding-top: var(--hr-space-2); }
    .bagan-deret .bagan-node { width: auto; max-width: 100%; }
</style>
@endonce
