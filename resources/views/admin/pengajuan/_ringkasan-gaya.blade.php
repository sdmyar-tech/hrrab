{{-- Gaya ringkasan penyetuju (dipakai halaman daftar & detail cuti, lembur, formasi). --}}
@once
<style>
    .ringkasan .min-w-0, .rk-ringkas .min-w-0 { min-width: 0; }
    .ringkasan { display: grid; gap: .75rem; font-size: .8125rem; text-align: left; }
    .rk-kepala { display: flex; align-items: flex-start; gap: .65rem; }
    .rk-ikon { flex: none; width: 36px; height: 36px; border-radius: 50%; display: grid; place-items: center; background: var(--hr-primary-soft); color: var(--hr-primary); font-size: 1.05rem; }
    .rk-nama { font-weight: 700; font-size: .9rem; }
    .rk-sub { color: var(--admin-text); }
    .rk-meta, .rk-catatan { color: var(--admin-muted); font-size: .75rem; }
    .rk-catatan { display: block; }
    .rk-judul { margin-left: auto; flex: none; padding: .2rem .6rem; border-radius: 999px; background: var(--admin-surface-soft); border: 1px solid var(--admin-border); font-weight: 600; font-size: .75rem; white-space: nowrap; }
    .rk-peringatan { display: flex; gap: .45rem; align-items: flex-start; padding: .45rem .65rem; border-radius: 8px; background: #fff7e6; border: 1px solid #f5d48a; color: #7a4b00; }
    .rk-peringatan .bi { flex: none; margin-top: .1rem; color: #d97706; }
    .rk-peringatan.kecil { padding: .3rem .55rem; font-size: .75rem; }
    .rk-angka { display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: .5rem; }
    .rk-kartu { display: flex; flex-direction: column; padding: .5rem .65rem; border: 1px solid var(--admin-border); border-radius: 9px; background: var(--admin-surface); }
    .rk-kartu strong { font-size: .95rem; }
    .rk-label { font-size: .7rem; color: var(--admin-muted); font-weight: 600; }
    .rk-kartu.waspada { border-color: #f5d48a; background: #fffbeb; }
    .rk-kartu.bahaya { border-color: #f3b4b4; background: #fef2f2; }
    .rk-kartu.bahaya strong { color: #b42318; }
    .rk-grid { display: grid; gap: .75rem; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); }
    .ringkasan .rk-h { font-size: .72rem; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; color: var(--admin-muted); margin: 0 0 .4rem; }
    .rk-detail { display: grid; grid-template-columns: max-content 1fr; gap: .25rem .75rem; margin: 0; }
    .rk-detail dt { color: var(--admin-muted); font-weight: 600; }
    .rk-detail dd { margin: 0; white-space: pre-line; overflow-wrap: anywhere; }
    /* Alur: garis tegak dengan titik status. */
    .rk-alur { list-style: none; margin: 0; padding: 0; }
    .rk-alur li { position: relative; display: flex; gap: .5rem; padding-bottom: .55rem; }
    .rk-alur li:not(:last-child)::before { content: ''; position: absolute; left: 7px; top: 18px; bottom: 0; width: 2px; background: var(--admin-border); }
    .rk-alur li > .bi { flex: none; font-size: 1rem; line-height: 1.2; background: var(--admin-surface); }
    .rk-alur li > div { display: flex; flex-direction: column; min-width: 0; }
    .rk-alur .selesai > .bi, .rk-alur-mini .selesai .bi { color: #15803d; }
    .rk-alur .berjalan > .bi, .rk-alur-mini .berjalan .bi { color: #d97706; }
    .rk-alur .ditolak > .bi, .rk-alur-mini .ditolak .bi { color: #b42318; }
    .rk-alur .menunggu > .bi, .rk-alur .lewati > .bi, .rk-alur-mini .menunggu .bi, .rk-alur-mini .lewati .bi { color: var(--admin-muted); }
    .rk-alur .lewati strong { color: var(--admin-muted); text-decoration: line-through; }
    .rk-riwayat { display: flex; align-items: center; justify-content: space-between; gap: .5rem; padding: .35rem 0; border-bottom: 1px dashed var(--admin-border); }
    .rk-riwayat:last-child { border-bottom: 0; }

    /* Versi baris di halaman daftar. */
    .rk-baris > td { background: var(--admin-surface-soft) !important; border-top: 0; padding-top: .35rem !important; }
    .rk-ringkas { display: grid; gap: .4rem; font-size: .78rem; }
    .rk-chips { display: flex; flex-wrap: wrap; gap: .35rem; }
    .rk-chip { padding: .15rem .55rem; border-radius: 999px; border: 1px solid var(--admin-border); background: var(--admin-surface); }
    .rk-chip .rk-catatan { display: inline; }
    .rk-chip.waspada { border-color: #f5d48a; background: #fffbeb; }
    .rk-chip.bahaya { border-color: #f3b4b4; background: #fef2f2; color: #b42318; }
    .rk-alur-mini, .rk-riwayat-mini { display: flex; flex-wrap: wrap; align-items: center; gap: .3rem .45rem; }
    .rk-alur-mini > span { display: inline-flex; align-items: center; gap: .25rem; }
    .rk-alur-mini .rk-catatan { display: inline; }
    .rk-panah { font-size: .65rem; color: var(--admin-muted); }
    .rk-riwayat-mini > span:not(.rk-label) { white-space: nowrap; }
    .rk-lengkap summary { cursor: pointer; color: var(--hr-primary); font-weight: 600; width: max-content; }
    .rk-lengkap[open] summary { margin-bottom: .5rem; }
    .rk-lengkap .ringkasan { padding: .75rem; border: 1px solid var(--admin-border); border-radius: 10px; background: var(--admin-surface); }

    html[data-theme="dark"] .rk-peringatan { background: rgba(245, 158, 11, .12); border-color: rgba(245, 158, 11, .35); color: #fcd34d; }
    html[data-theme="dark"] .rk-kartu.waspada, html[data-theme="dark"] .rk-chip.waspada { background: rgba(245, 158, 11, .1); border-color: rgba(245, 158, 11, .35); }
    html[data-theme="dark"] .rk-kartu.bahaya, html[data-theme="dark"] .rk-chip.bahaya { background: rgba(239, 68, 68, .1); border-color: rgba(239, 68, 68, .35); }
</style>
@endonce
