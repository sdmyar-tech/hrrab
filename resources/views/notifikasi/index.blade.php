@extends($layout)

@section('title', 'Notifikasi')

@section('content')
@php
    $jenisInfo = [
        'tugas' => ['Tugas', 'warning'],
        'pengingat' => ['Pengingat', 'info'],
        'info' => ['Info', 'neutral'],
    ];
    $paramFilter = ['urut' => request('urut'), 'tanggal_dari' => request('tanggal_dari'), 'tanggal_sampai' => request('tanggal_sampai')];
@endphp
<main class="dashboard-content">
    <div class="container-fluid">
        <x-page-header title="Notifikasi" eyebrow="Informasi" subtitle="Tugas yang perlu dikerjakan, kabar pengajuan, dan pengingat untuk Anda.">
            @if ($jumlahBelum)<x-badge tone="danger" dot>{{ $jumlahBelum }} belum dibaca</x-badge>@endif
            <x-slot:actions>
                @include('partials.tombol-ekspor', ['eksporRoute' => 'notifikasi.export'])
                @can('notifikasi.send')
                    <x-button :href="route('notifikasi.kirim')" variant="secondary" icon="bi-send">Kirim Notifikasi</x-button>
                @endcan
                @if ($jumlahBelum)
                    <form action="{{ route('notifikasi.baca-semua') }}" method="POST" class="m-0">@csrf
                        <x-button type="submit" icon="bi-check2-all">Tandai Semua Dibaca</x-button>
                    </form>
                @endif
            </x-slot:actions>
        </x-page-header>

        {{-- Filter: tab semua/belum dibaca · rentang tanggal · urutan --}}
        <div class="lamaran-filter">
            <div class="pub-chips mb-0" role="group" aria-label="Saring notifikasi">
                <x-chip :href="route('notifikasi.index', $paramFilter)" :active="! $belum" icon="bi-inbox">Semua</x-chip>
                <x-chip :href="route('notifikasi.index', ['tab' => 'belum'] + $paramFilter)" :active="$belum" icon="bi-envelope" :count="$jumlahBelum ?: null">Belum dibaca</x-chip>
            </div>
            @include('partials.urut')
        </div>
        <div class="mb-3">
            <form method="GET" action="{{ route('notifikasi.index') }}" class="mb-1 hr-filter-tanggal" role="search">
                @foreach (request()->only(['tab', 'urut']) as $kunci => $nilai)<input type="hidden" name="{{ $kunci }}" value="{{ $nilai }}">@endforeach
                @include('partials.filter-tanggal', ['tanggalLabel' => 'Diterima'])
            </form>
            @include('partials.daftar-ringkas', [
                'ringkasJumlah' => $notifikasi->total(), 'ringkasSatuan' => $belum ? 'notifikasi belum dibaca' : 'notifikasi',
                'ringkasFilter' => \App\Support\FilterTanggal::aktif(request()), 'ringkasReset' => route('notifikasi.index', $belum ? ['tab' => 'belum'] : []),
            ])
        </div>

        <x-card :padding="false" class="notif-kartu">
            <ul class="notif-daftar">
                @forelse ($notifikasi as $n)
                    @php
                        [$labelJenis, $toneJenis] = $jenisInfo[$n->data['jenis'] ?? 'info'] ?? $jenisInfo['info'];
                        $baru = ! $n->read_at;
                    @endphp
                    <li>
                        <a href="{{ route('notifikasi.buka', $n->id) }}" class="notif-item {{ $baru ? 'is-baru' : '' }}">
                            <span class="hr-stat-ikon hr-tone-{{ $toneJenis }}"><i class="bi {{ $n->data['ikon'] ?? 'bi-bell' }}" aria-hidden="true"></i></span>
                            <span class="notif-isi">
                                <span class="notif-kepala">
                                    <span class="notif-judul">{{ $n->data['judul'] ?? '' }}</span>
                                    <x-badge :tone="$toneJenis">{{ $labelJenis }}</x-badge>
                                    @if ($baru && isset($n->data['tugas']))
                                        <x-badge tone="warning" icon="bi-hourglass-split" title="Tanda hilang otomatis setelah tugas diselesaikan">Belum selesai</x-badge>
                                    @endif
                                </span>
                                <span class="notif-pesan">{{ $n->data['pesan'] ?? '' }}</span>
                                <span class="notif-waktu">
                                    <time datetime="{{ $n->created_at->toIso8601String() }}" title="{{ $n->created_at->translatedFormat('d M Y, H:i') }}">{{ $n->created_at->diffForHumans() }}</time>
                                    @if (! empty($n->data['pengirim'])) · dari {{ $n->data['pengirim'] }} @endif
                                </span>
                            </span>
                            @if ($baru)<span class="notif-titik" role="img" aria-label="Belum dibaca"></span>@endif
                            @if (! empty($n->data['url']))<i class="bi bi-chevron-right notif-panah" aria-hidden="true"></i>@endif
                        </a>
                    </li>
                @empty
                    <li>
                        <x-empty-state icon="bi-bell-slash" :title="$belum ? 'Semua sudah dibaca' : 'Belum ada notifikasi'">
                            {{ $belum ? 'Tidak ada notifikasi yang menunggu dibaca.' : 'Notifikasi tugas, kabar pengajuan, dan pengingat akan tampil di sini.' }}
                            @if ($belum)
                                <x-slot:action><x-button :href="route('notifikasi.index')" variant="secondary">Lihat semua notifikasi</x-button></x-slot:action>
                            @endif
                        </x-empty-state>
                    </li>
                @endforelse
            </ul>
            @if ($notifikasi->hasPages())
                <x-slot:footer><x-pagination :paginator="$notifikasi" /></x-slot:footer>
            @endif
        </x-card>
    </div>
</main>
@endsection
