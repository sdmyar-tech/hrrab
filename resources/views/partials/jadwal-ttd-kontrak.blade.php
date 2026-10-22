{{-- Jadwal tanda tangan kontrak milik pengguna yang login (dari evaluasi kontrak yang sudah diputuskan). --}}
@php
    $ttdSaya = auth()->user()?->data_pegawai_id
        ? \App\Models\EvaluasiKontrak::where('data_pegawai_id', auth()->user()->data_pegawai_id)
            ->where('status', \App\Models\EvaluasiKontrak::STATUS_SELESAI)
            ->whereIn('keputusan', [\App\Models\EvaluasiKontrak::PERPANJANG, \App\Models\EvaluasiKontrak::TETAP])
            ->where('ttd_jadwal', '>=', now()->startOfDay())->orderBy('ttd_jadwal')->first()
        : null;
@endphp
@if ($ttdSaya)
    <div class="alert alert-info d-flex align-items-start gap-2 {{ $kelas ?? 'mb-3' }}" role="status">
        <i class="bi bi-pen fs-5"></i>
        <div>
            <div class="fw-semibold">Jadwal tanda tangan kontrak</div>
            <div class="small">{{ $ttdSaya->labelKeputusan() }} — silakan datang pada <b>{{ $ttdSaya->labelTtd() }}</b>.</div>
        </div>
    </div>
@endif
