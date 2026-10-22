{{-- Parameter sengaja berawalan "ekspor" agar tidak tertimpa variabel halaman (mis. $label dari @foreach). --}}
<a href="{{ route($eksporRoute, array_merge($eksporParam ?? [], request()->except('page'))) }}"
    class="btn btn-outline-secondary btn-ekspor {{ $eksporUkuran ?? 'btn-sm' }} text-nowrap"
    title="Unduh Excel sesuai filter aktif (data terbaru di baris paling bawah)">
    <i class="bi {{ $eksporIkon ?? 'bi-file-earmark-excel' }}" aria-hidden="true"></i>{{ $eksporTeks ?? 'Ekspor Excel' }}
</a>
