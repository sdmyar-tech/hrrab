@extends($layout)

@section('content')
<div class="dashboard-content">
    <div class="container-fluid px-3 px-lg-4 py-4">
        <div class="page-heading">
            <div class="page-heading-copy">
                <span class="page-icon"><i class="bi bi-megaphone"></i></span>
                <div>
                    <p class="eyebrow mb-1">Informasi</p>
                    <h1 class="h3 mb-1">Pengumuman</h1>
                    <p class="text-muted mb-0">Kegiatan rutin, acara, kabar duka, dan informasi untuk pegawai.</p>
                </div>
            </div>
            @if ($bisaKelola)
                <div class="heading-actions d-flex flex-wrap gap-2">
                    @include('partials.tombol-ekspor', ['eksporRoute' => 'pengumuman.export'])
                    <a href="{{ route('pengumuman.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> Buat Pengumuman</a>
                </div>
            @endif
        </div>

        <div class="d-flex flex-wrap gap-2 mb-3">
            <a href="{{ route('pengumuman.index', ['arsip' => $arsip ?: null, 'urut' => request('urut')]) }}" class="btn btn-sm {{ ! $kategori ? 'btn-primary' : 'btn-outline-secondary' }}">Semua</a>
            @foreach (\App\Models\Pengumuman::KATEGORI as $key => $k)
                <a href="{{ route('pengumuman.index', ['kategori' => $key, 'arsip' => $arsip ?: null, 'urut' => request('urut')]) }}" class="btn btn-sm {{ $kategori === $key ? 'btn-primary' : 'btn-outline-secondary' }}">
                    <i class="bi {{ $k['ikon'] }} me-1"></i>{{ $k['label'] }}
                </a>
            @endforeach
            <div class="ms-auto">@include('partials.urut')</div>
            <a href="{{ route('pengumuman.index', ['kategori' => $kategori, 'arsip' => $arsip ? null : 1, 'urut' => request('urut')]) }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi {{ $arsip ? 'bi-arrow-left' : 'bi-archive' }} me-1"></i>{{ $arsip ? 'Kembali ke yang berlaku' : 'Arsip' }}
            </a>
        </div>
        <div class="mb-3">
            @include('partials.daftar-ringkas', [
                'ringkasJumlah' => $pengumumans->total(), 'ringkasSatuan' => $arsip ? 'pengumuman di arsip' : 'pengumuman',
                'ringkasFilter' => (bool) $kategori, 'ringkasReset' => route('pengumuman.index', ['arsip' => $arsip ?: null]),
            ])
        </div>

        <div class="row g-3">
            @forelse ($pengumumans as $p)
                @php $k = $p->kategoriInfo(); $berikut = $p->berikutnya(); @endphp
                <div class="col-12 col-lg-6" id="pengumuman-{{ $p->id }}">
                    <article class="panel p-4 h-100 {{ $p->penting ? 'border border-2 border-danger-subtle' : '' }}">
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                            <div class="d-flex flex-wrap gap-1 align-items-center">
                                <span class="badge {{ $k['badge'] }}"><i class="bi {{ $k['ikon'] }} me-1"></i>{{ $k['label'] }}</span>
                                @if ($p->penting)<span class="badge text-bg-danger"><i class="bi bi-pin-angle-fill me-1"></i>Penting</span>@endif
                                @unless ($p->sudah_dibaca)<span class="badge text-bg-light border text-danger">Baru</span>@endunless
                            </div>
                            @if ($bisaKelola && ($p->dibuat_oleh === auth()->id() || auth()->user()->isAdmin() || auth()->user()->isKepalaSdm()))
                                <div class="d-flex gap-1">
                                    <a href="{{ route('pengumuman.edit', $p) }}" class="btn btn-light btn-sm" title="Ubah" aria-label="Ubah pengumuman {{ $p->judul }}"><i class="bi bi-pencil" aria-hidden="true"></i></a>
                                    <form action="{{ route('pengumuman.destroy', $p) }}" method="POST" class="m-0">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-light btn-sm text-danger" title="Hapus" aria-label="Hapus pengumuman {{ $p->judul }}"
                                            data-confirm="true" data-icon="warning" data-title="Hapus pengumuman?"
                                            data-text="Pengumuman &quot;{{ $p->judul }}&quot; akan dihapus beserta data absennya. Tindakan ini tidak bisa dibatalkan."
                                            data-confirm-text="Ya, Hapus" data-confirm-color="#dc3545" data-cancel-text="Batal">
                                            <i class="bi bi-trash" aria-hidden="true"></i>
                                        </button>
                                    </form>
                                </div>
                            @endif
                        </div>

                        <h2 class="h5 mb-2">{{ $p->judul }}</h2>

                        @if ($p->jadwalLabel() || $p->lokasi)
                            <div class="d-flex flex-wrap gap-3 small mb-2">
                                @if ($p->jadwalLabel())
                                    <span><i class="bi {{ $p->isRutin() ? 'bi-arrow-repeat' : 'bi-calendar-event' }} me-1 text-muted"></i>{{ $p->jadwalLabel() }}</span>
                                @endif
                                @if ($p->lokasi)<span>@include('partials.lokasi', ['lokasi' => $p->lokasi])</span>@endif
                            </div>
                        @endif
                        @if ($berikut && $p->isRutin())
                            <p class="small text-success mb-2"><i class="bi bi-clock me-1"></i>Berikutnya: {{ $berikut->isToday() ? 'hari ini' : $berikut->translatedFormat('l, d M') }}</p>
                        @endif

                        <p class="mb-3" style="white-space: pre-line;">{{ $p->isi }}</p>

                        @if ($p->absen_aktif && ($p->peserta->isNotEmpty() || $bisaPantauAbsen))
                            @include('pengumuman._kartu-absen', ['saya' => $p->peserta->first()])
                        @endif

                        <div class="d-flex flex-wrap justify-content-between gap-2 small text-muted border-top pt-2">
                            <span><i class="bi bi-people me-1"></i>{{ $p->sasaranLabel() }}</span>
                            <span>{{ $p->pembuat?->name ?? 'Sistem' }} &middot; {{ $p->created_at->diffForHumans() }}</span>
                        </div>
                    </article>
                </div>
            @empty
                <div class="col-12">
                    <div class="panel">
                        @include('partials.kosong', [
                            'teks' => $kategori ? 'Belum ada pengumuman di kategori ini.' : ($arsip ? 'Arsip pengumuman masih kosong.' : 'Belum ada pengumuman.'),
                            'ikonKosong' => 'bi-megaphone',
                        ])
                    </div>
                </div>
            @endforelse
        </div>

        @if ($pengumumans->hasPages())
            <div class="mt-3">{{ $pengumumans->links('pagination::bootstrap-5') }}</div>
        @endif
    </div>
</div>
@if ($pengumumans->getCollection()->contains(fn ($p) => $p->absen_aktif && $p->peserta->isNotEmpty()))
    @include('pengumuman._modal-scan')
@endif
@endsection
