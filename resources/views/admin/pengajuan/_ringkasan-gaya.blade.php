{{-- Gaya ringkasan penyetuju (dipakai halaman daftar & detail cuti, lembur, izin keluar, formasi). Token saja (Batch 5). --}}
@once
<style>
    .ringkasan .min-w-0, .rk-ringkas .min-w-0 { min-width: 0; }
    .ringkasan { display: grid; gap: var(--hr-space-4); font-size: var(--hr-text-sm); text-align: left; }
    .rk-kepala { display: flex; align-items: flex-start; gap: var(--hr-space-3); }
    .rk-ikon { flex: none; width: 40px; height: 40px; border-radius: 50%; display: grid; place-items: center; background: var(--hr-primary-soft); color: var(--hr-primary); font-size: var(--hr-text-lg); }
    .rk-nama { font-weight: var(--hr-weight-semibold); font-size: var(--hr-text-base); color: var(--admin-heading); }
    .rk-sub { color: var(--admin-text); }
    .rk-meta, .rk-catatan { color: var(--admin-muted); font-size: var(--hr-text-xs); }
    .rk-catatan { display: block; }
    .rk-judul { margin-left: auto; flex: none; padding: 2px var(--hr-space-2); border-radius: var(--hr-radius-pill); background: var(--admin-surface-soft); border: 1px solid var(--admin-border); font-weight: var(--hr-weight-semibold); font-size: var(--hr-text-xs); white-space: nowrap; }
    .rk-peringatan { display: flex; gap: var(--hr-space-2); align-items: flex-start; padding: var(--hr-space-2) var(--hr-space-3); border-radius: var(--hr-radius-md); background: var(--hr-warning-soft); color: var(--hr-warning-dark); }
    .rk-peringatan .bi { flex: none; margin-top: 2px; color: var(--hr-warning); }
    .rk-peringatan.kecil { padding: var(--hr-space-1) var(--hr-space-2); font-size: var(--hr-text-xs); }
    .rk-angka { display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: var(--hr-space-2); }
    .rk-kartu { display: flex; flex-direction: column; gap: 2px; padding: var(--hr-space-2) var(--hr-space-3); border: 1px solid var(--admin-border); border-radius: var(--hr-radius-md); background: var(--admin-surface); }
    .rk-kartu strong { font-size: var(--hr-text-base); color: var(--admin-heading); }
    .rk-label { font-size: var(--hr-text-xs); color: var(--admin-muted); font-weight: var(--hr-weight-semibold); }
    .rk-kartu.waspada { border-color: transparent; background: var(--hr-warning-soft); }
    .rk-kartu.bahaya { border-color: transparent; background: var(--hr-danger-soft); }
    .rk-kartu.bahaya strong { color: var(--hr-danger); }
    .rk-grid { display: grid; gap: var(--hr-space-4); grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); }
    .rk-grid.rk-grid-tunggal { grid-template-columns: minmax(0, 1fr); }
    .ringkasan .rk-h { font-size: var(--hr-text-xs); font-weight: var(--hr-weight-semibold); letter-spacing: .04em; text-transform: uppercase; color: var(--admin-muted); margin: 0 0 var(--hr-space-2); }
    .rk-detail { display: grid; grid-template-columns: max-content 1fr; gap: var(--hr-space-1) var(--hr-space-4); margin: 0; }
    .rk-detail dt { color: var(--admin-muted); font-weight: var(--hr-weight-medium); }
    .rk-detail dd { margin: 0; white-space: pre-line; overflow-wrap: anywhere; color: var(--admin-heading); }
    @media (max-width: 575.98px) { .rk-detail { grid-template-columns: minmax(0, 1fr); } .rk-detail dd { margin-bottom: var(--hr-space-2); } }
    /* Alur: garis tegak dengan titik status. */
    .rk-alur { list-style: none; margin: 0; padding: 0; }
    .rk-alur li { position: relative; display: flex; gap: var(--hr-space-2); padding-bottom: var(--hr-space-2); }
    .rk-alur li:not(:last-child)::before { content: ''; position: absolute; left: 7px; top: 18px; bottom: 0; width: 2px; background: var(--admin-border); }
    .rk-alur li > .bi { flex: none; font-size: var(--hr-text-base); line-height: 1.2; background: var(--admin-surface); }
    .rk-alur li > div { display: flex; flex-direction: column; min-width: 0; }
    .rk-alur .selesai > .bi, .rk-alur-mini .selesai .bi { color: var(--hr-success); }
    .rk-alur .berjalan > .bi, .rk-alur-mini .berjalan .bi { color: var(--hr-warning); }
    .rk-alur .ditolak > .bi, .rk-alur-mini .ditolak .bi { color: var(--hr-danger); }
    .rk-alur .menunggu > .bi, .rk-alur .lewati > .bi, .rk-alur-mini .menunggu .bi, .rk-alur-mini .lewati .bi { color: var(--admin-muted); }
    .rk-alur .lewati strong { color: var(--admin-muted); text-decoration: line-through; }
    .rk-riwayat { display: flex; align-items: center; justify-content: space-between; gap: var(--hr-space-2); padding: var(--hr-space-2) 0; border-bottom: 1px dashed var(--admin-border); }
    .rk-riwayat:last-child { border-bottom: 0; }

    /* Versi baris di halaman daftar. */
    .rk-baris > td { background: var(--admin-surface-soft) !important; border-top: 0; padding-top: var(--hr-space-1) !important; }
    .rk-ringkas { display: grid; gap: var(--hr-space-2); font-size: var(--hr-text-xs); }
    .rk-chips { display: flex; flex-wrap: wrap; gap: var(--hr-space-1); }
    .rk-chip { padding: 2px var(--hr-space-2); border-radius: var(--hr-radius-pill); border: 1px solid var(--admin-border); background: var(--admin-surface); }
    .rk-chip .rk-catatan { display: inline; }
    .rk-chip.waspada { border-color: transparent; background: var(--hr-warning-soft); color: var(--hr-warning-dark); }
    .rk-chip.bahaya { border-color: transparent; background: var(--hr-danger-soft); color: var(--hr-danger); }
    .rk-alur-mini, .rk-riwayat-mini { display: flex; flex-wrap: wrap; align-items: center; gap: var(--hr-space-1) var(--hr-space-2); }
    .rk-alur-mini > span { display: inline-flex; align-items: center; gap: var(--hr-space-1); }
    .rk-alur-mini .rk-catatan { display: inline; }
    .rk-panah { font-size: var(--hr-text-xs); color: var(--admin-muted); }
    .rk-riwayat-mini > span:not(.rk-label) { white-space: nowrap; }
    .rk-lengkap summary { cursor: pointer; color: var(--hr-primary); font-weight: var(--hr-weight-semibold); width: max-content; }
    .rk-lengkap[open] summary { margin-bottom: var(--hr-space-2); }
    .rk-lengkap .ringkasan { padding: var(--hr-space-3); border: 1px solid var(--admin-border); border-radius: var(--hr-radius-md); background: var(--admin-surface); }
    html[data-theme="dark"] .rk-peringatan, html[data-theme="dark"] .rk-chip.waspada { color: var(--hr-warning); }
</style>
@endonce
