@extends('layouts.admin.app')

@php
    use App\Models\EvaluasiKontrak as EK;
    $p = $e->dataPegawai;
    $k = $p?->kepegawaian;
    $sisa = $e->sisaHari();
    $masa = $k?->tanggal_masuk?->diff(now());
    $skala = [1 => 'Kurang sekali', 2 => 'Kurang', 3 => 'Cukup', 4 => 'Baik', 5 => 'Sangat baik'];
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

        <div class="row g-3">
            <div class="col-lg-7">
                <section class="panel mb-3">
                    <h2 class="h6 fw-bold mb-3"><i class="bi bi-file-earmark-text"></i>Kontrak</h2>
                    <div class="ek-angka">
                        <div class="ek-kartu {{ $e->status !== EK::STATUS_SELESAI && $sisa <= 7 ? 'waspada' : '' }}">
                            <span>Habis kontrak</span>
                            <strong>{{ $e->tanggal_habis_kontrak->translatedFormat('d M Y') }}</strong>
                            @if ($e->status !== EK::STATUS_SELESAI)<small>{{ $sisa < 0 ? 'lewat ' . abs($sisa) . ' hari' : ($sisa === 0 ? 'hari ini' : $sisa . ' hari lagi') }}</small>@endif
                        </div>
                        <div class="ek-kartu"><span>Status kerja</span><strong>{{ $e->status_kerja ?? '-' }}</strong><small>saat evaluasi dibuat</small></div>
                        <div class="ek-kartu"><span>Masa kerja</span><strong>{{ $masa ? ($masa->y ? $masa->y . ' th ' : '') . $masa->m . ' bl' : '-' }}</strong><small>sejak {{ $k?->tanggal_masuk?->translatedFormat('d M Y') ?? '-' }}</small></div>
                    </div>
                    <p class="small text-muted mb-0 mt-3"><i class="bi bi-person-badge me-1"></i>Atasan penilai: {{ $atasan->pluck('name')->implode(', ') ?: 'belum ada atasan tercatat' }}</p>
                </section>

                <section class="panel mb-3">
                    <h2 class="h6 fw-bold mb-1"><i class="bi bi-bar-chart"></i>Data pendukung</h2>
                    <p class="small text-muted mb-3">Absensi {{ $rentangAbsensi[0]->translatedFormat('d M Y') }} – {{ $rentangAbsensi[1]->translatedFormat('d M Y') }}, cuti tahun {{ now()->year }}.</p>
                    @if ($absensi && ($absensi['hari_kerja'] ?? 0) > 0)
                        <div class="ek-angka">
                            <div class="ek-kartu"><span>Hadir</span><strong>{{ $absensi['hadir_total'] ?? 0 }}/{{ $absensi['hari_kerja'] }} hari</strong></div>
                            <div class="ek-kartu {{ ($absensi['telat'] ?? 0) > 5 ? 'waspada' : '' }}"><span>Telat</span><strong>{{ $absensi['telat'] ?? 0 }} kali</strong><small>{{ $absensi['telat_menit'] ?? 0 }} menit</small></div>
                            <div class="ek-kartu {{ ($absensi['alpa'] ?? 0) > 0 ? 'bahaya' : '' }}"><span>Alpa</span><strong>{{ $absensi['alpa'] ?? 0 }} hari</strong></div>
                            <div class="ek-kartu"><span>Izin / sakit</span><strong>{{ ($absensi['izin'] ?? 0) + ($absensi['sakit'] ?? 0) }} hari</strong></div>
                            <div class="ek-kartu"><span>Cuti {{ now()->year }}</span><strong>{{ $cutiTahunIni }} hari</strong></div>
                        </div>
                    @else
                        <p class="small text-muted mb-2">Belum ada data absensi pada rentang ini (pegawai mungkin tidak ikut absensi mesin).</p>
                        <div class="ek-angka"><div class="ek-kartu"><span>Cuti {{ now()->year }}</span><strong>{{ $cutiTahunIni }} hari</strong></div></div>
                    @endif
                </section>

                <section class="panel">
                    <h2 class="h6 fw-bold mb-3"><i class="bi bi-clock-history"></i>Riwayat evaluasi</h2>
                    @forelse ($riwayat as $r)
                        <div class="d-flex justify-content-between gap-2 py-2 border-bottom small">
                            <span>Kontrak s/d {{ $r->tanggal_habis_kontrak->translatedFormat('d M Y') }}</span>
                            <span class="text-end">{{ EK::labelPilihan($r->keputusan ?? $r->rekomendasi, $r->keputusan_lama_bulan ?? $r->lama_bulan) }} <span class="badge {{ $r->badgeClass() }}">{{ $r->statusLabel() }}</span></span>
                        </div>
                    @empty
                        <p class="small text-muted mb-0">Belum ada evaluasi kontrak sebelumnya.</p>
                    @endforelse
                </section>
            </div>

            <div class="col-lg-5">
                {{-- ===== Rekomendasi atasan ===== --}}
                <section class="panel mb-3">
                    <h2 class="h6 fw-bold mb-3"><i class="bi bi-person-check"></i>Rekomendasi atasan</h2>
                    @if ($bisaIsi)
                        <form action="{{ route('admin.evaluasi-kontrak.isi', $e) }}" method="POST" id="formIsi" data-confirm="Kirim evaluasi kontrak ini?">
                            @csrf
                            <label class="form-label fw-semibold small">Rekomendasi</label>
                            <div class="d-grid gap-2 mb-3">
                                @foreach (EK::PILIHAN as $kode => $label)
                                    <input type="radio" class="btn-check" name="rekomendasi" id="rek-{{ $kode }}" value="{{ $kode }}" @checked(old('rekomendasi', $e->rekomendasi) === $kode) required>
                                    <label class="btn btn-outline-secondary text-start ek-pilih" for="rek-{{ $kode }}">
                                        <i class="bi {{ ['perpanjang' => 'bi-arrow-repeat', 'tetap' => 'bi-award', 'tidak' => 'bi-x-circle'][$kode] }}"></i>{{ $label }}
                                    </label>
                                @endforeach
                            </div>
                            <div class="mb-3" data-tampil-jika="perpanjang">
                                <label class="form-label fw-semibold small" for="lama">Lama perpanjangan</label>
                                <select name="lama_bulan" id="lama" class="form-select">
                                    @foreach (EK::LAMA_BULAN as $b)
                                        <option value="{{ $b }}" @selected((int) old('lama_bulan', $e->lama_bulan ?? 12) === $b)>{{ $b }} bulan</option>
                                    @endforeach
                                </select>
                            </div>
                            <label class="form-label fw-semibold small">Penilaian (1–5)</label>
                            @foreach (EK::ASPEK as $kolom => $label)
                                <div class="mb-2">
                                    <div class="small mb-1">{{ $label }}</div>
                                    <div class="btn-group w-100 ek-nilai" role="group" aria-label="{{ $label }}">
                                        @foreach ($skala as $n => $ket)
                                            <input type="radio" class="btn-check" name="{{ $kolom }}" id="{{ $kolom }}-{{ $n }}" value="{{ $n }}" @checked((int) old($kolom, $e->$kolom) === $n) required>
                                            <label class="btn btn-outline-secondary" for="{{ $kolom }}-{{ $n }}" title="{{ $ket }}">{{ $n }}</label>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                            <p class="small text-muted mb-3">1 = kurang sekali · 3 = cukup · 5 = sangat baik</p>
                            <label class="form-label fw-semibold small" for="catatan">Catatan <span class="fw-normal text-muted" data-wajib-catatan>(opsional)</span></label>
                            <textarea name="catatan" id="catatan" rows="3" class="form-control mb-3" maxlength="1000" placeholder="Pencapaian, hal yang perlu diperbaiki, alasan rekomendasi…">{{ old('catatan', $e->catatan) }}</textarea>
                            <button type="submit" class="btn btn-primary w-100"><i class="bi bi-send me-1"></i>{{ $e->rekomendasi ? 'Simpan perubahan' : 'Kirim ke Kepala SDM' }}</button>
                        </form>
                    @elseif ($e->rekomendasi)
                        @include('admin.evaluasi-kontrak._ringkasan-atasan')
                    @else
                        <p class="small text-muted mb-0"><i class="bi bi-hourglass-split me-1"></i>Menunggu diisi oleh {{ $atasan->pluck('name')->implode(', ') ?: 'atasan' }}.</p>
                    @endif
                    @if ($bisaIsi && $e->rekomendasi)
                        <p class="small text-muted mt-2 mb-0">Terakhir diisi {{ $e->pengisi?->name }} · {{ $e->diisi_pada?->translatedFormat('d M Y H:i') }}</p>
                    @endif
                </section>

                {{-- ===== Keputusan Kepala SDM ===== --}}
                <section class="panel">
                    <h2 class="h6 fw-bold mb-3"><i class="bi bi-patch-check"></i>Keputusan Kepala SDM</h2>
                    @if ($e->keputusan)
                        <p class="mb-1 fw-semibold">{{ EK::labelPilihan($e->keputusan, $e->keputusan_lama_bulan) }}</p>
                        @if ($e->catatan_sdm)<p class="small mb-1">{{ $e->catatan_sdm }}</p>@endif
                        <p class="small text-muted mb-{{ $bisaPutuskan ? 3 : 0 }}">{{ $e->pemutus?->name }} · {{ $e->diputuskan_pada?->translatedFormat('d M Y H:i') }}</p>
                    @endif
                    @if ($bisaPutuskan)
                        <form action="{{ route('admin.evaluasi-kontrak.putuskan', $e) }}" method="POST" id="formPutuskan" data-confirm="Simpan keputusan? Data kontrak pegawai akan ikut diperbarui.">
                            @csrf
                            <select name="keputusan" class="form-select mb-2" required aria-label="Keputusan">
                                @foreach (EK::PILIHAN as $kode => $label)
                                    <option value="{{ $kode }}" @selected(old('keputusan', $e->keputusan ?? $e->rekomendasi) === $kode)>{{ $label }}{{ $e->rekomendasi === $kode ? ' (rekomendasi atasan)' : '' }}</option>
                                @endforeach
                            </select>
                            <select name="keputusan_lama_bulan" class="form-select mb-2" aria-label="Lama perpanjangan" data-tampil-jika-sdm="perpanjang">
                                @foreach (EK::LAMA_BULAN as $b)
                                    <option value="{{ $b }}" @selected((int) old('keputusan_lama_bulan', $e->keputusan_lama_bulan ?? $e->lama_bulan ?? 12) === $b)>{{ $b }} bulan</option>
                                @endforeach
                            </select>
                            <textarea name="catatan_sdm" rows="2" class="form-control mb-2" maxlength="1000" placeholder="Catatan keputusan (opsional)">{{ old('catatan_sdm', $e->catatan_sdm) }}</textarea>
                            <p class="small text-muted mb-2"><i class="bi bi-info-circle me-1"></i>Perpanjang: tanggal habis kontrak dimundurkan. Angkat tetap: status kerja jadi Tetap.</p>
                            <button type="submit" class="btn btn-success w-100"><i class="bi bi-check2-circle me-1"></i>Simpan keputusan</button>
                        </form>
                    @elseif (! $e->keputusan)
                        <p class="small text-muted mb-0"><i class="bi bi-hourglass-split me-1"></i>{{ $e->status === EK::STATUS_MENUNGGU_ATASAN ? 'Diputuskan setelah atasan mengisi evaluasi.' : 'Menunggu keputusan Kepala SDM.' }}</p>
                    @endif
                </section>
            </div>
        </div>
    </div>
