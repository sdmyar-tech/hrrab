@extends('layouts.admin.app')

@section('content')
<div class="dashboard-content">
    <div class="container-fluid px-3 px-lg-4 py-4">
        <div class="page-heading">
            <div class="page-heading-copy">
                <span class="page-icon"><i class="bi {{ $k['ikon'] }}"></i></span>
                <div>
                    <p class="eyebrow mb-1"><a href="{{ route('admin.data.index') }}" class="text-reset text-decoration-none"><i class="bi bi-arrow-left me-1"></i>Editor Data</a> · {{ $k['grup'] }}</p>
                    <h1 class="h3 mb-1">{{ $k['label'] }}</h1>
                    <p class="text-muted mb-0">{{ number_format($baris->total(), 0, ',', '.') }} data · terbaru di atas</p>
                </div>
            </div>
            <div class="heading-actions">
                @include('partials.tombol-ekspor', ['eksporRoute' => 'admin.data.export', 'eksporParam' => ['entitas' => $entitas]])
                @if ($k['tambah'] ?? true)
                    <a href="{{ route('admin.data.create', [$entitas, 'kembali' => request()->fullUrl()]) }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Tambah Data</a>
                @endif
            </div>
        </div>

        <section class="panel p-3 p-lg-4">
            <form method="GET" class="panel-filter mb-2" role="search">
                <div class="row g-2">
                    <div class="col-12 col-md-6 col-xl-4">
                        @include('partials.daftar-cari', ['cariTeks' => 'Cari data, contoh: Siti atau 125', 'cariLabel' => 'Cari data menurut nama, kata kunci, atau ID'])
                    </div>
                </div>
                @include('partials.daftar-ringkas', [
                    'ringkasJumlah' => $baris->total(), 'ringkasSatuan' => 'data',
                    'ringkasFilter' => request()->filled('q'), 'ringkasReset' => route('admin.data.daftar', $entitas),
                ])
            </form>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr class="small text-muted">
                            <th style="width:70px">ID</th>
                            <th>Data</th>
                            @foreach ($k['kolom'] as $kol)<th class="text-nowrap">{{ str_replace('_', ' ', $kol) }}</th>@endforeach
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($baris as $m)
                            <tr>
                                <td class="small text-muted font-monospace">{{ \Illuminate\Support\Str::limit((string) $m->getKey(), 8, '…') }}</td>
                                <td class="fw-semibold">{{ \Illuminate\Support\Str::limit(($k['judul'])($m), 70) }}</td>
                                @foreach ($k['kolom'] as $kol)<td class="small">{{ \App\Support\EditorData::nilaiRingkas($m, $kol) }}</td>@endforeach
                                <td class="text-end text-nowrap">
                                    @if (isset($k['tautan']) && ($url = ($k['tautan'])($m)))
                                        <a href="{{ $url }}" class="btn btn-light btn-sm" title="Buka di halaman asal"><i class="bi bi-box-arrow-up-right"></i></a>
                                    @endif
                                    <a href="{{ route('admin.data.edit', [$entitas, $m->getKey(), 'kembali' => request()->fullUrl()]) }}" class="btn btn-light btn-sm"><i class="bi bi-pencil me-1"></i>Ubah</a>
                                    <form action="{{ route('admin.data.destroy', [$entitas, $m->getKey()]) }}" method="POST" class="d-inline">
                                        @csrf @method('DELETE')
                                        <input type="hidden" name="kembali" value="{{ request()->fullUrl() }}">
                                        <button type="submit" class="btn btn-light btn-sm text-danger" title="Hapus" aria-label="Hapus {{ $k['label'] }} #{{ $m->getKey() }}"
                                            data-confirm="true" data-icon="warning" data-title="Hapus data permanen?"
                                            data-text="{{ $k['label'] }} #{{ $m->getKey() }} akan dihapus permanen. Tindakan ini tidak bisa dibatalkan."
                                            data-confirm-text="Ya, Hapus" data-confirm-color="#dc3545" data-cancel-text="Batal"><i class="bi bi-trash" aria-hidden="true"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            @include('partials.kosong-tabel', [
                                'kosongKolom' => count($k['kolom']) + 3,
                                'kosongTeks' => request()->filled('q') ? 'Tidak ada data yang cocok dengan pencarian ini.' : 'Belum ada data.',
                                'kosongIkon' => request()->filled('q') ? 'bi-search' : 'bi-database',
                                'kosongReset' => request()->filled('q') ? route('admin.data.daftar', $entitas) : null,
                            ])
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($baris->hasPages())
                <div class="mt-3">{{ $baris->links('pagination::bootstrap-5') }}</div>
            @endif
        </section>
    </div>
</div>
@endsection
