@extends($layout)

@section('content')
@php
    $fase = $pengumuman->absenFase();
    [$faseLabel, $faseTone] = [
        'belum_buka' => ['Belum dibuka', 'neutral'],
        'buka' => ['Sedang dibuka', 'success'],
        'tutup' => ['Sudah ditutup', 'neutral'],
    ][$fase] ?? ['-', 'neutral'];
    $persen = $ringkas['total'] ? round($ringkas['sudah'] / $ringkas['total'] * 100) : 0;
    $rincianBerhalangan = collect(['izin' => 'izin', 'sakit' => 'sakit', 'dinas' => 'dinas', 'cuti' => 'cuti'])
        ->filter(fn ($l, $k) => $ringkas[$k])->map(fn ($l, $k) => $ringkas[$k] . ' ' . $l)->implode(' · ');
@endphp
<main class="dashboard-content">
    <div class="container-fluid">
        <x-page-header :title="$pengumuman->judul" eyebrow="Absen Acara" :back="route('pengumuman.index') . '#pengumuman-' . $pengumuman->id" back-label="Pengumuman"
            :subtitle="$pengumuman->absenJendelaLabel() . ($pengumuman->lokasi ? ' · ' . $pengumuman->lokasi : '')">
            <x-badge :tone="$faseTone" size="lg" :dot="$fase === 'buka'">{{ $faseLabel }}</x-badge>
            <x-slot:actions>
                @include('partials.tombol-ekspor', ['eksporRoute' => 'pengumuman.absen.export', 'eksporParam' => ['pengumuman' => $pengumuman]])
                <x-button :href="route('pengumuman.edit', $pengumuman)" variant="secondary" icon="bi-pencil" title="Ubah waktu atau peserta">Ubah</x-button>
                @if ($fase !== 'tutup')
                    <form action="{{ route('pengumuman.absen.ingatkan', $pengumuman) }}" method="POST" class="m-0"
                        data-confirm="Kirim notifikasi pengingat ke {{ $ringkas['belum'] }} pegawai yang belum absen?">
                        @csrf
                        <x-button type="submit" variant="secondary" icon="bi-bell" :disabled="$ringkas['belum'] === 0">Ingatkan yang Belum</x-button>
                    </form>
                    <x-button :href="route('pengumuman.absen.qr', $pengumuman)" target="_blank" icon="bi-qr-code">Tampilkan QR</x-button>
                @endif
            </x-slot:actions>
        </x-page-header>

        {{-- ============ Ringkasan ============ --}}
        <section class="row g-3 mb-3" aria-label="Ringkasan kehadiran acara">
            <div class="col-6 col-xl-3"><x-stat-card label="Diundang" :value="$ringkas['total']" icon="bi-people" tone="primary" meta="Peserta wajib absen" /></div>
            <div class="col-6 col-xl-3"><x-stat-card label="Sudah Absen" :value="$ringkas['sudah']" icon="bi-check-circle" tone="success" :meta="$persen . '% dari peserta'" /></div>
            <div class="col-6 col-xl-3"><x-stat-card label="Berhalangan" :value="$ringkas['berhalangan']" icon="bi-envelope-paper" tone="info" :meta="$rincianBerhalangan ?: 'Izin / sakit / dinas / cuti'" /></div>
            <div class="col-6 col-xl-3"><x-stat-card label="Belum Absen" :value="$ringkas['belum']" icon="bi-hourglass-split" :tone="$ringkas['belum'] ? 'warning' : 'neutral'" :meta="$fase === 'tutup' ? 'Kedaluwarsa' : 'Masih bisa absen'" /></div>
        </section>
        @if ($ringkas['total'])
            <div class="absen-progres mb-4">
                <div class="progress" style="height: 8px;" role="progressbar" aria-label="Persentase hadir" aria-valuenow="{{ $persen }}" aria-valuemin="0" aria-valuemax="100">
                    <div class="progress-bar bg-success" style="width: {{ $persen }}%"></div>
                </div>
                <span class="small text-muted">{{ $persen }}% hadir</span>
            </div>
        @endif

        {{-- ============ Tabel peserta ============ --}}
        <x-card :padding="false">
            <form method="GET" class="absen-filter" id="formSaringAbsen">
                <div class="pub-chips mb-0" role="group" aria-label="Saring menurut status">
                    @foreach ($filterLabel as $kunci => $label)
                        <x-chip :href="request()->fullUrlWithQuery(['status' => $kunci === 'semua' ? null : $kunci])" :active="$filter === $kunci" :count="$hitungFilter[$kunci]">{{ $label }}</x-chip>
                    @endforeach
                </div>
                <input type="hidden" name="status" value="{{ $filter === 'semua' ? '' : $filter }}">
                <div class="d-flex flex-wrap gap-2 ms-lg-auto">
                    <div class="input-group hr-input-group absen-cari">
                        <span class="input-group-text"><i class="bi bi-search" aria-hidden="true"></i></span>
                        <input type="search" name="q" class="form-control form-control-sm" value="{{ request('q') }}" placeholder="Cari peserta, contoh: Siti atau SMK" aria-label="Cari peserta menurut nama, bagian, atau NIP">
                    </div>
                    <select name="urut" class="form-select form-select-sm w-auto" onchange="this.form.submit()" aria-label="Urutkan">
                        @foreach (['status' => 'Perlu perhatian dulu', 'nama' => 'Nama A–Z', 'waktu' => 'Waktu absen'] as $k => $l)
                            <option value="{{ $k }}" @selected($urut === $k)>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
            </form>

            <x-table caption="Peserta absen acara">
                <x-slot:head>
                    <tr><th class="kolom-no">#</th><th>Pegawai</th><th>Status</th><th>Waktu</th><th class="text-end">Aksi</th></tr>
                </x-slot:head>
                @forelse ($baris as $p)
                    @php $st = $p->tampilan(); @endphp
                    <tr>
                        <td class="kolom-no text-muted small">{{ $loop->iteration }}</td>
                        <td>
                            <div class="d-flex align-items-center gap-3 table-media">
                                <x-avatar :name="$nama($p)" size="sm" />
                                <div class="min-w-0">
                                    <div class="fw-semibold">{{ $nama($p) }}</div>
                                    <div class="small text-muted">{{ $unit($p) }}@if ($p->dataPegawai?->nip) · <span class="angka-utuh">{{ $p->dataPegawai->nip }}</span>@endif</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <x-status-badge :badge="$st['kelas']" :icon="$st['ikon']">{{ $st['label'] }}</x-status-badge>
                            @if ($p->status === 'manual')
                                <div class="small text-muted mt-1">{{ $p->catatan }} — {{ $p->penanda?->name }}</div>
                            @elseif ($p->berhalangan())
                                <div class="small text-muted mt-1">{{ $p->catatan }}<span class="d-block fst-italic">{{ $p->sumberLabel() }}</span></div>
                            @endif
                        </td>
                        <td class="small text-nowrap">{{ $p->waktu_absen?->format('H:i') ?? '–' }}@if ($p->waktu_absen && ! $p->waktu_absen->isSameDay($pengumuman->absen_buka))<span class="text-muted d-block">{{ $p->waktu_absen->translatedFormat('d M') }}</span>@endif</td>
                        <td class="text-end text-nowrap">
                            @include('admin.editor-data._tombol', ['entitas' => 'peserta_acara', 'id' => $p->id, 'teks' => '', 'hapus' => false])
                            @php $cutiOtomatis = $p->status === 'cuti' && $p->otomatis(); @endphp
                            @unless ($p->sudahAbsen())
                                <x-button variant="outline" size="sm" icon="bi-person-check" data-tetapkan="hadir"
                                    :data-url="route('pengumuman.absen.tandai', [$pengumuman, $p])" :data-nama="$nama($p)">Tandai hadir</x-button>
                                <div class="dropdown d-inline-block">
                                    <button type="button" class="btn btn-ghost btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" title="Tetapkan izin / sakit / dinas">Tetapkan</button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        @foreach ($bisaDitetapkan as $kode => $label)
                                            <li><button type="button" class="dropdown-item" data-tetapkan="{{ $kode }}" data-label="{{ $label }}"
                                                data-url="{{ route('pengumuman.absen.tandai', [$pengumuman, $p]) }}" data-nama="{{ $nama($p) }}">{{ $label }}</button></li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endunless
                            @if ($p->status !== 'belum' && ! $cutiOtomatis)
                                <form action="{{ route('pengumuman.absen.tandai', [$pengumuman, $p]) }}" method="POST" class="d-inline"
                                    data-confirm="{{ $p->sudahAbsen() ? 'Batalkan kehadiran' : 'Batalkan status ' . $st['label'] }} {{ $nama($p) }}? Status kembali menjadi belum absen.">
                                    @csrf <input type="hidden" name="aksi" value="batal">
                                    <x-button type="submit" variant="ghost" size="sm" class="btn-ikon text-danger" icon="bi-x-circle" title="Batalkan {{ $st['label'] }}" aria-label="Batalkan {{ $st['label'] }} {{ $nama($p) }}" />
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5"><x-empty-state icon="bi-search" title="Tidak ada peserta yang cocok" compact>Ubah status atau kata pencarian.</x-empty-state></td></tr>
                @endforelse
            </x-table>
        </x-card>
    </div>
