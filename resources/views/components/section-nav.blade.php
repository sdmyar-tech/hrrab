{{--
    Navigasi bagian halaman panjang (kolom kiri lengket): tautan ke #id bagian + penanda bagian yang sedang terlihat saat menggulir.
    <x-section-nav label="Bagian data pegawai" :items="[['id' => 'bagian-biodata', 'label' => 'Biodata', 'icon' => 'bi-person', 'status' => true], ...]" />
    status: true = lengkap (centang hijau) · false = belum lengkap (tanda kuning) · null = tanpa tanda.
    Desktop: daftar vertikal (pill + garis aksen); HP/tablet: baris tab yang bisa digeser.
    Bagian tujuan sebaiknya berkelas .hr-bagian (jarak gulir di bawah topbar).
--}}
@props([
    'items' => [],
    'label' => 'Bagian halaman',
])
<nav {{ $attributes->merge(['class' => 'hr-tabs hr-tabs-vertikal hr-section-nav']) }} aria-label="{{ $label }}" data-section-nav>
    @foreach ($items as $item)
        <a href="#{{ $item['id'] }}" class="hr-tab {{ $loop->first ? 'active' : '' }}" data-nav-bagian @if ($loop->first) aria-current="true" @endif>
            @if (! empty($item['icon']))<i class="bi {{ $item['icon'] }}" aria-hidden="true"></i>@endif
            <span>{{ $item['label'] }}</span>
            @if (($item['status'] ?? null) === true)
                <i class="bi bi-check-circle-fill text-success ms-auto" role="img" aria-label="lengkap"></i>
            @elseif (($item['status'] ?? null) === false)
                <i class="bi bi-exclamation-circle text-warning ms-auto" role="img" aria-label="belum lengkap"></i>
            @endif
        </a>
    @endforeach
</nav>
@once
    <script>
        // Tandai bagian yang sedang terlihat saat menggulir (semua x-section-nav di halaman).
        document.addEventListener('DOMContentLoaded', function () {
            if (!('IntersectionObserver' in window)) return;
            document.querySelectorAll('[data-section-nav]').forEach(function (nav) {
                const tautan = Array.from(nav.querySelectorAll('[data-nav-bagian]'));
                const pengamat = new IntersectionObserver(function (entri) {
                    entri.forEach(function (e) {
                        if (!e.isIntersecting) return;
                        tautan.forEach(function (a) {
                            const aktif = a.getAttribute('href') === '#' + e.target.id;
                            a.classList.toggle('active', aktif);
                            aktif ? a.setAttribute('aria-current', 'true') : a.removeAttribute('aria-current');
                            if (aktif && nav.scrollWidth > nav.clientWidth) a.scrollIntoView({ block: 'nearest', inline: 'nearest' });
                        });
                    });
                }, { rootMargin: '-30% 0px -60% 0px' });
                tautan.forEach(function (a) { const t = document.querySelector(a.getAttribute('href')); if (t) pengamat.observe(t); });
            });
        });
    </script>
@endonce
