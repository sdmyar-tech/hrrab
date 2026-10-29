{{--
    Modal Resign/Keluar pegawai. $dampakKeluar dari App\Support\PegawaiKeluar::dampak().
    Dipakai di Detail Pegawai (langsung) dan di daftar Data Pegawai (dimuat lewat route admin.pegawai.keluar.form, $kembali = URL daftar).
--}}
@php
    $d = $dampakKeluar;
    $terkunci = $d['terjadwal'] > 0;
@endphp
<div class="modal fade" id="modalKeluar" tabindex="-1" aria-labelledby="judulModalKeluar" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <form class="modal-content" method="POST" action="{{ route('admin.pegawai.keluar', $pegawai) }}"
            data-confirm="Proses {{ $pegawai->nama_lengkap }} resign/keluar? Akunnya menjadi akun pelamar.">
            @csrf
            @if (! empty($kembali))
                <input type="hidden" name="kembali" value="{{ $kembali }}">
            @endif
            <div class="modal-header">
                <h2 class="modal-title" id="judulModalKeluar"><i class="bi bi-box-arrow-right me-1"></i>Resign / Keluar · {{ $pegawai->nama_lengkap }}</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                @if ($terkunci)
                    <div class="alert alert-warning small">
                        <i class="bi bi-exclamation-triangle me-1"></i>Pegawai ini punya {{ $d['terjadwal'] }} mutasi/rangkap terjadwal.
                        Batalkan dulu di <a href="{{ route('admin.penugasan.index', ['q' => $pegawai->nama_lengkap]) }}">Mutasi &amp; Rangkap</a>.
                    </div>
                @endif

                <div class="mb-3">
                    <label for="jenis_keluar" class="form-label">Jenis keluar</label>
                    <select name="jenis_keluar" id="jenis_keluar" class="form-select @error('jenis_keluar', 'keluar') is-invalid @enderror" required>
                        <option value="">-- Pilih --</option>
                        @foreach (\App\Support\PegawaiKeluar::JENIS as $k => $label)
                            <option value="{{ $k }}" @selected(old('jenis_keluar') === $k)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('jenis_keluar', 'keluar')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label for="tanggal_keluar" class="form-label">Tanggal keluar (hari terakhir bekerja)</label>
                    <input type="date" name="tanggal_keluar" id="tanggal_keluar" class="form-control @error('tanggal_keluar', 'keluar') is-invalid @enderror"
                        value="{{ old('tanggal_keluar', now()->toDateString()) }}" max="{{ now()->toDateString() }}" min="{{ $d['tanggalMinimal'] }}" required>
                    @error('tanggal_keluar', 'keluar')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label for="alasan_keluar" class="form-label">Alasan / catatan <span class="fw-normal text-muted">(wajib untuk "Lainnya")</span></label>
                    <textarea name="alasan_keluar" id="alasan_keluar" rows="2" maxlength="1000" class="form-control @error('alasan_keluar', 'keluar') is-invalid @enderror"
                        placeholder="Contoh: melanjutkan studi, surat pengunduran diri tanggal 1 Oktober">{{ old('alasan_keluar') }}</textarea>
                    @error('alasan_keluar', 'keluar')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="keluar-dampak">
                    <p class="fw-semibold small mb-2"><i class="bi bi-list-check me-1"></i>Yang akan terjadi</p>
                    <ul class="small mb-0">
                        <li>Status kepegawaian menjadi <strong>Tidak Aktif</strong> per tanggal keluar.</li>
                        @forelse ($d['akun'] as $a)
                            <li>Akun <strong>{{ $a->email }}</strong> ({{ $a->roleLabel() }}) menjadi <strong>akun pelamar</strong>. Akun tidak dihapus: tetap bisa login, melihat lowongan, dan harus melamar lagi untuk kembali bekerja.</li>
                        @empty
                            <li>Pegawai ini tidak punya akun login.</li>
                        @endforelse
                        @foreach ($d['akunAdmin'] as $a)
                            <li class="text-warning-emphasis">Akun Admin {{ $a->email }} <strong>tidak</strong> diubah otomatis.</li>
                        @endforeach
                        @if ($d['penugasan']->isNotEmpty())
                            <li>{{ $d['penugasan']->count() }} penugasan aktif ({{ $d['penugasan']->pluck('unit_kerja')->unique()->implode(', ') }}) diakhiri.</li>
                        @endif
                        @foreach ($d['memimpin'] as $unit)
                            <li class="text-warning-emphasis">Unit {{ $unit }} kehilangan koordinatornya.</li>
                        @endforeach
                        @if ($d['pengajuan'])
                            <li>{{ $d['pengajuan'] }} pengajuan (cuti/lembur/izin keluar) yang masih menunggu ditutup.</li>
                        @endif
                        <li>Tidak lagi diabsen dari mesin.</li>
                    </ul>
                    <p class="small text-muted mt-2 mb-0"><i class="bi bi-arrow-counterclockwise me-1"></i>Bisa dibatalkan dari halaman ini bila salah input.</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-danger" @disabled($terkunci)><i class="bi bi-box-arrow-right me-1"></i>Proses Resign / Keluar</button>
            </div>
        </form>
    </div>
</div>
<style>
    .keluar-dampak { padding: .75rem .9rem; border: 1px solid var(--admin-border); border-radius: 10px; background: var(--admin-surface-soft); }
    .keluar-dampak ul { padding-left: 1.1rem; display: grid; gap: .25rem; }
</style>
@if ($errors->keluar->any())
    {{-- Validasi gagal: buka lagi modalnya agar pesan terlihat. --}}
    <script>
        document.addEventListener('DOMContentLoaded', () => bootstrap.Modal.getOrCreateInstance(document.getElementById('modalKeluar')).show());
    </script>
@endif
