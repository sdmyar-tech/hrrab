@extends(Auth::user()->panelLayout())

@section('content')
<div class="dashboard-content">
    <div class="container-fluid px-3 px-lg-4 py-4">
        <div class="page-heading">
            <div class="page-heading-copy">
                <span class="page-icon"><i class="bi bi-clock-history"></i></span>
                <div>
                    <div class="eyebrow">Pegawai</div>
                    <h1 class="mb-0">Pengajuan Lembur</h1>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-12 col-xl-5">
                <div class="panel p-4">
                    <h2 class="h6 fw-bold mb-3">Form Pengajuan Lembur</h2>
                    <form action="{{ route('pegawai.pengajuan-lembur.store') }}" method="POST" id="formLembur" data-confirm="Ajukan lembur ini?">
                        @csrf

                        @if (count($unitOptions) > 1)
                            <div class="mb-3">
                                <label for="unit_kerja" class="form-label">Unit Tempat Lembur</label>
                                <select name="unit_kerja" id="unit_kerja" class="form-select @error('unit_kerja') is-invalid @enderror" required>
                                    <option value="">-- Pilih unit --</option>
                                    @foreach ($unitOptions as $unit)
                                        <option value="{{ $unit }}" @selected(old('unit_kerja') === $unit)>{{ $unit }}{{ $loop->first ? ' (unit utama)' : '' }}</option>
                                    @endforeach
                                </select>
                                <div class="form-text">Anda bertugas di beberapa unit. Pengajuan akan diproses kepala unit yang dipilih.</div>
                                @error('unit_kerja')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        @endif

                        <div class="mb-3">
                            <label for="tanggal" class="form-label">Tanggal</label>
                            <input type="date" name="tanggal" id="tanggal"
                                class="form-control @error('tanggal') is-invalid @enderror"
                                value="{{ old('tanggal', now()->format('Y-m-d')) }}" required>
                            @error('tanggal')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="hari" class="form-label">Hari</label>
                            <input type="text" id="hari" class="form-control" readonly placeholder="Terisi otomatis">
                        </div>

                        <div class="mb-3">
                            <label for="uraian_tugas" class="form-label">Uraian Tugas</label>
                            <textarea name="uraian_tugas" id="uraian_tugas" rows="3"
                                class="form-control @error('uraian_tugas') is-invalid @enderror"
                                placeholder="Jelaskan pekerjaan yang dilembur..." required>{{ old('uraian_tugas') }}</textarea>
                            @error('uraian_tugas')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row g-2">
                            <div class="col-6">
                                <label for="jam_mulai" class="form-label">Jam Mulai</label>
                                <input type="time" name="jam_mulai" id="jam_mulai"
                                    class="form-control @error('jam_mulai') is-invalid @enderror"
                                    value="{{ old('jam_mulai') }}" required>
                                @error('jam_mulai')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-6">
                                <label for="jam_selesai" class="form-label">Jam Selesai</label>
                                <input type="time" name="jam_selesai" id="jam_selesai"
                                    class="form-control @error('jam_selesai') is-invalid @enderror"
                                    value="{{ old('jam_selesai') }}" required>
                                @error('jam_selesai')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3 mt-3">
                            <label for="jumlah_jam" class="form-label">Jumlah Jam</label>
                            <input type="text" id="jumlah_jam" class="form-control" readonly placeholder="Terisi otomatis">
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-send me-1"></i> Ajukan Lembur
                        </button>
                    </form>
                </div>
            </div>

            <div class="col-12 col-xl-7">
                <div class="panel p-4">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                        <h2 class="h6 fw-bold mb-0">Riwayat Pengajuan</h2>
                        @include('partials.tombol-ekspor', ['route' => 'pegawai.pengajuan-lembur.export'])
                    </div>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Jam</th>
                                    <th>Jml Jam</th>
                                    <th>Status</th>
                                    <th>Catatan Admin</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($pengajuanLemburs as $item)
                                    <tr>
                                        <td>
                                            {{ optional($item->tanggal)->format('d M Y') }}
                                            <small class="text-muted d-block">{{ $item->hari }}@if (count($unitOptions) > 1 && $item->unit_kerja) &middot; {{ $item->unit_kerja }}@endif</small>
                                        </td>
                                        <td>{{ $item->jam_mulai }} - {{ $item->jam_selesai }}</td>
                                        <td>{{ $item->jumlah_jam }} jam</td>
                                        <td><span class="badge {{ $item->badgeClass() }}">{{ $item->statusLabel() }}</span></td>
                                        <td>{{ $item->catatan_admin ?? '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">
                                            <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                            Belum ada pengajuan lembur.
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
        const hariNames = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', "Jum'at", 'Sabtu'];
        const tanggalInput = document.getElementById('tanggal');
        const hariInput = document.getElementById('hari');
        const jamMulaiInput = document.getElementById('jam_mulai');
        const jamSelesaiInput = document.getElementById('jam_selesai');
        const jumlahJamInput = document.getElementById('jumlah_jam');

        function updateHari() {
            if (!tanggalInput.value) {
                hariInput.value = '';
                return;
            }
            const date = new Date(tanggalInput.value + 'T00:00:00');
            hariInput.value = hariNames[date.getDay()];
        }

        function updateJumlahJam() {
            if (!jamMulaiInput.value || !jamSelesaiInput.value) {
                jumlahJamInput.value = '';
                return;
            }
            const [h1, m1] = jamMulaiInput.value.split(':').map(Number);
            const [h2, m2] = jamSelesaiInput.value.split(':').map(Number);
            const minutes = (h2 * 60 + m2) - (h1 * 60 + m1);
            jumlahJamInput.value = minutes > 0 ? (minutes / 60).toFixed(2) + ' jam' : '';
        }

        tanggalInput.addEventListener('change', updateHari);
        jamMulaiInput.addEventListener('change', updateJumlahJam);
        jamSelesaiInput.addEventListener('change', updateJumlahJam);

        updateHari();
        updateJumlahJam();
    });
</script>
@endsection
