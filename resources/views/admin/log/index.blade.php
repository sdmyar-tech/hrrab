@extends('layouts.admin.app')

@section('title', 'Log Aktivitas')

@section('content')
@php
    $adaFilter = request()->hasAny(['q', 'modul', 'aksi', 'role', 'status', 'dari', 'sampai']);
    $lamaSimpan = fn (int $hari) => $hari % 365 === 0 ? ($hari / 365) . ' tahun' : $hari . ' hari';
    $param = request()->except(['status', 'aksi', 'page']);
    $kartu = [
        ['Aktivitas hari ini', $ringkas['hari_ini'], 'bi-activity', 'primary', null],
        ['Pengguna aktif hari ini', $ringkas['pengguna_hari_ini'], 'bi-people', 'info', null],
        ['Masuk hari ini', $ringkas['login_hari_ini'], 'bi-box-arrow-in-right', 'success', route('admin.log.index', $param + ['aksi' => 'login', 'dari' => now()->toDateString()])],
        ['Gagal hari ini', $ringkas['gagal_hari_ini'], 'bi-exclamation-triangle', $ringkas['gagal_hari_ini'] ? 'danger' : 'neutral', route('admin.log.index', $param + ['status' => 'gagal', 'dari' => now()->toDateString()])],
    ];
    $jenisUbah = ['tambah' => 'dibuat', 'ubah' => 'diubah', 'hapus' => 'dihapus'];
    $tampilNilai = fn ($v) => $v === null || $v === '' ? '—' : (is_bool($v) ? ($v ? 'Ya' : 'Tidak') : (is_array($v) ? json_encode($v, JSON_UNESCAPED_UNICODE) : $v));
