{{--
    Isian keputusan evaluasi kontrak + jadwal & lokasi TTD. $e = EvaluasiKontrak, $form = id form pemilik
    (atribut form="…", untuk input yang ditaruh di luar <form>-nya) atau null bila berada di dalam form.
--}}
@php
    use App\Models\EvaluasiKontrak as EK;
    $f = $form ? 'form=' . $form : '';
    $ttdAwal = old('ttd_jadwal', $e->ttd_jadwal?->format('Y-m-d\TH:i') ?? now()->addWeekday()->setTime(9, 0)->format('Y-m-d\TH:i'));
@endphp
<div data-isian-keputusan>
    <label class="form-label fw-semibold small" for="keputusan-{{ $e->id }}">Keputusan</label>
    <select name="keputusan" id="keputusan-{{ $e->id }}" {!! $f !!} class="form-select mb-2" required data-keputusan>
        <option value="">-- Pilih keputusan --</option>
        @foreach (EK::PILIHAN as $kode => $label)
            <option value="{{ $kode }}" @selected(old('keputusan', $e->keputusan) === $kode)>{{ $label }}</option>
        @endforeach
    </select>
    <select name="keputusan_lama_bulan" {!! $f !!} class="form-select mb-2" aria-label="Lama perpanjangan" data-jika="perpanjang">
        @foreach (EK::LAMA_BULAN as $bln)
            <option value="{{ $bln }}" @selected((int) old('keputusan_lama_bulan', $e->keputusan_lama_bulan ?? 12) === $bln)>Perpanjang {{ $bln }} bulan</option>
        @endforeach
    </select>
    {{-- Naik gaji: hanya catatan nominal, tidak terhubung ke penggajian. --}}
    <div class="mb-2" data-jika-bukan="tidak">
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" role="switch" name="keputusan_naik_gaji" {!! $f !!} id="naik-{{ $e->id }}" value="1" @checked(old('keputusan_naik_gaji', $e->keputusan_naik_gaji)) data-naik>
            <label class="form-check-label small fw-semibold" for="naik-{{ $e->id }}">Naik gaji</label>
        </div>
        <div class="input-group input-group-sm mt-2" data-naik-isi>
            <span class="input-group-text">Rp</span>
            <input type="text" inputmode="numeric" name="keputusan_naik_gaji_nominal" {!! $f !!} class="form-control" data-rupiah placeholder="300.000" aria-label="Nominal kenaikan gaji"
                value="{{ old('keputusan_naik_gaji_nominal', $e->keputusan_naik_gaji_nominal ? number_format($e->keputusan_naik_gaji_nominal, 0, ',', '.') : '') }}">
        </div>
    </div>
    {{-- Jadwal & lokasi tanda tangan kontrak (perpanjang / angkat tetap). --}}
    <div class="ek-ttd mb-2" data-jika-bukan="tidak">
        <div class="small fw-semibold mb-1"><i class="bi bi-pen me-1"></i>Jadwal tanda tangan kontrak</div>
        <input type="datetime-local" name="ttd_jadwal" {!! $f !!} class="form-control form-control-sm mb-2" value="{{ $ttdAwal }}" aria-label="Tanggal & jam TTD kontrak" data-wajib-bukan-tidak>
        <div class="input-group input-group-sm">
            <span class="input-group-text"><i class="bi bi-geo-alt"></i></span>
            <input type="text" name="ttd_lokasi" {!! $f !!} class="form-control" maxlength="150" value="{{ old('ttd_lokasi', $e->ttd_lokasi ?? EK::TTD_LOKASI_BAWAAN) }}" aria-label="Lokasi TTD kontrak" data-wajib-bukan-tidak>
        </div>
    </div>
    <textarea name="catatan_sdm" {!! $f !!} rows="2" class="form-control mb-2" maxlength="1000" placeholder="Wajib bila tidak diperpanjang. Contoh: kinerja di bawah target" data-catatan-keputusan>{{ old('catatan_sdm', $e->catatan_sdm) }}</textarea>
    <p class="small text-muted mb-2"><i class="bi bi-info-circle me-1"></i>Perpanjang: tanggal habis kontrak dimundurkan. Angkat tetap: status kerja jadi Tetap. Pegawai menerima notifikasi jadwal TTD.</p>
</div>
