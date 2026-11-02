@extends(Auth::user()->panelLayout())

@section('title', 'Data Pegawai')

@section('content')
@php
    // Ada saringan aktif (pencarian, dropdown, atau klik grafik dashboard) → tampilkan "ditemukan" & Reset filter.
    $adaFilter = request()->hasAny(['q', 'unit_kerja', 'unit_organisasi', 'status_rekrutmen', 'tanggal_dari', 'tanggal_sampai']) || $saringanDashboard;
    $daftarUnitOrg = \App\Models\UnitOrganisasi::daftar();
    $toneStatus = fn (string $status) => match (strtolower($status)) {
        'tetap', 'aktif' => 'success',
        'kontrak' => 'info',
        'probation', 'magang' => 'warning',
        default => 'neutral',
    };
@endphp
<main class="dashboard-content">
    <div class="container-fluid">
        <x-page-header title="Data Pegawai" eyebrow="Kepegawaian" subtitle="Kelola data pegawai, informasi kepegawaian, dan dokumennya.">
            <x-slot:actions>
                @include('partials.tombol-ekspor', ['eksporRoute' => 'admin.pegawai.export'])
                @can('kelola-pegawai')
                    <x-button :href="route('admin.pegawai.create')" icon="bi-person-plus">Tambah Pegawai</x-button>
                @endcan
            </x-slot:actions>
        </x-page-header>

        @if (session('peringatan_keluar'))
            <x-alert tone="info" class="mb-3">
                <ul class="mb-0 ps-3">@foreach (session('peringatan_keluar') as $p)<li>{{ $p }}</li>@endforeach</ul>
            </x-alert>
        @endif

        <x-card :padding="false" aria-label="Daftar pegawai">
            <x-filter-bar :action="route('admin.pegawai.index')" id="filterPegawaiForm" search-id="searchInput"
                search="Cari pegawai, contoh: Siti atau 115010920001" search-label="Cari pegawai menurut nama, NIP, email, atau jabatan"
                :count="$pegawais->total()" unit="pegawai" :filtered="$adaFilter" :reset="route('admin.pegawai.index')">
                <select name="unit_kerja" class="form-select form-select-sm" aria-label="Saring bagian" onchange="this.form.submit()">
                    <option value="">Semua bagian</option>
                    @foreach ($unitKerjaOptions as $unit)
                        <option value="{{ $unit }}" @selected(request('unit_kerja') == $unit)>{{ $unit }}</option>
                    @endforeach
                </select>
                <select name="status_rekrutmen" class="form-select form-select-sm" aria-label="Saring status pegawai" onchange="this.form.submit()">
                    <option value="">Semua status</option>
                    <option value="Aktif" @selected(request('status_rekrutmen') == 'Aktif')>Aktif</option>
                    <option value="Tidak Aktif" @selected(request('status_rekrutmen') == 'Tidak Aktif')>Tidak aktif</option>
                </select>
                @if ($daftarUnitOrg)
                    <select name="unit_organisasi" class="form-select form-select-sm" aria-label="Saring unit" onchange="this.form.submit()">
                        <option value="">Semua unit</option>
                        <option value="-" @selected(request('unit_organisasi') === '-')>Belum ada unit</option>
                        @foreach ($daftarUnitOrg as $unitOrg)
                            <option value="{{ $unitOrg }}" @selected(request('unit_organisasi') === $unitOrg)>{{ $unitOrg }}</option>
                        @endforeach
                    </select>
                @endif
                @include('partials.filter-tanggal', ['tanggalLabel' => 'Tgl masuk'])
                @include('partials.urut', ['urutForm' => 'filterPegawaiForm'])

                {{-- Filter dari klik grafik dashboard: tetap terbawa saat mencari, bisa dilepas satu per satu. --}}
                @if ($saringanDashboard)
                    <x-slot:hidden>
                        @foreach (array_keys($saringanDashboard) as $kunci)
                            <input type="hidden" name="{{ $kunci }}" value="{{ request($kunci) }}">
                        @endforeach
                    </x-slot:hidden>
                    <x-slot:chips>
                        <span class="hr-filter-bar-jumlah align-self-center"><i class="bi bi-funnel me-1" aria-hidden="true"></i>Dari dashboard:</span>
                        @foreach ($saringanDashboard as $kunci => $nilai)
                            <x-chip :href="request()->fullUrlWithoutQuery([$kunci, 'page'])" active icon="bi-x-lg" data-saringan-dashboard
                                aria-label="Lepas filter {{ \App\Support\KelompokPegawai::LABEL[$kunci] }}: {{ $nilai }}">
                                {{ \App\Support\KelompokPegawai::LABEL[$kunci] }}: {{ $nilai }}
                            </x-chip>
                        @endforeach
                    </x-slot:chips>
                @endif
            </x-filter-bar>

            <x-table caption="Daftar pegawai" id="pegawaiTable" sticky striped scroll>
                <x-slot:head>
                    <tr>
                        <th>Pegawai</th>
                        <th>Jabatan &amp; Bagian</th>
                        <th>Status</th>
                        {{-- Kolom kontak disembunyikan di tablet agar kolom lain tidak berdesakan; tetap tampil di desktop & kartu HP. --}}
                        <th class="d-md-none d-lg-table-cell">Kontak</th>
                        <th>Tanggal Masuk</th>
                        <th class="hr-kolom-aksi">Aksi</th>
                    </tr>
                </x-slot:head>
                @forelse ($pegawais as $pegawai)
                    @php
                        $status = $pegawai->kepegawaian->status_rekrutmen ?? 'Non-Aktif';
                        $noBpjs = optional($pegawai->bpjs)->no_bpjs_kesehatan;
                        $foto = optional($pegawai->dokumen)->pas_foto ? \App\Support\Berkas::url($pegawai->dokumen->pas_foto) : null;
                        $kp = $pegawai->kepegawaian;
                    @endphp
                    <tr>
                        <td>
                            <div class="hr-sel-utama">
                                <x-avatar :src="$foto" :name="$pegawai->nama_lengkap" size="sm" />
                                <div class="min-w-0">
                                    {{-- Nama langsung membuka detail: aksi paling sering cukup satu klik. --}}
                                    <a href="{{ route('admin.pegawai.show', $pegawai->id) }}" class="hr-sel-judul">{{ $pegawai->nama_lengkap }}</a>
                                    <span class="hr-sel-sub angka-utuh">{{ $pegawai->nip ? 'NIP ' . $pegawai->nip : 'NIP belum diisi' }}</span>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="d-block">{{ $kp->jabatan ?? '-' }}</span>
                            @if ($kp?->unit_kerja || $kp?->unit_organisasi)
                                <span class="hr-sel-sub">{{ collect([$kp->unit_kerja, $kp->unit_organisasi ? 'Unit ' . $kp->unit_organisasi : null])->filter()->implode(' · ') }}</span>
                            @endif
                            @if (($rangkap = $pegawai->penugasanAktif->where('is_utama', false))->isNotEmpty())
                                <span class="hr-sel-badge">
                                    @foreach ($rangkap as $r)
                                        <x-badge tone="info" title="Rangkap tugas">Rangkap {{ $r->unit_kerja }}</x-badge>
                                    @endforeach
                                </span>
                            @endif
                        </td>
                        <td><x-badge :tone="$toneStatus($status)" dot>{{ ucfirst($status) }}</x-badge></td>
                        <td class="d-md-none d-lg-table-cell">
                            @if ($pegawai->no_hp || $noBpjs)
                                @if ($pegawai->no_hp)<span class="angka-utuh d-block">{{ $pegawai->no_hp }}</span>@endif
                                @if ($noBpjs)<span class="hr-sel-sub angka-utuh">BPJS {{ $noBpjs }}</span>@endif
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="text-nowrap">{{ optional($kp?->tanggal_masuk)->translatedFormat('d M Y') ?? '-' }}</td>
                        <td class="text-end hr-kolom-aksi">
                            <div class="hr-aksi-baris">
                                <x-icon-button icon="bi-eye" label="Lihat detail" :href="route('admin.pegawai.show', $pegawai->id)" />
                                @can('kelola-pegawai')
                                    <x-icon-button icon="bi-pencil" label="Ubah data" :href="route('admin.pegawai.edit', $pegawai->id)" />
                                    @if (($kp->status_rekrutmen ?? null) === 'Aktif')
                                        <x-icon-button icon="bi-box-arrow-right" label="Resign / Keluar" tone="warning"
                                            data-buka-keluar="{{ route('admin.pegawai.keluar.form', [$pegawai->id, 'kembali' => request()->fullUrl()]) }}" />
                                    @else
                                        {{-- Pengganti selebar tombol agar ikon antarbaris tetap sejajar. --}}
                                        <span class="hr-ikon-btn invisible" aria-hidden="true"></span>
                                    @endif
                                    <form action="{{ route('admin.pegawai.destroy', $pegawai->id) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <x-icon-button type="submit" icon="bi-trash" label="Hapus" tone="danger"
                                            data-confirm="true" data-icon="warning" data-title="Hapus Data Pegawai?"
                                            data-text="Data {{ $pegawai->nama_lengkap }} akan dihapus permanen beserta dokumen terkait."
                                            data-confirm-text="Ya, Hapus" data-cancel-text="Batal" />
                                    </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <x-table-empty :colspan="6" :icon="$adaFilter ? 'bi-search' : 'bi-people'"
                        :title="$adaFilter ? 'Tidak ada pegawai yang cocok' : 'Belum ada data pegawai'">
                        {{ $adaFilter ? 'Coba kata kunci lain atau reset filter untuk melihat semua pegawai.' : 'Tambahkan pegawai pertama untuk mulai mengelola data kepegawaian.' }}
                        <x-slot:action>
                            @if ($adaFilter)
                                <x-button :href="route('admin.pegawai.index')" variant="secondary" icon="bi-x-circle">Reset filter</x-button>
                            @else
                                @can('kelola-pegawai')<x-button :href="route('admin.pegawai.create')" icon="bi-person-plus">Tambah Pegawai</x-button>@endcan
                            @endif
                        </x-slot:action>
                    </x-table-empty>
                @endforelse
            </x-table>

            @if ($pegawais->hasPages())
                <x-slot:footer><x-pagination :paginator="$pegawais" /></x-slot:footer>
            @endif
        </x-card>
    </div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Resign / Keluar dari daftar: isi dialog dimuat saat diklik (dampak dihitung per pegawai), lalu dibuka di halaman ini.
        const wadah = document.getElementById('wadahModalKeluar');
        document.querySelectorAll('[data-buka-keluar]').forEach(function (tombol) {
            tombol.addEventListener('click', async function () {
                const teksAsli = tombol.innerHTML;
                tombol.disabled = true;
                tombol.innerHTML = '<span class="spinner-border spinner-border-sm" aria-hidden="true"></span>';
                tombol.setAttribute('aria-busy', 'true');
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
                    tombol.removeAttribute('aria-busy');
                    tombol.innerHTML = teksAsli;
                }
            });
        });
    });
</script>
<div id="wadahModalKeluar"></div>
@endsection
