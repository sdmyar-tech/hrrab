@extends($layout)

@section('title', 'Pengumuman')

@section('content')
@php
    $param = fn (array $ubah = []) => array_filter(array_merge([
        'kategori' => $kategori, 'arsip' => $arsip ?: null, 'urut' => request('urut'),
        'tanggal_dari' => request('tanggal_dari'), 'tanggal_sampai' => request('tanggal_sampai'),
    ], $ubah), fn ($v) => ! is_null($v) && $v !== '');
    $nadaKategori = fn (string $badge) => match (true) {
        str_contains($badge, 'danger') => 'danger', str_contains($badge, 'success') => 'success', str_contains($badge, 'warning') => 'warning',
        str_contains($badge, 'info') => 'info', str_contains($badge, 'primary') => 'primary', default => 'neutral',
    };
@endphp
<main class="dashboard-content">
    <div class="container-fluid">
        <x-page-header title="Pengumuman" eyebrow="Informasi" subtitle="Kegiatan rutin, acara, kabar duka, dan informasi untuk pegawai.">
            @if ($arsip)<x-badge tone="neutral" icon="bi-archive">Arsip</x-badge>@endif
            @if ($bisaKelola)
                <x-slot:actions>
                    @include('partials.tombol-ekspor', ['eksporRoute' => 'pengumuman.export'])
                    <x-button :href="route('pengumuman.create')" icon="bi-plus-lg">Buat Pengumuman</x-button>
                </x-slot:actions>
            @endif
        </x-page-header>

        {{-- ============ Filter ============ --}}
        <div class="lamaran-filter">
            <div class="pub-chips mb-0" role="group" aria-label="Saring menurut kategori">
                <x-chip :href="route('pengumuman.index', $param(['kategori' => null]))" :active="! $kategori" icon="bi-grid">Semua</x-chip>
                @foreach (\App\Models\Pengumuman::KATEGORI as $key => $k)
                    <x-chip :href="route('pengumuman.index', $param(['kategori' => $key]))" :active="$kategori === $key" :icon="$k['ikon']">{{ $k['label'] }}</x-chip>
                @endforeach
                <x-chip :href="route('pengumuman.index', $param(['arsip' => $arsip ? null : 1]))" :active="(bool) $arsip" :icon="$arsip ? 'bi-arrow-left' : 'bi-archive'">{{ $arsip ? 'Yang berlaku' : 'Arsip' }}</x-chip>
            </div>
            @include('partials.urut')
        </div>
        <div class="d-flex flex-wrap align-items-start gap-3 mb-3">
            <div class="input-group hr-input-group pengumuman-cari">
                <span class="input-group-text"><i class="bi bi-search" aria-hidden="true"></i></span>
                <input type="search" class="form-control" placeholder="Cari di halaman ini, contoh: kajian" data-saring="#daftar-pengumuman"
                    autocomplete="off" aria-label="Cari pengumuman di halaman ini menurut judul atau isi">
            </div>
            <form method="GET" action="{{ route('pengumuman.index') }}" class="hr-filter-tanggal" role="search">
                @foreach (request()->only(['kategori', 'arsip', 'urut']) as $kunci => $nilai)<input type="hidden" name="{{ $kunci }}" value="{{ $nilai }}">@endforeach
                @include('partials.filter-tanggal', ['tanggalLabel' => 'Diterbitkan'])
            </form>
        </div>
        <div class="mb-3">
            @include('partials.daftar-ringkas', [
                'ringkasJumlah' => $pengumumans->total(), 'ringkasSatuan' => $arsip ? 'pengumuman di arsip' : 'pengumuman',
                'ringkasFilter' => $kategori || \App\Support\FilterTanggal::aktif(request()), 'ringkasReset' => route('pengumuman.index', ['arsip' => $arsip ?: null]),
            ])
        </div>

        {{-- ============ Grid kartu ============ --}}
        <div class="row g-3" id="daftar-pengumuman">
            @forelse ($pengumumans as $p)
                @php $k = $p->kategoriInfo(); $berikut = $p->berikutnya(); @endphp
                <div class="col-12 col-xl-6" id="pengumuman-{{ $p->id }}" data-cari="{{ mb_strtolower($p->judul . ' ' . $p->isi . ' ' . $p->lokasi) }}">
                    <article class="hr-card h-100 pengumuman-kartu {{ $p->penting ? 'is-penting' : '' }}">
                        <div class="hr-card-body">
                            <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
                                <div class="d-flex flex-wrap gap-1 align-items-center">
                                    <x-badge :tone="$nadaKategori($k['badge'])" :icon="$k['ikon']">{{ $k['label'] }}</x-badge>
                                    @if ($p->penting)<x-badge tone="danger" icon="bi-pin-angle-fill">Penting</x-badge>@endif
                                    @unless ($p->sudah_dibaca)<x-badge tone="primary" dot>Baru</x-badge>@endunless
                                </div>
                                @if ($bisaKelola && ($p->dibuat_oleh === auth()->id() || auth()->user()->can('pengumuman.manage_all')))
                                    <div class="d-flex gap-1">
                                        <x-button :href="route('pengumuman.edit', $p)" variant="ghost" size="sm" class="btn-ikon" icon="bi-pencil" title="Ubah" aria-label="Ubah pengumuman {{ $p->judul }}" />
                                        <form action="{{ route('pengumuman.destroy', $p) }}" method="POST" class="m-0">
                                            @csrf @method('DELETE')
                                            <x-button type="submit" variant="ghost" size="sm" class="btn-ikon text-danger" icon="bi-trash" title="Hapus" aria-label="Hapus pengumuman {{ $p->judul }}"
                                                data-confirm="true" data-icon="warning" data-title="Hapus pengumuman?"
                                                data-text="Pengumuman &quot;{{ $p->judul }}&quot; akan dihapus beserta data absennya. Tindakan ini tidak bisa dibatalkan."
                                                data-confirm-text="Ya, Hapus" data-cancel-text="Batal" />
                                        </form>
                                    </div>
                                @endif
                            </div>

                            <h2 class="pengumuman-judul">{{ $p->judul }}</h2>

                            @if ($p->jadwalLabel() || $p->lokasi)
                                <ul class="pub-rincian mb-3">
                                    @if ($p->jadwalLabel())
                                        <li><i class="bi {{ $p->isRutin() ? 'bi-arrow-repeat' : 'bi-calendar-event' }}" aria-hidden="true"></i>{{ $p->jadwalLabel() }}</li>
                                    @endif
                                    @if ($p->lokasi)<li>@include('partials.lokasi', ['lokasi' => $p->lokasi])</li>@endif
                                    @if ($berikut && $p->isRutin())
                                        <li class="text-success"><i class="bi bi-clock text-success" aria-hidden="true"></i>Berikutnya: {{ $berikut->isToday() ? 'hari ini' : $berikut->translatedFormat('l, d M') }}</li>
                                    @endif
                                </ul>
                            @endif

                            <p class="pengumuman-isi">{{ $p->isi }}</p>

                            @if ($p->absen_aktif && ($p->peserta->isNotEmpty() || $bisaPantauAbsen))
                                @include('pengumuman._kartu-absen', ['saya' => $p->peserta->first()])
                            @endif
                        </div>
                        <footer class="hr-card-foot pengumuman-kaki">
                            <span><i class="bi bi-people me-1" aria-hidden="true"></i>{{ $p->sasaranLabel() }}</span>
                            <span class="d-inline-flex align-items-center gap-2">
                                <x-avatar :name="$p->pembuat?->name ?? 'Sistem'" size="sm" class="pengumuman-avatar" />
                                {{ $p->pembuat?->name ?? 'Sistem' }} · <time datetime="{{ $p->created_at->toIso8601String() }}">{{ $p->created_at->diffForHumans() }}</time>
                            </span>
                        </footer>
                    </article>
                </div>
            @empty
                <div class="col-12">
                    <x-card>
                        <x-empty-state icon="bi-megaphone" :title="$kategori ? 'Belum ada pengumuman di kategori ini' : ($arsip ? 'Arsip masih kosong' : 'Belum ada pengumuman')">
                            {{ $bisaKelola ? 'Buat pengumuman untuk menyampaikan kegiatan atau informasi kepada pegawai.' : 'Pengumuman dari SDM dan pimpinan akan tampil di sini.' }}
                            @if ($bisaKelola && ! $arsip)
                                <x-slot:action><x-button :href="route('pengumuman.create')" icon="bi-plus-lg">Buat Pengumuman</x-button></x-slot:action>
                            @endif
                        </x-empty-state>
                    </x-card>
                </div>
            @endforelse
            @if ($pengumumans->isNotEmpty())
                <div class="col-12" data-saring-kosong="#daftar-pengumuman" hidden>
                    <x-card><x-empty-state icon="bi-search" title="Tidak ditemukan" compact>Tidak ada pengumuman di halaman ini yang cocok dengan pencarian.</x-empty-state></x-card>
                </div>
            @endif
        </div>

        <x-pagination :paginator="$pengumumans" class="mt-4" />
    </div>
</main>
@if ($pengumumans->getCollection()->contains(fn ($p) => $p->absen_aktif && $p->peserta->isNotEmpty()))
    @include('pengumuman._modal-scan')
@endif
@endsection
