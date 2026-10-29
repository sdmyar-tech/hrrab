@extends($layout)

@section('content')
<div class="dashboard-content">
    <div class="container-fluid px-3 px-lg-4 py-4">
        <div class="page-heading">
            <div class="page-heading-copy">
                <span class="page-icon"><i class="bi bi-bell"></i></span>
                <div>
                    <p class="eyebrow mb-1">Informasi</p>
                    <h1 class="h3 mb-1">Notifikasi</h1>
                    <p class="text-muted mb-0">Tugas yang perlu dikerjakan, kabar pengajuan, dan pengingat untuk Anda.</p>
                </div>
            </div>
            <div class="heading-actions d-flex flex-wrap gap-2">
                @include('partials.tombol-ekspor', ['eksporRoute' => 'notifikasi.export'])
                @can('kirim-notifikasi')
                    <a href="{{ route('notifikasi.kirim') }}" class="btn btn-outline-primary"><i class="bi bi-send me-1"></i> Kirim Notifikasi</a>
                @endcan
                @if ($jumlahBelum)
                    <form action="{{ route('notifikasi.baca-semua') }}" method="POST">@csrf
                        <button type="submit" class="btn btn-primary"><i class="bi bi-check2-all me-1"></i> Tandai semua dibaca</button>
                    </form>
                @endif
            </div>
        </div>

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <ul class="nav nav-pills">
            <li class="nav-item"><a class="nav-link {{ $belum ? '' : 'active' }}" href="{{ route('notifikasi.index', ['urut' => request('urut'), 'tanggal_dari' => request('tanggal_dari'), 'tanggal_sampai' => request('tanggal_sampai')]) }}">Semua</a></li>
            <li class="nav-item"><a class="nav-link {{ $belum ? 'active' : '' }}" href="{{ route('notifikasi.index', ['tab' => 'belum', 'urut' => request('urut'), 'tanggal_dari' => request('tanggal_dari'), 'tanggal_sampai' => request('tanggal_sampai')]) }}">Belum dibaca
                @if ($jumlahBelum)<span class="badge text-bg-primary ms-1">{{ $jumlahBelum }}</span>@endif</a></li>
        </ul>
        @include('partials.urut')
        </div>
        <div class="mb-2">
            <form method="GET" action="{{ route('notifikasi.index') }}" class="mb-1" style="max-width: 440px;" role="search">
                @foreach (request()->only(['tab', 'urut']) as $kunci => $nilai)<input type="hidden" name="{{ $kunci }}" value="{{ $nilai }}">@endforeach
                @include('partials.filter-tanggal', ['tanggalLabel' => 'Diterima'])
            </form>
            @include('partials.daftar-ringkas', [
                'ringkasJumlah' => $notifikasi->total(), 'ringkasSatuan' => $belum ? 'notifikasi belum dibaca' : 'notifikasi',
                'ringkasFilter' => \App\Support\FilterTanggal::aktif(request()), 'ringkasReset' => route('notifikasi.index', $belum ? ['tab' => 'belum'] : []),
            ])
        </div>

        <section class="panel p-0">
            <div class="list-group list-group-flush">
                @forelse ($notifikasi as $n)
                    @php
                        $jenis = $n->data['jenis'] ?? 'info';
                        $label = ['tugas' => ['Tugas', 'text-bg-warning'], 'pengingat' => ['Pengingat', 'text-bg-info'], 'info' => ['Info', 'text-bg-light border']][$jenis] ?? ['Info', 'text-bg-light border'];
                    @endphp
                    <a href="{{ route('notifikasi.buka', $n->id) }}" class="list-group-item list-group-item-action d-flex gap-3 py-3" @unless ($n->read_at) style="background: var(--admin-surface-soft);" @endunless>
                        <span class="page-icon flex-none" style="width:40px;height:40px;font-size:1.1rem;"><i class="bi {{ $n->data['ikon'] ?? 'bi-bell' }}" aria-hidden="true"></i></span>
                        <span class="flex-grow-1">
                            <span class="d-flex flex-wrap align-items-center gap-2">
                                <span class="fw-semibold">{{ $n->data['judul'] ?? '' }}</span>
                                <span class="badge {{ $label[1] }}">{{ $label[0] }}</span>
                                @if (! $n->read_at && isset($n->data['tugas']))
                                    <span class="badge text-bg-warning" title="Tanda hilang otomatis setelah tugas diselesaikan"><i class="bi bi-hourglass-split me-1"></i>Belum selesai</span>
                                @elseif (! $n->read_at)
                                    <span class="badge text-bg-info">Baru</span>
                                @endif
                            </span>
                            <span class="d-block text-muted small mt-1">{{ $n->data['pesan'] ?? '' }}</span>
                            <span class="d-block small text-muted mt-1">
                                {{ $n->created_at->translatedFormat('d M Y, H:i') }} ({{ $n->created_at->diffForHumans() }})
                                @if (! empty($n->data['pengirim'])) &middot; dari {{ $n->data['pengirim'] }} @endif
                            </span>
                        </span>
                        @if (! empty($n->data['url']))<i class="bi bi-chevron-right text-muted align-self-center"></i>@endif
                    </a>
                @empty
                    @include('partials.kosong', ['teks' => $belum ? 'Semua notifikasi sudah dibaca.' : 'Belum ada notifikasi.', 'ikonKosong' => 'bi-check2-circle'])
                @endforelse
            </div>
        </section>

        @if ($notifikasi->hasPages())
            <div class="mt-3">{{ $notifikasi->links('pagination::bootstrap-5') }}</div>
        @endif
    </div>
</div>
@endsection
