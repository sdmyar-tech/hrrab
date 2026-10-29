@extends($layout)

@section('content')
@php
    $ik = \App\Support\Absensi\TampilanAbsensi::IKON;
    $alasanCepat = ['Hujan deras', 'Banjir / jalan tergenang', 'Gangguan listrik', 'Acara yayasan', 'Rapat di luar'];
@endphp
<main class="dashboard-content">
    <div class="container-fluid px-3 px-lg-4 py-4">
        <div class="page-heading">
            <div class="page-heading-copy">
                <span class="page-icon"><i class="bi {{ $ik['izin'] }}"></i></span>
                <div>
                    <p class="eyebrow mb-1">Absensi</p>
                    <h1 class="h3 mb-1">Izin Telat & Pulang Cepat</h1>
                    <p class="text-muted mb-0">Beri kelonggaran pada hari tertentu (mis. hujan deras). Yang datang dalam batas izin dicatat <strong>Telat Izin</strong>, bukan Telat.</p>
                </div>
            </div>
            <div class="heading-actions">
                @include('partials.tombol-ekspor', ['eksporRoute' => 'admin.absensi.izin.export', 'eksporParam' => ['bulan' => $bulan->format('Y-m')]])
            </div>
        </div>

        @include('admin.absensi._nav')

        <div class="row g-3 align-items-start">
            <div class="col-12 col-xl-5">
                <section class="panel p-3 p-md-4">
                    <h2 class="h6 fw-bold mb-3"><i class="bi bi-plus-circle me-1"></i>Beri Izin</h2>
                    <form action="{{ route('admin.absensi.izin.store') }}" method="POST" id="formIzin">
                        @csrf
                        <p class="fw-semibold small mb-2">1. Jenis izin</p>
                        <div class="abs-pilihan mb-3">
                            @foreach (\App\Models\IzinAbsensi::JENIS as $k => $label)
                                <input type="radio" name="jenis" value="{{ $k }}" id="j-{{ $k }}" @checked(old('jenis', 'telat') === $k)
                                    data-menit="{{ $k === 'telat' ? config('hrrab.absensi.izin_telat_menit', 60) : config('hrrab.absensi.izin_pulang_menit', 60) }}">
                                <label for="j-{{ $k }}">
                                    <i class="bi {{ $k === 'telat' ? 'bi-alarm' : 'bi-door-open' }}"></i><strong>{{ $label }}</strong>
                                    <span class="text-muted">{{ $k === 'telat' ? 'Boleh datang terlambat' : 'Boleh pulang lebih awal' }}</span>
                                </label>
                            @endforeach
                        </div>

                        <p class="fw-semibold small mb-2">2. Kapan & berapa lama</p>
                        <div class="row g-2 mb-3">
                            <div class="col-7">
                                <label class="form-label small mb-1" for="i-tanggal">Tanggal</label>
                                <input type="date" name="tanggal" id="i-tanggal" class="form-control @error('tanggal') is-invalid @enderror" value="{{ old('tanggal', $tanggalAwal) }}" required>
                            </div>
                            <div class="col-5">
                                <label class="form-label small mb-1" for="i-menit">Kelonggaran</label>
                                <div class="input-group">
                                    <input type="number" name="menit" id="i-menit" class="form-control @error('menit') is-invalid @enderror" min="1" max="480"
                                        value="{{ old('menit', config('hrrab.absensi.izin_telat_menit', 60)) }}" required>
                                    <span class="input-group-text">menit</span>
                                </div>
                            </div>
                        </div>

                        <p class="fw-semibold small mb-2">3. Untuk siapa</p>
                        <div class="btn-group w-100 mb-2" role="group" aria-label="Sasaran izin">
                            @foreach (['semua' => 'Semua pegawai', 'unit' => 'Satu unit', 'pegawai' => 'Pilih pegawai'] as $k => $label)
                                <input type="radio" class="btn-check" name="sasaran" value="{{ $k }}" id="s-{{ $k }}" @checked(old('sasaran', 'semua') === $k)>
                                <label class="btn btn-outline-secondary btn-sm" for="s-{{ $k }}">{{ $label }}</label>
                            @endforeach
                        </div>
                        <div class="mb-3" data-sasaran="unit">
                            <select name="unit_kerja" class="form-select @error('unit_kerja') is-invalid @enderror" aria-label="Unit">
                                <option value="">Pilih unit...</option>
                                @foreach ($unitOpsi as $u)<option value="{{ $u }}" @selected(old('unit_kerja') === $u)>{{ $u }}</option>@endforeach
                            </select>
                        </div>
                        <div class="mb-3" data-sasaran="pegawai">
                            <input type="search" class="form-control form-control-sm mb-1" placeholder="Saring nama, contoh: Siti" data-cari-izin aria-label="Cari pegawai">
                            <div class="border rounded-3 p-2" style="max-height:200px; overflow-y:auto;">
                                @forelse ($pegawais as $p)
                                    <div class="form-check" data-nama="{{ strtolower($p->nama_lengkap) }}">
                                        <input class="form-check-input" type="checkbox" name="pegawai_ids[]" value="{{ $p->id }}" id="ip-{{ $p->id }}" @checked(in_array($p->id, old('pegawai_ids', [])))>
                                        <label class="form-check-label small" for="ip-{{ $p->id }}">{{ $p->nama_lengkap }}</label>
                                    </div>
                                @empty
                                    <p class="small text-muted mb-0">Belum ada pegawai yang ikut absensi.</p>
                                @endforelse
                            </div>
                        </div>

                        <p class="fw-semibold small mb-2">4. Alasan</p>
                        <div class="d-flex flex-wrap gap-1 mb-2">
                            @foreach ($alasanCepat as $a)<button type="button" class="abs-chip" data-alasan-izin="{{ $a }}">{{ $a }}</button>@endforeach
                        </div>
                        <input type="text" name="alasan" class="form-control mb-3 @error('alasan') is-invalid @enderror" maxlength="255" value="{{ old('alasan') }}" placeholder="Atau tulis alasan lain, contoh: banjir di jalan utama" required>

                        <button type="submit" class="btn btn-primary w-100"><i class="bi bi-check2 me-1"></i> Simpan Izin</button>
                    </form>
                </section>
            </div>

            <div class="col-12 col-xl-7">
                <section class="panel">
                    <div class="panel-header panel-header-baris">
                        <a href="{{ route('admin.absensi.izin', ['bulan' => $bulan->copy()->subMonth()->format('Y-m')]) }}" class="btn btn-light btn-sm" aria-label="Bulan sebelumnya"><i class="bi bi-chevron-left"></i></a>
                        <h2 class="h6 fw-bold mb-0 mx-auto">Izin {{ $bulan->translatedFormat('F Y') }}</h2>
                        <a href="{{ route('admin.absensi.izin', ['bulan' => $bulan->copy()->addMonth()->format('Y-m')]) }}" class="btn btn-light btn-sm" aria-label="Bulan berikutnya"><i class="bi bi-chevron-right"></i></a>
                    </div>
                    <div class="list-group list-group-flush">
                        @forelse ($izins as $z)
                            <div class="list-group-item d-flex align-items-center gap-3">
                                <div class="text-center flex-none" style="width:48px;">
                                    <div class="fw-bold fs-5 lh-1">{{ $z->tanggal->format('d') }}</div>
                                    <small class="text-muted">{{ $z->tanggal->translatedFormat('D') }}</small>
                                </div>
                                <div class="flex-grow-1 min-w-0">
                                    <span class="badge {{ $z->jenis === 'telat' ? 'text-bg-warning' : 'text-bg-info' }}">{{ \App\Models\IzinAbsensi::JENIS[$z->jenis] }} · {{ $z->menit }} menit</span>
                                    <div class="small mt-1"><strong>{{ $z->sasaranLabel() }}</strong> — {{ $z->alasan }}</div>
                                    <small class="text-muted">oleh {{ $z->pembuat?->name ?? '-' }}</small>
                                </div>
                                <a href="{{ route('admin.absensi.harian', ['tanggal' => $z->tanggal->toDateString()]) }}" class="btn btn-light btn-sm" title="Lihat absensi tanggal ini" aria-label="Lihat absensi tanggal ini"><i class="bi {{ $ik['harian'] }}"></i></a>
                                <form action="{{ route('admin.absensi.izin.destroy', $z) }}" method="POST" class="m-0">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-sm" title="Hapus izin" aria-label="Hapus izin"
                                        data-confirm="true" data-icon="warning" data-confirm-color="#dc3545" data-confirm-text="Ya, hapus"
                                        data-title="Hapus izin?" data-text="Status absensi tanggal itu dihitung ulang tanpa izin ini.">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        @empty
                            @include('partials.kosong', ['teks' => 'Belum ada izin pada bulan ini.', 'ikonKosong' => 'bi-cloud-sun'])
                        @endforelse
                    </div>
                </section>
            </div>
        </div>
    </div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('formIzin');
        const sinkron = function () {
            const s = form.querySelector('[name="sasaran"]:checked').value;
            form.querySelectorAll('[data-sasaran]').forEach((el) => { el.hidden = el.dataset.sasaran !== s; });
        };
        form.querySelectorAll('[name="sasaran"]').forEach((r) => r.addEventListener('change', sinkron));
        sinkron();
        form.querySelectorAll('[name="jenis"]').forEach((r) => r.addEventListener('change', function () { form.menit.value = r.dataset.menit; }));
        form.querySelectorAll('[data-alasan-izin]').forEach((b) => b.addEventListener('click', function () {
            form.alasan.value = b.dataset.alasanIzin;
            form.querySelectorAll('[data-alasan-izin]').forEach((x) => x.classList.toggle('aktif', x === b));
        }));
        form.querySelector('[data-cari-izin]').addEventListener('input', function () {
            const kata = this.value.trim().toLowerCase();
            form.querySelectorAll('[data-nama]').forEach((el) => { el.hidden = kata !== '' && !el.dataset.nama.includes(kata); });
        });
    });
</script>
@endsection
