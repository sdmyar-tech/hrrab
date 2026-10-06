@if (filled($lokasi ?? null))
    @php $peta = \App\Support\Lokasi::isPeta($lokasi) || ! \App\Support\Lokasi::isUrl($lokasi); @endphp
    <span class="lokasi-teks">
        @unless ($tanpaIkon ?? false)<i class="bi {{ $peta ? 'bi-geo-alt' : 'bi-camera-video' }} me-1 text-muted" aria-hidden="true"></i>@endunless{{ \App\Support\Lokasi::label($lokasi) }}
        <a href="{{ \App\Support\Lokasi::url($lokasi) }}" target="_blank" rel="noopener noreferrer" class="ms-1 small text-nowrap"
            title="{{ $peta ? 'Buka di Google Maps' : 'Buka link' }}">
            <i class="bi {{ $peta ? 'bi-map' : 'bi-box-arrow-up-right' }}" aria-hidden="true"></i> {{ $peta ? 'Maps' : 'Buka' }}
        </a>
    </span>
@endif
