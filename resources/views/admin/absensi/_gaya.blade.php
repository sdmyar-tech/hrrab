@once
<style>
    .absensi-nav .nav-link { border-radius: .6rem; font-weight: 600; font-size: .88rem; padding: .45rem .85rem; }
    .absensi-nav .nav-link:not(.active) { color: var(--admin-muted, #6b7280); }
    .absensi-nav .nav-link.active { background: var(--hr-primary, #880f12); }
    .absensi-kartu { border: 1px solid transparent; transition: border-color .15s, transform .15s; }
    .absensi-kartu:hover { border-color: rgba(136, 15, 18, .3); transform: translateY(-1px); }
    .absensi-kartu.aktif { border-color: var(--hr-primary, #880f12); box-shadow: 0 0 0 2px rgba(136, 15, 18, .12); }
    .absensi-kartu-ikon { width: 40px; height: 40px; border-radius: .7rem; display: inline-grid; place-items: center; font-size: 1.2rem; background: var(--admin-surface-soft, #f8fafc); flex: none; }
    .text-bg-purple { background: #6f42c1 !important; color: #fff !important; }
    .absensi-angka { font-variant-numeric: tabular-nums; white-space: nowrap; }
    .absensi-nol { color: var(--admin-muted, #9ca3af); }
    .absensi-rekap th { white-space: nowrap; font-size: .72rem; }
    .absensi-hari-libur { background: var(--admin-surface-soft, #f8fafc); }
    .absensi-jadwal-hari .form-check { min-width: 64px; }
</style>
@endonce
