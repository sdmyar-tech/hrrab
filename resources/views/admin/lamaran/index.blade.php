@extends('layouts.admin.app')

@section('title', 'Lamaran Masuk')

@section('content')
@php
    $adaFilter = request()->hasAny(['q', 'status', 'jalur', 'lowongan_id', 'tanggal_dari', 'tanggal_sampai']);
    $statusAktif = (string) request('status');
    $param = request()->except(['status', 'page']);
    // Kartu angka: klik = saring status (saringan lain tetap).
    $kartu = [
        ['', 'Total lamaran', $hitungStatus['semua'], 'bi-collection', 'primary',
            $hitungStatus['ditolak'] . ' ditolak · ' . $hitungStatus['dibatalkan'] . ' dibatalkan'],
        ['menunggu_verifikasi', 'Baru', $hitungStatus['menunggu_verifikasi'], 'bi-hourglass-split', 'warning', 'Menunggu verifikasi berkas'],
        ['proses', 'Dalam proses', $hitungStatus['proses'], 'bi-arrow-repeat', 'info', 'Interview, onboarding, kontrak'],
        ['diterima', 'Diterima', $hitungStatus['diterima'], 'bi-check2-circle', 'success', 'Direkrut / ditetapkan'],
    ];
    $statusOpsi = [
        'menunggu_verifikasi' => 'Menunggu verifikasi', 'proses' => 'Dalam proses', 'diterima' => 'Diterima',
        'ditolak' => 'Ditolak', 'dibatalkan' => 'Dibatalkan pelamar',
    ];
    // Pilihan saringan lowongan (backend sudah mendukung ?lowongan_id): hanya lowongan yang boleh dilihat pengguna ini.
    $opsiLowongan = \App\Support\RekrutmenAccess::scopeUnitKerja(\App\Models\Lowongan::query(), auth()->user())
        ->latest()->get(['id', 'judul', 'unit_kerja']);
