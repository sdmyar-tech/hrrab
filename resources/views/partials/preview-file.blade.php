@php
    $url = $path ? \App\Support\Berkas::url($path) : null;
    $ext = $path ? strtolower(pathinfo($path, PATHINFO_EXTENSION)) : null;

    $isImage = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
    $isPdf   = $ext === 'pdf';
@endphp

<div class="dok-kartu {{ $url ? '' : 'kosong' }}">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <small class="text-muted fw-semibold">{{ $label }}</small>
        @if ($url)
            <div class="d-flex gap-1">
                @if ($isImage)
                    <button type="button" class="btn btn-sm btn-light btn-zoom-image" data-src="{{ $url }}" title="Perbesar">
                        <i class="bi bi-arrows-fullscreen"></i>
                    </button>
                @endif
                <a href="{{ $url }}" target="_blank" rel="noopener"
                    class="btn btn-sm btn-light" title="Buka di tab baru">
                    <i class="bi bi-box-arrow-up-right"></i>
                </a>
                <a href="{{ $url }}" download class="btn btn-sm btn-light" title="Download">
                    <i class="bi bi-download"></i>
                </a>
            </div>
        @endif
    </div>

    @if (!$url)
        <div class="dok-kosong">
            <i class="bi bi-file-earmark-x" aria-hidden="true"></i>
            <span>Belum diunggah</span>
            @isset($tautanUnggah)
                <a href="{{ $tautanUnggah }}" class="ms-auto small">Unggah</a>
            @endisset
        </div>
    @elseif ($isImage)
        <div class="text-center">
            <img src="{{ $url }}" alt="{{ $label }}" loading="lazy" decoding="async"
                class="img-fluid rounded border"
                style="max-height: 220px; object-fit: contain; cursor: zoom-in;"
                onclick="document.querySelector('.btn-zoom-image[data-src=\'{{ $url }}\']')?.click();">
        </div>
    @elseif ($isPdf)
        <div class="ratio ratio-4x3 border rounded overflow-hidden">
            <iframe src="{{ $url }}#toolbar=1&navpanes=0" title="{{ $label }}"></iframe>
        </div>
    @else
        <div class="text-center text-muted py-4">
            <i class="bi bi-file-earmark fs-2 d-block mb-2"></i>
            <small>File tidak bisa dipratinjau. Klik tombol di atas untuk membuka.</small>
        </div>
    @endif
</div>
