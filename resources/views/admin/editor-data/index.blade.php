@extends('layouts.admin.app')

@section('content')
<div class="dashboard-content">
    <div class="container-fluid px-3 px-lg-4 py-4">
        <div class="page-heading">
            <div class="page-heading-copy">
                <span class="page-icon"><i class="bi bi-database-gear"></i></span>
                <div>
                    <p class="eyebrow mb-1">Sistem · Khusus Admin</p>
                    <h1 class="h3 mb-1">Editor Data</h1>
                    <p class="text-muted mb-0">Ubah atau hapus data apa pun secara langsung — termasuk yang sudah selesai, ditolak, atau lewat tanggal.</p>
                </div>
            </div>
            <div class="heading-actions">
                @include('partials.tombol-ekspor', ['eksporRoute' => 'admin.data.export-semua', 'eksporTeks' => 'Ekspor Semua Data', 'eksporIkon' => 'bi-database-down'])
            </div>
        </div>

        @include('admin.editor-data._peringatan')

        <div class="mb-3" style="max-width:360px">
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input type="search" class="form-control" placeholder="Cari jenis data… (mis. interview)" data-cari-entitas aria-label="Cari jenis data">
            </div>
        </div>

        @foreach ($grup as $nama => $daftar)
            <section class="mb-4" data-grup-entitas>
                <h2 class="h6 text-uppercase text-muted fw-bold mb-2">{{ $nama }}</h2>
                <div class="row g-2">
                    @foreach ($daftar as $e)
                        <div class="col-sm-6 col-lg-4 col-xxl-3" data-entitas="{{ strtolower($e['label'] . ' ' . $e['kunci'] . ' ' . $nama) }}">
                            <a href="{{ route('admin.data.daftar', $e['kunci']) }}" class="panel p-3 d-flex align-items-center gap-3 text-decoration-none text-body h-100 entitas-kartu">
                                <span class="entitas-ikon"><i class="bi {{ $e['ikon'] }}"></i></span>
                                <span class="min-w-0">
                                    <span class="fw-semibold d-block text-truncate">{{ $e['label'] }}</span>
                                    <span class="small text-muted">{{ number_format($e['jumlah'], 0, ',', '.') }} data</span>
                                </span>
                                <i class="bi bi-chevron-right ms-auto text-muted"></i>
                            </a>
                        </div>
                    @endforeach
                </div>
            </section>
        @endforeach
    </div>
</div>
@include('admin.editor-data._gaya')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelector('[data-cari-entitas]').addEventListener('input', function () {
            const kata = this.value.trim().toLowerCase();
            document.querySelectorAll('[data-entitas]').forEach((el) => { el.hidden = kata && !el.dataset.entitas.includes(kata); });
            document.querySelectorAll('[data-grup-entitas]').forEach((g) => { g.hidden = !g.querySelector('[data-entitas]:not([hidden])'); });
        });
    });
</script>
@endsection
