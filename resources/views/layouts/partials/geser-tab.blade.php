{{-- HP: deretan tab yang bisa digeser — tab aktif dibawa ke layar & tepi kanan memudar selama masih ada tab tersembunyi. --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
    const deret = document.querySelectorAll('.nav-tabs, .nav-pills, .pd-tabs, .absensi-nav');
    const tandai = (el) => el.classList.toggle('bisa-geser-kanan', el.scrollLeft + el.clientWidth < el.scrollWidth - 4);

    deret.forEach(function (el) {
        if (el.closest('.dropdown-menu')) return;
        const aktif = el.querySelector('.active');
        if (aktif && el.scrollWidth > el.clientWidth) {
            // Taruh tab aktif kira-kira di tengah tanpa menggulir halaman.
            el.scrollLeft = aktif.offsetLeft - (el.clientWidth - aktif.offsetWidth) / 2;
        }
        tandai(el);
        el.addEventListener('scroll', () => tandai(el), { passive: true });
    });
    window.addEventListener('resize', () => deret.forEach(tandai));
});
</script>