</main>

<form method="POST" id="formTandaiHadir" hidden>
    @csrf
    <input type="hidden" name="aksi" value="hadir">
    <input type="hidden" name="catatan">
</form>
<script>
    // Tetapkan status peserta: hadir manual, izin, sakit, atau dinas luar — selalu dengan alasan/keterangan.
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('formTandaiHadir');
        const teks = {
            hadir: ['Tandai hadir manual?', 'mis. hadir tetapi HP mati / tidak bisa scan.', 'Contoh: HP mati, terlihat hadir oleh panitia', 'Tandai hadir'],
            izin: ['Tetapkan izin?', 'Peserta tidak hadir dengan izin.', 'Contoh: izin keperluan keluarga', 'Tetapkan izin'],
            sakit: ['Tetapkan sakit?', 'Peserta tidak hadir karena sakit.', 'Contoh: sakit, surat dokter menyusul', 'Tetapkan sakit'],
            dinas: ['Tetapkan dinas luar?', 'Peserta sedang tugas di luar.', 'Contoh: rapat di dinas pendidikan', 'Tetapkan dinas luar'],
        };
        document.querySelectorAll('[data-tetapkan]').forEach((b) => b.addEventListener('click', function () {
            const [judul, ket, contoh, tombol] = teks[b.dataset.tetapkan];
            Swal.fire({
                title: judul,
                html: 'Untuk <strong></strong> — ' + ket,
                didOpen: (el) => { el.querySelector('strong').textContent = b.dataset.nama; },
                input: 'text', inputLabel: b.dataset.tetapkan === 'hadir' ? 'Alasan' : 'Keterangan', inputPlaceholder: contoh,
                inputAttributes: { maxlength: 255 },
                inputValidator: (v) => (!v || !v.trim()) ? 'Isi alasan terlebih dahulu.' : undefined,
                showCancelButton: true, confirmButtonText: tombol, cancelButtonText: 'Batal',
                confirmButtonColor: '#880f12', reverseButtons: true,
            }).then((r) => {
                if (!r.isConfirmed) return;
                form.action = b.dataset.url;
                form.aksi.value = b.dataset.tetapkan;
                form.catatan.value = r.value.trim();
                form.submit();
            });
        }));
    });
</script>
@endsection