</main>

<style>
    .ek-angka { display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: .5rem; }
    .ek-kartu { display: flex; flex-direction: column; padding: .55rem .7rem; border: 1px solid var(--admin-border); border-radius: 9px; background: var(--admin-surface); }
    .ek-kartu span { font-size: .72rem; font-weight: 600; color: var(--admin-muted); }
    .ek-kartu strong { font-size: .95rem; }
    .ek-kartu small { font-size: .72rem; color: var(--admin-muted); }
    .ek-kartu.waspada { border-color: #f5d48a; background: #fffbeb; }
    .ek-kartu.bahaya { border-color: #f3b4b4; background: #fef2f2; }
    .ek-pilih { display: flex; align-items: center; gap: .5rem; }
    .ek-nilai .btn { padding-block: .35rem; font-weight: 700; }
    html[data-theme="dark"] .ek-kartu.waspada { background: rgba(245, 158, 11, .1); border-color: rgba(245, 158, 11, .35); }
    html[data-theme="dark"] .ek-kartu.bahaya { background: rgba(239, 68, 68, .1); border-color: rgba(239, 68, 68, .35); }
</style>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Lama perpanjangan hanya untuk "Perpanjang"; catatan wajib bila "Tidak diperpanjang".
        const isi = document.getElementById('formIsi');
        if (isi) {
            const atur = () => {
                const v = (isi.querySelector('[name=rekomendasi]:checked') || {}).value;
                isi.querySelector('[data-tampil-jika]').hidden = v !== 'perpanjang';
                isi.querySelector('[name=catatan]').required = v === 'tidak';
                isi.querySelector('[data-wajib-catatan]').textContent = v === 'tidak' ? '(wajib — alasan tidak diperpanjang)' : '(opsional)';
            };
            isi.querySelectorAll('[name=rekomendasi]').forEach((r) => r.addEventListener('change', atur));
            atur();
        }
        const putus = document.getElementById('formPutuskan');
        if (putus) {
            const atur = () => { putus.querySelector('[data-tampil-jika-sdm]').hidden = putus.querySelector('[name=keputusan]').value !== 'perpanjang'; };
            putus.querySelector('[name=keputusan]').addEventListener('change', atur);
            atur();
        }
    });
</script>
@endsection
