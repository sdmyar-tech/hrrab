{{--
    Baris "tidak ada data" untuk tabel daftar.
    Param: $kosongKolom (colspan), $kosongTeks, opsional $kosongIkon, $kosongReset (URL; tampilkan tautan "Tampilkan semua data" bila diisi).
--}}
<tr>
    <td colspan="{{ $kosongKolom }}">
        @include('partials.kosong', ['teks' => $kosongTeks, 'ikonKosong' => $kosongIkon ?? 'bi-inbox'])
        @if (! empty($kosongReset))
            <p class="text-center small mb-3"><a href="{{ $kosongReset }}">Tampilkan semua data</a></p>
        @endif
    </td>
</tr>
