@extends('layouts.admin.app')

@section('content')
@php
    use App\Support\EditorData;
    $baru = ! $model->exists;
    $tautan = ! $baru && isset($k['tautan']) ? ($k['tautan'])($model) : null;
    $kembaliKe = $kembali ?? route('admin.data.daftar', $entitas);
@endphp
<div class="dashboard-content">
    <div class="container-fluid px-3 px-lg-4 py-4">
        <div class="page-heading">
            <div class="page-heading-copy">
                <span class="page-icon"><i class="bi {{ $k['ikon'] }}"></i></span>
                <div class="min-w-0">
                    <p class="eyebrow mb-1"><a href="{{ route('admin.data.daftar', $entitas) }}" class="text-reset text-decoration-none"><i class="bi bi-arrow-left me-1"></i>{{ $k['label'] }}</a> · {{ $baru ? 'Data baru' : 'ID ' . $model->getKey() }}</p>
                    <h1 class="h4 mb-1">{{ $baru ? 'Tambah ' . $k['label'] : ($k['judul'])($model) }}</h1>
                    <p class="text-muted small mb-0">{{ $baru ? 'Kolom yang dikosongkan memakai nilai bawaan database.' : 'Dibuat ' . ($model->created_at?->format('d-m-Y H:i') ?? '–') . ' · diubah ' . ($model->updated_at?->format('d-m-Y H:i') ?? '–') }}</p>
                </div>
            </div>
            <div class="heading-actions d-flex flex-wrap gap-2">
                @if ($tautan)
                    <a href="{{ $tautan }}" class="btn btn-light"><i class="bi bi-box-arrow-up-right me-1"></i>Buka di halaman asal</a>
                @endif
                @unless ($baru)
                <form action="{{ route('admin.data.destroy', [$entitas, $model->getKey()]) }}" method="POST"
                    data-confirm="Hapus permanen {{ $k['label'] }} #{{ $model->getKey() }}?{{ ($ikut = collect($dampak)->where('aturan', 'CASCADE')->sum('jumlah')) ? ' ' . $ikut . ' data terkait IKUT TERHAPUS.' : '' }} Tindakan ini tidak bisa dibatalkan.">
                    @csrf @method('DELETE')
                    <input type="hidden" name="kembali" value="{{ $kembali }}">
                    <button class="btn btn-outline-danger"><i class="bi bi-trash me-1"></i>Hapus</button>
                </form>
                @endunless
            </div>
        </div>

        @include('admin.editor-data._peringatan')

        @if ($dampak)
            @php
                $aturanLabel = ['CASCADE' => ['ikut TERHAPUS', 'text-danger'], 'SET NULL' => ['dikosongkan rujukannya', 'text-warning-emphasis'],
                    'RESTRICT' => ['MENGHALANGI penghapusan', 'text-secondary'], 'NO ACTION' => ['MENGHALANGI penghapusan', 'text-secondary']];
            @endphp
            <div class="alert alert-light border small" data-dampak-hapus>
                <strong><i class="bi bi-diagram-2 me-1"></i>Data lain yang terhubung</strong> — bila data ini dihapus:
                <ul class="mb-0 mt-1">
                    @foreach ($dampak as $d)
                        @php [$teks, $kelas] = $aturanLabel[$d['aturan']] ?? [$d['aturan'], '']; @endphp
                        <li><strong>{{ $d['jumlah'] }}</strong> baris <code>{{ $d['tabel'] }}</code> <span class="{{ $kelas }} fw-semibold">{{ $teks }}</span></li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Galat isian ditampilkan di halaman ini; banner global tidak perlu. --}}
        @section('galat-di-halaman', '1')
        @if ($errors->any())
            <div class="alert alert-danger small"><ul class="mb-0 ps-3">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
        @endif

        <form method="POST" action="{{ $baru ? route('admin.data.store', $entitas) : route('admin.data.update', [$entitas, $model->getKey()]) }}" id="formEditorData"
            data-confirm="{{ $baru ? 'Simpan ' . $k['label'] . ' baru?' : 'Simpan perubahan langsung ke ' . $k['label'] . ' #' . $model->getKey() . '?' }}">
            @csrf @unless ($baru) @method('PUT') @endunless
            <input type="hidden" name="kembali" value="{{ $kembali }}">

            <section class="panel p-3 p-lg-4">
                @foreach ($kolom as $kol)
                    @php
                        $n = $kol['nama'];
                        $nilai = old("d.$n", EditorData::nilaiInput($model, $kol));
                        $id = 'f-' . $n;
                        $bisaNull = ! $kol['wajib'] && ! $kol['baca'];
                        $kosong = (bool) old("null.$n", false);
                    @endphp
                    @continue($baru && $kol['baca'])
                    <div class="kolom-data row g-2 align-items-start" data-kolom>
                        <div class="col-md-4 col-xl-3">
                            <label class="form-label mb-0 fw-semibold" for="{{ $id }}">{{ $kol['label'] }}</label>
                            <div class="small text-muted"><code class="nama-kolom">{{ $n }}</code> · {{ $kol['tipe'] }}@if ($kol['wajib']) · <span class="text-danger">wajib</span>@endif</div>
                        </div>
                        <div class="col-md-8 col-xl-9">
                            @if ($kol['baca'])
                                <input type="text" class="form-control-plaintext py-1" id="{{ $id }}" value="{{ EditorData::nilaiRingkas($model, $n) }}" readonly>
                            @else
                                <div class="d-flex gap-2 align-items-start">
                                    <div class="flex-grow-1">
                                        @switch($kol['jenis'])
                                            @case('boolean')
                                                <select name="d[{{ $n }}]" id="{{ $id }}" class="form-select" data-nilai-awal="{{ $nilai }}">
                                                    @if ($bisaNull)<option value="" @selected($nilai === '')>(kosong)</option>@endif
                                                    <option value="1" @selected($nilai === '1')>Ya (1)</option>
                                                    <option value="0" @selected($nilai === '0')>Tidak (0)</option>
                                                </select>
                                                @break
                                            @case('enum')
                                                <select name="d[{{ $n }}]" id="{{ $id }}" class="form-select" data-nilai-awal="{{ $nilai }}">
                                                    @if ($bisaNull || $nilai === '')<option value="">(kosong)</option>@endif
                                                    @foreach ($kol['opsi'] as $o)<option value="{{ $o }}" @selected($nilai === $o)>{{ $o }}</option>@endforeach
                                                </select>
                                                @break
                                            @case('pilihan')
                                                <select name="d[{{ $n }}]" id="{{ $id }}" class="form-select" data-nilai-awal="{{ $nilai }}">
                                                    @if ($bisaNull || $nilai === '')<option value="">(kosong)</option>@endif
                                                    @if ($nilai !== '' && ! array_key_exists($nilai, $kol['pilihan']))<option value="{{ $nilai }}" selected>{{ $nilai }} (nilai saat ini)</option>@endif
                                                    @foreach ($kol['pilihan'] as $v => $lbl)<option value="{{ $v }}" @selected((string) $nilai === (string) $v)>{{ $lbl }}</option>@endforeach
                                                </select>
                                                @break
                                            @case('fk')
                                                @if ($kol['fk']['opsi'] !== null)
                                                    <select name="d[{{ $n }}]" id="{{ $id }}" class="form-select" data-nilai-awal="{{ $nilai }}">
                                                        <option value="">(kosong)</option>
                                                        @foreach ($kol['fk']['opsi'] as $fid => $label)<option value="{{ $fid }}" @selected((string) $nilai === (string) $fid)>{{ $label }}</option>@endforeach
                                                    </select>
                                                @else
                                                    <input type="number" name="d[{{ $n }}]" id="{{ $id }}" class="form-control" value="{{ $nilai }}" data-nilai-awal="{{ $nilai }}">
                                                @endif
                                                <div class="form-text">ID dari tabel <code>{{ $kol['fk']['tabel'] }}</code></div>
                                                @break
                                            @case('date')
                                                <input type="date" name="d[{{ $n }}]" id="{{ $id }}" class="form-control" value="{{ $nilai }}" data-nilai-awal="{{ $nilai }}">
                                                @break
                                            @case('datetime')
                                                <input type="datetime-local" step="1" name="d[{{ $n }}]" id="{{ $id }}" class="form-control" value="{{ $nilai }}" data-nilai-awal="{{ $nilai }}">
                                                @break
                                            @case('time')
                                                <input type="time" step="1" name="d[{{ $n }}]" id="{{ $id }}" class="form-control" value="{{ $nilai }}" data-nilai-awal="{{ $nilai }}">
                                                @break
                                            @case('angka')
                                                <input type="number" step="any" name="d[{{ $n }}]" id="{{ $id }}" class="form-control" value="{{ $nilai }}" data-nilai-awal="{{ $nilai }}">
                                                @break
                                            @case('json')
                                                <textarea name="d[{{ $n }}]" id="{{ $id }}" rows="{{ min(12, max(3, substr_count($nilai, "\n") + 1)) }}" class="form-control json" data-nilai-awal="{{ $nilai }}">{{ $nilai }}</textarea>
                                                <div class="form-text">Format JSON.</div>
                                                @break
                                            @case('teks_panjang')
                                                <textarea name="d[{{ $n }}]" id="{{ $id }}" rows="3" class="form-control" data-nilai-awal="{{ $nilai }}">{{ $nilai }}</textarea>
                                                @break
                                            @default
                                                <input type="text" name="d[{{ $n }}]" id="{{ $id }}" class="form-control" value="{{ $nilai }}" data-nilai-awal="{{ $nilai }}"
                                                    @if ($kol['saran']) list="saran-{{ $n }}" @endif>
                                                @if ($kol['saran'])
                                                    <datalist id="saran-{{ $n }}">@foreach ($kol['saran'] as $s)<option value="{{ $s }}"></option>@endforeach</datalist>
                                                @endif
                                        @endswitch
                                        @error("d.$n")<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                                    </div>
                                    @if ($bisaNull)
                                        <div class="form-check text-nowrap mt-2" title="Simpan sebagai NULL (kosong)">
                                            <input class="form-check-input" type="checkbox" name="null[{{ $n }}]" value="1" id="null-{{ $n }}" data-null @checked($kosong)>
                                            <label class="form-check-label small text-muted" for="null-{{ $n }}">NULL</label>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </section>

            <div class="aksi-lengket d-flex flex-wrap gap-2 justify-content-end mt-3">
                <span class="me-auto small text-muted align-self-center" data-jumlah-ubah>Belum ada perubahan.</span>
                <a href="{{ $kembaliKe }}" class="btn btn-light">Batal</a>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check2 me-1"></i>{{ $baru ? 'Simpan Data Baru' : 'Simpan Perubahan' }}</button>
            </div>
        </form>
    </div>
</div>
@include('admin.editor-data._gaya')
<style>
    .aksi-lengket { position: sticky; bottom: 0; background: var(--bs-body-bg); padding: .75rem 0; border-top: 1px solid var(--bs-border-color); z-index: 5; }
</style>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('formEditorData');
        // Tandai baris yang berubah agar admin tahu persis apa yang akan disimpan.
        function tandai() {
            let n = 0;
            form.querySelectorAll('[data-kolom]').forEach((baris) => {
                const input = baris.querySelector('[data-nilai-awal]');
                const nul = baris.querySelector('[data-null]');
                if (!input) return;
                input.disabled = !!(nul && nul.checked);
                const berubah = (nul && nul.checked && input.dataset.nilaiAwal !== '') || (!input.disabled && input.value !== input.dataset.nilaiAwal);
                baris.classList.toggle('berubah', berubah);
                if (berubah) n++;
            });
            form.querySelector('[data-jumlah-ubah]').textContent = n ? n + ' kolom akan diubah.' : 'Belum ada perubahan.';
        }
        form.addEventListener('input', tandai);
        form.addEventListener('change', tandai);
        tandai();
    });
</script>
@endsection
