{{--
    Saring kartu menurut status di halaman (tanpa muat ulang), untuk daftar yang semua datanya sudah dimuat.
    Pasang: chip/tombol [data-saring-status="kunci"] ("semua" = tampilkan semua), kartu [data-status="kunci"]
    di dalam #daftar-lamaran, dan satu elemen [data-saring-kosong-status] (hidden) untuk keadaan kosong.
--}}
@once
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const chip = document.querySelectorAll('[data-saring-status]');
        const kartu = document.querySelectorAll('#daftar-lamaran [data-status]');
        const kosong = document.querySelector('[data-saring-kosong-status]');
        chip.forEach(function (c) {
            c.addEventListener('click', function () {
                const pilih = c.dataset.saringStatus;
                chip.forEach((x) => { x.classList.toggle('is-active', x === c); x.setAttribute('aria-pressed', String(x === c)); });
                let tampil = 0;
                kartu.forEach((k) => { const ok = pilih === 'semua' || k.dataset.status === pilih; k.hidden = !ok; if (ok) tampil++; });
                if (kosong) kosong.hidden = tampil > 0;
            });
        });
    });
</script>
@endonce
