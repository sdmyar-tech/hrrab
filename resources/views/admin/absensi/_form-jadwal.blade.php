{{--
    Isian jam kerja (modal ubah per pegawai & modal massal), dua bagian x-form-section: Jam kerja · Hari kerja.
    Param: $awalan (prefix id unik), $wajib (bool), (opsional) $bisaHapus = true → centang "Hapus jam kerja".
    Atribut data-j / data-khusus / data-isi-jam dipakai skrip halaman Pengaturan.
--}}
@php
    $hariBawaan = config('hrrab.hari_kerja', [1, 2, 3, 4, 5]);
    $tolBawaan = (int) config('hrrab.absensi.toleransi_menit', 15);
@endphp
<x-form-section title="Jam kerja" icon="bi-clock" :id="$awalan . '-bagian-jam'" subtitle="On Time / Telat dihitung dari jam masuk ditambah toleransi.">
    <div class="col-12">
        <div class="abs-chip-baris" data-jam-cepat role="group" aria-label="Pilihan cepat jam kerja">
            <span class="abs-chip-label">Pilihan cepat:</span>
            @foreach (\App\Support\Absensi\TampilanAbsensi::JAM_CEPAT as [$m, $p])
                <button type="button" class="hr-chip" data-isi-jam="{{ $m }}|{{ $p }}" aria-pressed="false">{{ $m }}–{{ $p }}</button>
            @endforeach
        </div>
    </div>
    <div class="col-6 col-sm-4">
        <x-input type="time" name="jam_masuk" :id="$awalan . '-masuk'" label="Jam masuk" :required="$wajib" data-j="masuk" />
    </div>
    <div class="col-6 col-sm-4">
        <x-input type="time" name="jam_pulang" :id="$awalan . '-pulang'" label="Jam pulang" :required="$wajib" data-j="pulang" />
    </div>
    <div class="col-12 col-sm-4">
        <div class="hr-field">
            <label class="form-label" for="{{ $awalan }}-tol">Toleransi telat</label>
            <div class="input-group">
                <input type="number" name="toleransi_menit" id="{{ $awalan }}-tol" class="form-control" min="0" max="240"
                    placeholder="Contoh: {{ $tolBawaan }}" data-j="toleransi" aria-describedby="{{ $awalan }}-tol-bantuan">
                <span class="input-group-text">menit</span>
            </div>
            <div class="form-text" id="{{ $awalan }}-tol-bantuan">Kosongkan untuk bawaan {{ $tolBawaan }} menit.</div>
        </div>
    </div>
    @if ($bisaHapus ?? false)
        <div class="col-12" data-hapus-jadwal>
            <div class="form-check abs-hapus-jadwal">
                <input class="form-check-input" type="checkbox" name="hapus_jadwal" value="1" id="{{ $awalan }}-hapus">
                <label class="form-check-label" for="{{ $awalan }}-hapus">Hapus jam kerja (kosongkan jam masuk)</label>
            </div>
        </div>
    @endif
</x-form-section>

<x-form-section title="Hari kerja" icon="bi-calendar-week" :id="$awalan . '-bagian-hari'" subtitle="Hari yang tidak dipilih dianggap libur untuk pegawai ini.">
    <div class="col-12">
        <div class="abs-hari-pilih" data-j="hari" role="group" aria-label="Hari kerja">
            @foreach (\App\Models\JadwalKerjaPegawai::NAMA_HARI as $no => $nama)
                <input type="checkbox" class="btn-check" name="hari_kerja[]" value="{{ $no }}" id="{{ $awalan }}-h{{ $no }}" autocomplete="off" @checked(in_array($no, $hariBawaan, true))>
                <label class="hr-chip" for="{{ $awalan }}-h{{ $no }}" title="{{ $nama }}">{{ mb_substr($nama, 0, 3) }}</label>
            @endforeach
        </div>
    </div>
    <div class="col-12">
        <details class="abs-khusus" data-j="khusus-wrap">
            <summary><i class="bi bi-plus-circle" aria-hidden="true"></i>Jam berbeda di hari tertentu (mis. Jumat pulang lebih awal)</summary>
            <div class="abs-khusus-isi">
                @foreach (\App\Models\JadwalKerjaPegawai::NAMA_HARI as $no => $nama)
                    <div class="abs-khusus-baris" data-khusus="{{ $no }}">
                        <span class="abs-khusus-hari">{{ $nama }}</span>
                        <input type="time" name="khusus[{{ $no }}][masuk]" class="form-control form-control-sm" aria-label="Jam masuk {{ $nama }}">
                        <span class="text-muted" aria-hidden="true">–</span>
                        <input type="time" name="khusus[{{ $no }}][pulang]" class="form-control form-control-sm" aria-label="Jam pulang {{ $nama }}">
                    </div>
                @endforeach
                <p class="abs-khusus-catatan">Kosongkan bila sama dengan jam biasa.</p>
            </div>
        </details>
    </div>
</x-form-section>

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
        form.querySelectorAll('[data-isi-jam]').forEach((b) => { b.classList.toggle('is-active', b === btn); b.setAttribute('aria-pressed', b === btn ? 'true' : 'false'); });
    });
</script>
@endonce
