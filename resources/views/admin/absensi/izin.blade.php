@extends($layout)

@section('content')
<main class="dashboard-content">
    <div class="container-fluid px-3 px-lg-4 py-4">
        <div class="page-heading">
            <div class="page-heading-copy">
                <span class="page-icon"><i class="bi bi-cloud-drizzle"></i></span>
                <div>
                    <p class="eyebrow mb-1">Absensi</p>
                    <h1 class="h3 mb-1">Izin Telat & Pulang Cepat</h1>
                    <p class="text-muted mb-0">Kelonggaran pada tanggal tertentu, mis. hujan deras. Pegawai yang masuk dalam batas izin berstatus <strong>Telat Izin</strong>, bukan Telat.</p>
                </div>
            </div>
        </div>

        @include('admin.absensi._nav')

        <div class="row g-3 align-items-start">
            <div class="col-12 col-xl-5">
                <section class="panel p-4">
                    <h2 class="h6 fw-bold mb-3"><i class="bi bi-plus-circle me-1"></i>Buat Izin</h2>
                    <form action="{{ route('admin.absensi.izin.store') }}" method="POST" id="formIzin">
                        @csrf
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label small" for="i-tanggal">Tanggal</label>
                                <input type="date" name="tanggal" id="i-tanggal" class="form-control @error('tanggal') is-invalid @enderror" value="{{ old('tanggal', now()->toDateString()) }}" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label small" for="i-menit">Kelonggaran (menit)</label>
                                <input type="number" name="menit" id="i-menit" class="form-control @error('menit') is-invalid @enderror" min="1" max="480"
                                    value="{{ old('menit', config('hrrab.absensi.izin_telat_menit', 60)) }}" required>
                            </div>
                        </div>

                        <label class="form-label small">Jenis</label>
                        <div class="btn-group w-100 mb-3" role="group">
                            @foreach (\App\Models\IzinAbsensi::JENIS as $k => $label)
                                <input type="radio" class="btn-check" name="jenis" value="{{ $k }}" id="j-{{ $k }}" @checked(old('jenis', 'telat') === $k)
                                    data-menit="{{ $k === 'telat' ? config('hrrab.absensi.izin_telat_menit', 60) : config('hrrab.absensi.izin_pulang_menit', 60) }}">
                                <label class="btn btn-outline-primary" for="j-{{ $k }}">{{ $label }}</label>
                            @endforeach
                        </div>

                        <label class="form-label small">Berlaku untuk</label>
                        <div class="btn-group w-100 mb-2" role="group">
                            @foreach (['semua' => 'Semua pegawai', 'unit' => 'Satu unit', 'pegawai' => 'Pegawai tertentu'] as $k => $label)
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
                            <input type="search" class="form-control form-control-sm mb-1" placeholder="Cari nama..." data-cari-izin aria-label="Cari pegawai">
                            <div class="border rounded p-2" style="max-height:200px; overflow-y:auto;">
                                @foreach ($pegawais as $p)
                                    <div class="form-check" data-nama="{{ strtolower($p->nama_lengkap) }}">
                                        <input class="form-check-input" type="checkbox" name="pegawai_ids[]" value="{{ $p->id }}" id="ip-{{ $p->id }}" @checked(in_array($p->id, old('pegawai_ids', [])))>
                                        <label class="form-check-label small" for="ip-{{ $p->id }}">{{ $p->nama_lengkap }}</label>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small" for="i-alasan">Alasan</label>
                            <input type="text" name="alasan" id="i-alasan" class="form-control @error('alasan') is-invalid @enderror" maxlength="255" value="{{ old('alasan') }}" placeholder="mis. Hujan deras pagi hari" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100"><i class="bi bi-check2 me-1"></i> Simpan Izin</button>
                    </form>
                </section>
            </div>

            <div class="col-12 col-xl-7">
                <section class="panel">
                    <div class="panel-header flex-wrap gap-2">
                        <h2 class="h6 fw-bold mb-0"><i class="bi bi-list-ul me-1"></i>Izin {{ $bulan->translatedFormat('F Y') }}</h2>
                        <div class="d-flex gap-1">
                            <a href="{{ route('admin.absensi.izin', ['bulan' => $bulan->copy()->subMonth()->format('Y-m')]) }}" class="btn btn-outline-secondary btn-sm" title="Bulan sebelumnya"><i class="bi bi-chevron-left"></i></a>
                            <a href="{{ route('admin.absensi.izin', ['bulan' => $bulan->copy()->addMonth()->format('Y-m')]) }}" class="btn btn-outline-secondary btn-sm" title="Bulan berikutnya"><i class="bi bi-chevron-right"></i></a>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead><tr><th>Tanggal</th><th>Jenis</th><th>Untuk</th><th>Alasan</th><th class="text-end">Aksi</th></tr></thead>
                            <tbody>
                                @forelse ($izins as $z)
                                    <tr>
                                        <td class="text-nowrap">{{ $z->tanggal->translatedFormat('D, d M Y') }}</td>
                                        <td><span class="badge {{ $z->jenis === 'telat' ? 'text-bg-warning' : 'text-bg-info' }}">{{ \App\Models\IzinAbsensi::JENIS[$z->jenis] }} {{ $z->menit }} m</span></td>
                                        <td>{{ $z->sasaranLabel() }}</td>
                                        <td class="small">{{ $z->alasan }}<span class="text-muted d-block">{{ $z->pembuat?->name }}</span></td>
                                        <td class="text-end">
                                            <form action="{{ route('admin.absensi.izin.destroy', $z) }}" method="POST" class="d-inline">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn btn-outline-danger btn-sm" title="Hapus"
                                                    data-confirm="true" data-icon="warning" data-confirm-color="#dc3545" data-confirm-text="Ya, hapus"
                                                    data-title="Hapus izin?" data-text="Status absensi pada tanggal itu akan dihitung ulang tanpa izin ini.">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5">@include('partials.kosong', ['teks' => 'Belum ada izin pada bulan ini.', 'ikonKosong' => 'bi-cloud-sun'])</td></tr>
                                @endforelse
                            </tbody>
                        </table>
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
        // Ganti jenis → isi menit bawaan jenis tersebut.
        form.querySelectorAll('[name="jenis"]').forEach((r) => r.addEventListener('change', function () { form.menit.value = r.dataset.menit; }));
        form.querySelector('[data-cari-izin]').addEventListener('input', function () {
            const kata = this.value.trim().toLowerCase();
            form.querySelectorAll('[data-nama]').forEach((el) => { el.hidden = kata !== '' && !el.dataset.nama.includes(kata); });
        });
    });
</script>
@include('admin.absensi._gaya')
@endsection
