@extends($layout)

@section('content')
@php
    $baru = ! $pengumuman->exists;
    $jadwal = old('jadwal', $pengumuman->jadwal ?? 'sekali');
    $sasaran = old('sasaran_unit', $pengumuman->sasaran_unit ?? []);
@endphp
<div class="dashboard-content">
    <div class="container-fluid px-3 px-lg-4 py-4">
        <div class="page-heading">
            <div class="page-heading-copy">
                <span class="page-icon"><i class="bi bi-megaphone"></i></span>
                <div>
                    <p class="eyebrow mb-1">Pengumuman</p>
                    <h1 class="h3 mb-1">{{ $baru ? 'Buat Pengumuman' : 'Ubah Pengumuman' }}</h1>
                    <p class="text-muted mb-0">Contoh: Doa pagi setiap hari, kajian setiap Rabu, acara besar yayasan, atau kabar duka.</p>
                </div>
            </div>
        </div>

        <form action="{{ $baru ? route('pengumuman.store') : route('pengumuman.update', $pengumuman) }}" method="POST" id="formPengumuman"
            data-confirm="{{ $baru ? 'Terbitkan pengumuman ini?' : 'Simpan perubahan pengumuman?' }}">
            @csrf
            @unless ($baru) @method('PUT') @endunless

            {{-- Galat isian ditampilkan di halaman ini; banner global tidak perlu. --}}
            @section('galat-di-halaman', '1')
            @if ($errors->any())
                <div class="alert alert-danger small"><ul class="mb-0 ps-3">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
            @endif

            <div class="row g-3 align-items-start">
                <div class="col-12 col-lg-8">
                    <section class="panel p-4">
                        <h2 class="h6 fw-bold mb-3">Isi Pengumuman</h2>
                        <div class="mb-3">
                            <label class="form-label" for="judul">Judul</label>
                            <input type="text" name="judul" id="judul" class="form-control @error('judul') is-invalid @enderror" maxlength="150" required
                                value="{{ old('judul', $pengumuman->judul) }}" placeholder="mis. Kajian Rutin Rabu Sore">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Kategori</label>
                            <div class="d-flex flex-wrap gap-2">
                                @foreach (\App\Models\Pengumuman::KATEGORI as $key => $k)
                                    <input type="radio" class="btn-check" name="kategori" id="kat-{{ $key }}" value="{{ $key }}" @checked(old('kategori', $pengumuman->kategori) === $key) required>
                                    <label class="btn btn-outline-secondary btn-sm" for="kat-{{ $key }}"><i class="bi {{ $k['ikon'] }} me-1"></i>{{ $k['label'] }}</label>
                                @endforeach
                            </div>
                        </div>
                        <div>
                            <label class="form-label" for="isi">Isi</label>
                            <textarea name="isi" id="isi" rows="7" class="form-control @error('isi') is-invalid @enderror" maxlength="3000" required
                                placeholder="Tuliskan informasi lengkap: tujuan, susunan acara, pakaian, dll.">{{ old('isi', $pengumuman->isi) }}</textarea>
                        </div>
                    </section>

                    @if ($absen)
                        @include('pengumuman._form-absen')
                    @endif
                </div>

                <div class="col-12 col-lg-4 d-flex flex-column gap-3">
                    <section class="panel p-4">
                        <h2 class="h6 fw-bold mb-3">Waktu & Tempat <span class="text-muted fw-normal small">(opsional)</span></h2>
                        <div class="btn-group w-100 mb-3" role="group" aria-label="Jenis jadwal">
                            @foreach (['sekali' => 'Sekali', 'harian' => 'Setiap hari', 'mingguan' => 'Mingguan'] as $val => $label)
                                <input type="radio" class="btn-check" name="jadwal" id="jadwal-{{ $val }}" value="{{ $val }}" @checked($jadwal === $val)>
                                <label class="btn btn-outline-primary btn-sm" for="jadwal-{{ $val }}">{{ $label }}</label>
                            @endforeach
                        </div>
                        <div class="mb-3" data-jadwal="sekali">
                            <label class="form-label small" for="tanggal">Tanggal</label>
                            <input type="date" name="tanggal" id="tanggal" class="form-control" @unless (auth()->user()->isAdmin()) min="{{ now()->format('Y-m-d') }}" @endunless
                                value="{{ old('tanggal', $pengumuman->tanggal?->format('Y-m-d')) }}">
                        </div>
                        <div class="mb-3" data-jadwal="mingguan">
                            <label class="form-label small" for="hari">Setiap hari</label>
                            <select name="hari" id="hari" class="form-select">
                                @foreach (\App\Models\Pengumuman::HARI as $i => $h)
                                    <option value="{{ $i }}" @selected((string) old('hari', $pengumuman->hari ?? 3) === (string) $i)>{{ $h }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label small" for="jam_mulai">Jam mulai</label>
                                <input type="time" name="jam_mulai" id="jam_mulai" class="form-control" value="{{ old('jam_mulai', $pengumuman->jam_mulai ? substr($pengumuman->jam_mulai, 0, 5) : '') }}">
                            </div>
                            <div class="col-6">
                                <label class="form-label small" for="jam_selesai">Jam selesai</label>
                                <input type="time" name="jam_selesai" id="jam_selesai" class="form-control" value="{{ old('jam_selesai', $pengumuman->jam_selesai ? substr($pengumuman->jam_selesai, 0, 5) : '') }}">
                            </div>
                        </div>
                        <div>
                            <label class="form-label small" for="lokasi">Lokasi</label>
                            <input type="text" name="lokasi" id="lokasi" class="form-control" maxlength="150" value="{{ old('lokasi', $pengumuman->lokasi) }}" placeholder="mis. Masjid Yayasan, atau tempel link Google Maps">
                        </div>
                    </section>

                    <section class="panel p-4">
                        <h2 class="h6 fw-bold mb-3">Ditujukan Kepada</h2>
                        @if (! $wajibUnit)
                            <p class="small text-muted mb-2">Kosongkan untuk <strong>semua pegawai</strong>, atau pilih unit tertentu.</p>
                        @else
                            <p class="small text-muted mb-2">Pilih unit yang Anda pimpin.</p>
                        @endif
                        <div class="form-check border-bottom pb-2 mb-2">
                            <input class="form-check-input" type="checkbox" id="pilih-semua-unit" data-pilih-semua="sasaran_unit[]">
                            <label class="form-check-label small fw-semibold" for="pilih-semua-unit">
                                Pilih semua unit <span class="text-muted fw-normal" data-jumlah-terpilih></span>
                            </label>
                        </div>
                        <div class="border rounded p-2" style="max-height:200px; overflow-y:auto;">
                            @foreach ($pilihanUnit as $u)
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="sasaran_unit[]" value="{{ $u }}" id="unit-{{ $loop->index }}" @checked(in_array($u, $sasaran, true))>
                                    <label class="form-check-label small" for="unit-{{ $loop->index }}">{{ $u }}</label>
                                </div>
                            @endforeach
                        </div>
                        <div class="form-check form-switch mt-3">
                            <input class="form-check-input" type="checkbox" role="switch" name="penting" value="1" id="penting" @checked(old('penting', $pengumuman->penting))>
                            <label class="form-check-label small" for="penting"><i class="bi bi-pin-angle me-1"></i>Sematkan sebagai penting</label>
                        </div>
                        <div class="mt-3">
                            <label class="form-label small" for="tampil_sampai">Tampil sampai <span class="text-muted">(opsional)</span></label>
                            <input type="date" name="tampil_sampai" id="tampil_sampai" class="form-control" @unless (auth()->user()->isAdmin()) min="{{ now()->format('Y-m-d') }}" @endunless
                                value="{{ old('tampil_sampai', $pengumuman->tampil_sampai?->format('Y-m-d')) }}">
                            <div class="form-text">Kosong: acara sekali tampil sampai hari-H (info tanpa tanggal 14 hari); kegiatan rutin tampil terus.</div>
                        </div>
                    </section>

                    <div class="d-flex gap-2">
                        <a href="{{ route('pengumuman.index') }}" class="btn btn-outline-secondary flex-fill">Batal</a>
                        <button type="submit" class="btn btn-primary flex-fill"><i class="bi bi-send me-1"></i>{{ $baru ? 'Terbitkan' : 'Simpan' }}</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        function sinkron() {
            const jadwal = document.querySelector('input[name="jadwal"]:checked').value;
            document.querySelectorAll('[data-jadwal]').forEach(function (el) { el.hidden = el.dataset.jadwal !== jadwal; });
        }
        document.querySelectorAll('input[name="jadwal"]').forEach(function (r) { r.addEventListener('change', sinkron); });
        sinkron();
    });
</script>
@include('partials.pilih-semua')
@endsection
