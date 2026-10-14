@once
<style>
    .viz {
        --viz-surface: var(--bs-body-bg, #fff);
        --viz-track: #eef0f3;
        --viz-text: #1f2328;
        --viz-muted: #6b6f76;
        --viz-1: #2a78d6; --viz-2: #eb6834; --viz-3: #1baf7a; --viz-4: #eda100;
        --viz-5: #e87ba4; --viz-6: #008300; --viz-7: #4a3aa7; --viz-8: #e34948;
        --viz-netral: #a3a7ad;
        --viz-seq: #a3292c;
    }
    html[data-theme="dark"] .viz {
        --viz-track: #2b2f36;
        --viz-text: #f1f3f5;
        --viz-muted: #b5b9bf;
        --viz-1: #3987e5; --viz-2: #d95926; --viz-3: #199e70; --viz-4: #c98500;
        --viz-5: #d55181; --viz-6: #008300; --viz-7: #9085e9; --viz-8: #e66767;
        --viz-netral: #6c7178;
        --viz-seq: #e06a6d;
    }

    .viz-stack { display: flex; gap: 2px; height: 14px; border-radius: 4px; overflow: hidden; background: var(--viz-track); }
    .viz-stack > span { display: block; height: 100%; min-width: 3px; position: relative; }
    .viz-stack-sm { height: 8px; }
    .viz-legend { list-style: none; padding: 0; margin: .6rem 0 0; display: grid; gap: .25rem; font-size: .8rem; color: var(--viz-text); }
    .viz-legend li { display: flex; align-items: center; gap: .45rem; }
    .viz-legend .sw { width: 10px; height: 10px; border-radius: 2px; flex: none; }
    .viz-legend .val { margin-left: auto; font-variant-numeric: tabular-nums; color: var(--viz-muted); }
    .viz-legend .val strong { color: var(--viz-text); font-weight: 600; }

    .viz-hbar { display: grid; grid-template-columns: minmax(70px, 34%) 1fr 2.6rem; align-items: center; gap: .5rem; font-size: .8rem; color: var(--viz-text); margin-bottom: .35rem; }
    .viz-hbar .track { height: 10px; background: var(--viz-track); border-radius: 4px; overflow: hidden; }
    .viz-hbar .fill { display: block; height: 100%; background: var(--viz-seq); border-radius: 0 4px 4px 0; }
    .viz-hbar .num { text-align: right; font-variant-numeric: tabular-nums; font-weight: 600; }
    .viz-hbar .lbl { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: var(--viz-muted); }

    .viz-cols { display: flex; align-items: flex-end; gap: 10px; height: 150px; padding-top: 1.4rem; border-bottom: 1px solid var(--viz-track); }
    .viz-col { flex: 1; height: 100%; display: flex; flex-direction: column; justify-content: flex-end; align-items: center; position: relative; outline: none; }
    .viz-col .bar { width: 100%; max-width: 42px; background: var(--viz-seq); border-radius: 4px 4px 0 0; min-height: 2px; transition: opacity .15s; }
    .viz-cols:hover .viz-col .bar { opacity: .55; }
    .viz-cols .viz-col:hover .bar, .viz-col:focus-visible .bar { opacity: 1; }
    .viz-col .tag { position: absolute; top: -1.3rem; font-size: .72rem; font-weight: 600; color: var(--viz-text); font-variant-numeric: tabular-nums; }
    .viz-col .tip { position: absolute; bottom: calc(100% + 4px); left: 50%; transform: translateX(-50%); white-space: nowrap; background: var(--viz-text); color: var(--viz-surface);
        font-size: .72rem; padding: .25rem .5rem; border-radius: 4px; pointer-events: none; opacity: 0; transition: opacity .12s; z-index: 2; }
    .viz-col:hover .tip, .viz-col:focus-visible .tip { opacity: 1; }
    .viz-col:hover .tag, .viz-col:focus-visible .tag { visibility: hidden; }
    .viz-xlab { display: flex; gap: 10px; margin-top: .35rem; }
    .viz-xlab span { flex: 1; text-align: center; font-size: .72rem; color: var(--viz-muted); }

    .viz-empty { font-size: .8rem; color: var(--viz-muted); }

    /* Elemen grafik yang bisa diklik ke detail. */
    .viz-stack > a { display: block; height: 100%; min-width: 3px; transition: filter .15s; }
    .viz-stack > a:hover { filter: brightness(1.12) saturate(1.1); }
    .viz-legend-link { display: flex; align-items: center; gap: .45rem; width: 100%; padding: .1rem .3rem; margin: -.1rem -.3rem; border-radius: .35rem; color: inherit !important; text-decoration: none !important; }
    .viz-legend-link:hover { background: var(--admin-surface-soft, #f8f9fb); }
    a.viz-col { text-decoration: none; cursor: pointer; }
    .viz-hbar a.lbl:hover { color: var(--hr-primary, #8b1215) !important; text-decoration: underline; }
    /* Baris daftar di panel dashboard (kontrak, cuti, pegawai baru). */
    .dash-baris { padding: .4rem .5rem; margin: 0 -.5rem 2px; border-radius: .5rem; color: inherit !important; text-decoration: none !important; }
    a.dash-baris[href]:hover { background: var(--admin-surface-soft, #f8f9fb); }
    a.dash-baris[href]:hover .fw-semibold { color: var(--hr-primary, #8b1215); }
    .dash-link { color: #880f12; }
    .dash-link:hover { color: #880f12; text-decoration: underline; }
    html[data-theme="dark"] .dash-link { color: #f08a8c; }
    .dash-hero { background: linear-gradient(135deg, rgba(136, 15, 18, .08), rgba(42, 120, 214, .06)); border-radius: 1rem; }
    .dash-avatar { width: 34px; height: 34px; border-radius: 50%; object-fit: cover; background: var(--viz-track); flex: none; display: inline-flex; align-items: center; justify-content: center; font-size: .75rem; font-weight: 600; color: var(--viz-muted); }
    .dash-scroll { max-height: 420px; overflow-y: auto; }
    /* Isi masih bisa digulir: baris terbawah memudar agar tidak terlihat "terpotong". */
    .dash-scroll.ada-lanjutan { -webkit-mask-image: linear-gradient(to bottom, #000 calc(100% - 48px), transparent); mask-image: linear-gradient(to bottom, #000 calc(100% - 48px), transparent); }
    .dash-mini-stat { border: 1px solid var(--viz-track); border-radius: .6rem; padding: .55rem .7rem; }
    .dash-mini-stat .n { font-size: 1.15rem; font-weight: 700; line-height: 1.1; }
    .dash-mini-stat .l { font-size: .72rem; color: var(--viz-muted); }
    a.dash-mini-stat { display: flex; flex-direction: column; justify-content: center; height: 100%; color: inherit; text-decoration: none; transition: border-color .15s, background-color .15s; }
    a.dash-mini-stat:hover { border-color: #880f12; background: rgba(136, 15, 18, .04); }
    .dash-mini-stat.besar { padding: .9rem .75rem; }
    .dash-mini-stat.besar .n { font-size: 1.6rem; }
    .dash-mini-stat.besar .l { font-size: .8rem; }

    /* Ringkasan per Unit: grid kartu (responsif tanpa scroll horizontal). */
    .unit-alat { flex: 0 1 420px; align-items: center; align-content: flex-start; }
    /* Di HP header panel menumpuk vertikal: alat cari jangan ikut "melar" tingginya. */
    @media (max-width: 767.98px) { .unit-alat { flex: none; width: 100%; } }
    .unit-alat input { flex: 1 1 150px; min-width: 0; }
    .unit-alat select { flex: 0 1 170px; width: auto; }
    .unit-sw { width: 9px; height: 9px; border-radius: 2px; display: inline-block; flex: none; }
    .unit-kartu {
        display: flex; flex-direction: column; gap: .55rem; height: 100%;
        padding: .85rem .95rem; border: 1px solid var(--viz-track); border-radius: .8rem;
        background: var(--viz-surface); color: var(--viz-text) !important; text-decoration: none !important;
        transition: border-color .15s, box-shadow .15s, transform .15s;
    }
    .unit-kartu:hover, .unit-kartu:focus-visible { border-color: rgba(136, 15, 18, .45); box-shadow: 0 6px 18px rgba(15, 23, 42, .08); transform: translateY(-1px); }
    .unit-nama { font-weight: 700; font-size: .9rem; color: #880f12; line-height: 1.25; overflow-wrap: anywhere; }
    html[data-theme="dark"] .unit-nama { color: #f08a8c; }
    .unit-jumlah { flex: none; font-size: .75rem; color: var(--viz-muted); white-space: nowrap; }
    .unit-jumlah strong { font-size: 1.05rem; color: var(--viz-text); font-variant-numeric: tabular-nums; }
    .unit-kepala { display: flex; align-items: center; gap: .4rem; min-width: 0; font-size: .8rem; color: var(--viz-muted); }
    .unit-kepala.kosong { color: #dc3545; font-weight: 600; }
    .unit-rincian { display: flex; flex-wrap: wrap; align-items: center; gap: .25rem .7rem; font-size: .74rem; color: var(--viz-muted); font-variant-numeric: tabular-nums; }
    .unit-rincian > span { display: inline-flex; align-items: center; gap: .3rem; white-space: nowrap; }
    .unit-chip-wrap { display: flex; flex-wrap: wrap; gap: .3rem; margin-top: auto; padding-top: .55rem; border-top: 1px dashed var(--viz-track); }
    .unit-chip { display: inline-flex; align-items: center; gap: .3rem; padding: .15rem .5rem; border-radius: 999px; font-size: .7rem; font-weight: 600; line-height: 1.4; }
    .unit-chip.is-danger { background: rgba(220, 53, 69, .1); color: #b42318; }
    .unit-chip.is-warning { background: rgba(217, 119, 6, .12); color: #a15c00; }
    .unit-chip.is-info { background: rgba(42, 120, 214, .1); color: #1f5fae; }
    .unit-chip.is-netral { background: var(--viz-track); color: var(--viz-muted); }
    .unit-chip.is-aman { background: transparent; color: var(--viz-muted); font-weight: 500; padding-inline: 0; }
    html[data-theme="dark"] .unit-chip.is-danger { color: #f19a9a; }
    html[data-theme="dark"] .unit-chip.is-warning { color: #f0b85a; }
    html[data-theme="dark"] .unit-chip.is-info { color: #8fbaf0; }
</style>
<script>
    // Tandai area gulir yang masih punya isi di bawah (untuk efek pudar).
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.dash-scroll').forEach(function (el) {
            const cek = function () { el.classList.toggle('ada-lanjutan', el.scrollTop + el.clientHeight < el.scrollHeight - 4); };
            el.addEventListener('scroll', cek, { passive: true });
            window.addEventListener('resize', cek);
            new MutationObserver(cek).observe(el, { childList: true, subtree: true, attributes: true, attributeFilter: ['hidden', 'style'] });
            cek();
        });
    });
</script>
@endonce
