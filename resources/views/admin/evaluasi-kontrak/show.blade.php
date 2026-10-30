@extends('layouts.admin.app')

@php
    use App\Models\EvaluasiKontrak as EK;
    $p = $e->dataPegawai;
    $k = $p?->kepegawaian;
    $sisa = $e->sisaHari();
    $masa = $k?->tanggal_masuk?->diff(now());
    $skala = config('penilaian_kontrak.skala');
    $bagian = config('penilaian_kontrak.bagian');
    $jumlahButir = count(EK::butir());
    $nilai = old('nilai', $e->nilai ?? []);
    [$periodeMulai, $periodeSelesai] = $periode;
    $labelPemutus = $pemutus->isNotEmpty() && $pemutus->every->isGm() ? 'GM' : 'Kepala SDM';
@endphp

@section('content')
<main class="dashboard-content">
    <div class="container-fluid px-3 px-lg-4 py-4">
        @include('partials.kembali', ['ke' => route('admin.evaluasi-kontrak.index'), 'label' => 'Evaluasi Kontrak'])
        <div class="page-heading">
            <div class="page-heading-copy">
                <span class="page-icon"><i class="bi bi-clipboard-check"></i></span>
                <div>
                    <p class="eyebrow mb-1">Evaluasi Kontrak</p>
                    <h1 class="h3 mb-1">{{ $p?->nama_lengkap ?? '-' }}</h1>
                    <p class="text-muted mb-0">{{ $k?->jabatan }} · {{ $e->unit_kerja }}</p>
                </div>
            </div>
            <span class="badge fs-6 {{ $e->badgeClass() }}">{{ $e->statusLabel() }}</span>
        </div>

        {{-- Form keputusan & jadwal TTD berdiri sendiri; inputnya di panel kanan memakai atribut form="…". --}}
        @if ($bisaPutuskan && ! $satuLangkah)
            <form action="{{ route('admin.evaluasi-kontrak.putuskan', $e) }}" method="POST" id="formPutuskan" data-confirm="Simpan keputusan? Data kontrak pegawai akan ikut diperbarui.">@csrf</form>
        @endif
        @if ($bisaAturTtd)
            <form action="{{ route('admin.evaluasi-kontrak.jadwal-ttd', $e) }}" method="POST" id="formTtd" data-confirm="Simpan jadwal TTD? Pegawai akan menerima notifikasi.">@csrf</form>
        @endif

        @if ($bisaIsi)
            <form action="{{ route('admin.evaluasi-kontrak.isi', $e) }}" method="POST" id="formIsi"
                data-confirm="{{ $satuLangkah ? 'Simpan penilaian & keputusan? Data kontrak pegawai diperbarui dan pegawai menerima jadwal TTD.' : 'Kirim penilaian ini ke ' . $labelPemutus . '?' }}">
                @csrf
        @endif

        <div class="row g-3 align-items-start">
            <div class="col-xl-8">
                <section class="panel mb-3">
                    <h2 class="h6 fw-bold mb-3"><i class="bi bi-person-vcard"></i>Identitas pegawai</h2>
                    <dl class="pk-identitas">
                        <dt>Nama</dt><dd>{{ $p?->nama_lengkap ?? '-' }}</dd>
                        <dt>Tempat / tanggal lahir</dt><dd>{{ collect([$p?->tempat_lahir, $p?->tanggal_lahir?->translatedFormat('d F Y')])->filter()->implode(', ') ?: '-' }}</dd>
                        <dt>TMT sebagai pegawai</dt><dd>{{ $k?->tanggal_masuk?->translatedFormat('d F Y') ?? '-' }}</dd>
                        <dt>Masa kerja</dt><dd>{{ $masa ? ($masa->y ? $masa->y . ' tahun ' : '') . $masa->m . ' bulan' : '-' }}</dd>
                        <dt>Jenis kelamin</dt><dd>{{ $p?->jenis_kelamin ?? '-' }}</dd>
                        <dt>Divisi / bagian</dt><dd>{{ collect([$e->unit_kerja, $k?->jabatan])->filter()->implode(' · ') }}</dd>
                        <dt>Status kerja</dt><dd>{{ $e->status_kerja ?? '-' }} · habis kontrak {{ $e->tanggal_habis_kontrak->translatedFormat('d F Y') }}
                            @if ($e->status !== EK::STATUS_SELESAI)<span class="{{ $sisa <= 7 ? 'text-danger fw-semibold' : 'text-muted' }}">({{ $sisa < 0 ? 'lewat ' . abs($sisa) . ' hari' : ($sisa === 0 ? 'hari ini' : $sisa . ' hari lagi') }})</span>@endif
                        </dd>
                        <dt>Periode penilaian</dt>
                        <dd>
                            @if ($bisaIsi)
                                <div class="d-flex flex-wrap align-items-center gap-2">
                                    <input type="date" name="periode_mulai" class="form-control form-control-sm w-auto" value="{{ old('periode_mulai', $periodeMulai?->toDateString()) }}" aria-label="Awal periode">
                                    <span>s/d</span>
                                    <input type="date" name="periode_selesai" class="form-control form-control-sm w-auto" value="{{ old('periode_selesai', $periodeSelesai?->toDateString()) }}" aria-label="Akhir periode">
                                </div>
                            @else
                                {{ $periodeMulai?->translatedFormat('d M Y') ?? '-' }} – {{ $periodeSelesai?->translatedFormat('d M Y') }}
                            @endif
                        </dd>
                        <dt>Penilai</dt><dd>{{ $penilai->pluck('name')->implode(', ') ?: 'belum ada penilai tercatat' }}</dd>
                        <dt>Pemutus</dt><dd>{{ $pemutus->pluck('name')->implode(', ') ?: '-' }} <span class="text-muted">({{ $labelPemutus }})</span></dd>
                    </dl>
                </section>

                <section class="panel mb-3">
                    <h2 class="h6 fw-bold mb-1"><i class="bi bi-bar-chart"></i>Data pendukung</h2>
                    <p class="small text-muted mb-3">Absensi {{ $rentangAbsensi[0]->translatedFormat('d M Y') }} – {{ $rentangAbsensi[1]->translatedFormat('d M Y') }}, cuti tahun {{ now()->year }}.</p>
                    <div class="ek-angka">
                        @if ($absensi && ($absensi['hari_kerja'] ?? 0) > 0)
                            <div class="ek-kartu"><span>Hadir</span><strong>{{ $absensi['hadir_total'] ?? 0 }}/{{ $absensi['hari_kerja'] }} hari</strong></div>
                            <div class="ek-kartu {{ ($absensi['telat'] ?? 0) > 5 ? 'waspada' : '' }}"><span>Telat</span><strong>{{ $absensi['telat'] ?? 0 }} kali</strong><small>{{ $absensi['telat_menit'] ?? 0 }} menit</small></div>
                            <div class="ek-kartu {{ ($absensi['alpa'] ?? 0) > 0 ? 'bahaya' : '' }}"><span>Alpa</span><strong>{{ $absensi['alpa'] ?? 0 }} hari</strong></div>
                            <div class="ek-kartu"><span>Izin / sakit</span><strong>{{ ($absensi['izin'] ?? 0) + ($absensi['sakit'] ?? 0) }} hari</strong></div>
                        @endif
                        <div class="ek-kartu"><span>Cuti {{ now()->year }}</span><strong>{{ $cutiTahunIni }} hari</strong></div>
                        <div class="ek-kartu {{ $spKontrak->isNotEmpty() ? ($spKontrak->max('tingkat') >= 3 ? 'bahaya' : 'waspada') : '' }}"><span>SP selama kontrak</span><strong>{{ $spKontrak->count() }} kali</strong>
                            <small>{{ $spKontrak->isEmpty() ? 'tidak ada' : collect([1, 2, 3])->map(fn ($t) => 'SP' . $t . ': ' . $spKontrak->where('tingkat', $t)->count())->implode(' · ') }}</small></div>
                        <div class="ek-kartu"><span>Evaluasi sebelumnya</span><strong>{{ $riwayat->count() }} kali</strong>
                            @if ($r = $riwayat->first())<small>{{ $r->keputusan ? $r->labelKeputusan() : $r->statusLabel() }}{{ $r->rata_nilai ? ' · ' . $r->rata_nilai . '/4' : '' }}</small>@endif
                        </div>
                    </div>
                    @unless ($absensi && ($absensi['hari_kerja'] ?? 0) > 0)
                        <p class="small text-muted mb-0 mt-2">Belum ada data absensi pada rentang ini (pegawai mungkin tidak ikut absensi mesin).</p>
                    @endunless
                </section>

                <section class="panel mb-3">
                    <h2 class="h6 fw-bold mb-1"><i class="bi bi-exclamation-octagon"></i>Surat peringatan selama kontrak</h2>
                    <p class="small text-muted mb-2">Periode {{ $periodeMulai?->translatedFormat('d M Y') ?? 'awal' }} – {{ $periodeSelesai?->translatedFormat('d M Y') }}.</p>
                    @forelse ($spKontrak as $sp)
                        <div class="d-flex justify-content-between align-items-start gap-2 py-2 border-top small">
                            <div><span class="badge {{ $sp->badgeClass() }} me-1">{{ $sp->label() }}</span>{{ $sp->tanggal->translatedFormat('d M Y') }} · {{ $sp->alasan }}
                                @if ($sp->keterangan)<span class="d-block text-muted">{{ $sp->keterangan }}</span>@endif
                            </div>
                            <span class="text-muted text-nowrap">{{ $sp->pemberi?->name }}</span>
                        </div>
                    @empty
                        <p class="small text-muted mb-0"><i class="bi bi-check-circle text-success me-1"></i>Tidak ada surat peringatan selama periode ini.</p>
                    @endforelse
                </section>

                <section class="panel" id="penilaian">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                        <h2 class="h6 fw-bold mb-0"><i class="bi bi-list-check"></i>Penilaian</h2>
                        <div class="pk-legenda">
                            @foreach ($skala as $n => $ket)<span><b>{{ $n }}</b> {{ $ket }}</span>@endforeach
                        </div>
                    </div>
                    @error('nilai')<div class="alert alert-danger small py-2">{{ $message }}</div>@enderror

                    @foreach ($bagian as $b)
                        <div class="pk-bagian" data-bagian="{{ $b['kode'] }}">
                            <div class="pk-bagian-kepala">
                                <h3>{{ $b['kode'] }}. {{ $b['judul'] }}</h3>
                                @if ($bisaIsi)
                                    <div class="pk-isi-semua" aria-label="Isi semua butir {{ $b['judul'] }}">
                                        <span>Isi semua:</span>
                                        @foreach (array_keys($skala) as $n)
                                            <button type="button" class="btn btn-link btn-sm p-0" data-isi-semua="{{ $b['kode'] }}" data-nilai="{{ $n }}">{{ $n }}</button>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="pk-rata-bagian">rata-rata {{ $e->rataBagian()[$b['kode']] ?? '-' }}</span>
                                @endif
                            </div>
                            @foreach ($b['kelompok'] as $kel)
                                @if ($kel['judul'])<div class="pk-kelompok">{{ $kel['judul'] }}</div>@endif
                                @foreach ($kel['butir'] as $no => $teks)
                                    <div class="pk-butir">
                                        <span class="pk-no">{{ $no }}</span>
                                        <span class="pk-teks">{{ $teks }}</span>
                                        @if ($bisaIsi)
                                            <div class="btn-group btn-group-sm pk-skala" role="group" aria-label="Nilai butir {{ $no }}">
                                                @foreach (array_reverse(array_keys($skala)) as $n)
                                                    <input type="radio" class="btn-check" name="nilai[{{ $no }}]" id="n{{ $no }}-{{ $n }}" value="{{ $n }}" @checked((int) ($nilai[$no] ?? 0) === $n)>{{-- wajib dicek JS (tandai kuning) & server --}}
                                                    <label class="btn btn-outline-secondary" for="n{{ $no }}-{{ $n }}" title="{{ $skala[$n] }}">{{ $n }}</label>
                                                @endforeach
                                            </div>
                                        @else
                                            @php $v = $nilai[$no] ?? null; @endphp
                                            <span class="pk-hasil n{{ $v }}" title="{{ $v ? $skala[$v] : 'Belum dinilai' }}">{{ $v ?? '–' }}</span>
                                        @endif
                                    </div>
                                @endforeach
                            @endforeach
                        </div>
                    @endforeach
                </section>
            </div>

            {{-- ===== Kolom kanan (lengket): hasil nilai, penilaian, keputusan & jadwal TTD ===== --}}
            <div class="col-xl-4">
                <div class="pk-lengket">
                    <section class="panel mb-3">
                        <h2 class="h6 fw-bold mb-3"><i class="bi bi-calculator"></i>Hasil penilaian</h2>
                        <div class="ek-angka">
                            <div class="ek-kartu"><span>Terisi</span><strong><span data-terisi>{{ count(array_filter($nilai)) }}</span>/{{ $jumlahButir }}</strong></div>
                            <div class="ek-kartu"><span>Total nilai</span><strong data-total>{{ $e->total_nilai ?? array_sum($nilai) }}</strong><small data-predikat>{{ $e->total_nilai !== null ? EK::predikat($e->total_nilai) : 'maks. ' . $jumlahButir * 4 }}</small></div>
                            <div class="ek-kartu"><span>Rata-rata</span><strong data-rata>{{ $e->rata_nilai ?? '-' }}</strong><small>skala 1–4</small></div>
                        </div>
                        <div class="pk-skor mt-3">
                            <div class="small fw-semibold mb-1">Keterangan skor <span class="fw-normal text-muted">(total nilai = jumlah seluruh butir)</span></div>
                            @foreach (EK::rentangPredikat() as [$min, $maks, $label])
                                <div class="pk-skor-baris {{ $e->total_nilai !== null && $e->total_nilai >= $min && $e->total_nilai <= $maks ? 'aktif' : '' }}" data-min="{{ $min }}" data-maks="{{ $maks }}">
                                    <span>{{ $min }} – {{ $maks }}</span><b>{{ $label }}</b>
                                </div>
                            @endforeach
                        </div>
                    </section>

                    <section class="panel mb-3">
                        <h2 class="h6 fw-bold mb-3"><i class="bi bi-person-check"></i>Penilaian</h2>
                        @if ($bisaIsi)
                            <label class="form-label fw-semibold small" for="catatan">Catatan penilai <span class="fw-normal text-muted">(opsional)</span></label>
                            <textarea name="catatan" id="catatan" rows="3" class="form-control mb-3" maxlength="1000" placeholder="Contoh: target tercapai; perlu meningkatkan ketepatan waktu">{{ old('catatan', $e->catatan) }}</textarea>
                            @unless ($satuLangkah)
                                <p class="small text-muted"><i class="bi bi-info-circle me-1"></i>Penilai hanya memberi nilai; keputusan kontrak diambil {{ $labelPemutus }}.</p>
                                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-send me-1"></i>{{ $e->diisi_pada ? 'Simpan perubahan nilai' : 'Kirim nilai ke ' . $labelPemutus }}</button>
                            @endunless
                            @if ($e->diisi_pada)
                                <p class="small text-muted mt-2 mb-0">Terakhir diisi {{ $e->pengisi?->name }} · {{ $e->diisi_pada->translatedFormat('d M Y H:i') }}</p>
                            @endif
                        @elseif ($e->diisi_pada)
                            @if ($e->catatan)<p class="small mb-1" style="white-space: pre-line;">{{ $e->catatan }}</p>@endif
                            <p class="small text-muted mb-0">Dinilai {{ $e->pengisi?->name }} · {{ $e->diisi_pada->translatedFormat('d M Y H:i') }}</p>
                        @else
                            <p class="small text-muted mb-0"><i class="bi bi-hourglass-split me-1"></i>Menunggu penilaian oleh {{ $penilai->pluck('name')->implode(', ') ?: 'penilai' }}.</p>
                        @endif
                    </section>

                    {{-- ===== Keputusan (Kepala SDM; GM untuk Kepala SDM) ===== --}}
                    <section class="panel">
                        <h2 class="h6 fw-bold mb-3"><i class="bi bi-patch-check"></i>Keputusan {{ $labelPemutus }}</h2>
                        @if ($e->keputusan)
                            <p class="mb-1 fw-semibold">{{ $e->labelKeputusan() }}</p>
                            @if ($e->catatan_sdm)<p class="small mb-1">{{ $e->catatan_sdm }}</p>@endif
                            <p class="small text-muted mb-2">{{ $e->pemutus?->name }} · {{ $e->diputuskan_pada?->translatedFormat('d M Y H:i') }}</p>
                            @if ($e->keputusan !== EK::TIDAK)
                                <div class="ek-ttd-info mb-2"><i class="bi bi-pen me-1"></i><b>TTD kontrak:</b> {{ $e->labelTtd() ?? 'belum dijadwalkan' }}</div>
                            @endif
                            @if ($bisaAturTtd)
                                <details class="small mb-2" @if (! $e->ttd_jadwal) open @endif>
                                    <summary class="fw-semibold">{{ $e->ttd_jadwal ? 'Ubah jadwal TTD' : 'Atur jadwal TTD' }}</summary>
                                    <input type="datetime-local" name="ttd_jadwal" form="formTtd" class="form-control form-control-sm my-2" required aria-label="Tanggal & jam TTD"
                                        value="{{ old('ttd_jadwal', $e->ttd_jadwal?->format('Y-m-d\TH:i')) }}">
                                    <input type="text" name="ttd_lokasi" form="formTtd" class="form-control form-control-sm mb-2" maxlength="150" required aria-label="Lokasi TTD"
                                        value="{{ old('ttd_lokasi', $e->ttd_lokasi ?? EK::TTD_LOKASI_BAWAAN) }}">
                                    <button type="submit" form="formTtd" class="btn btn-outline-primary btn-sm w-100"><i class="bi bi-calendar-check me-1"></i>Simpan jadwal TTD</button>
                                </details>
                            @endif
                        @endif
                        @if ($satuLangkah)
                            {{-- Penilai sekaligus pemutus: keputusan diisi bersama nilai (di dalam formIsi). --}}
                            <p class="small text-muted"><i class="bi bi-info-circle me-1"></i>Anda penilai sekaligus pemutus: nilai & keputusan disimpan sekaligus.</p>
                            @include('admin.evaluasi-kontrak._isian-keputusan', ['form' => null])
                            <button type="submit" class="btn btn-success w-100"><i class="bi bi-check2-circle me-1"></i>Simpan nilai & tetapkan kontrak</button>
                        @elseif ($bisaPutuskan)
                            @if ($e->status === EK::STATUS_MENUNGGU_ATASAN)<p class="small text-warning-emphasis"><i class="bi bi-exclamation-triangle me-1"></i>Penilaian belum masuk (Admin tetap bisa memutuskan).</p>@endif
                            @include('admin.evaluasi-kontrak._isian-keputusan', ['form' => 'formPutuskan'])
                            <button type="submit" form="formPutuskan" class="btn btn-success w-100"><i class="bi bi-check2-circle me-1"></i>Simpan keputusan</button>
                        @elseif (! $e->keputusan)
                            <p class="small text-muted mb-0"><i class="bi bi-hourglass-split me-1"></i>{{ $e->status === EK::STATUS_MENUNGGU_ATASAN ? 'Diputuskan setelah penilaian masuk.' : 'Menunggu keputusan ' . $labelPemutus . '.' }}</p>
                        @endif
                    </section>                </div>
            </div>
        </div>

        @if ($bisaIsi)
            </form>
        @endif
    </div>