@endphp
<main class="dashboard-content">
    <div class="container-fluid">
        <x-page-header title="Lamaran Masuk" eyebrow="Rekrutmen" subtitle="Verifikasi, interview, kontrak, hingga keputusan akhir.">
            <x-slot:actions>
                @include('partials.tombol-ekspor', ['eksporRoute' => 'admin.lamaran.export'])
                @include('partials.tombol-ekspor', ['eksporRoute' => 'admin.lamaran.export-jadwal', 'eksporTeks' => 'Ekspor Jadwal'])
                @if ($canReview)
                    <x-button :href="route('admin.lamaran.internal.create')" icon="bi-person-plus">Tambah Kandidat Internal</x-button>
                @endif
            </x-slot:actions>
        </x-page-header>

        <section class="row g-3 mb-4" aria-label="Ringkasan status lamaran">
            @foreach ($kartu as [$status, $label, $angka, $ikon, $tone, $meta])
                <div class="col-6 col-xl-3">
                    <x-stat-card :label="$label" :value="$angka" :icon="$ikon" :tone="$tone" :meta="$meta"
                        :href="route('admin.lamaran.index', $status === '' ? $param : $param + ['status' => $status])"
                        @class(['hr-stat-aktif' => $statusAktif === $status]) />
                </div>
            @endforeach
        </section>

        <x-card :padding="false" aria-label="Daftar lamaran">
            <x-filter-bar :action="route('admin.lamaran.index')" id="filterLamaranForm"
                search="Cari kandidat, contoh: Budi Santoso" search-label="Cari lamaran menurut nama atau email kandidat"
                :count="$lamarans->total()" unit="lamaran" :filtered="$adaFilter" :reset="route('admin.lamaran.index')">
                <select name="status" class="form-select form-select-sm" aria-label="Saring status lamaran" onchange="this.form.submit()">
                    <option value="">Semua status</option>
                    @foreach ($statusOpsi as $nilai => $label)
                        <option value="{{ $nilai }}" @selected($statusAktif === $nilai)>{{ $label }}</option>
                    @endforeach
                </select>
                <select name="lowongan_id" class="form-select form-select-sm rk-saring-lowongan" aria-label="Saring lowongan" onchange="this.form.submit()">
                    <option value="">Semua lowongan</option>
                    @foreach ($opsiLowongan as $lw)
                        <option value="{{ $lw->id }}" @selected((string) request('lowongan_id') === (string) $lw->id)>{{ $lw->judul }} · {{ $lw->unit_kerja }}</option>
                    @endforeach
                </select>
                <select name="jalur" class="form-select form-select-sm" aria-label="Saring jalur lamaran" onchange="this.form.submit()">
                    <option value="">Semua jalur</option>
                    <option value="umum" @selected(request('jalur') === 'umum')>Jalur umum (lowongan)</option>
                    <option value="internal" @selected(request('jalur') === 'internal')>Jalur langsung (internal)</option>
                </select>
                @include('partials.filter-tanggal', ['tanggalLabel' => 'Masuk'])
                @include('partials.urut', ['urutForm' => 'filterLamaranForm'])
            </x-filter-bar>

            <x-table caption="Daftar lamaran" id="tabelLamaran" sticky striped scroll>
                <x-slot:head>
                    <tr>
                        <th>Kandidat</th>
                        <th>Lowongan / Posisi</th>
                        {{-- Disembunyikan di tablet (768–1199) agar kolom aksi tidak terpotong; tanggal tetap tampil di bawah posisi. --}}
                        <th class="d-md-none d-xl-table-cell">Tanggal Lamar</th>
                        <th>Status</th>
                        <th class="hr-kolom-aksi">Aksi</th>
                    </tr>
                </x-slot:head>
                @forelse ($lamarans as $item)
                    @php
                        $sudahKaryawan = $item->dataPelamar?->user?->role === \App\Models\User::ROLE_STAFF;
                        $riwayat = $sudahKaryawan
                            ? collect()
                            : ($item->dataPelamar?->lamarans->where('id', '!=', $item->id) ?? collect());
                        $foto = $item->dataPelamar?->foto_path ? \App\Support\Berkas::url($item->dataPelamar->foto_path) : null;
                        $nama = $item->namaKandidat() ?? '-';
                        // Konteks dialog Tolak: jelas siapa & posisi apa yang ditolak.
                        $konteks = [
                            ['Kandidat', $nama . ($item->emailKandidat() ? ' · ' . $item->emailKandidat() : '')],
                            ['Posisi', $item->judulPosisi() . ($item->unitKerjaTujuan() ? ' · ' . $item->unitKerjaTujuan() : '')],
                            ['Tahap', $item->statusLabel()],
                        ];
                    @endphp
                    <tr>
                        <td>
                            <div class="hr-sel-utama align-items-start">
                                <x-avatar :src="$foto" :name="$nama" size="sm" />
                                <div class="min-w-0">
                                    <a href="{{ route('admin.lamaran.show', $item) }}" class="hr-sel-judul">{{ $nama }}</a>
                                    <span class="hr-sel-sub">{{ $item->emailKandidat() ?? '-' }}</span>
                                    @if ($item->isJalurInternal())
                                        <span class="hr-sel-badge">
                                            <x-badge tone="info" icon="bi-person-check">Jalur Langsung</x-badge>
                                            @if ($item->isKandidatPegawai())<x-badge tone="info">Pegawai Yayasan</x-badge>@endif
                                        </span>
                                    @endif
                                    @if ($riwayat->isNotEmpty())
                                        <button type="button" class="btn btn-link btn-sm rk-riwayat-buka" data-bs-toggle="collapse" data-bs-target="#riwayat-{{ $item->id }}"
                                            aria-expanded="false" aria-controls="riwayat-{{ $item->id }}">
                                            <i class="bi bi-clock-history" aria-hidden="true"></i>{{ $riwayat->count() }}x lamaran sebelumnya
                                            <i class="bi bi-chevron-down rk-riwayat-panah" aria-hidden="true"></i>
                                        </button>
                                        <div class="collapse" id="riwayat-{{ $item->id }}">
                                            <ul class="rk-riwayat">
                                                @foreach ($riwayat as $r)
                                                    <li>
                                                        <div class="min-w-0">
                                                            <span class="d-block">{{ $r->lowongan?->judul ?? $r->judulPosisi() }}</span>
                                                            @if ($r->catatan_admin)<span class="hr-sel-sub fst-italic">{{ $r->catatan_admin }}</span>@endif
                                                        </div>
                                                        <x-status-badge :badge="$r->badgeClass()">{{ $r->statusLabel() }}</x-status-badge>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="d-block">{{ $item->judulPosisi() ?? '-' }}</span>
                            <span class="hr-sel-sub">{{ $item->unitKerjaTujuan() }}{{ $item->lowongan ? '' : ' · tanpa lowongan' }}</span>
                            <span class="hr-sel-sub d-none d-md-block d-xl-none">Masuk {{ optional($item->created_at)->translatedFormat('d M Y') }}</span>
                        </td>
                        <td class="d-md-none d-xl-table-cell">
                            <span class="text-nowrap">{{ optional($item->created_at)->translatedFormat('d M Y') }}</span>
                            <span class="hr-sel-sub">{{ optional($item->created_at)->diffForHumans() }}</span>
                        </td>
                        <td>
                            {{-- Status + tahap dalam satu kolom: tahap hanya relevan saat lamaran sedang diproses. --}}
                            <x-status-badge :badge="$item->badgeClass()" dot>{{ $item->isProses() ? 'Proses' : $item->statusLabel() }}</x-status-badge>
                            @if ($item->isProses())
                                <span class="hr-sel-sub mt-1">Tahap: {{ $item->tahapLabel() }}</span>
                            @endif
                        </td>
                        <td class="text-end hr-kolom-aksi">
                            {{-- Ubah status langsung (endpoint yang sudah ada): Terima verifikasi · Tolak — lalu Lihat detail. Pengganti menjaga ikon sejajar. --}}
                            <div class="hr-aksi-baris">
                                @if ($canReview)
                                    @if ($item->isMenungguVerifikasi())
                                        <form action="{{ route('admin.lamaran.verifikasi.terima', $item) }}" method="POST">
                                            @csrf
                                            <x-icon-button type="submit" icon="bi-check-lg" tone="success" label="Terima verifikasi"
                                                data-confirm="true" data-title="Terima verifikasi lamaran?" data-confirm-text="Ya, Terima" data-cancel-text="Batal"
                                                :data-text="$nama . ' — ' . $item->judulPosisi() . '. Berkas dinyatakan lengkap dan kandidat lanjut ke tahap Interview 1 (Kepala SDM).'" />
                                        </form>
                                    @else
                                        <span class="hr-ikon-btn invisible" aria-hidden="true"></span>
                                    @endif
                                    @if ($item->isMenungguVerifikasi() || $item->isProses())
                                        <x-icon-button icon="bi-x-lg" tone="danger" label="Tolak lamaran" data-tolak-lamaran
                                            :data-url="$item->isMenungguVerifikasi() ? route('admin.lamaran.verifikasi.tolak', $item) : route('admin.lamaran.tolak', $item)"
                                            :data-konteks="json_encode($konteks, JSON_UNESCAPED_UNICODE)" />
                                    @else
                                        <span class="hr-ikon-btn invisible" aria-hidden="true"></span>
                                    @endif
                                @endif
                                <x-icon-button icon="bi-eye" label="Lihat detail" :href="route('admin.lamaran.show', $item)" />
                            </div>
                        </td>
                    </tr>
                @empty
                    <x-table-empty :colspan="5" :icon="$adaFilter ? 'bi-search' : 'bi-inbox'"
                        :title="$adaFilter ? 'Tidak ada lamaran yang cocok' : 'Belum ada lamaran masuk'">
                        {{ $adaFilter ? 'Coba kata kunci lain atau reset filter untuk melihat semua lamaran.' : 'Lamaran dari halaman karier dan kandidat yang ditambahkan langsung akan tampil di sini.' }}
                        <x-slot:action>
                            @if ($adaFilter)
                                <x-button :href="route('admin.lamaran.index')" variant="secondary" icon="bi-x-circle">Reset filter</x-button>
                            @elseif ($canReview)
                                <x-button :href="route('admin.lamaran.internal.create')" icon="bi-person-plus">Tambah Kandidat Internal</x-button>
                            @endif
                        </x-slot:action>
                    </x-table-empty>
                @endforelse
            </x-table>

            @if ($lamarans->hasPages())
                <x-slot:footer><x-pagination :paginator="$lamarans" /></x-slot:footer>
            @endif
        </x-card>
    </div>