@endphp
<main class="dashboard-content">
    <div class="container-fluid">
        <x-page-header title="Log Aktivitas" eyebrow="Sistem"
            subtitle="Jejak tindakan semua pengguna selain superadmin: masuk, tambah, ubah, hapus, persetujuan, dan ekspor data.">
            <x-slot:actions>
                @include('partials.tombol-ekspor', ['eksporRoute' => 'admin.log.export'])
            </x-slot:actions>
        </x-page-header>

        <section class="row g-3 mb-4" aria-label="Ringkasan aktivitas hari ini">
            @foreach ($kartu as [$label, $angka, $ikon, $tone, $href])
                <div class="col-6 col-xl-3">
                    <x-stat-card :label="$label" :value="$angka" :icon="$ikon" :tone="$tone" :href="$href" />
                </div>
            @endforeach
        </section>

        <x-card :padding="false" aria-label="Daftar log aktivitas">
            <x-filter-bar :action="route('admin.log.index')" id="filterLogForm" data-filter-daftar
                search="Cari aktivitas, contoh: Siti atau cuti" search-label="Cari log menurut nama pengguna atau deskripsi aktivitas"
                :count="$logs->total()" unit="aktivitas" :filtered="$adaFilter" :reset="route('admin.log.index')">
                <select name="aksi" class="form-select form-select-sm" aria-label="Saring jenis aksi" onchange="this.form.submit()">
                    <option value="">Semua aksi</option>
                    @foreach (\App\Models\LogAktivitas::AKSI as $kunci => [$labelAksi])
                        @continue($kunci === 'lainnya')
                        <option value="{{ $kunci }}" @selected(request('aksi') === $kunci)>{{ $labelAksi }}</option>
                    @endforeach
                </select>
                <select name="modul" class="form-select form-select-sm" aria-label="Saring modul" onchange="this.form.submit()">
                    <option value="">Semua modul</option>
                    @foreach ($modulOpsi as $m)
                        <option value="{{ $m }}" @selected(request('modul') === $m)>{{ $m }}</option>
                    @endforeach
                </select>
                <select name="role" class="form-select form-select-sm" aria-label="Saring role pengguna" onchange="this.form.submit()">
                    <option value="">Semua role</option>
                    @foreach (array_diff([...\App\Models\User::INTERNAL_ROLES, \App\Models\User::ROLE_PELAMAR], [\App\Models\User::ROLE_ADMIN]) as $r)
                        <option value="{{ $r }}" @selected(request('role') === $r)>{{ \App\Models\User::roleLabelFor($r) }}</option>
                    @endforeach
                </select>
                <select name="status" class="form-select form-select-sm" aria-label="Saring status aktivitas" onchange="this.form.submit()">
                    <option value="">Semua status</option>
                    <option value="berhasil" @selected(request('status') === 'berhasil')>Berhasil</option>
                    <option value="gagal" @selected(request('status') === 'gagal')>Gagal</option>
                </select>
                {{-- Rentang tanggal memakai nama isian log sendiri (dari / sampai). --}}
                <div class="input-group input-group-sm filter-tanggal" role="group" aria-label="Saring rentang tanggal">
                    <span class="input-group-text"><i class="bi bi-calendar-range" aria-hidden="true"></i><span class="filter-tanggal-teks ms-1">Tanggal</span></span>
                    <input type="date" name="dari" class="form-control" value="{{ request('dari') }}" aria-label="Dari tanggal" title="Dari tanggal" onchange="this.form.submit()">
                    <span class="input-group-text">s/d</span>
                    <input type="date" name="sampai" class="form-control" value="{{ request('sampai') }}" aria-label="Sampai tanggal" title="Sampai tanggal" onchange="this.form.submit()">
                </div>
                @include('partials.urut', ['urutForm' => 'filterLogForm'])
            </x-filter-bar>

            <x-table caption="Daftar log aktivitas" id="log-tabel" class="log-tabel-wadah" sticky scroll>
                <x-slot:head>
                    <tr>
                        <th>Waktu</th>
                        <th>Pengguna</th>
                        <th>Aksi</th>
                        <th>Modul</th>
                        <th>Deskripsi</th>
                        <th class="d-md-none d-xl-table-cell">IP</th>
                        <th class="hr-kolom-aksi"><span class="visually-hidden">Rincian</span></th>
                    </tr>
                </x-slot:head>
                @forelse ($logs as $log)
                    @php
                        [$labelAksi, $badgeAksi, $ikonAksi] = $log->aksiInfo();
                        $idRinci = 'log-rinci-' . $log->id;
                    @endphp
                    <tr class="log-baris {{ $loop->even ? 'is-genap' : '' }} {{ $log->status === 'gagal' ? 'is-gagal' : '' }}" data-buka-rinci="{{ $idRinci }}">
                        <td class="text-nowrap">
                            <span class="d-block hr-angka text-start" title="{{ $log->created_at->translatedFormat('d M Y H:i:s') }}">{{ $log->created_at->format('H:i') }}</span>
                            <span class="hr-sel-sub">{{ $log->created_at->isToday() ? 'Hari ini' : ($log->created_at->isYesterday() ? 'Kemarin' : $log->created_at->translatedFormat('d M Y')) }}</span>
                        </td>
                        <td>
                            <div class="hr-sel-utama">
                                <x-avatar :name="$log->nama_pengguna" size="sm" />
                                <div class="min-w-0">
                                    <span class="hr-sel-judul">{{ $log->nama_pengguna }}</span>
                                    <span class="hr-sel-sub">{{ \App\Models\User::roleLabelFor($log->role) }}</span>
                                </div>
                            </div>
                        </td>
                        <td>
                            <x-status-badge :badge="$badgeAksi" :icon="$ikonAksi">{{ $labelAksi }}</x-status-badge>
                            @if ($log->status === 'gagal')<span class="hr-sel-badge"><x-badge tone="danger" dot>Gagal</x-badge></span>@endif
                        </td>
                        <td><x-badge tone="neutral">{{ $log->modul }}</x-badge></td>
                        <td class="log-deskripsi">{{ \Illuminate\Support\Str::limit($log->deskripsi, 90) }}</td>
                        <td class="d-md-none d-xl-table-cell"><span class="angka-utuh small">{{ $log->ip ?? '-' }}</span></td>
                        <td class="text-end hr-kolom-aksi">
                            <div class="hr-aksi-baris">
                                <x-icon-button icon="bi-chevron-down" class="log-buka" :label="'Lihat rincian' . ($log->perubahan ? ' (' . count($log->perubahan) . ' perubahan)' : '')"
                                    aria-expanded="false" :aria-controls="$idRinci" />
                            </div>
                        </td>
                    </tr>
                    <tr class="log-rinci" id="{{ $idRinci }}" hidden>
                        <td colspan="7">
                            <div class="log-rinci-isi">
                                <dl class="hr-konteks">
                                    <dt>Waktu</dt><dd>{{ $log->created_at->translatedFormat('l, d F Y · H:i:s') }}</dd>
                                    <dt>Deskripsi</dt><dd>{{ $log->deskripsi }}</dd>
                                    <dt>Status</dt><dd>{{ $log->status === 'gagal' ? 'Gagal' : 'Berhasil' }}</dd>
                                    <dt>IP</dt><dd>{{ $log->ip ?? '-' }}</dd>
                                    <dt>Perangkat</dt><dd>{{ $log->perangkat ?: '-' }}</dd>
                                </dl>
                                @foreach ($log->perubahan ?? [] as $p)
                                    <div class="log-perubahan">
                                        <p class="log-perubahan-judul">
                                            {{ $p['model'] }}@if ($p['id']) #{{ $p['id'] }}@endif
                                            <span>· {{ $jenisUbah[$p['jenis']] ?? $p['jenis'] }}</span>
                                        </p>
                                        @if (! empty($p['kolom']))
                                            <table class="table table-sm mb-0 log-banding">
                                                <thead>
                                                    <tr>
                                                        <th scope="col">Kolom</th>
                                                        @if ($p['jenis'] === 'ubah')<th scope="col">Sebelum</th><th scope="col">Sesudah</th>@else<th scope="col">Nilai</th>@endif
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($p['kolom'] as $kolom => [$lama, $baru])
                                                        <tr>
                                                            <th scope="row">{{ \Illuminate\Support\Str::headline($kolom) }}</th>
                                                            @if ($p['jenis'] === 'ubah')
                                                                <td class="lama">{{ $tampilNilai($lama) }}</td>
                                                                <td class="baru">{{ $tampilNilai($baru) }}</td>
                                                            @else
                                                                <td class="{{ $p['jenis'] === 'hapus' ? 'lama' : 'baru' }}">{{ $tampilNilai($p['jenis'] === 'hapus' ? $lama : $baru) }}</td>
                                                            @endif
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        @endif
                                    </div>
                                @endforeach
                                @if (! $log->perubahan)
                                    <p class="form-text mb-0">Aktivitas ini tidak mengubah data (mis. masuk, membuka, atau ekspor).</p>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <x-table-empty :colspan="7" :icon="$adaFilter ? 'bi-search' : 'bi-journal'"
                        :title="$adaFilter ? 'Tidak ada aktivitas yang cocok' : 'Belum ada aktivitas tercatat'">
                        {{ $adaFilter ? 'Coba kata kunci lain atau reset filter.' : 'Tindakan pengguna akan tercatat di sini secara otomatis.' }}
                        @if ($adaFilter)
                            <x-slot:action><x-button :href="route('admin.log.index')" variant="secondary" icon="bi-x-circle">Reset filter</x-button></x-slot:action>
                        @endif
                    </x-table-empty>
                @endforelse
            </x-table>

            @if ($logs->hasPages())
                <x-slot:footer><x-pagination :paginator="$logs" /></x-slot:footer>
            @endif
        </x-card>

        <x-card title="Masa Simpan & Arsip" icon="bi-archive" class="mt-4"
            subtitle="Log yang lewat masa simpan otomatis diarsipkan ke file Excel di server (tidak bisa diakses publik), lalu dihapus dari daftar di atas. Berjalan setiap tanggal 1.">
            <div class="row g-4">
                <div class="col-lg-5">
                    <dl class="hr-konteks">
                        @foreach (\App\Support\ArsipLog::KATEGORI as $kunci => [$labelKategori])
                            <dt>{{ $labelKategori }}</dt><dd>{{ $lamaSimpan($retensi[$kunci]) }}</dd>
                        @endforeach
                    </dl>
                </div>
                <div class="col-lg-7">
                    @forelse ($arsip as $file)
                        <a href="{{ route('admin.log.arsip', $file['nama']) }}" class="log-arsip">
                            <i class="bi bi-file-earmark-excel" aria-hidden="true"></i>
                            <span class="flex-grow-1 text-truncate">{{ $file['nama'] }}</span>
                            <span class="hr-sel-sub text-nowrap">{{ \Carbon\Carbon::createFromTimestamp($file['waktu'])->translatedFormat('d M Y') }} · {{ number_format($file['ukuran'] / 1024, 0, ',', '.') }} KB</span>
                            <i class="bi bi-download" aria-hidden="true"></i>
                        </a>
                    @empty
                        <x-empty-state icon="bi-archive" title="Belum ada arsip" compact>File pertama muncul setelah ada log yang melewati masa simpan.</x-empty-state>
                    @endforelse
                </div>
            </div>
        </x-card>
    </div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Baris log bisa dibuka (klik baris atau tombol panah) untuk melihat rincian & perubahan sebelum → sesudah.
        function alih(baris, buka) {
            const rinci = document.getElementById(baris.dataset.bukaRinci);
            const tombol = baris.querySelector('.log-buka');
            const jadi = buka ?? rinci.hidden;
            rinci.hidden = !jadi;
            baris.classList.toggle('is-terbuka', jadi);
            tombol.setAttribute('aria-expanded', jadi ? 'true' : 'false');
        }
        document.querySelectorAll('[data-buka-rinci]').forEach(function (baris) {
            baris.addEventListener('click', function (e) {
                if (e.target.closest('a, input, select, textarea') || (e.target.closest('button') && !e.target.closest('.log-buka'))) return;
                if (window.getSelection && String(window.getSelection()).length) return; // sedang menyalin teks
                alih(baris);
            });
        });
    });
</script>
@endsection
