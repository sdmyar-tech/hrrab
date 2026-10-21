{{-- Gaya & skrip bagan hanya baca. Dimuat sekali per halaman; halaman yang memuat bagan lewat fetch menyertakannya langsung
     (skrip di HTML hasil fetch tidak dijalankan browser). --}}
@include('admin.pengajuan-formasi._bagan-gaya')
@once
<style>
    /* width 0 + min-width 100%: bagan tidak ikut menentukan lebar sel tabel (rincian di bawah baris daftar),
       tetap selebar wadahnya; bagan lebar digulir di dalam kotaknya. */
    .bagan-statis { width: 0; min-width: 100%; }
    .bagan-statis .bagan-layar { cursor: default; max-height: 60vh; }
    .bagan-statis .bagan-rak:not(.buka) > .lebih { display: none; }
</style>
<script>
    // Tombol "+N lainnya" pada rak bagan: buka/ringkas (dipasang sekali untuk semua bagan di halaman).
    document.addEventListener('click', function (e) {
        const t = e.target.closest('[data-bagan-lagi]');
        if (!t) return;
        const rak = t.parentElement;
        rak.classList.toggle('buka');
        t.textContent = rak.classList.contains('buka') ? 'Ringkas' : t.dataset.baganLagi;
    });
</script>
@endonce
