{{--
    Isian keputusan evaluasi kontrak + jadwal & lokasi TTD. $e = EvaluasiKontrak, $form = id form pemilik
    (atribut form="…", untuk input yang ditaruh di luar <form>-nya) atau null bila berada di dalam form.
    Kotak [data-isian-keputusan] diatur skrip di show.blade.php: [data-jika] / [data-jika-bukan] tampil sesuai keputusan,
    [data-wajib-bukan-tidak] wajib kecuali "tidak", [data-naik-isi] tampil bila naik gaji dicentang.
--}}
@php
    use App\Models\EvaluasiKontrak as EK;
    $ttdAwal = old('ttd_jadwal', $e->ttd_jadwal?->format('Y-m-d\TH:i') ?? now()->addWeekday()->setTime(9, 0)->format('Y-m-d\TH:i'));
    $lamaOpsi = collect(EK::LAMA_BULAN)->mapWithKeys(fn ($bln) => [$bln => 'Perpanjang ' . $bln . ' bulan'])->all();
@endphp
<div class="row g-3" data-isian-keputusan>
    <div class="col-md-6">
        <x-select name="keputusan" :id="'keputusan-' . $e->id" label="Keputusan" :options="EK::PILIHAN" :selected="$e->keputusan"
            placeholder="Pilih keputusan" required :form="$form" data-keputusan field-class="mb-0" />
    </div>
    <div class="col-md-6" data-jika="perpanjang">
        <x-select name="keputusan_lama_bulan" :id="'lama-' . $e->id" label="Lama Perpanjangan" :options="$lamaOpsi"
            :selected="$e->keputusan_lama_bulan ?? 12" :form="$form" field-class="mb-0" />
    </div>

    {{-- Naik gaji: hanya catatan nominal, tidak terhubung ke penggajian. --}}
    <div class="col-12" data-jika-bukan="tidak">
        <div class="form-check form-switch mb-0">
            <input class="form-check-input" type="checkbox" role="switch" name="keputusan_naik_gaji" @if ($form) form="{{ $form }}" @endif id="naik-{{ $e->id }}" value="1"
                @checked(old('keputusan_naik_gaji', $e->keputusan_naik_gaji)) data-naik>
            <label class="form-check-label fw-semibold" for="naik-{{ $e->id }}">Naik gaji</label>
        </div>
        <div class="hr-field mb-0 mt-2" data-naik-isi>
            <label class="form-label required" for="nominal-{{ $e->id }}">Nominal kenaikan</label>
            <div class="input-group">
                <span class="input-group-text">Rp</span>
                <input type="text" inputmode="numeric" name="keputusan_naik_gaji_nominal" id="nominal-{{ $e->id }}" @if ($form) form="{{ $form }}" @endif
                    class="form-control @error('keputusan_naik_gaji_nominal') is-invalid @enderror" data-rupiah placeholder="Nominal per bulan, contoh: 300.000"
                    value="{{ old('keputusan_naik_gaji_nominal', $e->keputusan_naik_gaji_nominal ? number_format($e->keputusan_naik_gaji_nominal, 0, ',', '.') : '') }}">
            </div>
            @error('keputusan_naik_gaji_nominal')
                <div class="invalid-feedback d-block"><i class="bi bi-exclamation-circle" aria-hidden="true"></i> {{ $message }}</div>
            @else
                <div class="form-text">Hanya catatan; tidak mengubah data penggajian.</div>
            @enderror
        </div>
    </div>

    {{-- Jadwal & lokasi tanda tangan kontrak (perpanjang / angkat tetap). --}}
    <div class="col-12" data-jika-bukan="tidak">
        <fieldset class="ek-ttd">
            <legend class="form-label"><i class="bi bi-pen me-1" aria-hidden="true"></i>Jadwal tanda tangan kontrak</legend>
            <div class="row g-3">
                <div class="col-md-6">
                    <x-input type="datetime-local" name="ttd_jadwal" :id="'ttd-jadwal-' . $e->id" label="Tanggal & Jam" :value="$ttdAwal"
                        required :form="$form" data-wajib-bukan-tidak field-class="mb-0" />
                </div>
                <div class="col-md-6">
                    <x-input name="ttd_lokasi" :id="'ttd-lokasi-' . $e->id" label="Lokasi" icon="bi-geo-alt" maxlength="150"
                        :value="$e->ttd_lokasi ?? EK::TTD_LOKASI_BAWAAN" placeholder="Tempat tanda tangan, contoh: Ruangan SDM"
                        required :form="$form" data-wajib-bukan-tidak field-class="mb-0" />
                </div>
            </div>
        </fieldset>
    </div>

    <div class="col-12">
        <x-textarea name="catatan_sdm" :id="'catatan-sdm-' . $e->id" label="Catatan Keputusan" rows="2" maxlength="1000" :value="$e->catatan_sdm"
            placeholder="Wajib bila tidak diperpanjang. Contoh: kinerja di bawah target" :form="$form" data-catatan-keputusan
            field-class="mb-0" />
    </div>
    <div class="col-12">
        <ul class="hr-daftar-info">
            <li><i class="bi bi-calendar-plus" aria-hidden="true"></i>Perpanjang: tanggal habis kontrak dimundurkan sesuai lama perpanjangan.</li>
            <li><i class="bi bi-person-check" aria-hidden="true"></i>Angkat tetap: status kerja pegawai menjadi Tetap.</li>
            <li><i class="bi bi-bell" aria-hidden="true"></i>Pegawai menerima notifikasi keputusan beserta jadwal TTD.</li>
        </ul>
    </div>
</div>
