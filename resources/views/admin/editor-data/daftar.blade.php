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
        </div>

        <section class="panel p-3 p-lg-4">
            <form method="GET" class="mb-3" style="max-width:420px">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="search" name="q" class="form-control" value="{{ request('q') }}" placeholder="Cari nama, kata kunci, atau ID…" aria-label="Cari">
                    <button class="btn btn-primary">Cari</button>
                </div>
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
                                    <a href="{{ route('admin.data.edit', [$entitas, $m->getKey(), 'kembali' => request()->fullUrl()]) }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-pencil me-1"></i>Ubah</a>
                                    <form action="{{ route('admin.data.destroy', [$entitas, $m->getKey()]) }}" method="POST" class="d-inline"
                                        data-confirm="Hapus permanen {{ $k['label'] }} #{{ $m->getKey() }}? Tindakan ini tidak bisa dibatalkan.">
                                        @csrf @method('DELETE')
                                        <input type="hidden" name="kembali" value="{{ request()->fullUrl() }}">
                                        <button class="btn btn-light btn-sm text-danger" title="Hapus"><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="{{ count($k['kolom']) + 3 }}" class="text-center text-muted py-5"><i class="bi bi-search d-block fs-3 mb-1"></i>Tidak ada data.</td></tr>
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
