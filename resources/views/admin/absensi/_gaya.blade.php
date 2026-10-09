@once
<style>
    /* ---------- Navigasi tab modul ---------- */
    .absensi-nav { gap: .25rem; border-bottom: 1px solid var(--admin-border, #e5e7eb); padding-bottom: .5rem; scrollbar-width: none; }
    .absensi-nav::-webkit-scrollbar { display: none; }
    .absensi-nav .nav-link { border-radius: .6rem; font-weight: 600; font-size: .88rem; padding: .45rem .85rem; color: var(--admin-muted, #6b7280); }
    .absensi-nav .nav-link:hover { background: var(--admin-surface-soft, #f8fafc); color: var(--admin-text, #1f2937); }
    .absensi-nav .nav-link.active { background: var(--hr-primary, #880f12); color: #fff; }

    /* ---------- Kartu angka (bisa diklik sebagai filter) ---------- */
    .absensi-kartu { border: 1px solid transparent; transition: border-color .15s, transform .15s, box-shadow .15s; }
    a.absensi-kartu:hover { border-color: rgba(136, 15, 18, .3); transform: translateY(-1px); }
    .absensi-kartu.aktif { border-color: var(--hr-primary, #880f12); box-shadow: 0 0 0 3px rgba(136, 15, 18, .12); }
    .absensi-kartu-ikon { width: 42px; height: 42px; border-radius: .75rem; display: inline-grid; place-items: center; font-size: 1.2rem; flex: none; }
    .absensi-kartu-ikon.success { background: #e8f6ee; color: #198754; }
    .absensi-kartu-ikon.danger { background: #fdecec; color: #dc3545; }
    .absensi-kartu-ikon.warning { background: #fff4de; color: #b7791f; }
    .absensi-kartu-ikon.dark { background: #eceef1; color: #343a40; }
    .absensi-kartu-ikon.info { background: #e7f4fb; color: #0b7aa6; }
    .absensi-kartu-ikon.primary { background: #f7e7e7; color: var(--hr-primary, #880f12); }
    .absensi-kartu-ikon.purple { background: #efe9fb; color: #6f42c1; }
    .absensi-kartu-ikon.secondary { background: #eef0f3; color: #6b7280; }

    /* ---------- Panduan langkah persiapan ---------- */
    .abs-langkah { display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: .75rem; }
    .abs-langkah-item { display: flex; gap: .7rem; align-items: flex-start; padding: .85rem; border: 1px solid var(--admin-border, #e5e7eb); border-radius: .8rem; background: var(--admin-surface, #fff); }
    .abs-langkah-item.selesai { background: #f3faf6; border-color: #cfe9d9; }
    .abs-langkah-no { width: 28px; height: 28px; border-radius: 50%; display: inline-grid; place-items: center; font-weight: 700; font-size: .85rem; flex: none; background: var(--hr-primary, #880f12); color: #fff; }
    .abs-langkah-item.selesai .abs-langkah-no { background: #198754; }
    @media (max-width: 575.98px) { .abs-langkah-item.selesai { display: none; } } /* HP: cukup langkah yang belum selesai */
    .abs-progres { height: 6px; border-radius: 3px; background: var(--admin-border, #e5e7eb); overflow: hidden; }
    .abs-progres > span { display: block; height: 100%; background: #198754; }

    /* ---------- Tampilan jam masuk → keluar ---------- */
    .abs-jam { display: inline-flex; align-items: center; gap: .35rem; font-variant-numeric: tabular-nums; font-weight: 600; white-space: nowrap; }
    .abs-jam .panah { color: var(--admin-muted, #9ca3af); font-weight: 400; }
    .abs-jam .kosong { color: var(--admin-muted, #9ca3af); font-weight: 400; }
    .abs-jadwal { font-size: .75rem; color: var(--admin-muted, #6b7280); white-space: nowrap; }
    .abs-info { color: var(--admin-muted, #6b7280); cursor: help; }

    /* ---------- Tombol pilihan cepat (chip) ---------- */
    .abs-chip { border: 1px solid var(--admin-border, #dbe4ef); background: var(--admin-surface, #fff); border-radius: 999px; padding: .25rem .7rem; font-size: .8rem; color: var(--admin-text, #1f2937); transition: all .12s; }
    .abs-chip:hover, .abs-chip.aktif { border-color: var(--hr-primary, #880f12); color: var(--hr-primary, #880f12); background: rgba(136, 15, 18, .05); }

    /* ---------- Pilihan mode (kartu radio) ---------- */
    .abs-pilihan { display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: .5rem; }
    .abs-pilihan input { position: absolute; opacity: 0; pointer-events: none; }
    .abs-pilihan label { display: flex; flex-direction: column; gap: .2rem; height: 100%; padding: .7rem .8rem; border: 1.5px solid var(--admin-border, #dbe4ef); border-radius: .75rem; cursor: pointer; font-size: .82rem; transition: all .12s; }
    .abs-pilihan label i { font-size: 1.15rem; color: var(--admin-muted, #6b7280); }
    .abs-pilihan label strong { font-size: .88rem; }
    .abs-pilihan input:checked + label { border-color: var(--hr-primary, #880f12); background: rgba(136, 15, 18, .05); }
    .abs-pilihan input:checked + label i { color: var(--hr-primary, #880f12); }
    .abs-pilihan input:focus-visible + label { box-shadow: 0 0 0 3px rgba(136, 15, 18, .2); }

    /* ---------- Area unggah (seret & lepas) ---------- */
    .abs-dropzone { position: relative; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: .4rem; text-align: center;
        padding: 2rem 1rem; border: 2px dashed var(--admin-border, #cbd5e1); border-radius: 1rem; background: var(--admin-surface-soft, #f8fafc); cursor: pointer; transition: all .15s; }
    .abs-dropzone:hover, .abs-dropzone.seret { border-color: var(--hr-primary, #880f12); background: rgba(136, 15, 18, .04); }
    .abs-dropzone.ada-file { border-style: solid; border-color: #198754; background: #f3faf6; }
    .abs-dropzone i.bi-cloud-arrow-up { font-size: 2.4rem; color: var(--hr-primary, #880f12); }
    .abs-dropzone input[type=file] { position: absolute; inset: 0; opacity: 0; cursor: pointer; }

    /* ---------- Tahapan import ---------- */
    .abs-tahap { display: flex; align-items: center; gap: .5rem; flex-wrap: wrap; font-size: .85rem; }
    .abs-tahap span { display: inline-flex; align-items: center; gap: .4rem; color: var(--admin-muted, #9ca3af); font-weight: 600; }
    .abs-tahap span b { width: 24px; height: 24px; border-radius: 50%; display: inline-grid; place-items: center; background: var(--admin-border, #e5e7eb); color: var(--admin-muted, #6b7280); font-size: .75rem; }
    .abs-tahap span.aktif { color: var(--hr-primary, #880f12); }
    .abs-tahap span.aktif b { background: var(--hr-primary, #880f12); color: #fff; }
    .abs-tahap span.selesai b { background: #198754; color: #fff; }
    .abs-tahap .garis { flex: 0 0 28px; height: 2px; background: var(--admin-border, #e5e7eb); }

    /* ---------- Bilah aksi saat ada pegawai dicentang ---------- */
    .abs-aksi-bar { position: sticky; bottom: 1rem; z-index: 20; display: flex; flex-wrap: wrap; align-items: center; gap: .5rem; margin: .75rem; padding: .65rem .9rem;
        border-radius: .9rem; background: #1f2937; color: #fff; box-shadow: 0 12px 30px rgba(15, 23, 42, .25); }
    .abs-aksi-bar[hidden] { display: none !important; }
    .abs-aksi-bar .btn { --bs-btn-padding-y: .3rem; }

    /* ---------- Kalender bulanan ---------- */
    .abs-kalender { display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); gap: .4rem; }
    .abs-kalender .kepala { font-size: .72rem; font-weight: 700; text-transform: uppercase; color: var(--admin-muted, #6b7280); text-align: center; padding-bottom: .2rem; }
    .abs-hari { min-height: 78px; padding: .4rem .45rem; border-radius: .65rem; border: 1px solid var(--admin-border, #e5e7eb); background: var(--admin-surface, #fff);
        display: flex; flex-direction: column; gap: .15rem; font-size: .72rem; position: relative; text-align: left; }
    button.abs-hari { cursor: pointer; }
    button.abs-hari:hover { border-color: var(--hr-primary, #880f12); }
    .abs-hari .tgl { font-weight: 700; font-size: .85rem; }
    .abs-hari .jam { font-variant-numeric: tabular-nums; color: var(--admin-text, #374151); }
    .abs-hari.luar { visibility: hidden; }
    .abs-hari.hari-ini { box-shadow: inset 0 0 0 2px var(--hr-primary, #880f12); }
    .abs-hari .titik { width: 8px; height: 8px; border-radius: 50%; position: absolute; top: .55rem; right: .5rem; }
    .abs-st-on_time { background: #f3faf6; } .abs-st-on_time .titik { background: #198754; }
    .abs-st-telat { background: #fdf1f1; } .abs-st-telat .titik { background: #dc3545; }
    .abs-st-telat_izin { background: #fff8e8; } .abs-st-telat_izin .titik { background: #e0a100; }
    .abs-st-alpa { background: #eef0f3; } .abs-st-alpa .titik { background: #343a40; }
    .abs-st-cuti, .abs-st-izin, .abs-st-sakit { background: #eef7fc; } .abs-st-cuti .titik, .abs-st-izin .titik, .abs-st-sakit .titik { background: #0b7aa6; }
    .abs-st-dinas_luar, .abs-st-hadir { background: #f7eeee; } .abs-st-dinas_luar .titik, .abs-st-hadir .titik { background: var(--hr-primary, #880f12); }
    .abs-st-libur, .abs-st-belum, .abs-st-belum_scan { background: var(--admin-surface-soft, #f8fafc); color: var(--admin-muted, #9ca3af); }
    .abs-legenda { display: flex; flex-wrap: wrap; gap: .4rem 1rem; font-size: .78rem; color: var(--admin-muted, #6b7280); }
    .abs-legenda span { display: inline-flex; align-items: center; gap: .35rem; }
    .abs-legenda i { width: 10px; height: 10px; border-radius: 50%; display: inline-block; }
    @media (max-width: 575.98px) {
        .abs-kalender { gap: .2rem; }
        .abs-hari { min-height: 54px; padding: .3rem; }
        .abs-hari .jam, .abs-hari .ket { display: none; }
        .abs-hari .titik { top: auto; bottom: .35rem; right: 50%; transform: translateX(50%); }
    }

    /* Tab di HP digeser ke samping (tidak bertumpuk). */
    .abs-tab-geser { scrollbar-width: none; }
    .abs-tab-geser::-webkit-scrollbar { display: none; }

    /* ---------- Lain-lain ---------- */
    .text-bg-purple { background: #6f42c1 !important; color: #fff !important; }
    .absensi-angka { font-variant-numeric: tabular-nums; white-space: nowrap; }
    .absensi-nol { color: var(--admin-muted, #9ca3af); }
    .absensi-rekap th { white-space: nowrap; font-size: .72rem; }
    .absensi-hari-libur { background: var(--admin-surface-soft, #f8fafc); }
    .absensi-jadwal-hari .form-check { min-width: 64px; }
    .abs-kosong-besar { padding: 2.5rem 1rem; text-align: center; }
    .abs-kosong-besar i { font-size: 2.6rem; color: var(--admin-muted, #9ca3af); }
    html[data-theme="dark"] .abs-langkah-item.selesai, html[data-theme="dark"] .abs-dropzone.ada-file { background: rgba(25, 135, 84, .12); }
    html[data-theme="dark"] .abs-hari { background: transparent; }
</style>
@endonce
