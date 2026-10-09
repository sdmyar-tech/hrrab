{{-- Isian jam kerja. Param: $awalan (prefix id unik), $wajib (bool) --}}
@php $hariBawaan = config('hrrab.hari_kerja', [1, 2, 3, 4, 5]); @endphp
<div class="mb-2" data-jam-cepat>
    <span class="small text-muted me-1">Pilihan cepat:</span>
    @foreach (\App\Support\Absensi\TampilanAbsensi::JAM_CEPAT as [$m, $p])
        <button type="button" class="abs-chip mb-1" data-isi-jam="{{ $m }}|{{ $p }}">{{ $m }}–{{ $p }}</button>
    @endforeach
</div>
<div class="row g-2 mb-3">
    <div class="col-6 col-sm-4">
        <label class="form-label small mb-1" for="{{ $awalan }}-masuk">Jam masuk @if ($wajib)<span class="text-danger">*</span>@endif</label>
        <input type="time" name="jam_masuk" id="{{ $awalan }}-masuk" class="form-control" @required($wajib) data-j="masuk">
    </div>
    <div class="col-6 col-sm-4">
        <label class="form-label small mb-1" for="{{ $awalan }}-pulang">Jam pulang @if ($wajib)<span class="text-danger">*</span>@endif</label>
        <input type="time" name="jam_pulang" id="{{ $awalan }}-pulang" class="form-control" @required($wajib) data-j="pulang">
    </div>
    <div class="col-12 col-sm-4">
        <label class="form-label small mb-1" for="{{ $awalan }}-tol">Toleransi telat</label>
        <div class="input-group">
            <input type="number" name="toleransi_menit" id="{{ $awalan }}-tol" class="form-control" min="0" max="240"
                placeholder="{{ config('hrrab.absensi.toleransi_menit', 15) }}" data-j="toleransi">
            <span class="input-group-text">menit</span>
        </div>
    </div>
</div>

<label class="form-label small mb-1">Hari kerja</label>
<div class="d-flex flex-wrap gap-1 mb-2" data-j="hari">
    @foreach (\App\Models\JadwalKerjaPegawai::NAMA_HARI as $no => $nama)
        <input type="checkbox" class="btn-check" name="hari_kerja[]" value="{{ $no }}" id="{{ $awalan }}-h{{ $no }}" autocomplete="off" @checked(in_array($no, $hariBawaan, true))>
        <label class="btn btn-sm btn-outline-primary" for="{{ $awalan }}-h{{ $no }}" style="min-width:52px;">{{ mb_substr($nama, 0, 3) }}</label>
    @endforeach
</div>

<details class="small mb-1" data-j="khusus-wrap">
    <summary class="text-primary" style="cursor:pointer;"><i class="bi bi-plus-circle me-1"></i>Jam berbeda di hari tertentu (mis. Jumat pulang lebih awal)</summary>
    <div class="border rounded-3 p-2 mt-2 d-grid gap-2">
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

@once
<script>
    // Tombol pilihan cepat jam kerja mengisi jam masuk & pulang pada form yang sama.
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('[data-isi-jam]');
        if (!btn) return;
        const form = btn.closest('form');
        const [m, p] = btn.dataset.isiJam.split('|');
        form.querySelector('[data-j="masuk"]').value = m;
        form.querySelector('[data-j="pulang"]').value = p;
        form.querySelectorAll('[data-isi-jam]').forEach((b) => b.classList.toggle('aktif', b === btn));
    });
</script>
@endonce
