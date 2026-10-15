{{-- Gaya bagan unit (form & detail pengajuan formasi). Hijau = aktif, abu = tidak aktif, merah = posisi kosong/baru. --}}
@once
<style>
    .bagan { --b-hijau: #15803d; --b-hijau-bg: #e8f5ec; --b-abu: #6b7280; --b-abu-bg: #f1f2f4; --b-merah: #b42318; --b-merah-bg: #fdecec;
        display: flex; flex-direction: column; align-items: center; gap: 0; padding: .75rem; border: 1px solid var(--admin-border); border-radius: 12px; background: var(--admin-surface-soft); }
    .bagan-legenda { display: flex; flex-wrap: wrap; gap: .4rem 1rem; font-size: .75rem; color: var(--admin-muted); margin-bottom: .5rem; }
    .bagan-legenda span { display: inline-flex; align-items: center; gap: .35rem; }
    .bagan-legenda i.kotak { width: 12px; height: 12px; border-radius: 3px; display: inline-block; }
    .bagan-kepala { position: relative; }
    .bagan-garis { width: 2px; height: 16px; background: var(--admin-border); }
    .bagan-grup-wrap { display: flex; flex-wrap: wrap; justify-content: center; gap: .75rem; width: 100%; padding-top: .75rem; border-top: 2px solid var(--admin-border); }
    .bagan-grup { flex: 0 1 220px; min-width: 170px; border: 1px dashed transparent; border-radius: 10px; padding: .35rem; transition: border-color .15s, background .15s; }
    .bagan-grup.terpilih { border-color: var(--hr-primary); background: var(--hr-primary-soft); }
    .bagan-jabatan { font-size: .68rem; font-weight: 700; letter-spacing: .04em; color: var(--admin-muted); text-transform: uppercase; text-align: center; margin-bottom: .35rem; overflow-wrap: anywhere; }
    .bagan-anggota { display: grid; gap: .3rem; }
    .bagan-node { position: relative; display: flex; align-items: center; gap: .45rem; width: 100%; padding: .4rem .55rem; border-radius: 8px; border: 1.5px solid; font-size: .78rem; font-weight: 600; text-align: left; line-height: 1.25; background: #fff; }
    .bagan-node .bi { flex: none; font-size: .95rem; }
    .bagan-node .nm { min-width: 0; overflow-wrap: anywhere; }
    .bagan-node.aktif { border-color: var(--b-hijau); background: var(--b-hijau-bg); color: #14532d; }
    .bagan-node.nonaktif { border-color: #c4c7cc; background: var(--b-abu-bg); color: var(--b-abu); }
    .bagan-node.kosong { border-style: dashed; border-color: var(--b-merah); background: var(--b-merah-bg); color: var(--b-merah); }
    .bagan-node.kepala { border-color: var(--hr-primary); background: #fff; color: var(--admin-text); min-width: 200px; justify-content: center; }
    button.bagan-node { cursor: pointer; transition: transform .12s, box-shadow .12s; }
    button.bagan-node:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 3px 10px rgba(16, 24, 40, .12); }
    button.bagan-node:disabled { cursor: default; opacity: .75; }
    /* Lencana bulat di pojok: ganti (reverse) untuk pegawai yang digantikan, plus untuk posisi baru. */
    .bagan-lencana { position: absolute; top: -8px; right: -8px; width: 22px; height: 22px; border-radius: 50%; display: grid; place-items: center; color: #fff; font-size: .75rem; box-shadow: 0 0 0 2px #fff; }
    .bagan-node.diganti { box-shadow: 0 0 0 2px var(--hr-primary); }
    .bagan-node.diganti .bagan-lencana { background: var(--hr-primary); }
    .bagan-node.kosong .bagan-lencana { background: var(--b-merah); }
    .bagan-kosong-info { font-size: .8rem; color: var(--admin-muted); text-align: center; padding: .75rem; }
    html[data-theme="dark"] .bagan-node { background: var(--admin-surface); }
    html[data-theme="dark"] .bagan-node.aktif { background: rgba(34, 197, 94, .12); color: #86efac; }
    html[data-theme="dark"] .bagan-node.nonaktif { background: rgba(148, 163, 184, .1); color: #94a3b8; }
    html[data-theme="dark"] .bagan-node.kosong { background: rgba(239, 68, 68, .1); color: #fca5a5; }
</style>
@endonce
