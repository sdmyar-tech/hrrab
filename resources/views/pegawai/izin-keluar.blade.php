@extends(Auth::user()->panelLayout())

@section('content')
<div class="dashboard-content">
    <div class="container-fluid px-3 px-lg-4 py-4">
        <div class="page-heading">
            <div class="page-heading-copy">
                <span class="page-icon"><i class="bi bi-door-open"></i></span>
                <div>
                    <div class="eyebrow">Pegawai</div>
                    <h1 class="mb-0">Izin Keluar Jam Kerja</h1>
                    <p class="text-muted mb-0">Setelah disetujui, tunjukkan <strong>tiket keluar</strong> ke security di gerbang.</p>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-12 col-xl-5">
                <div class="panel p-4">
                    <h2 class="h6 fw-bold mb-3">Form Izin Keluar</h2>
                    <form action="{{ route('pegawai.izin-keluar.store') }}" method="POST" data-confirm="Ajukan izin keluar ini?">
                        @csrf

                        <div class="mb-3">
                            <label for="tanggal" class="form-label">Tanggal</label>
                            <input type="date" name="tanggal" id="tanggal" class="form-control @error('tanggal') is-invalid @enderror"
                                value="{{ old('tanggal', now()->toDateString()) }}" min="{{ now()->toDateString() }}"
                                max="{{ now()->addDays($maksHari)->toDateString() }}" required>
                            @error('tanggal')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="row g-2">
                            <div class="col-6">
                                <label for="jam_keluar" class="form-label">Jam Keluar</label>
                                <input type="time" name="jam_keluar" id="jam_keluar" class="form-control @error('jam_keluar') is-invalid @enderror"
                                    value="{{ old('jam_keluar') }}" required>
                                @error('jam_keluar')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-6">
                                <label for="jam_kembali" class="form-label">Perkiraan Kembali</label>
                                <input type="time" name="jam_kembali" id="jam_kembali" class="form-control @error('jam_kembali') is-invalid @enderror"
                                    value="{{ old('jam_kembali') }}">
                                @error('jam_kembali')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="form-check mt-2 mb-3">
                            <input class="form-check-input" type="checkbox" name="tidak_kembali" value="1" id="tidak_kembali" @checked(old('tidak_kembali'))>
                            <label class="form-check-label" for="tidak_kembali">Tidak kembali lagi hari ini</label>
                        </div>

                        <div class="mb-3">
                            <label for="keperluan" class="form-label">Keperluan</label>
                            <select name="keperluan" id="keperluan" class="form-select @error('keperluan') is-invalid @enderror" required>
                                <option value="">-- Pilih keperluan --</option>
                                @foreach ($keperluanOptions as $kode => $label)
                                    <option value="{{ $kode }}" @selected(old('keperluan') === $kode)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('keperluan')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label for="alasan" class="form-label">Alasan / Tujuan</label>
                            <textarea name="alasan" id="alasan" rows="3" maxlength="500" class="form-control @error('alasan') is-invalid @enderror"
                                placeholder="mis. ke bank untuk urusan gaji, ke puskesmas, menjemput anak..." required>{{ old('alasan') }}</textarea>
                            @error('alasan')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        @if ($rute)
                            <div class="alert alert-light border small">
                                <i class="bi bi-signpost-split me-1"></i>Disetujui oleh:
                                <strong>{{ collect($rute)->pluck('label')->map(fn ($l) => str_replace(' (Approve Akhir)', '', $l))->implode(' → ') }}</strong>.
                                Tiket keluar terbit setelah persetujuan terakhir.
                            </div>
                        @endif

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-send me-1"></i> Ajukan Izin Keluar
                        </button>
                    </form>
                </div>
            </div>

            <div class="col-12 col-xl-7">
                <div class="panel p-4">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                        <h2 class="h6 fw-bold mb-0">Riwayat Izin Keluar</h2>
                        @if ($urutOpsi) @include('partials.urut') @endif
                    </div>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Jam</th>
                                    <th>Keperluan</th>
                                    <th>Status</th>
                                    <th class="text-end">Tiket</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($riwayat as $item)
                                    <tr>
                                        <td>
                                            {{ $item->tanggal->translatedFormat('d M Y') }}
                                            <small class="text-muted d-block">{{ $item->tanggal->translatedFormat('l') }}</small>
                                        </td>
                                        <td>{{ $item->jamLabel() }}</td>
                                        <td>
                                            {{ $item->keperluanLabel() }}
                                            <small class="text-muted d-block">{{ \Illuminate\Support\Str::limit($item->alasan, 50) }}</small>
                                        </td>
                                        <td>
                                            <span class="badge {{ $item->badgeClass() }}">{{ $item->statusLabel() }}</span>
                                            @if ($item->isPending())
                                                <small class="text-muted d-block mt-1">Tahap: {{ $item->currentLevelLabel() }}</small>
                                            @elseif ($item->status === 'rejected' && $item->catatan_admin)
                                                <small class="text-muted d-block mt-1">{{ $item->catatan_admin }}</small>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            @if ($item->isApproved())
                                                @php $st = $item->statusTiket(); @endphp
                                                <a href="{{ route('pegawai.izin-keluar.tiket', $item) }}"
                                                    class="btn btn-sm {{ $st === 'lewat' ? 'btn-outline-secondary' : 'btn-success' }}">
                                                    <i class="bi bi-ticket-perforated me-1"></i>{{ $st === 'lewat' ? 'Lihat' : 'Tampilkan Tiket' }}
                                                </a>
                                            @else
                                                <span class="text-muted small">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">
                                            <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                            Belum ada izin keluar.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // "Tidak kembali" → jam kembali tidak perlu diisi.
        const tidakKembali = document.getElementById('tidak_kembali');
        const jamKembali = document.getElementById('jam_kembali');
        const atur = () => {
            jamKembali.disabled = tidakKembali.checked;
            jamKembali.required = !tidakKembali.checked;
            if (tidakKembali.checked) jamKembali.value = '';
        };
        tidakKembali.addEventListener('change', atur);
        atur();
    });
</script>
@endsection
