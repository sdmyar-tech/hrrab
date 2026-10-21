{{-- Gaya bagan unit (form & detail pengajuan formasi). Hijau = aktif, abu = tidak aktif, merah = posisi kosong/baru. --}}
@once
<style>
    .bagan, .bagan-deret { --b-hijau: #15803d; --b-hijau-bg: #e8f5ec; --b-abu: #6b7280; --b-abu-bg: #f1f2f4; --b-merah: #b42318; --b-merah-bg: #fdecec;
        --garis: #c3c8cf; --node: 148px; }
    .bagan {
        border: 1px solid var(--admin-border); border-radius: 12px; background: var(--admin-surface-soft); overflow: hidden; }
    .bagan-legenda { display: flex; flex-wrap: wrap; gap: .4rem 1rem; font-size: .75rem; color: var(--admin-muted); margin-bottom: .5rem; }
    .bagan-legenda span { display: inline-flex; align-items: center; gap: .35rem; }
    .bagan-legenda i.kotak { width: 12px; height: 12px; border-radius: 3px; display: inline-block; }

    .bagan-alat { display: flex; flex-wrap: wrap; align-items: center; gap: .4rem; padding: .45rem .55rem; border-bottom: 1px solid var(--admin-border); background: var(--admin-surface); }
    .bagan-alat input[type=search] { flex: 1 1 180px; max-width: 320px; }
    .bagan-alat .btn { --bs-btn-padding-y: .2rem; --bs-btn-padding-x: .5rem; --bs-btn-font-size: .75rem; }
    .bagan-skala { min-width: 3.2rem; text-align: center; font-size: .75rem; font-variant-numeric: tabular-nums; color: var(--admin-muted); }
    .bagan-layar { overflow: auto; max-height: 72vh; padding: 1rem .75rem 1.25rem; cursor: grab; }
    .bagan-layar.geser { cursor: grabbing; user-select: none; }

    /* Pohon: 3 kolom (kiri | tulang punggung | kanan). Kolom kiri & kanan selalu sama lebar → tulang tepat di tengah bawah kepala. */
    .bagan-pohon { display: grid; grid-template-columns: 1fr 28px 1fr; width: max-content; min-width: 100%; margin: 0 auto; }
    .bagan-akar { grid-column: 1 / -1; display: flex; flex-direction: column; align-items: center; }
    .bagan-akar-label { font-size: .66rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: var(--admin-muted); margin-bottom: .3rem; }
    .bagan-akar-deret { display: flex; flex-wrap: wrap; justify-content: center; gap: .5rem; }
    .bagan-akar::after { content: ''; width: 2px; height: 18px; background: var(--garis); }
    .bagan-sisi { display: flex; align-items: flex-start; }
    .bagan-sisi.kiri { justify-content: flex-end; }
    .bagan-sisi.tunggal { grid-column: 1 / -1; justify-content: center; }
    .bagan-tulang { position: relative; }
    .bagan-tulang::before { content: ''; position: absolute; left: 50%; top: 0; bottom: 0; width: 2px; margin-left: -1px; background: var(--garis); }
    .bagan-tulang.akhir::before { display: none; }
    .bagan-tulang::after { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 2px; background: var(--garis); }
    .bagan-tulang.hanya-kiri::after { right: 50%; }

    /* Cabang = satu jabatan. Garis datar (bus) di atas + garis turun ke label jabatan. */
    .bagan-cabang { position: relative; padding: 18px 8px 22px; display: flex; flex-direction: column; align-items: center; }
    .bagan-cabang::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 2px; background: var(--garis); }
    .bagan-sisi.kiri .bagan-cabang:first-child::before { left: 50%; }
    .bagan-sisi.kanan .bagan-cabang:last-child::before { right: 50%; }
    .bagan-sisi.tunggal .bagan-cabang::before { display: none; }
    .bagan-cabang::after { content: ''; position: absolute; top: 0; left: 50%; width: 2px; margin-left: -1px; height: 18px; background: var(--garis); }
    .bagan-jabatan { max-width: calc(var(--node) + 12px); padding: .3rem .6rem; border: 1px solid var(--admin-border); border-radius: 8px; background: var(--admin-surface);
        font-size: .66rem; font-weight: 700; letter-spacing: .04em; color: var(--admin-muted); text-transform: uppercase; text-align: center; overflow-wrap: anywhere; }
    .bagan-cabang.terpilih .bagan-jabatan { border-color: var(--hr-primary); color: var(--hr-primary); background: var(--hr-primary-soft); }
    .bagan-turun { width: 2px; height: 14px; background: var(--garis); }

    /* Anggota sejajar (≤ 4 orang): tiap orang punya garis sendiri dari bus. */
    .bagan-anak { display: flex; }
    .bagan-daun { position: relative; padding: 14px 4px 0; }
    .bagan-daun::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 2px; background: var(--garis); }
    .bagan-daun:first-child::before { left: 50%; }
    .bagan-daun:last-child::before { right: 50%; }
    .bagan-daun:only-child::before { display: none; }
    .bagan-daun::after { content: ''; position: absolute; top: 0; left: 50%; width: 2px; margin-left: -1px; height: 14px; background: var(--garis); }
    /* Banyak orang: rak grid rata (tetap sejajar per baris) + bisa dilipat. */
    .bagan-rak { display: grid; grid-template-columns: repeat(var(--kol, 4), var(--node)); gap: .45rem .5rem; padding: .55rem; border: 1.5px dashed var(--garis); border-radius: 10px; background: color-mix(in srgb, var(--admin-surface) 60%, transparent); }
    .bagan-lagi { grid-column: 1 / -1; justify-self: center; font-size: .75rem; }

    .bagan-node { position: relative; display: flex; align-items: center; gap: .4rem; width: var(--node); min-height: 38px; padding: .38rem .5rem; border-radius: 8px; border: 1.5px solid;
        font-size: .74rem; font-weight: 600; text-align: left; line-height: 1.2; background: #fff; }
    .bagan-node .bi { flex: none; font-size: .9rem; }
    .bagan-node .nm { min-width: 0; overflow-wrap: anywhere; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    .bagan-node.aktif { border-color: var(--b-hijau); background: var(--b-hijau-bg); color: #14532d; }
    .bagan-node.nonaktif { border-color: #c4c7cc; background: var(--b-abu-bg); color: var(--b-abu); }
    /* Merah = posisi kosong. Putus-putus = kursi kosong yang bisa diklik untuk diajukan; garis penuh = posisi baru yang sudah diajukan. */
    .bagan-node.kosong { border-style: dashed; border-color: var(--b-merah); background: var(--b-merah-bg); color: var(--b-merah); }
    .bagan-node.kosong.slot { border-style: solid; }
    .bagan-node.tambah .sub { display: block; font-size: .64rem; font-weight: 500; opacity: .8; }
    button.bagan-node.tambah:hover { background: #fbd5d5; }
    /* Kepala = pegawai aktif (hijau), sedikit lebih tegas. */
    .bagan-node.kepala { width: auto; min-width: 180px; max-width: 260px; justify-content: center; border-width: 2px; font-weight: 700; }
    .bagan-node.kepala:disabled { opacity: 1; }
    button.bagan-node { cursor: pointer; transition: transform .12s, box-shadow .12s; }
    button.bagan-node:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 3px 10px rgba(16, 24, 40, .12); }
    button.bagan-node:disabled { cursor: default; opacity: .8; }
    .bagan-node.cocok { outline: 2px solid #f59e0b; outline-offset: 2px; }
    /* Lencana bulat di pojok: ganti (reverse) untuk pegawai yang digantikan, plus untuk posisi baru. */
    .bagan-lencana { position: absolute; top: -8px; right: -8px; width: 22px; height: 22px; border-radius: 50%; display: grid; place-items: center; color: #fff; font-size: .75rem; box-shadow: 0 0 0 2px #fff; }
    .bagan-node.diganti { box-shadow: 0 0 0 2px var(--hr-primary); }
    .bagan-node.diganti .bagan-lencana { background: var(--hr-primary); }
    .bagan-node.kosong .bagan-lencana { background: var(--b-merah); }
    .bagan-kosong-info { grid-column: 1 / -1; font-size: .8rem; color: var(--admin-muted); text-align: center; padding: .75rem; }

    /* Ringkasan di pop-up detail: berjajar & membungkus. */
    .bagan-deret { display: flex; flex-wrap: wrap; gap: .6rem .5rem; padding-top: .5rem; }
    .bagan-deret .bagan-node { width: auto; max-width: 100%; }

    html[data-theme="dark"] .bagan { --garis: #4b5563; }
    html[data-theme="dark"] .bagan-node { background: var(--admin-surface); }
    html[data-theme="dark"] .bagan-node.aktif { background: rgba(34, 197, 94, .12); color: #86efac; }
    html[data-theme="dark"] .bagan-node.nonaktif { background: rgba(148, 163, 184, .1); color: #94a3b8; }
    html[data-theme="dark"] .bagan-node.kosong { background: rgba(239, 68, 68, .1); color: #fca5a5; }
</style>
@endonce
