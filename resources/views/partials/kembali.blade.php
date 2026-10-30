{{--
    Tautan kembali di atas judul halaman dalam (detail / tambah / ubah) — pengganti breadcrumb satu tingkat.
    Parameter: $ke (URL tujuan), opsional $label (nama halaman tujuan, mis. "Lowongan").
--}}
<nav aria-label="Navigasi halaman">
    <a href="{{ $ke }}" class="text-decoration-none text-muted small d-inline-block mb-2">
        <i class="bi bi-arrow-left" aria-hidden="true"></i> Kembali{{ ! empty($label) ? ' ke ' . $label : '' }}
    </a>
</nav>
