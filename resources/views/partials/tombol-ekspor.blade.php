<a href="{{ route($route, array_merge($param ?? [], request()->except('page'))) }}" class="btn btn-outline-success {{ $ukuran ?? 'btn-sm' }} text-nowrap"
    title="Unduh Excel sesuai filter aktif (data terbaru di baris paling bawah)">
    <i class="bi bi-file-earmark-excel me-1" aria-hidden="true"></i>Ekspor Excel
</a>
