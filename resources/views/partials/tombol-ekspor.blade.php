{{-- Parameter sengaja berawalan "ekspor" agar tidak tertimpa variabel halaman (mis. $label dari @foreach).
     Ikon sengaja tetap (tidak bisa diganti) agar tombol Ekspor sama di semua halaman. --}}
<a href="{{ route($eksporRoute, array_merge($eksporParam ?? [], request()->except('page'))) }}"
    class="btn btn-outline-secondary btn-ekspor {{ $eksporUkuran ?? '' }} text-nowrap"
    title="Unduh Excel sesuai filter aktif (data terbaru di baris paling bawah)">
    <i class="bi bi-file-earmark-arrow-down" aria-hidden="true"></i>{{ $eksporTeks ?? 'Ekspor Excel' }}
</a>
