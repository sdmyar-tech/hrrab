<x-card title="Ubah Langsung (Admin)" icon="bi-shield-lock" subtitle="Mengubah data & status tanpa mengikuti tahapan persetujuan. Izin keluar yang Disetujui langsung mendapat tiket; izin telat masuk & tidak masuk langsung tercatat di absensi.">
    <form action="{{ route('admin.izin-keluar.ubah-admin', $izinKeluar) }}" method="POST" data-confirm="Simpan perubahan langsung pada izin ini?" id="formUbahIzin">
        @csrf
        @method('PUT')
        @php $jenisUbah = old('jenis', $izinKeluar->jenis ?? 'jam'); @endphp
        <div class="row g-3">
            @include('admin.partials.status-admin', ['pengajuan' => $izinKeluar])
            <div class="col-12">
                <label class="form-label" for="ubah-jenis">Jenis Izin</label>
                <select name="jenis" id="ubah-jenis" class="form-select form-select-sm" required>
                    @foreach (\App\Models\PengajuanIzinKeluar::JENIS as $kode => $label)
                        <option value="{{ $kode }}" @selected($jenisUbah === $kode)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-6">
                <label class="form-label" for="ubah-tanggal">Tanggal <span data-ubah-izin="harian">Mulai</span></label>
                <input type="date" name="tanggal" id="ubah-tanggal" class="form-control form-control-sm" value="{{ old('tanggal', $izinKeluar->tanggal->format('Y-m-d')) }}" required>
            </div>
            <div class="col-sm-6" data-ubah-izin="harian">
                <label class="form-label" for="ubah-tanggal-selesai">Tanggal Selesai</label>
                <input type="date" name="tanggal_selesai" id="ubah-tanggal-selesai" class="form-control form-control-sm" value="{{ old('tanggal_selesai', $izinKeluar->tanggal_selesai?->format('Y-m-d')) }}">
                <div class="form-text">Lama dihitung ulang dalam hari kerja.</div>
            </div>
            <div class="col-sm-6" data-ubah-izin="telat">
                <label class="form-label" for="ubah-jam-tiba">Jam Tiba</label>
                <input type="text" data-jam24 inputmode="numeric" maxlength="5" autocomplete="off" placeholder="Contoh: 08:15" name="jam_kembali" id="ubah-jam-tiba" class="form-control form-control-sm" value="{{ old('jam_kembali', $izinKeluar->jamTiba()) }}">
            </div>
            <div class="col-sm-6" data-ubah-izin="jam">
                <label class="form-label" for="ubah-jam-keluar">Jam Keluar</label>
                <input type="text" data-jam24 inputmode="numeric" maxlength="5" autocomplete="off" placeholder="Contoh: 10:00" name="jam_keluar" id="ubah-jam-keluar" class="form-control form-control-sm" value="{{ old('jam_keluar', substr((string) $izinKeluar->jam_keluar, 0, 5)) }}">
            </div>
            <div class="col-sm-6" data-ubah-izin="jam">
                <label class="form-label" for="ubah-jam-kembali">Jam Kembali</label>
                <input type="text" data-jam24 inputmode="numeric" maxlength="5" autocomplete="off" placeholder="Contoh: 11:30" name="jam_kembali" id="ubah-jam-kembali" class="form-control form-control-sm" value="{{ old('jam_kembali', substr((string) $izinKeluar->jam_kembali, 0, 5)) }}">
            </div>
            <div class="col-12" data-ubah-izin="jam">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="tidak_kembali" value="1" id="admin-tidak-kembali" @checked(old('tidak_kembali', $izinKeluar->tidakKembali()))>
                    <label class="form-check-label" for="admin-tidak-kembali">Tidak kembali lagi hari itu</label>
                </div>
            </div>
            <div class="col-12">
                <label class="form-label" for="ubah-keperluan">Keperluan</label>
                <select name="keperluan" id="ubah-keperluan" class="form-select form-select-sm" required>
                    @foreach (\App\Models\PengajuanIzinKeluar::KEPERLUAN as $kode => $label)
                        <option value="{{ $kode }}" @selected(old('keperluan', $izinKeluar->keperluan) === $kode)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12">
                <label class="form-label" for="ubah-alasan">Alasan</label>
                <textarea name="alasan" id="ubah-alasan" rows="2" class="form-control form-control-sm" maxlength="500" required>{{ old('alasan', $izinKeluar->alasan) }}</textarea>
            </div>
            <div class="col-12">
                <label class="form-label" for="ubah-catatan">Catatan / Alasan Penolakan <span class="hr-field-opsional">(opsional)</span></label>
                <input type="text" name="catatan_admin" id="ubah-catatan" class="form-control form-control-sm" maxlength="1000"
                    placeholder="Tulis catatan koreksi, contoh: jam kembali disesuaikan" value="{{ old('catatan_admin', $izinKeluar->catatan_admin) }}">
            </div>
        </div>
        {{-- Galat isian ditampilkan di halaman ini; banner global tidak perlu. --}}
        @section('galat-di-halaman', '1')
        @if ($errors->any())
            <x-alert tone="danger" class="mt-3 mb-0">{{ $errors->first() }}</x-alert>
        @endif
        <div class="hr-kartu-aksi-tombol">
            <x-button type="submit" size="sm" icon="bi-check-lg">Simpan Langsung</x-button>
        </div>
    </form>

    <x-slot:footer>
        <form action="{{ route('admin.izin-keluar.destroy', $izinKeluar) }}" method="POST"
            data-confirm="Hapus permanen izin {{ $izinKeluar->dataPegawai?->nama_lengkap }}?">
            @csrf
            @method('DELETE')
            <x-button type="submit" variant="danger-outline" size="sm" icon="bi-trash" block>Hapus Pengajuan</x-button>
        </form>
    </x-slot:footer>
</x-card>
<script>
    // Tampilkan isian sesuai jenis izin; isian jenis lain dinonaktifkan agar tidak terkirim.
    document.addEventListener('DOMContentLoaded', function () {
        const jenis = document.getElementById('ubah-jenis');
        const atur = function () {
            document.querySelectorAll('#formUbahIzin [data-ubah-izin]').forEach(function (el) {
                const aktif = el.dataset.ubahIzin === jenis.value;
                el.classList.toggle('d-none', ! aktif);
                el.querySelectorAll('input').forEach((i) => { i.disabled = ! aktif; });
            });
            document.getElementById('ubah-tanggal-selesai').required = jenis.value === 'harian';
            document.getElementById('ubah-jam-keluar').required = jenis.value === 'jam';
            document.getElementById('ubah-jam-tiba').required = jenis.value === 'telat';
        };
        jenis.addEventListener('change', atur);
        atur();
    });
</script>
