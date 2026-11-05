{{-- Centang bagian yang dibawahi unit. $bagian (Unit + unitOrganisasi), $dipilih (id bagian tercentang). --}}
<fieldset class="hr-field mb-0" data-pilih-bagian>
    <legend class="form-label fs-6 mb-1">Bagian yang dibawahi <span class="hr-field-opsional">(opsional)</span></legend>
    <p class="form-text mt-0 mb-2">Unit pegawai di bagian ini terisi otomatis. Bagian dari unit lain yang dicentang ikut pindah ke unit ini.</p>
    <input type="search" class="form-control form-control-sm mb-2" placeholder="Saring bagian, contoh: SMK" aria-label="Saring daftar bagian" data-cari-bagian autocomplete="off">
    <div class="hr-pilih-grid">
        @foreach ($bagian as $b)
            <label class="form-check" data-nama-bagian="{{ mb_strtolower($b->nama) }}">
                <input class="form-check-input" type="checkbox" name="bagian_ids[]" value="{{ $b->id }}" data-unit-id="{{ $b->unit_organisasi_id }}" @checked(in_array($b->id, $dipilih, true))>
                <span class="form-check-label">{{ $b->nama }}
                    @if ($b->unitOrganisasi)<small class="text-muted d-block" data-unit-lain>di unit {{ $b->unitOrganisasi->nama }}</small>@endif
                </span>
            </label>
        @endforeach
    </div>
</fieldset>
