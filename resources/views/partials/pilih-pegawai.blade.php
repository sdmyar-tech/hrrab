{{--
    Dropdown pemilihan pegawai (sumber: pegawai aktif) + kotak cari.
    Parameter: name, id?, nilai ('id' | 'nama', bawaan 'id'), terpilih?, wajib?, kosong? (label opsi kosong yang bermakna, mis. "Tidak ada"),
    placeholder? (bawaan "-- Pilih pegawai --"), utama? (daftar nama/id yang dikelompokkan di atas), labelUtama?, kecuali? (id pegawai yang disembunyikan), kelas?
    Nilai lama yang tidak ada di daftar (mis. teks bebas data lama) tetap ditampilkan sebagai "(nilai saat ini)".
--}}
@php
    $nilai ??= 'id';
    $id ??= 'pp-' . $name;
    $terpilih = (string) ($terpilih ?? '');
    $utama = collect($utama ?? [])->map(fn ($v) => (string) $v)->all();
    $semua = \App\Models\DataPegawai::opsiPilihan()->reject(fn ($p) => in_array($p['id'], (array) ($kecuali ?? []), true));
    $nilaiDari = fn ($p) => (string) $p[$nilai];
    [$grupUtama, $grupLain] = $semua->partition(fn ($p) => in_array($nilaiDari($p), $utama, true));
    $adaDiDaftar = $terpilih !== '' && $semua->contains(fn ($p) => $nilaiDari($p) === $terpilih);
    $opsi = fn ($p) => '<option value="' . e($nilaiDari($p)) . '" data-cari="' . e(mb_strtolower($p['nama'] . ' ' . $p['nip'] . ' ' . $p['jabatan'] . ' ' . $p['unit'])) . '"'
        . ($nilaiDari($p) === $terpilih ? ' selected' : '') . '>' . e($p['label']) . '</option>';
@endphp
<div class="pilih-pegawai-wadah">
    <input type="search" class="form-control form-control-sm mb-1" placeholder="Cari pegawai, contoh: Siti atau SMK" data-saring-pilihan="#{{ $id }}" aria-label="Cari pegawai" autocomplete="off">
    <select name="{{ $name }}" id="{{ $id }}" class="form-select {{ $kelas ?? '' }} @error($name) is-invalid @enderror" @if ($wajib ?? false) required @endif>
        @if (! empty($kosong))
            <option value="" @selected($terpilih === '')>{{ $kosong }}</option>
        @else
            <option value="" @selected($terpilih === '')>{{ $placeholder ?? '-- Pilih pegawai --' }}</option>
        @endif
        @if ($terpilih !== '' && ! $adaDiDaftar)
            <option value="{{ $terpilih }}" selected>{{ $terpilih }} (nilai saat ini)</option>
        @endif
        @if ($grupUtama->isNotEmpty())
            <optgroup label="{{ $labelUtama ?? 'Disarankan' }}">{!! $grupUtama->map($opsi)->implode('') !!}</optgroup>
            <optgroup label="Pegawai lain">{!! $grupLain->map($opsi)->implode('') !!}</optgroup>
        @else
            {!! $grupLain->map($opsi)->implode('') !!}
        @endif
    </select>
    @error($name)<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>

@once
    <script>
        // Kotak cari di atas dropdown pegawai: sembunyikan opsi yang tidak cocok (opsi terpilih & opsi kosong tetap tampil).
        document.addEventListener('input', function (e) {
            const kotak = e.target.closest('[data-saring-pilihan]');
            if (!kotak) return;
            const pilihan = document.querySelector(kotak.dataset.saringPilihan);
            const kata = kotak.value.trim().toLowerCase();
            Array.from(pilihan.options).forEach((o) => {
                o.hidden = o.value !== '' && !o.selected && !!kata && !(o.dataset.cari || o.text.toLowerCase()).includes(kata);
            });
            pilihan.querySelectorAll('optgroup').forEach((g) => { g.hidden = !Array.from(g.children).some((o) => !o.hidden); });
        });
    </script>
@endonce
