@extends('layouts.admin.app')

@section('content')
<main class="dashboard-content">
    <div class="container-fluid px-3 px-lg-4 py-4">
        <div class="page-heading">
            <div class="page-heading-copy">
                <span class="page-icon"><i class="bi bi-exclamation-octagon"></i></span>
                <div>
                    <p class="eyebrow mb-1">Kepegawaian</p>
                    <h1 class="h3 mb-1">Surat Peringatan</h1>
                    <p class="text-muted mb-0">SP bertahap dalam satu bulan: SP1 → SP2 → SP3 (maksimal 3). Bulan berikutnya mulai lagi dari SP1.</p>
                </div>
            </div>
        </div>

        <div class="row g-3 align-items-start">
            @if ($pegawai->isNotEmpty())
                <div class="col-lg-4">
                    <section class="panel">
                        <h2 class="h6 fw-bold mb-3"><i class="bi bi-plus-circle"></i>Beri SP</h2>
                        <form action="{{ route('admin.surat-peringatan.store') }}" method="POST" id="formSp" data-confirm="Berikan surat peringatan ini? Pegawai akan menerima notifikasi.">
                            @csrf
                            <label class="form-label small fw-semibold" for="cari-pegawai">Pegawai</label>
                            <input type="search" id="cari-pegawai" class="form-control form-control-sm mb-1" placeholder="Cari nama…" data-cari-sp>
                            <select name="data_pegawai_id" id="sp-pegawai" class="form-select mb-1" required>
                                <option value="">-- Pilih pegawai --</option>
                                @foreach ($pegawai as $p)
                                    <option value="{{ $p->id }}" data-tingkat="{{ (int) ($tingkatBulanIni[$p->id] ?? 0) }}" @selected((int) old('data_pegawai_id') === $p->id)>
                                        {{ $p->nama_lengkap }} — {{ $p->kepegawaian?->unit_kerja }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="sp-pratinjau mb-3" data-pratinjau hidden></div>

                            <label class="form-label small fw-semibold" for="sp-tanggal">Tanggal</label>
                            <input type="date" name="tanggal" id="sp-tanggal" class="form-control mb-3" value="{{ old('tanggal', now()->toDateString()) }}" max="{{ now()->toDateString() }}" required>

                            <label class="form-label small fw-semibold" for="sp-alasan">Alasan / pelanggaran</label>
                            <input type="text" name="alasan" id="sp-alasan" class="form-control mb-3" maxlength="255" value="{{ old('alasan') }}" placeholder="mis. terlambat 5 kali dalam sebulan" required>

                            <label class="form-label small fw-semibold" for="sp-keterangan">Keterangan <span class="fw-normal text-muted">(opsional)</span></label>
                            <textarea name="keterangan" id="sp-keterangan" rows="3" class="form-control mb-3" maxlength="2000" placeholder="Kronologi, tindak lanjut yang diharapkan…">{{ old('keterangan') }}</textarea>

                            <button type="submit" class="btn btn-danger w-100" data-tombol-sp><i class="bi bi-exclamation-octagon me-1"></i>Berikan SP</button>
                        </form>
                    </section>
                </div>
            @endif

            <div class="{{ $pegawai->isNotEmpty() ? 'col-lg-8' : 'col-12' }}">
                <section class="panel">
                    <form method="GET" action="{{ route('admin.surat-peringatan.index') }}" class="panel-filter d-flex flex-wrap gap-2 align-items-center mb-3">
                        <div class="input-group input-group-sm" style="max-width: 220px;">
                            <span class="input-group-text">Bulan</span>
                            <input type="month" name="bulan" class="form-control" value="{{ $bulan->format('Y-m') }}" onchange="this.form.submit()" aria-label="Bulan">
                        </div>
                        <div class="input-group input-group-sm" style="max-width: 260px;">
                            <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
                            <input type="search" name="q" class="form-control border-start-0" placeholder="Cari nama…" value="{{ request('q') }}">
                        </div>
                    </form>

                    <div class="sp-ringkas mb-3">
                        <span><b>{{ $daftar->count() }}</b> SP pada {{ $bulan->translatedFormat('F Y') }}</span>
                        @foreach ([1, 2, 3] as $t)
                            <span class="badge {{ (new \App\Models\SuratPeringatan(['tingkat' => $t]))->badgeClass() }}">SP{{ $t }}: {{ $daftar->where('tingkat', $t)->count() }}</span>
                        @endforeach
                    </div>

                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Pegawai</th>
                                    <th>Tanggal</th>
                                    <th>SP</th>
                                    <th>Alasan</th>
                                    <th>Diberikan oleh</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($daftar as $sp)
                                    <tr>
                                        <td>
                                            <span class="fw-semibold d-block">{{ $sp->dataPegawai?->nama_lengkap ?? '-' }}</span>
                                            <small class="text-muted">{{ $sp->dataPegawai?->kepegawaian?->jabatan }} · {{ $sp->unit_kerja }}</small>
                                        </td>
                                        <td>{{ $sp->tanggal->translatedFormat('d M Y') }}</td>
                                        <td><span class="badge {{ $sp->badgeClass() }}">{{ $sp->label() }}</span></td>
                                        <td>{{ $sp->alasan }}@if ($sp->keterangan)<small class="d-block text-muted" style="white-space: pre-line;">{{ $sp->keterangan }}</small>@endif</td>
                                        <td><small>{{ $sp->pemberi?->name ?? '-' }}</small></td>
                                        <td class="text-end">
                                            @if (\App\Support\SuratPeringatanAkses::bisaHapus(auth()->user(), $sp))
                                                <form action="{{ route('admin.surat-peringatan.destroy', $sp) }}" method="POST" class="d-inline" data-confirm="Hapus {{ $sp->label() }} {{ $sp->dataPegawai?->nama_lengkap }}?">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="btn btn-outline-danger btn-sm" title="Hapus"><i class="bi bi-trash"></i></button>
                                                </form>
                                            @else
                                                <span class="text-muted small">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">
                                            <i class="bi bi-check-circle fs-3 d-block mb-2"></i>Tidak ada surat peringatan pada {{ $bulan->translatedFormat('F Y') }}.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </div>
    </div>
</main>

<style>
    .sp-ringkas { display: flex; flex-wrap: wrap; gap: .5rem; align-items: center; font-size: .85rem; }
    .sp-pratinjau { font-size: .8rem; padding: .4rem .6rem; border-radius: 8px; background: #fff7e6; border: 1px solid #f5d48a; color: #7a4b00; }
    .sp-pratinjau.penuh { background: #fef2f2; border-color: #f3b4b4; color: #991b1b; }
    html[data-theme="dark"] .sp-pratinjau { background: rgba(245, 158, 11, .12); color: #fcd34d; }
    html[data-theme="dark"] .sp-pratinjau.penuh { background: rgba(239, 68, 68, .12); color: #fca5a5; }
</style>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('formSp');
        if (!form) return;
        const pilih = form.querySelector('#sp-pegawai');
        const tanggal = form.querySelector('#sp-tanggal');
        const kotak = form.querySelector('[data-pratinjau]');
        const tombol = form.querySelector('[data-tombol-sp]');
        const bulanIni = @json(now()->format('Y-m'));

        // Pratinjau tingkat: SP bulan ini sudah sampai mana → SP berikutnya; SP3 = penuh (tombol dikunci).
        const pratinjau = () => {
            const opsi = pilih.selectedOptions[0];
            kotak.hidden = !opsi || !opsi.value;
            tombol.disabled = false;
            if (kotak.hidden) return;
            if (tanggal.value.slice(0, 7) !== bulanIni) {
                kotak.className = 'sp-pratinjau mb-3';
                kotak.textContent = 'Tingkat SP dihitung menurut bulan tanggal yang dipilih.';
                return;
            }
            const ada = +opsi.dataset.tingkat;
            kotak.className = 'sp-pratinjau mb-3' + (ada >= 3 ? ' penuh' : '');
            kotak.textContent = ada >= 3 ? 'Sudah SP3 bulan ini — tidak bisa memberi SP lagi sampai bulan depan.'
                : 'Akan menjadi SP' + (ada + 1) + (ada ? ' (bulan ini sudah SP' + ada + ').' : ' (belum ada SP bulan ini).');
            tombol.disabled = ada >= 3;
        };
        pilih.addEventListener('change', pratinjau);
        tanggal.addEventListener('change', pratinjau);
        pratinjau();

        // Cari pegawai: saring opsi menurut nama.
        form.querySelector('[data-cari-sp]').addEventListener('input', (e) => {
            const kata = e.target.value.trim().toLowerCase();
            [...pilih.options].forEach((o) => { if (o.value) o.hidden = kata && !o.textContent.toLowerCase().includes(kata); });
        });
    });
</script>
@endsection
