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
            <div class="heading-actions d-flex gap-2">
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

        <ul class="nav nav-pills mb-3">
            <li class="nav-item"><a class="nav-link {{ $belum ? '' : 'active' }}" href="{{ route('notifikasi.index') }}">Semua</a></li>
            <li class="nav-item"><a class="nav-link {{ $belum ? 'active' : '' }}" href="{{ route('notifikasi.index', ['tab' => 'belum']) }}">Belum dibaca
                @if ($jumlahBelum)<span class="badge text-bg-danger ms-1">{{ $jumlahBelum }}</span>@endif</a></li>
        </ul>

        <section class="panel p-0">
            <div class="list-group list-group-flush">
                @forelse ($notifikasi as $n)
                    @php
                        $jenis = $n->data['jenis'] ?? 'info';
                        $label = ['tugas' => ['Tugas', 'text-bg-warning'], 'pengingat' => ['Pengingat', 'text-bg-primary'], 'info' => ['Info', 'text-bg-light border']][$jenis] ?? ['Info', 'text-bg-light border'];
                    @endphp
                    <a href="{{ route('notifikasi.buka', $n->id) }}" class="list-group-item list-group-item-action d-flex gap-3 py-3 {{ $n->read_at ? '' : 'bg-body-tertiary' }}">
                        <span class="page-icon flex-none" style="width:40px;height:40px;font-size:1.1rem;"><i class="bi {{ $n->data['ikon'] ?? 'bi-bell' }}"></i></span>
                        <span class="flex-grow-1">
                            <span class="d-flex flex-wrap align-items-center gap-2">
                                <span class="fw-semibold">{{ $n->data['judul'] ?? '' }}</span>
                                <span class="badge {{ $label[1] }}">{{ $label[0] }}</span>
                                @unless ($n->read_at)<span class="badge text-bg-danger">Baru</span>@endunless
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
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-check2-circle fs-1 d-block mb-2"></i>
                        {{ $belum ? 'Semua notifikasi sudah dibaca.' : 'Belum ada notifikasi.' }}
                    </div>
                @endforelse
            </div>
        </section>

        @if ($notifikasi->hasPages())
            <div class="mt-3">{{ $notifikasi->links('pagination::bootstrap-5') }}</div>
        @endif
    </div>
</div>
@endsection
