{{-- Isian jam kerja. Param: $awalan (prefix id unik), $wajib (bool) --}}
@php $hariBawaan = config('hrrab.hari_kerja', [1, 2, 3, 4, 5]); @endphp
<div class="row g-2 mb-2">
    <div class="col-6 col-sm-4">
        <label class="form-label small" for="{{ $awalan }}-masuk">Jam masuk @if ($wajib)<span class="text-danger">*</span>@endif</label>
        <input type="time" name="jam_masuk" id="{{ $awalan }}-masuk" class="form-control" @required($wajib) data-j="masuk">
    </div>
    <div class="col-6 col-sm-4">
        <label class="form-label small" for="{{ $awalan }}-pulang">Jam pulang @if ($wajib)<span class="text-danger">*</span>@endif</label>
        <input type="time" name="jam_pulang" id="{{ $awalan }}-pulang" class="form-control" @required($wajib) data-j="pulang">
    </div>
    <div class="col-12 col-sm-4">
        <label class="form-label small" for="{{ $awalan }}-tol">Toleransi (menit)</label>
        <input type="number" name="toleransi_menit" id="{{ $awalan }}-tol" class="form-control" min="0" max="240"
            placeholder="{{ config('hrrab.absensi.toleransi_menit', 15) }} (bawaan)" data-j="toleransi">
    </div>
</div>

<label class="form-label small mb-1">Hari kerja</label>
<div class="d-flex flex-wrap gap-2 mb-2 absensi-jadwal-hari" data-j="hari">
    @foreach (\App\Models\JadwalKerjaPegawai::NAMA_HARI as $no => $nama)
        <div class="form-check">
            <input class="form-check-input" type="checkbox" name="hari_kerja[]" value="{{ $no }}" id="{{ $awalan }}-h{{ $no }}" @checked(in_array($no, $hariBawaan, true))>
            <label class="form-check-label small" for="{{ $awalan }}-h{{ $no }}">{{ mb_substr($nama, 0, 3) }}</label>
        </div>
    @endforeach
</div>

<details class="small mb-2" data-j="khusus-wrap">
    <summary class="text-primary" style="cursor:pointer;">Jam berbeda di hari tertentu (mis. Jumat pulang lebih awal)</summary>
    <div class="border rounded p-2 mt-2 d-grid gap-2">
        @foreach (\App\Models\JadwalKerjaPegawai::NAMA_HARI as $no => $nama)
            <div class="d-flex align-items-center gap-2" data-khusus="{{ $no }}">
                <span class="text-muted" style="width:56px;">{{ $nama }}</span>
                <input type="time" name="khusus[{{ $no }}][masuk]" class="form-control form-control-sm" aria-label="Masuk {{ $nama }}">
                <span class="text-muted">–</span>
                <input type="time" name="khusus[{{ $no }}][pulang]" class="form-control form-control-sm" aria-label="Pulang {{ $nama }}">
            </div>
        @endforeach
        <span class="text-muted">Kosongkan bila sama dengan jam biasa.</span>
    </div>
</details>
