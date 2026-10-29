{{--
    Banner umpan balik di area konten (layout panel): galat dengan tombol "Coba lagi", galat isian, dan peringatan.
    Pesan sukses & info tampil sebagai toast (layouts.partials.sweetalert).
    Halaman yang sudah menampilkan galat isiannya sendiri cukup menulis @section('galat-di-halaman', '1').
    Banner dirender di sini lalu dipindah skrip ke bawah judul halaman; tanpa JS tetap tampil di atas isi.
--}}
@php
    $galatIsian = $errors->any() && ! View::hasSection('galat-di-halaman');
@endphp
@if (session('error') || session('warning') || $galatIsian)
    <div class="container-fluid px-3 px-lg-4 pt-3" data-umpan-balik>
        @if (session('error'))
            <div class="alert alert-danger banner-umpan" role="alert">
                <i class="bi bi-x-octagon-fill" aria-hidden="true"></i>
                <div class="banner-umpan-isi"><strong>Gagal.</strong> {{ session('error') }}</div>
                <div class="banner-umpan-aksi">
                    <a href="{{ request()->fullUrl() }}" class="btn btn-sm btn-outline-danger" title="Muat ulang halaman lalu ulangi aksi">
                        <i class="bi bi-arrow-clockwise" aria-hidden="true"></i>Coba lagi
                    </a>
                    <button type="button" class="btn-close" aria-label="Tutup pesan" data-tutup-banner></button>
                </div>
            </div>
        @endif

        @if ($galatIsian)
            <div class="alert alert-danger banner-umpan" role="alert">
                <i class="bi bi-exclamation-octagon-fill" aria-hidden="true"></i>
                <div class="banner-umpan-isi">
                    <strong>Data belum tersimpan.</strong> Periksa kembali isian berikut, lalu simpan lagi:
                    <ul>
                        @foreach ($errors->all() as $galat)
                            <li>{{ $galat }}</li>
                        @endforeach
                    </ul>
                </div>
                <div class="banner-umpan-aksi">
                    <button type="button" class="btn-close" aria-label="Tutup pesan" data-tutup-banner></button>
                </div>
            </div>
        @endif

        @if (session('warning'))
            <div class="alert alert-warning banner-umpan" role="status">
                <i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i>
                <div class="banner-umpan-isi"><strong>Perhatian.</strong> {{ session('warning') }}</div>
                <div class="banner-umpan-aksi">
                    <button type="button" class="btn-close" aria-label="Tutup pesan" data-tutup-banner></button>
                </div>
            </div>
        @endif
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const wadah = document.querySelector('[data-umpan-balik]');
            const isi = document.querySelector('.dashboard-content > .container-fluid');
            if (wadah && isi) {
                // Pindah ke bawah judul halaman (dekat konteks), tanpa padding ganda.
                const judul = isi.querySelector(':scope > .page-heading, :scope > .dash-hero');
                const kumpulan = document.createDocumentFragment();
                kumpulan.append(...wadah.children);
                judul ? judul.after(kumpulan) : isi.prepend(kumpulan);
                wadah.remove();
            }
            document.querySelectorAll('[data-tutup-banner]').forEach(function (tombol) {
                tombol.addEventListener('click', function () { tombol.closest('.banner-umpan').remove(); });
            });
        });
    </script>
@endif
