{{-- Satu baris isian riwayat kontrak (form tambah). Param: $i (indeks), $b (isian: jenis, tanggal_mulai, …). --}}
@php $galat = fn ($f) => $errors->first("baris.$i.$f"); @endphp
<div class="border rounded-3 p-3" data-baris>
    <div class="row g-2">
        <div class="col-12 d-flex align-items-end gap-2">
            <div class="flex-grow-1">
                <label class="form-label required" for="bk-{{ $i }}-jenis">Jenis</label>
                <select name="baris[{{ $i }}][jenis]" id="bk-{{ $i }}-jenis" class="form-select form-select-sm {{ $galat('jenis') ? 'is-invalid' : '' }}">
                    <option value="">Pilih jenis kontrak</option>
                    @foreach (\App\Models\KontrakPegawai::JENIS as $j)<option value="{{ $j }}" @selected(($b['jenis'] ?? '') === $j)>{{ $j }}</option>@endforeach
                </select>
            </div>
            <button type="button" class="btn btn-sm btn-outline-danger" data-hapus-baris title="Hapus baris ini" aria-label="Hapus baris ini"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
        </div>
        @if ($galat('jenis'))<div class="col-12"><div class="invalid-feedback d-block">{{ $galat('jenis') }}</div></div>@endif
        <div class="col-6">
            <label class="form-label required" for="bk-{{ $i }}-mulai">Mulai</label>
            <input type="date" name="baris[{{ $i }}][tanggal_mulai]" id="bk-{{ $i }}-mulai" value="{{ $b['tanggal_mulai'] ?? '' }}" class="form-control form-control-sm {{ $galat('tanggal_mulai') ? 'is-invalid' : '' }}">
            @if ($galat('tanggal_mulai'))<div class="invalid-feedback">{{ $galat('tanggal_mulai') }}</div>@endif
        </div>
        <div class="col-6">
            <label class="form-label" for="bk-{{ $i }}-selesai">Selesai</label>
            <input type="date" name="baris[{{ $i }}][tanggal_selesai]" id="bk-{{ $i }}-selesai" value="{{ $b['tanggal_selesai'] ?? '' }}" class="form-control form-control-sm {{ $galat('tanggal_selesai') ? 'is-invalid' : '' }}">
            @if ($galat('tanggal_selesai'))<div class="invalid-feedback">{{ $galat('tanggal_selesai') }}</div>@endif
        </div>
        <div class="col-12">
            <input type="text" name="baris[{{ $i }}][nomor_kontrak]" value="{{ $b['nomor_kontrak'] ?? '' }}" maxlength="100" class="form-control form-control-sm mb-2"
                placeholder="Nomor kontrak (opsional), contoh: 012/SDM/PKWT/2025" aria-label="Nomor kontrak">
            <input type="text" name="baris[{{ $i }}][keterangan]" value="{{ $b['keterangan'] ?? '' }}" maxlength="500" class="form-control form-control-sm"
                placeholder="Keterangan (opsional), contoh: Lulus training, lanjut kontrak" aria-label="Keterangan">
        </div>
    </div>
</div>
