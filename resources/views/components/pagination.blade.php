{{--
    Paginasi + ringkasan "Menampilkan 1–20 dari 135". <x-pagination :paginator="$pegawai" />
    Parameter filter di URL ikut terbawa (withQueryString). Tidak tampil bila hanya satu halaman.
--}}
@props(['paginator'])
@if ($paginator instanceof \Illuminate\Contracts\Pagination\Paginator && $paginator->hasPages())
    <nav {{ $attributes->merge(['class' => 'hr-pagination']) }} aria-label="Navigasi halaman data">
        @if ($paginator instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator)
            <p class="hr-pagination-info">
                Menampilkan <strong>{{ number_format($paginator->firstItem() ?? 0, 0, ',', '.') }}–{{ number_format($paginator->lastItem() ?? 0, 0, ',', '.') }}</strong>
                dari <strong>{{ number_format($paginator->total(), 0, ',', '.') }}</strong>
            </p>
        @endif
        {{ $paginator->withQueryString()->onEachSide(1)->links('pagination::bootstrap-5') }}
    </nav>
@endif
