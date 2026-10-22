<div class="pd-grid">
    @foreach ($items as $label => $value)
        @php $panjang = is_string($value) && mb_strlen($value) > 60; @endphp
        <div class="pd-item {{ $panjang ? 'pd-item-wide' : '' }}">
            <span class="pd-label">{{ $label }}</span>
            <span class="pd-value {{ filled($value) ? '' : 'is-empty' }}">{{ filled($value) ? $value : 'Belum diisi' }}</span>
        </div>
    @endforeach
</div>