</main>

<style>
    .pk-identitas { display: grid; grid-template-columns: minmax(150px, max-content) 1fr; gap: .4rem 1rem; margin: 0; font-size: .875rem; }
    .pk-identitas dt { color: var(--admin-muted); font-weight: 600; }
    .pk-identitas dd { margin: 0; }
    .ek-angka { display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: .5rem; }
    .ek-kartu { display: flex; flex-direction: column; padding: .55rem .7rem; border: 1px solid var(--admin-border); border-radius: 9px; background: var(--admin-surface); }
    .ek-kartu > span { font-size: .72rem; font-weight: 600; color: var(--admin-muted); }
    .ek-kartu strong { font-size: .95rem; }
    .ek-kartu small { font-size: .72rem; color: var(--admin-muted); }
    .ek-kartu.waspada { border-color: #f5d48a; background: #fffbeb; }
    .ek-kartu.bahaya { border-color: #f3b4b4; background: #fef2f2; }
    .ek-ttd, .ek-ttd-info { padding: .5rem .6rem; border: 1px dashed var(--admin-border); border-radius: 8px; background: var(--admin-surface-soft); }
    .ek-ttd-info { font-size: .85rem; border-style: solid; border-color: #bfdbfe; background: #eff6ff; }
    html[data-theme="dark"] .ek-ttd-info { background: rgba(59, 130, 246, .12); border-color: rgba(59, 130, 246, .35); }
    .pk-legenda { display: flex; flex-wrap: wrap; gap: .25rem .75rem; font-size: .75rem; color: var(--admin-muted); }
    .pk-bagian { border: 1px solid var(--admin-border); border-radius: 10px; margin-bottom: .75rem; overflow: hidden; }
    .pk-bagian-kepala { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: .5rem; padding: .5rem .75rem; background: var(--admin-surface-soft); border-bottom: 1px solid var(--admin-border); }
    .pk-bagian-kepala h3 { font-size: .85rem; font-weight: 700; margin: 0; }
    .pk-isi-semua, .pk-rata-bagian { font-size: .75rem; color: var(--admin-muted); display: flex; gap: .4rem; align-items: center; }
    .pk-kelompok { padding: .35rem .75rem; font-size: .75rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; color: var(--hr-primary); background: var(--hr-primary-soft); }
    .pk-butir { display: grid; grid-template-columns: 2rem 1fr auto; align-items: center; gap: .5rem; padding: .45rem .75rem; border-top: 1px dashed var(--admin-border); font-size: .85rem; }
    .pk-kelompok + .pk-butir, .pk-bagian-kepala + .pk-butir { border-top: 0; }
    .pk-no { color: var(--admin-muted); font-weight: 600; font-variant-numeric: tabular-nums; }
    .pk-skala .btn { min-width: 2.25rem; font-weight: 700; }
    .pk-butir.belum { background: #fff7e6; }
    .pk-skor-baris { display: flex; justify-content: space-between; gap: .5rem; padding: .25rem .55rem; border-radius: 6px; font-size: .8rem; color: var(--admin-muted); }
    .pk-skor-baris b { font-weight: 600; }
    .pk-skor-baris.aktif { background: var(--hr-primary-soft); color: var(--hr-primary); }
    .pk-skor-baris.aktif b { font-weight: 800; }
    .pk-hasil { min-width: 2rem; text-align: center; font-weight: 700; border-radius: 6px; padding: .1rem .4rem; background: var(--admin-surface-soft); }
    .pk-hasil.n4 { background: #dcfce7; color: #166534; } .pk-hasil.n3 { background: #e0f2fe; color: #075985; }
    .pk-hasil.n2 { background: #fef3c7; color: #92400e; } .pk-hasil.n1 { background: #fee2e2; color: #991b1b; }
    /* Kolom kanan lengket & dibatasi setinggi layar (gulir sendiri) agar tombol simpan selalu terjangkau
       walau penilaian di kiri sangat panjang. */
    @media (min-width: 1200px) {
        .pk-lengket { position: sticky; top: 80px; max-height: calc(100vh - 96px); overflow-y: auto; scrollbar-width: thin; padding-right: 2px; }
    }
    @media (max-width: 575.98px) {
        .pk-identitas { grid-template-columns: 1fr; gap: 0; }
        .pk-identitas dd { margin-bottom: .45rem; }
        /* Nilai baca-saja tetap sebaris di kanan; tombol 1–4 saat mengisi turun ke baris bawah. */
        .pk-butir { grid-template-columns: 1.6rem 1fr auto; }
        .pk-skala { grid-column: 2 / -1; justify-self: start; }
    }
    html[data-theme="dark"] .ek-kartu.waspada, html[data-theme="dark"] .pk-butir.belum { background: rgba(245, 158, 11, .1); }
    html[data-theme="dark"] .ek-kartu.bahaya { background: rgba(239, 68, 68, .1); }
</style>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const isi = document.getElementById('formIsi');
        if (isi) {
            // Predikat dari total nilai (rentang dari config); baru berlaku setelah semua butir dinilai.
            const RENTANG = @json(EK::rentangPredikat());
            const PREDIKAT = (total) => (RENTANG.find(([min]) => total >= min) || [])[2] || '-';
            const butir = [...isi.querySelectorAll('.pk-butir')];
            // Hasil langsung: terisi, total, rata-rata, predikat & baris keterangan skor yang berlaku.
            const hitung = () => {
                const nilai = butir.map((b) => +(b.querySelector('input:checked') || {}).value || 0).filter(Boolean);
                const rata = nilai.length ? Math.round((nilai.reduce((a, n) => a + n, 0) / nilai.length) * 100) / 100 : null;
                document.querySelector('[data-terisi]').textContent = nilai.length;
                document.querySelector('[data-total]').textContent = nilai.reduce((a, n) => a + n, 0);
                document.querySelector('[data-rata]').textContent = rata ?? '-';
                const total = nilai.reduce((a, n) => a + n, 0);
                const lengkap = nilai.length === butir.length;
                document.querySelector('[data-predikat]').textContent = lengkap ? PREDIKAT(total) : 'lengkapi semua butir';
                document.querySelectorAll('.pk-skor-baris').forEach((b) => b.classList.toggle('aktif', lengkap && total >= +b.dataset.min && total <= +b.dataset.maks));
            };
            isi.addEventListener('change', (ev) => { if (ev.target.name?.startsWith('nilai[')) { ev.target.closest('.pk-butir').classList.remove('belum'); hitung(); } });
            isi.querySelectorAll('[data-isi-semua]').forEach((btn) => btn.addEventListener('click', () => {
                isi.querySelectorAll(`.pk-bagian[data-bagian="${btn.dataset.isiSemua}"] input[value="${btn.dataset.nilai}"]`).forEach((r) => { r.checked = true; r.closest('.pk-butir').classList.remove('belum'); });
                hitung();
            }));
            // Kirim: tandai butir yang belum dinilai dan arahkan ke yang pertama.
            isi.addEventListener('submit', (ev) => {
                const belum = butir.filter((b) => !b.querySelector('input:checked'));
                if (belum.length) {
                    ev.preventDefault(); ev.stopImmediatePropagation();
                    belum.forEach((b) => b.classList.add('belum'));
                    belum[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
                    Swal.fire({ icon: 'info', title: 'Penilaian belum lengkap', text: belum.length + ' butir belum dinilai (ditandai kuning).', confirmButtonColor: '#8b1215' });
                }
            }, true);
            hitung();
        }
        // Isian keputusan: lama hanya untuk "Perpanjang"; naik gaji & jadwal TTD tidak untuk "Tidak diperpanjang".
        document.querySelectorAll('[data-isian-keputusan]').forEach((kotak) => {
            const pilih = kotak.querySelector('[data-keputusan]');
            const naik = kotak.querySelector('[data-naik]');
            const atur = () => {
                const v = pilih.value;
                kotak.querySelectorAll('[data-jika]').forEach((el) => { el.hidden = el.dataset.jika !== v; });
                kotak.querySelectorAll('[data-jika-bukan]').forEach((el) => { el.hidden = !v || el.dataset.jikaBukan === v; });
                kotak.querySelectorAll('[data-wajib-bukan-tidak]').forEach((el) => { el.required = !!v && v !== 'tidak'; });
                kotak.querySelector('[data-naik-isi]').hidden = !naik.checked;
                kotak.querySelector('[name=keputusan_naik_gaji_nominal]').required = naik.checked && v !== 'tidak';
                kotak.querySelector('[data-catatan-keputusan]').required = v === 'tidak';
            };
            pilih.addEventListener('change', atur);
            naik.addEventListener('change', atur);
            atur();
        });
        // Nominal rupiah: tampil dengan titik ribuan saat diketik (server menyimpan angkanya saja).
        document.querySelectorAll('[data-rupiah]').forEach((el) => el.addEventListener('input', () => {
            const angka = el.value.replace(/\D/g, '');
            el.value = angka ? Number(angka).toLocaleString('id-ID') : '';
        }));
    });
</script>
@endsection
