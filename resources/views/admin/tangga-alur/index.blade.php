@extends(Auth::user()->panelLayout())

@section('content')
@use('App\Models\TanggaAlur')
<main class="dashboard-content">
    <div class="container-fluid px-3 px-lg-4 py-4">
        <x-page-header title="Tangga Alur Pengajuan" eyebrow="Kepegawaian"
            subtitle="Atur siapa yang menyetujui cuti, lembur, dan izin: per bagian, atau khusus untuk satu orang. Urutan bebas, boleh lintas bagian, dan setiap tahap wajib dilalui.">
            <x-button icon="bi-person-plus" data-bs-toggle="modal" data-bs-target="#modalTambahKhusus">Tambah Tangga Khusus</x-button>
        </x-page-header>

        <x-alert tone="info">
            Urutan yang dipakai: <strong>tangga khusus orang</strong> → tangga bagian → alur otomatis.
            Perubahan berlaku untuk pengajuan baru; pengajuan yang sedang berjalan tetap memakai tangga saat diajukan.
        </x-alert>

        <div class="row g-3 mb-3">
            <div class="col-6 col-lg-3"><x-stat-card label="Bagian" :value="$jumlah['semua']" icon="bi-buildings" tone="info" meta="tiap bagian punya 2 tangga" /></div>
            <div class="col-6 col-lg-3"><x-stat-card label="Tangga bagian diatur" :value="$jumlahDiatur" icon="bi-signpost-split" tone="primary" meta="sisanya alur otomatis" /></div>
            <div class="col-6 col-lg-3"><x-stat-card label="Tangga khusus orang" :value="$pribadi->count()" icon="bi-person-gear" tone="primary" meta="didahulukan atas bagian" /></div>
            <div class="col-6 col-lg-3"><x-stat-card label="Perlu perhatian" :value="$jumlahPeringatan + $pribadi->where('peringatan', true)->count()" icon="bi-exclamation-triangle" :tone="($jumlahPeringatan + $pribadi->where('peringatan', true)->count()) ? 'warning' : 'success'" meta="tahap tanpa penyetuju" /></div>
        </div>

        <nav class="pub-chips mb-4" aria-label="Loncat ke bagian halaman">
            <x-chip href="#per-bagian" icon="bi-buildings" :count="$jumlah['semua']">Per bagian</x-chip>
            <x-chip href="#per-orang" icon="bi-person-gear" :count="$pribadi->count()">Khusus per orang</x-chip>
        </nav>

        <h2 class="h5 mb-3" id="per-bagian"><i class="bi bi-buildings me-1" aria-hidden="true"></i>Tangga Per Bagian</h2>

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

        {{-- Tangga khusus per orang: didahulukan atas tangga bagian. Dibuat dari detail pegawai (tab Penugasan → Alur Persetujuan). --}}
        <x-card class="mt-4" id="per-orang" title="Tangga Khusus Per Orang" icon="bi-person-gear"
            subtitle="Pegawai yang alurnya diatur sendiri. Alur ini didahulukan atas tangga bagian.">
            <x-slot:actions>
                <x-button size="sm" icon="bi-person-plus" data-bs-toggle="modal" data-bs-target="#modalTambahKhusus">Tambah</x-button>
            </x-slot:actions>
            @forelse ($pribadi as $p)
                @php $pg = $p['model']->dataPegawai; @endphp
                <section class="tangga-sel {{ $p['peringatan'] ? 'is-peringatan' : '' }} mb-3">
                    <div class="tangga-sel-kepala">
                        <h3 class="tangga-sel-judul d-flex flex-wrap align-items-center gap-2">
                            <i class="bi bi-person" aria-hidden="true"></i>
                            {{ $pg?->nama_lengkap ?? 'Pegawai terhapus' }}
                            @if ($p['unit'])<x-badge tone="neutral">{{ $p['unit'] }}</x-badge>@endif
                            @if ($p['peringatan'])<x-badge tone="warning" icon="bi-exclamation-triangle">Ada tahap tanpa penyetuju</x-badge>@endif
                        </h3>
                        @if ($pg)
                            <div class="tangga-sel-aksi">
                                <x-button size="sm" variant="outline" icon="bi-pencil" :href="route('admin.tangga-alur.pribadi.edit', $pg)">Ubah</x-button>
                                <form action="{{ route('admin.tangga-alur.pribadi.destroy', $pg) }}" method="POST"
                                    data-confirm="Hapus tangga khusus {{ $pg->nama_lengkap }}? Pengajuan baru kembali memakai tangga bagian / alur otomatis.">
                                    @csrf @method('DELETE')
                                    <x-button type="submit" size="sm" variant="ghost" icon="bi-arrow-counterclockwise">Kembali Otomatis</x-button>
                                </form>
                            </div>
                        @endif
                    </div>
                    <x-tangga-alur :tahap="$p['tahap']" :awal="$pg?->nama_lengkap ?? 'Pegawai'" akhir="Disetujui" :label="'Tangga khusus ' . ($pg?->nama_lengkap ?? '')" />
                    <p class="tangga-sel-info">
                        Diubah {{ $p['model']->pengubah?->name ? 'oleh ' . $p['model']->pengubah->name : '' }} · {{ $p['model']->updated_at?->translatedFormat('d M Y H:i') }}
                        @if ($p['model']->catatan) · {{ $p['model']->catatan }} @endif
                    </p>
                </section>
            @empty
                <p class="text-muted small mb-0">Belum ada. Klik <strong>Tambah</strong>, atau buka detail pegawai → Alur Persetujuan → Atur Tangga Khusus.</p>
            @endforelse
        </x-card>
    </div>
</main>

{{-- Pilih pegawai → langsung ke form tangga khusus orang itu. --}}
<div class="modal fade" id="modalTambahKhusus" tabindex="-1" aria-labelledby="judulTambahKhusus" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="judulTambahKhusus"><i class="bi bi-person-plus me-1"></i>Tambah Tangga Khusus</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted">Pilih pegawai yang alur persetujuannya ingin diatur sendiri. Hanya pegawai aktif yang belum punya tangga khusus.</p>
                <x-input name="cari-khusus" type="search" icon="bi-search" placeholder="Cari nama atau bagian, contoh: Siti" aria-label="Cari pegawai" data-saring="#daftar-calon" />
                <p class="small text-muted my-2"><span data-saring-jumlah="#daftar-calon">{{ $calonPribadi->count() }}</span> pegawai</p>
                <div class="list-group" id="daftar-calon" style="max-height: 50vh; overflow-y: auto;">
                    @foreach ($calonPribadi as $c)
                        <a href="{{ route('admin.tangga-alur.pribadi.edit', $c) }}" class="list-group-item list-group-item-action"
                            data-cari="{{ mb_strtolower($c->nama_lengkap . ' ' . $c->kepegawaian?->unit_kerja . ' ' . $c->kepegawaian?->jabatan) }}">
                            <span class="fw-semibold d-block">{{ $c->nama_lengkap }}</span>
                            <span class="small text-muted">{{ collect([$c->kepegawaian?->jabatan, $c->kepegawaian?->unit_kerja])->filter()->implode(' · ') ?: '-' }}</span>
                        </a>
                    @endforeach
                </div>
                <div data-saring-kosong="#daftar-calon" hidden class="text-muted small mt-2">Pegawai tidak ditemukan.</div>
            </div>
        </div>
    </div>
</div>
@endsection
