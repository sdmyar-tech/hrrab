{{--
    Baris "tidak ada data" untuk tabel daftar.
    Param: $kosongKolom (colspan), $kosongTeks, opsional $kosongIkon, $kosongReset (URL; tampilkan tautan Reset filter bila diisi).
--}}
<tr>
    <td colspan="{{ $kosongKolom }}">
        @include('partials.kosong', ['teks' => $kosongTeks, 'ikonKosong' => $kosongIkon ?? 'bi-inbox'])
        @if (! empty($kosongReset))
            <p class="text-center small mb-3"><a href="{{ $kosongReset }}">Reset filter</a></p>
        @endif
    </td>
</tr>
