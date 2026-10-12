@once
<style>
    .entitas-kartu { transition: border-color .15s, transform .15s; border: 1px solid transparent; }
    .entitas-kartu:hover { border-color: rgba(136, 15, 18, .35); transform: translateY(-1px); }
    .entitas-ikon { display: grid; place-items: center; width: 2.4rem; height: 2.4rem; flex-shrink: 0; border-radius: .7rem; background: rgba(136, 15, 18, .1); color: #880f12; font-size: 1.15rem; }
    .kolom-data { border-bottom: 1px dashed var(--bs-border-color); padding: .65rem 0; }
    .kolom-data:last-child { border-bottom: 0; }
    .kolom-data .nama-kolom { font-family: var(--bs-font-monospace); font-size: .8rem; }
    .kolom-data.berubah { background: rgba(255, 193, 7, .12); margin: 0 -.75rem; padding-left: .75rem; padding-right: .75rem; border-radius: .5rem; }
    .kolom-data textarea.json { font-family: var(--bs-font-monospace); font-size: .8rem; }
</style>
@endonce
