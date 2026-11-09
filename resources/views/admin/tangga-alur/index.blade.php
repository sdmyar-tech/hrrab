@extends(Auth::user()->panelLayout())

@section('content')
@use('App\Models\TanggaAlur')
<main class="dashboard-content">
    <div class="container-fluid px-3 px-lg-4 py-4">
        <x-page-header title="Tangga Alur Pengajuan" eyebrow="Kepegawaian"
            subtitle="Atur siapa yang menyetujui cuti, lembur, dan izin di tiap bagian: urutan bebas, boleh lintas bagian, dan setiap tahap wajib dilalui." />

        <x-alert tone="info">
            Bagian yang belum diatur memakai <strong>alur otomatis</strong>. Alur khusus per pegawai (di detail pegawai) tetap didahulukan.
            Perubahan berlaku untuk pengajuan baru; pengajuan yang sedang berjalan tetap memakai tangga saat diajukan.
        </x-alert>

        <div class="row g-3 mb-4">
            <div class="col-sm-4"><x-stat-card label="Bagian" :value="$jumlah['semua']" icon="bi-buildings" tone="info" meta="tiap bagian punya 2 tangga" /></div>
            <div class="col-sm-4"><x-stat-card label="Tangga diatur" :value="$jumlahDiatur" icon="bi-signpost-split" tone="primary" meta="sisanya mengikuti alur otomatis" /></div>
            <div class="col-sm-4"><x-stat-card label="Perlu perhatian" :value="$jumlahPeringatan" icon="bi-exclamation-triangle" :tone="$jumlahPeringatan ? 'warning' : 'success'" meta="tahap tanpa penyetuju" /></div>
        </div>

        <div class="lamaran-filter mb-3">
            <nav class="pub-chips" aria-label="Saring bagian">
                @foreach (['semua' => ['Semua', 'bi-grid'], 'diatur' => ['Sudah diatur', 'bi-signpost-split'], 'perhatian' => ['Perlu perhatian', 'bi-exclamation-triangle']] as $kunci => [$teks, $ikon])
                    <x-chip :href="route('admin.tangga-alur.index', $kunci === 'semua' ? [] : ['tampil' => $kunci])" :active="$tampil === $kunci" :icon="$ikon" :count="$jumlah[$kunci]">{{ $teks }}</x-chip>
                @endforeach
            </nav>
            <div class="hr-filter-tanggal ms-lg-auto">
                <x-input name="cari" type="search" icon="bi-search" placeholder="Cari bagian, contoh: SDM" aria-label="Cari bagian" data-saring="#daftar-tangga" />
            </div>
        </div>
        <p class="small text-muted mb-2"><span data-saring-jumlah="#daftar-tangga">{{ $baris->count() }}</span> bagian ditampilkan</p>

        <div class="d-grid gap-3" id="daftar-tangga">
            @if ($baris->isEmpty())
                <x-empty-state icon="bi-signpost-split" :title="$tampil === 'perhatian' ? 'Semua tangga punya penyetuju' : 'Belum ada tangga yang diatur'">
                    {{ $tampil === 'perhatian' ? 'Tidak ada tahap tanpa penyetuju.' : 'Pilih bagian di tab Semua lalu klik Atur Tangga.' }}
                    <x-slot:action><x-button variant="secondary" :href="route('admin.tangga-alur.index')">Tampilkan semua bagian</x-button></x-slot:action>
                </x-empty-state>
            @endif
            @foreach ($baris as $b)
                @php
                    $unit = $b['unit'];
                    $koor = $b['tangga']->first()['koordinator'];
                @endphp
                {{-- Koordinator saat ini ikut dicari, mis. ketik nama koordinator untuk menemukan bagiannya. --}}
                <x-card :title="$unit->nama" icon="bi-buildings" :subtitle="$koor ? 'Koordinator saat ini: ' . implode(', ', $koor) : 'Belum ada koordinator'"
                    data-cari="{{ mb_strtolower($unit->nama . ' ' . implode(' ', $koor)) }}">
                    <div class="tangga-baris">
                        @foreach ($b['tangga'] as $sasaran => $t)
                            <section class="tangga-sel {{ $t['model'] ? '' : 'is-otomatis' }} {{ $t['peringatan'] ? 'is-peringatan' : '' }}" aria-labelledby="tangga-{{ $unit->id }}-{{ $sasaran }}">
                                <div class="tangga-sel-kepala">
                                    <h3 class="tangga-sel-judul d-flex flex-wrap align-items-center gap-2" id="tangga-{{ $unit->id }}-{{ $sasaran }}">
                                        <i class="bi {{ $sasaran === TanggaAlur::SASARAN_STAF ? 'bi-people' : 'bi-person-badge' }}" aria-hidden="true"></i>
                                        Pengaju: {{ TanggaAlur::SASARAN_LABEL[$sasaran] }}
                                        @if (! $t['model'])
                                            <x-badge tone="neutral">Otomatis</x-badge>
                                        @elseif ($t['peringatan'])
                                            <x-badge tone="warning" icon="bi-exclamation-triangle">Ada tahap tanpa penyetuju</x-badge>
                                        @else
                                            <x-badge tone="success" dot>Diatur</x-badge>
                                        @endif
                                    </h3>
                                    <div class="tangga-sel-aksi">
                                        <x-button size="sm" variant="outline" :icon="$t['model'] ? 'bi-pencil' : 'bi-plus-lg'" :href="route('admin.tangga-alur.edit', [$unit, $sasaran])">
                                            {{ $t['model'] ? 'Ubah' : 'Atur Tangga' }}
                                        </x-button>
                                        @if ($t['model'])
                                            <form action="{{ route('admin.tangga-alur.destroy', [$unit, $sasaran]) }}" method="POST"
                                                data-confirm="Hapus tangga {{ strtolower(TanggaAlur::SASARAN_LABEL[$sasaran]) }} bagian {{ $unit->nama }}? Pengajuan baru kembali memakai alur otomatis.">
                                                @csrf @method('DELETE')
                                                <x-button type="submit" size="sm" variant="ghost" icon="bi-arrow-counterclockwise">Kembali Otomatis</x-button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                                @if ($t['model'])
                                    <x-tangga-alur :tahap="$t['tahap']" :awal="($sasaran === TanggaAlur::SASARAN_STAF ? 'Pegawai ' : 'Koordinator ') . $unit->nama" akhir="Disetujui"
                                        :label="'Tangga ' . TanggaAlur::SASARAN_LABEL[$sasaran] . ' bagian ' . $unit->nama" />
                                    <p class="tangga-sel-info">
                                        Diubah {{ $t['model']->pengubah?->name ? 'oleh ' . $t['model']->pengubah->name : '' }} · {{ $t['model']->updated_at?->translatedFormat('d M Y H:i') }}
                                        @if ($t['model']->catatan) · {{ $t['model']->catatan }} @endif
                                    </p>
                                @else
                                    <p class="tangga-sel-otomatis">Alur otomatis: {{ $t['otomatis'] }}.</p>
                                @endif
                            </section>
                        @endforeach
                    </div>
                </x-card>
            @endforeach
            <div data-saring-kosong="#daftar-tangga" hidden>
                <x-empty-state icon="bi-search" title="Bagian tidak ditemukan">Coba kata lain, contoh: KEUANGAN.</x-empty-state>
            </div>
        </div>
    </div>
</main>
@endsection
