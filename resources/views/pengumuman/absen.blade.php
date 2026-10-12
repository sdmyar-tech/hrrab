@extends($layout)

@section('content')
@php
    $fase = $pengumuman->absenFase();
    $faseInfo = [
        'belum_buka' => ['Belum dibuka', 'text-bg-secondary'],
        'buka' => ['Sedang dibuka', 'text-bg-success'],
        'tutup' => ['Sudah ditutup', 'text-bg-dark'],
    ][$fase] ?? ['-', 'text-bg-light'];
    $persen = $ringkas['total'] ? round($ringkas['sudah'] / $ringkas['total'] * 100) : 0;
@endphp
<div class="dashboard-content">
    <div class="container-fluid px-3 px-lg-4 py-4">
        <div class="page-heading">
            <div class="page-heading-copy">
                <span class="page-icon"><i class="bi bi-qr-code-scan"></i></span>
                <div class="min-w-0">
                    <p class="eyebrow mb-1"><a href="{{ route('pengumuman.index') }}#pengumuman-{{ $pengumuman->id }}" class="text-reset text-decoration-none"><i class="bi bi-arrow-left me-1"></i>Pengumuman</a> · Absen Acara</p>
                    <h1 class="h3 mb-1">{{ $pengumuman->judul }}</h1>
                    <p class="text-muted mb-0">
                        <span class="badge {{ $faseInfo[1] }} me-1">{{ $faseInfo[0] }}</span>
                        {{ $pengumuman->absenJendelaLabel() }} · radius {{ $pengumuman->absen_radius }} m
                        · <a href="https://www.google.com/maps?q={{ $pengumuman->absen_lat }},{{ $pengumuman->absen_lng }}" target="_blank" rel="noopener">lihat titik di peta</a>
                    </p>
                </div>
            </div>
            <div class="heading-actions d-flex flex-wrap gap-2">
                @include('partials.tombol-ekspor', ['eksporRoute' => 'pengumuman.absen.export', 'eksporParam' => ['pengumuman' => $pengumuman], 'eksporUkuran' => ''])
                @if ($fase !== 'tutup')
                    <a href="{{ route('pengumuman.absen.qr', $pengumuman) }}" target="_blank" class="btn btn-primary"><i class="bi bi-qr-code me-1"></i>Tampilkan QR</a>
                    <form action="{{ route('pengumuman.absen.ingatkan', $pengumuman) }}" method="POST"
                        data-confirm="Kirim notifikasi pengingat ke {{ $ringkas['belum'] }} pegawai yang belum absen?">
                        @csrf
                        <button type="submit" class="btn btn-outline-primary" @disabled($ringkas['belum'] === 0)><i class="bi bi-bell me-1"></i>Ingatkan yang belum</button>
                    </form>
                @endif
                <a href="{{ route('pengumuman.edit', $pengumuman) }}" class="btn btn-light" title="Ubah waktu, lokasi, atau peserta"><i class="bi bi-pencil me-1"></i>Ubah</a>
            </div>
        </div>

        <div class="row g-3 mb-3">
            @foreach ([
                ['Diundang', $ringkas['total'], 'bi-people', 'text-primary', null],
                ['Sudah absen', $ringkas['sudah'], 'bi-check-circle', 'text-success', $persen . '%'],
                ['Belum absen', $ringkas['belum'], 'bi-hourglass-split', 'text-secondary', $fase === 'tutup' ? 'kedaluwarsa' : null],
                ['Ditolak: luar radius', $ringkas['radius'], 'bi-geo-alt', 'text-warning', 'percobaan terakhir'],
            ] as [$label, $angka, $ikon, $warna, $ket])
                <div class="col-6 col-xl-3">
                    <div class="panel p-3 h-100 d-flex align-items-center gap-3">
                        <i class="bi {{ $ikon }} fs-3 {{ $warna }}"></i>
                        <div>
                            <div class="fs-4 fw-bold lh-1">{{ $angka }}</div>
                            <div class="small text-muted">{{ $label }}@if ($ket) <span class="d-block">{{ $ket }}</span>@endif</div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        @if ($ringkas['total'])
            <div class="progress mb-3" style="height:8px" role="progressbar" aria-label="Persentase hadir" aria-valuenow="{{ $persen }}" aria-valuemin="0" aria-valuemax="100">
                <div class="progress-bar bg-success" style="width: {{ $persen }}%"></div>
            </div>
        @endif

        <section class="panel p-3 p-lg-4">
            <form method="GET" class="d-flex flex-wrap gap-2 align-items-center mb-3" id="formSaringAbsen">
                <div class="d-flex flex-wrap gap-1">
                    @foreach ($filterLabel as $kunci => $label)
                        <a href="{{ request()->fullUrlWithQuery(['status' => $kunci === 'semua' ? null : $kunci]) }}"
                            class="btn btn-sm {{ $filter === $kunci ? 'btn-primary' : 'btn-outline-secondary' }}">{{ $label }} <span class="opacity-75">{{ $hitungFilter[$kunci] }}</span></a>
                    @endforeach
                </div>
                <input type="hidden" name="status" value="{{ $filter === 'semua' ? '' : $filter }}">
                <div class="input-group input-group-sm ms-lg-auto" style="max-width:260px">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="search" name="q" class="form-control" value="{{ request('q') }}" placeholder="Cari nama, unit, NIP…">
                </div>
                <select name="urut" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()" aria-label="Urutkan">
                    @foreach (['status' => 'Perlu perhatian dulu', 'nama' => 'Nama A–Z', 'waktu' => 'Waktu absen', 'jarak' => 'Jarak terdekat'] as $k => $l)
                        <option value="{{ $k }}" @selected($urut === $k)>{{ $l }}</option>
                    @endforeach
                </select>
            </form>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr class="small text-muted">
                            <th style="width:40px">#</th>
                            <th>Pegawai</th>
                            <th>Status</th>
                            <th>Waktu</th>
                            <th>Jarak</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($baris as $p)
                            @php $st = $p->tampilan(); $gagal = $p->gagal_terakhir; @endphp
                            <tr>
                                <td class="text-muted small">{{ $loop->iteration }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $nama($p) }}</div>
                                    <div class="small text-muted">{{ $unit($p) }}@if ($p->dataPegawai?->nip) · {{ $p->dataPegawai->nip }}@endif</div>
                                </td>
                                <td>
                                    <span class="badge {{ $st['kelas'] }}"><i class="bi {{ $st['ikon'] }} me-1"></i>{{ $st['label'] }}</span>
                                    @if ($p->status === 'manual')
                                        <div class="small text-muted mt-1">{{ $p->catatan }} — {{ $p->penanda?->name }}</div>
                                    @elseif (! $p->sudahAbsen() && $gagal)
                                        <div class="small text-muted mt-1">Ditolak {{ \Carbon\Carbon::parse($gagal['waktu'])->format('H:i') }}: {{ $gagal['jarak'] }} m
                                            @if (! empty($gagal['akurasi'])) (akurasi ±{{ $gagal['akurasi'] }} m)@endif</div>
                                    @endif
                                </td>
                                <td class="small">{{ $p->waktu_absen?->format('H:i') ?? '–' }}@if ($p->waktu_absen && ! $p->waktu_absen->isSameDay($pengumuman->absen_buka))<span class="text-muted d-block">{{ $p->waktu_absen->translatedFormat('d M') }}</span>@endif</td>
                                <td class="small">
                                    @if ($p->jarak !== null)
                                        {{ $p->jarak }} m
                                        @if ($p->akurasi)<span class="text-muted d-block">±{{ $p->akurasi }} m</span>@endif
                                    @else – @endif
                                </td>
                                <td class="text-end text-nowrap">
                                    @include('admin.editor-data._tombol', ['entitas' => 'peserta_acara', 'id' => $p->id, 'teks' => '', 'hapus' => false])
                                    @if ($p->sudahAbsen())
                                        <form action="{{ route('pengumuman.absen.tandai', [$pengumuman, $p]) }}" method="POST" class="d-inline"
                                            data-confirm="Batalkan kehadiran {{ $nama($p) }}? Status kembali menjadi belum absen.">
                                            @csrf <input type="hidden" name="aksi" value="batal">
                                            <button type="submit" class="btn btn-light btn-sm text-danger" title="Batalkan kehadiran"><i class="bi bi-x-circle"></i></button>
                                        </form>
                                    @else
                                        <button type="button" class="btn btn-outline-primary btn-sm" data-tandai-hadir
                                            data-url="{{ route('pengumuman.absen.tandai', [$pengumuman, $p]) }}" data-nama="{{ $nama($p) }}">
                                            <i class="bi bi-person-check me-1"></i>Tandai hadir
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-5"><i class="bi bi-search d-block fs-3 mb-1"></i>Tidak ada peserta yang cocok.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>

<form method="POST" id="formTandaiHadir" hidden>
    @csrf
    <input type="hidden" name="aksi" value="hadir">
    <input type="hidden" name="catatan">
</form>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('formTandaiHadir');
        document.querySelectorAll('[data-tandai-hadir]').forEach((b) => b.addEventListener('click', function () {
            Swal.fire({
                title: 'Tandai hadir manual?',
                html: 'Untuk <strong></strong> — mis. hadir tetapi HP mati / GPS bermasalah.',
                didOpen: (el) => { el.querySelector('strong').textContent = b.dataset.nama; },
                input: 'text', inputLabel: 'Alasan', inputPlaceholder: 'mis. HP mati, terlihat hadir oleh panitia',
                inputAttributes: { maxlength: 255 },
                inputValidator: (v) => (!v || !v.trim()) ? 'Isi alasan terlebih dahulu.' : undefined,
                showCancelButton: true, confirmButtonText: 'Tandai hadir', cancelButtonText: 'Batal',
                confirmButtonColor: '#880f12', reverseButtons: true,
            }).then((r) => {
                if (!r.isConfirmed) return;
                form.action = b.dataset.url;
                form.catatan.value = r.value.trim();
                form.submit();
            });
        }));
    });
</script>
@endsection
