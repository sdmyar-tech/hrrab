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
    [$periodeMulai, $periodeSelesai] = $e->periode_mulai ? [$e->periode_mulai, $e->periode_selesai] : $e->periodeBawaan();
    $ksLangsung = $bisaIsi && auth()->user()->isKepalaSdm();
@endphp

@section('content')
<main class="dashboard-content">
    <div class="container-fluid px-3 px-lg-4 py-4">
        <a href="{{ route('admin.evaluasi-kontrak.index') }}" class="small text-decoration-none"><i class="bi bi-arrow-left me-1"></i>Kembali ke Evaluasi Kontrak</a>
        <div class="page-heading mt-2">
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

        {{-- Form keputusan Kepala SDM berdiri sendiri; inputnya ditaruh di panel kanan memakai atribut form="formPutuskan". --}}
        @if ($bisaPutuskan)
            <form action="{{ route('admin.evaluasi-kontrak.putuskan', $e) }}" method="POST" id="formPutuskan" data-confirm="Simpan keputusan? Data kontrak pegawai akan ikut diperbarui.">@csrf</form>
        @endif

        @if ($bisaIsi)
            <form action="{{ route('admin.evaluasi-kontrak.isi', $e) }}" method="POST" id="formIsi"
                data-confirm="{{ $ksLangsung ? 'Simpan evaluasi? Sebagai Kepala SDM, rekomendasi Anda langsung menjadi keputusan dan data kontrak diperbarui.' : 'Kirim evaluasi kontrak ini ke Kepala SDM?' }}">
                @csrf
        @endif

        <div class="row g-3 align-items-start">
            <div class="col-xl-8">
                {{-- ===== Identitas & periode ===== --}}
                <section class="panel mb-3">
                    <h2 class="h6 fw-bold mb-3"><i class="bi bi-person-vcard"></i>Identitas pegawai</h2>
                    <dl class="pk-identitas">
                        <dt>Nama</dt><dd>{{ $p?->nama_lengkap ?? '-' }}</dd>
                        <dt>Tempat / tanggal lahir</dt><dd>{{ collect([$p?->tempat_lahir, $p?->tanggal_lahir?->translatedFormat('d F Y')])->filter()->implode(', ') ?: '-' }}</dd>
                        <dt>TMT sebagai pegawai</dt><dd>{{ $k?->tanggal_masuk?->translatedFormat('d F Y') ?? '-' }}</dd>
                        <dt>Masa kerja</dt><dd>{{ $masa ? ($masa->y ? $masa->y . ' tahun ' : '') . $masa->m . ' bulan' : '-' }}</dd>
                        <dt>Jenis kelamin</dt><dd>{{ $p?->jenis_kelamin ?? '-' }}</dd>
                        <dt>Divisi / unit / bagian</dt><dd>{{ collect([$e->unit_kerja, $k?->jabatan])->filter()->implode(' · ') }}</dd>
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
                        <dt>Atasan penilai</dt><dd>{{ $atasan->pluck('name')->implode(', ') ?: 'belum ada atasan tercatat' }}</dd>
                    </dl>
                </section>

                {{-- ===== Data pendukung ===== --}}
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
                        <div class="ek-kartu"><span>Evaluasi sebelumnya</span><strong>{{ $riwayat->count() }} kali</strong>
                            @if ($r = $riwayat->first())<small>{{ $r->keputusan ? $r->labelKeputusan() : $r->labelRekomendasi() }}{{ $r->rata_nilai ? ' · ' . $r->rata_nilai . '/4' : '' }}</small>@endif
                        </div>
                    </div>
                    @unless ($absensi && ($absensi['hari_kerja'] ?? 0) > 0)
                        <p class="small text-muted mb-0 mt-2">Belum ada data absensi pada rentang ini (pegawai mungkin tidak ikut absensi mesin).</p>
                    @endunless
                </section>

                {{-- ===== Penilaian 50 butir ===== --}}
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

            {{-- ===== Kolom kanan (lengket): ringkasan nilai, rekomendasi, keputusan ===== --}}
            <div class="col-xl-4">
                <div class="pk-lengket">
                    <section class="panel mb-3">
                        <h2 class="h6 fw-bold mb-3"><i class="bi bi-calculator"></i>Hasil penilaian</h2>
                        <div class="ek-angka">
                            <div class="ek-kartu"><span>Terisi</span><strong><span data-terisi>{{ count(array_filter($nilai)) }}</span>/{{ $jumlahButir }}</strong></div>
                            <div class="ek-kartu"><span>Total nilai</span><strong data-total>{{ $e->total_nilai ?? array_sum($nilai) }}</strong><small>maks. {{ $jumlahButir * 4 }}</small></div>
                            <div class="ek-kartu"><span>Rata-rata</span><strong data-rata>{{ $e->rata_nilai ?? '-' }}</strong><small data-predikat>{{ EK::predikat($e->rata_nilai) }}</small></div>
                        </div>
                    </section>

                    <section class="panel mb-3">
                        <h2 class="h6 fw-bold mb-3"><i class="bi bi-person-check"></i>Rekomendasi atasan</h2>
                        @if ($bisaIsi)
                            <div class="d-grid gap-2 mb-3">
                                @foreach (EK::PILIHAN as $kode => $label)
                                    <input type="radio" class="btn-check" name="rekomendasi" id="rek-{{ $kode }}" value="{{ $kode }}" @checked(old('rekomendasi', $e->rekomendasi) === $kode) required>
                                    <label class="btn btn-outline-secondary text-start ek-pilih" for="rek-{{ $kode }}">
                                        <i class="bi {{ ['perpanjang' => 'bi-arrow-repeat', 'tetap' => 'bi-award', 'tidak' => 'bi-x-circle'][$kode] }}"></i>{{ $label }}
                                    </label>
                                @endforeach
                            </div>
                            <div class="mb-3" data-tampil-jika>
                                <label class="form-label fw-semibold small" for="lama">Lama perpanjangan</label>
                                <select name="lama_bulan" id="lama" class="form-select">
                                    @foreach (EK::LAMA_BULAN as $bln)
                                        <option value="{{ $bln }}" @selected((int) old('lama_bulan', $e->lama_bulan ?? 12) === $bln)>{{ $bln }} bulan</option>
                                    @endforeach
                                </select>
                            </div>
                            {{-- Naik gaji: hanya catatan nominal + keterangan, tidak mengubah data gaji. --}}
                            <div class="mb-3 pk-naik" data-tampil-naik>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" name="naik_gaji" id="naik" value="1" @checked(old('naik_gaji', $e->naik_gaji))>
                                    <label class="form-check-label fw-semibold small" for="naik">Usulkan naik gaji</label>
                                </div>
                                <div class="mt-2" data-naik-isi>
                                    <div class="input-group mb-2">
                                        <span class="input-group-text">Rp</span>
                                        <input type="text" inputmode="numeric" name="naik_gaji_nominal" class="form-control" data-rupiah placeholder="300.000" aria-label="Nominal kenaikan gaji"
                                            value="{{ old('naik_gaji_nominal', $e->naik_gaji_nominal ? number_format($e->naik_gaji_nominal, 0, ',', '.') : '') }}">
                                    </div>
                                    <input type="text" name="naik_gaji_keterangan" class="form-control" maxlength="255" placeholder="Keterangan (mis. mulai kontrak baru)" value="{{ old('naik_gaji_keterangan', $e->naik_gaji_keterangan) }}">
                                    <div class="form-text">Hanya catatan usulan — tidak mengubah data gaji.</div>
                                </div>
                            </div>
                            <label class="form-label fw-semibold small" for="catatan">Catatan <span class="fw-normal text-muted" data-wajib-catatan>(opsional)</span></label>
                            <textarea name="catatan" id="catatan" rows="3" class="form-control mb-3" maxlength="1000" placeholder="Pencapaian, hal yang perlu diperbaiki, alasan rekomendasi…">{{ old('catatan', $e->catatan) }}</textarea>
                            @if ($ksLangsung)
                                <p class="small text-muted"><i class="bi bi-info-circle me-1"></i>Anda Kepala SDM: rekomendasi ini <b>langsung ditetapkan</b> sebagai keputusan dan data kontrak diperbarui.</p>
                            @endif
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi {{ $ksLangsung ? 'bi-check2-circle' : 'bi-send' }} me-1"></i>{{ $ksLangsung ? 'Simpan & tetapkan kontrak' : ($e->rekomendasi ? 'Simpan perubahan' : 'Kirim ke Kepala SDM') }}
                            </button>
                            @if ($e->rekomendasi)
                                <p class="small text-muted mt-2 mb-0">Terakhir diisi {{ $e->pengisi?->name }} · {{ $e->diisi_pada?->translatedFormat('d M Y H:i') }}</p>
                            @endif
                        @elseif ($e->rekomendasi)
                            <p class="mb-1 fw-semibold">{{ $e->labelRekomendasi() }}</p>
                            @if ($e->naik_gaji && $e->naik_gaji_keterangan)<p class="small mb-1"><i class="bi bi-cash-coin me-1"></i>{{ $e->naik_gaji_keterangan }}</p>@endif
                            @if ($e->catatan)<p class="small mb-1" style="white-space: pre-line;">{{ $e->catatan }}</p>@endif
                            <p class="small text-muted mb-0">{{ $e->pengisi?->name }} · {{ $e->diisi_pada?->translatedFormat('d M Y H:i') }}</p>
                        @else
                            <p class="small text-muted mb-0"><i class="bi bi-hourglass-split me-1"></i>Menunggu diisi oleh {{ $atasan->pluck('name')->implode(', ') ?: 'atasan' }}.</p>
                        @endif
                    </section>

                    <section class="panel">
                        <h2 class="h6 fw-bold mb-3"><i class="bi bi-patch-check"></i>Keputusan Kepala SDM</h2>
                        @if ($e->keputusan)
                            <p class="mb-1 fw-semibold">{{ $e->labelKeputusan() }}</p>
                            @if ($e->catatan_sdm)<p class="small mb-1">{{ $e->catatan_sdm }}</p>@endif
                            <p class="small text-muted mb-{{ $bisaPutuskan ? 3 : 0 }}">{{ $e->pemutus?->name }} · {{ $e->diputuskan_pada?->translatedFormat('d M Y H:i') }}</p>
                        @endif
                        @if ($bisaPutuskan)
                            <select name="keputusan" form="formPutuskan" class="form-select mb-2" required aria-label="Keputusan">
                                @foreach (EK::PILIHAN as $kode => $label)
                                    <option value="{{ $kode }}" @selected(old('keputusan', $e->keputusan ?? $e->rekomendasi) === $kode)>{{ $label }}{{ $e->rekomendasi === $kode ? ' (rekomendasi atasan)' : '' }}</option>
                                @endforeach
                            </select>
                            <select name="keputusan_lama_bulan" form="formPutuskan" class="form-select mb-2" aria-label="Lama perpanjangan" data-tampil-jika-sdm>
                                @foreach (EK::LAMA_BULAN as $bln)
                                    <option value="{{ $bln }}" @selected((int) old('keputusan_lama_bulan', $e->keputusan_lama_bulan ?? $e->lama_bulan ?? 12) === $bln)>{{ $bln }} bulan</option>
                                @endforeach
                            </select>
                            <div class="mb-2 pk-naik" data-tampil-naik-sdm>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch" name="keputusan_naik_gaji" form="formPutuskan" id="naik-sdm" value="1" @checked(old('keputusan_naik_gaji', $e->keputusan ? $e->keputusan_naik_gaji : $e->naik_gaji))>
                                    <label class="form-check-label small fw-semibold" for="naik-sdm">Setujui naik gaji{{ $e->naik_gaji ? ' (diusulkan ' . EK::rupiah($e->naik_gaji_nominal) . ')' : '' }}</label>
                                </div>
                                <div class="input-group input-group-sm mt-2" data-naik-isi-sdm>
                                    <span class="input-group-text">Rp</span>
                                    <input type="text" inputmode="numeric" name="keputusan_naik_gaji_nominal" form="formPutuskan" class="form-control" data-rupiah aria-label="Nominal kenaikan gaji disetujui"
                                        value="{{ old('keputusan_naik_gaji_nominal', ($nominalAwal = $e->keputusan_naik_gaji_nominal ?? $e->naik_gaji_nominal) ? number_format($nominalAwal, 0, ',', '.') : '') }}">
                                </div>
                            </div>
                            <textarea name="catatan_sdm" form="formPutuskan" rows="2" class="form-control mb-2" maxlength="1000" placeholder="Catatan keputusan (opsional)">{{ old('catatan_sdm', $e->catatan_sdm) }}</textarea>
                            <p class="small text-muted mb-2"><i class="bi bi-info-circle me-1"></i>Perpanjang: tanggal habis kontrak dimundurkan. Angkat tetap: status kerja jadi Tetap.</p>
                            <button type="submit" form="formPutuskan" class="btn btn-success w-100"><i class="bi bi-check2-circle me-1"></i>Simpan keputusan</button>
                        @elseif (! $e->keputusan)
                            <p class="small text-muted mb-0"><i class="bi bi-hourglass-split me-1"></i>{{ $e->status === EK::STATUS_MENUNGGU_ATASAN ? 'Diputuskan setelah atasan mengisi evaluasi.' : 'Menunggu keputusan Kepala SDM.' }}</p>
                        @endif
                    </section>
                </div>
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
    .ek-kartu span { font-size: .72rem; font-weight: 600; color: var(--admin-muted); }
    .ek-kartu strong { font-size: .95rem; }
    .ek-kartu small { font-size: .72rem; color: var(--admin-muted); }
    .ek-kartu.waspada { border-color: #f5d48a; background: #fffbeb; }
    .ek-kartu.bahaya { border-color: #f3b4b4; background: #fef2f2; }
    .ek-pilih { display: flex; align-items: center; gap: .5rem; }
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
    .pk-hasil { min-width: 2rem; text-align: center; font-weight: 700; border-radius: 6px; padding: .1rem .4rem; background: var(--admin-surface-soft); }
    .pk-hasil.n4 { background: #dcfce7; color: #166534; } .pk-hasil.n3 { background: #e0f2fe; color: #075985; }
    .pk-hasil.n2 { background: #fef3c7; color: #92400e; } .pk-hasil.n1 { background: #fee2e2; color: #991b1b; }
    @media (min-width: 1200px) { .pk-lengket { position: sticky; top: 80px; } }
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
            const PREDIKAT = (r) => r === null ? '-' : r >= 3.5 ? 'Sangat Baik' : r >= 2.5 ? 'Baik' : r >= 1.5 ? 'Sedang' : 'Kurang';
            const butir = [...isi.querySelectorAll('.pk-butir')];
            // Hasil langsung: terisi, total, rata-rata & predikat.
            const hitung = () => {
                const nilai = butir.map((b) => +(b.querySelector('input:checked') || {}).value || 0).filter(Boolean);
                const rata = nilai.length ? Math.round((nilai.reduce((a, n) => a + n, 0) / nilai.length) * 100) / 100 : null;
                document.querySelector('[data-terisi]').textContent = nilai.length;
                document.querySelector('[data-total]').textContent = nilai.reduce((a, n) => a + n, 0);
                document.querySelector('[data-rata]').textContent = rata ?? '-';
                document.querySelector('[data-predikat]').textContent = PREDIKAT(rata);
            };
            isi.addEventListener('change', (ev) => { if (ev.target.name?.startsWith('nilai[')) { ev.target.closest('.pk-butir').classList.remove('belum'); hitung(); } });
            // "Isi semua" per bagian.
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
            // Lama perpanjangan hanya untuk "Perpanjang"; catatan wajib bila "Tidak diperpanjang".
            const atur = () => {
                const v = (isi.querySelector('[name=rekomendasi]:checked') || {}).value;
                isi.querySelector('[data-tampil-jika]').hidden = v !== 'perpanjang';
                isi.querySelector('[name=catatan]').required = v === 'tidak';
                isi.querySelector('[data-wajib-catatan]').textContent = v === 'tidak' ? '(wajib — alasan tidak diperpanjang)' : '(opsional)';
            };
            // Naik gaji: tidak untuk "Tidak diperpanjang"; nominal & keterangan tampil saat dicentang.
            const naik = isi.querySelector('[name=naik_gaji]');
            const aturNaik = () => {
                const v = (isi.querySelector('[name=rekomendasi]:checked') || {}).value;
                isi.querySelector('[data-tampil-naik]').hidden = v === 'tidak';
                isi.querySelector('[data-naik-isi]').hidden = !naik.checked;
                isi.querySelector('[name=naik_gaji_nominal]').required = naik.checked && v !== 'tidak';
            };
            naik.addEventListener('change', aturNaik);
            isi.querySelectorAll('[name=rekomendasi]').forEach((r) => r.addEventListener('change', aturNaik));
            aturNaik();
            isi.querySelectorAll('[name=rekomendasi]').forEach((r) => r.addEventListener('change', atur));
            atur();
            hitung();
        }
        const putus = document.querySelector('[name=keputusan][form=formPutuskan]');
        if (putus) {
            const naikSdm = document.querySelector('[name=keputusan_naik_gaji]');
            const atur = () => {
                document.querySelector('[data-tampil-jika-sdm]').hidden = putus.value !== 'perpanjang';
                document.querySelector('[data-tampil-naik-sdm]').hidden = putus.value === 'tidak';
                document.querySelector('[data-naik-isi-sdm]').hidden = !naikSdm.checked;
            };
            naikSdm.addEventListener('change', atur);
            putus.addEventListener('change', atur);
            atur();
        }

        // Nominal rupiah: tampil dengan titik ribuan saat diketik (server menyimpan angkanya saja).
        document.querySelectorAll('[data-rupiah]').forEach((el) => el.addEventListener('input', () => {
            const angka = el.value.replace(/\D/g, '');
            el.value = angka ? Number(angka).toLocaleString('id-ID') : '';
        }));
    });
</script>
@endsection
