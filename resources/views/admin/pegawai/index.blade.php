@extends(Auth::user()->panelLayout())

@section('content')
    @php
        // Ada saringan aktif (pencarian, dropdown, atau klik grafik dashboard) → tampilkan tombol reset & teks "ditemukan".
        $adaFilter = request()->hasAny(['q', 'unit_kerja', 'status_rekrutmen', 'tanggal_dari', 'tanggal_sampai']) || $saringanDashboard;
    @endphp
    <main class="dashboard-content">
        <div class="container-fluid px-3 px-lg-4 py-4">

            @include('layouts.admin.header')

            @if (session('peringatan_keluar'))
                <div class="alert alert-info small d-flex gap-2">
                    <i class="bi bi-info-circle-fill"></i>
                    <ul class="mb-0 ps-3">@foreach (session('peringatan_keluar') as $p)<li>{{ $p }}</li>@endforeach</ul>
                </div>
            @endif

            <section class="panel" aria-label="Daftar pegawai">
                {{-- Bilah filter: cari (otomatis), unit, status, urutan. Judul kartu tidak diulang (sudah ada di judul halaman). --}}
                <form action="{{ route('admin.pegawai.index') }}" method="GET" id="filterPegawaiForm"
                    class="panel-filter px-3 pt-3 pb-2" role="search">
                    <div class="row g-2 align-items-center">
                        <div class="col-12 col-md-6 col-xl-4">
                            @include('partials.daftar-cari', [
                                'cariTeks' => 'Cari pegawai, contoh: Siti atau 115010920001',
                                'cariLabel' => 'Cari pegawai menurut nama, NIP, email, atau jabatan',
                                'cariId' => 'searchInput',
                            ])
                        </div>
                        <div class="col-6 col-md-3 col-xl-3">
                            <select name="unit_kerja" class="form-select form-select-sm" aria-label="Unit kerja"
                                onchange="document.getElementById('filterPegawaiForm').submit()">
                                <option value="">Semua Unit Kerja</option>
                                @foreach ($unitKerjaOptions as $unit)
                                    <option value="{{ $unit }}"
                                        {{ request('unit_kerja') == $unit ? 'selected' : '' }}>{{ $unit }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6 col-md-3 col-xl-2">
                            <select name="status_rekrutmen" class="form-select form-select-sm" aria-label="Status pegawai"
                                onchange="document.getElementById('filterPegawaiForm').submit()">
                                <option value="">Semua Status</option>
                                <option value="Aktif" {{ request('status_rekrutmen') == 'Aktif' ? 'selected' : '' }}>Aktif</option>
                                <option value="Tidak Aktif" {{ request('status_rekrutmen') == 'Tidak Aktif' ? 'selected' : '' }}>Tidak Aktif</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-6 col-xl-4">@include('partials.filter-tanggal', ['tanggalLabel' => 'Tgl masuk'])</div>
                    <div class="col-12 col-md-auto">@include('partials.urut', ['urutForm' => 'filterPegawaiForm'])</div>
                    </div>

                    {{-- Filter yang datang dari klik grafik dashboard: tetap terbawa saat mencari, bisa dilepas satu per satu. --}}
                    @if ($saringanDashboard)
                        @foreach (array_keys($saringanDashboard) as $kunci)
                            <input type="hidden" name="{{ $kunci }}" value="{{ request($kunci) }}">
                        @endforeach
                        <div class="d-flex flex-wrap align-items-center gap-2 mt-2" data-saringan-dashboard>
                            <span class="small text-muted"><i class="bi bi-funnel me-1"></i>Dari dashboard:</span>
                            @foreach ($saringanDashboard as $kunci => $nilai)
                                <a href="{{ request()->fullUrlWithoutQuery([$kunci, 'page']) }}" class="badge text-bg-info text-decoration-none" title="Lepas filter ini">
                                    {{ \App\Support\KelompokPegawai::LABEL[$kunci] }}: {{ $nilai }} <i class="bi bi-x-lg ms-1"></i>
                                </a>
                            @endforeach
                        </div>
                    @endif

                    {{-- Jumlah data selalu terlihat: pengguna tahu cakupan daftar tanpa menggulir ke paginasi. --}}
                    @include('partials.daftar-ringkas', [
                        'ringkasJumlah' => $pegawais->total(), 'ringkasSatuan' => 'pegawai',
                        'ringkasFilter' => $adaFilter, 'ringkasReset' => route('admin.pegawai.index'),
                    ])
                </form>

                <div class="table-responsive">
                    <table class="table align-middle mb-0" id="pegawaiTable">
                        <thead>
                            <tr>
                                <th>Pegawai</th>
                                <th>Jabatan</th>
                                <th>Status</th>
                                {{-- Kolom kontak disembunyikan di tablet agar kolom lain tidak berdesakan; tetap tampil di desktop & kartu HP. --}}
                                <th class="d-md-none d-lg-table-cell">Kontak</th>
                                <th>Tanggal Masuk</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($pegawais as $pegawai)
                                @php
                                    $status = $pegawai->kepegawaian->status_rekrutmen ?? 'Non-Aktif';
                                    $badgeClass = match (strtolower($status)) {
                                        'tetap', 'aktif' => 'text-bg-success',
                                        'kontrak' => 'text-bg-info',
                                        'probation', 'magang' => 'text-bg-warning',
                                        default => 'text-bg-secondary',
                                    };
                                    $noBpjs = optional($pegawai->bpjs)->no_bpjs_kesehatan;
                                @endphp
                                <tr>
                                    <td>
                                        <div class="table-media">
                                            <img class="product-thumb rounded-circle" loading="lazy" alt=""
                                                src="{{ optional($pegawai->dokumen)->pas_foto ? \App\Support\Berkas::url($pegawai->dokumen->pas_foto) : asset('assets-admin/images/avatar/avatar-fallback.jpg') }}">
                                            <div class="min-w-0">
                                                {{-- Nama langsung membuka detail: aksi paling sering cukup satu klik. --}}
                                                <a href="{{ route('admin.pegawai.show', $pegawai->id) }}" class="nama-tautan d-block">{{ $pegawai->nama_lengkap }}</a>
                                                <small class="text-muted angka-utuh">{{ $pegawai->nip ? 'NIP ' . $pegawai->nip : 'NIP belum diisi' }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        {{ $pegawai->kepegawaian->jabatan ?? '-' }}
                                        @if ($pegawai->kepegawaian?->unit_kerja)
                                            <small class="text-muted d-block">{{ $pegawai->kepegawaian->unit_kerja }}</small>
                                        @endif
                                        @foreach ($pegawai->penugasanAktif->where('is_utama', false) as $rangkap)
                                            <span class="badge text-bg-info" title="Rangkap tugas">Rangkap {{ $rangkap->unit_kerja }}</span>
                                        @endforeach
                                    </td>
                                    <td><span class="badge {{ $badgeClass }}">{{ ucfirst($status) }}</span></td>
                                    <td class="d-md-none d-lg-table-cell">
                                        @if ($pegawai->no_hp || $noBpjs)
                                            @if ($pegawai->no_hp)
                                                <span class="angka-utuh d-block">{{ $pegawai->no_hp }}</span>
                                            @endif
                                            @if ($noBpjs)
                                                <small class="text-muted angka-utuh">BPJS {{ $noBpjs }}</small>
                                            @endif
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="text-nowrap">{{ optional(optional($pegawai->kepegawaian)->tanggal_masuk)->translatedFormat('d M Y') ?? '-' }}</td>
                                    <td class="text-end">
                                        {{-- Aksi utama baris (Detail) terlihat; aksi lain dikumpulkan di menu ⋮ agar baris tetap tenang. --}}
                                        <div class="d-inline-flex align-items-center gap-1">
                                            <a href="{{ route('admin.pegawai.show', $pegawai->id) }}" class="btn btn-light btn-sm">
                                                <i class="bi bi-eye" aria-hidden="true"></i>Detail
                                            </a>
                                            @can('kelola-pegawai')
                                                <div class="dropdown">
                                                    @include('partials.tombol-aksi-lain', ['aksiUntuk' => $pegawai->nama_lengkap])
                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        <li>
                                                            <a class="dropdown-item" href="{{ route('admin.pegawai.edit', $pegawai->id) }}">
                                                                <i class="bi bi-pencil me-2"></i>Ubah Data
                                                            </a>
                                                        </li>
                                                        @if (($pegawai->kepegawaian->status_rekrutmen ?? null) === 'Aktif')
                                                            <li>
                                                                <button type="button" class="dropdown-item text-warning-emphasis"
                                                                    data-buka-keluar="{{ route('admin.pegawai.keluar.form', [$pegawai->id, 'kembali' => request()->fullUrl()]) }}">
                                                                    <i class="bi bi-box-arrow-right me-2"></i>Resign / Keluar
                                                                </button>
                                                            </li>
                                                        @endif
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li>
                                                            <form action="{{ route('admin.pegawai.destroy', $pegawai->id) }}" method="POST">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="dropdown-item text-danger"
                                                                    data-confirm="true"
                                                                    data-icon="warning"
                                                                    data-title="Hapus Data Pegawai?"
                                                                    data-text="Data {{ $pegawai->nama_lengkap }} akan dihapus permanen beserta dokumen terkait."
                                                                    data-confirm-text="Ya, Hapus"
                                                                    data-confirm-color="#dc3545"
                                                                    data-cancel-text="Batal">
                                                                    <i class="bi bi-trash me-2"></i>Hapus
                                                                </button>
                                                            </form>
                                                        </li>
                                                    </ul>
                                                </div>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                @include('partials.kosong-tabel', [
                                    'kosongKolom' => 6,
                                    'kosongTeks' => $adaFilter ? 'Tidak ada pegawai yang cocok dengan pencarian/filter ini.' : 'Belum ada data pegawai.',
                                    'kosongIkon' => $adaFilter ? 'bi-search' : 'bi-people',
                                    'kosongReset' => $adaFilter ? route('admin.pegawai.index') : null,
                                ])
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($pegawais->hasPages())
                    <div class="panel-footer px-3 py-2 border-top">
                        {{ $pegawais->links('pagination::bootstrap-5') }}
                    </div>
                @endif
            </section>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Cari otomatis saat mengetik ditangani global (main.js: data-cari-otomatis).

            // Resign / Keluar dari daftar: isi dialog dimuat saat diklik (dampak dihitung per pegawai), lalu dibuka di halaman ini.
            const wadah = document.getElementById('wadahModalKeluar');
            document.querySelectorAll('[data-buka-keluar]').forEach(function (tombol) {
                tombol.addEventListener('click', async function () {
                    const teksAsli = tombol.innerHTML;
                    tombol.disabled = true;
                    tombol.innerHTML = '<span class="spinner-border spinner-border-sm me-2" aria-hidden="true"></span>Memuat…';
                    try {
                        const res = await fetch(tombol.dataset.bukaKeluar, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' });
                        if (!res.ok) throw new Error(res.status === 404 ? 'Pegawai ini sudah tidak aktif.' : 'Dialog gagal dimuat (' + res.status + ').');
                        wadah.innerHTML = await res.text();
                        const modal = wadah.querySelector('#modalKeluar');
                        const form = modal.querySelector('form');
                        // Form dimuat setelah halaman siap: konfirmasi akhir dipasang di sini (form[data-confirm] global tidak menjangkaunya).
                        form.addEventListener('submit', function (e) {
                            if (form.dataset.confirmed === '1') return;
                            e.preventDefault();
                            if (!form.reportValidity()) return;
                            Swal.fire({
                                icon: 'warning', title: 'Konfirmasi', text: form.dataset.confirm, showCancelButton: true, reverseButtons: true,
                                confirmButtonText: 'Ya, proses', cancelButtonText: 'Batal', confirmButtonColor: '#dc3545', cancelButtonColor: '#6c757d',
                            }).then(function (r) {
                                if (!r.isConfirmed) return;
                                form.dataset.confirmed = '1';
                                form.querySelector('[type=submit]').disabled = true;
                                form.submit();
                            });
                        });
                        bootstrap.Modal.getOrCreateInstance(modal).show();
                    } catch (err) {
                        // Gagal memuat dialog (mis. koneksi putus): tawarkan coba lagi tanpa memuat ulang halaman.
                        Swal.fire({
                            icon: 'error', title: 'Dialog gagal dimuat', text: err.message, showCancelButton: true, reverseButtons: true,
                            confirmButtonText: 'Coba lagi', cancelButtonText: 'Tutup', confirmButtonColor: '#880f12', cancelButtonColor: '#6c757d',
                        }).then(function (r) { if (r.isConfirmed) tombol.click(); });
                    } finally {
                        tombol.disabled = false;
                        tombol.innerHTML = teksAsli;
                    }
                });
            });
        });
    </script>
    <div id="wadahModalKeluar"></div>
@endsection