</main>

@if ($canReview)
    {{-- Dialog Tolak (verifikasi.tolak / tolak): alasan wajib, dikirim sebagai catatan_admin seperti di halaman detail. --}}
    <x-modal id="modalTolakLamaran" title="Tolak lamaran?" icon="bi-x-octagon" tone="danger">
        <form method="POST" id="formTolakLamaran" novalidate>
            @csrf
            <p class="mb-3">Lamaran berikut akan <strong>ditolak</strong> dan prosesnya berhenti. Alasan tersimpan sebagai catatan dan terlihat oleh kandidat.</p>
            <dl class="hr-konteks" data-konteks></dl>
            <div class="mt-3">
                <label class="form-label required" for="tolak-lamaran-alasan">Alasan penolakan</label>
                <textarea name="catatan_admin" id="tolak-lamaran-alasan" class="form-control" rows="3" maxlength="1000" required
                    placeholder="Tulis alasan yang jelas, contoh: kualifikasi pendidikan belum sesuai persyaratan lowongan."></textarea>
                <div class="invalid-feedback">Alasan penolakan wajib diisi.</div>
            </div>
        </form>
        <x-slot:footer>
            <x-button variant="secondary" data-bs-dismiss="modal">Batal</x-button>
            <x-button type="submit" variant="danger" form="formTolakLamaran" icon="bi-x-lg" data-kirim-tolak>Ya, Tolak</x-button>
        </x-slot:footer>
    </x-modal>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modal = document.getElementById('modalTolakLamaran');
            const form = document.getElementById('formTolakLamaran');
            const alasan = document.getElementById('tolak-lamaran-alasan');
            const kirim = modal.querySelector('[data-kirim-tolak]');
            const dl = modal.querySelector('[data-konteks]');

            // Buka dialog dari ikon Tolak di baris mana pun: isi tujuan form + konteks kandidat.
            document.addEventListener('click', function (e) {
                const tombol = e.target.closest('[data-tolak-lamaran]');
                if (!tombol) return;
                form.action = tombol.dataset.url;
                form.classList.remove('was-validated');
                alasan.value = '';
                let konteks = [];
                try { konteks = JSON.parse(tombol.dataset.konteks || '[]'); } catch (err) { konteks = []; }
                dl.replaceChildren();
                konteks.forEach(function ([label, nilai]) {
                    if (!nilai) return;
                    const dt = document.createElement('dt');
                    const dd = document.createElement('dd');
                    dt.textContent = label;
                    dd.textContent = nilai;
                    dl.append(dt, dd);
                });
                bootstrap.Modal.getOrCreateInstance(modal).show();
            });
            modal.addEventListener('shown.bs.modal', function () { alasan.focus(); });

            // Kirim: alasan wajib diisi, lalu kunci tombol agar tidak terkirim dua kali.
            form.addEventListener('submit', function (e) {
                alasan.value = alasan.value.trim();
                form.classList.add('was-validated');
                if (!form.checkValidity()) {
                    e.preventDefault();
                    alasan.focus();
                    return;
                }
                kirim.disabled = true;
                kirim.setAttribute('aria-busy', 'true');
                kirim.insertAdjacentHTML('afterbegin', '<span class="spinner-border spinner-border-sm" aria-hidden="true"></span>');
            });
            // Kembali lewat tombol Back (halaman dari cache): lepas kunci tombol.
            window.addEventListener('pageshow', function (e) {
                if (!e.persisted) return;
                kirim.disabled = false;
                kirim.removeAttribute('aria-busy');
                kirim.querySelector('.spinner-border')?.remove();
            });
        });
    </script>
@endif
@endsection
