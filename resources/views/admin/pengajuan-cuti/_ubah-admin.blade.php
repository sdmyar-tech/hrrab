<x-card title="Ubah Langsung (Admin)" icon="bi-shield-lock" subtitle="Mengubah data & status tanpa mengikuti tahapan persetujuan. Gunakan untuk koreksi.">
    <form action="{{ route('admin.pengajuan-cuti.ubah-admin', $pengajuanCuti) }}" method="POST"
        data-confirm="Simpan perubahan langsung pada pengajuan cuti ini?">
        @csrf
        @method('PUT')
        <div class="row g-3">
            @include('admin.partials.status-admin', ['pengajuan' => $pengajuanCuti])
            <div class="col-sm-6">
                <label class="form-label" for="ubah-jenis-cuti">Jenis Cuti</label>
                <select name="jenis_cuti" id="ubah-jenis-cuti" class="form-select form-select-sm">
                    <option value="tahunan" @selected(old('jenis_cuti', $pengajuanCuti->jenis_cuti) === 'tahunan')>Cuti Tahunan</option>
                    <option value="besar" @selected(old('jenis_cuti', $pengajuanCuti->jenis_cuti) === 'besar')>Cuti Besar</option>
                </select>
            </div>
            <div class="col-sm-6">
                <label class="form-label" for="ubah-kategori">Kategori Cuti Besar</label>
                <select name="kategori_cuti_besar" id="ubah-kategori" class="form-select form-select-sm">
                    <option value="">-</option>
                    @foreach (\App\Models\PengajuanCuti::KATEGORI_BESAR_OPTIONS as $nilai => $label)
                        <option value="{{ $nilai }}" @selected(old('kategori_cuti_besar', $pengajuanCuti->kategori_cuti_besar) === $nilai)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-6">
                <label class="form-label" for="ubah-mulai">Tanggal Mulai</label>
                <input type="date" name="tanggal_mulai" id="ubah-mulai" class="form-control form-control-sm" value="{{ old('tanggal_mulai', $pengajuanCuti->tanggal_mulai->format('Y-m-d')) }}" required>
            </div>
            <div class="col-sm-6">
                <label class="form-label" for="ubah-selesai">Tanggal Selesai</label>
                <input type="date" name="tanggal_selesai" id="ubah-selesai" class="form-control form-control-sm" value="{{ old('tanggal_selesai', $pengajuanCuti->tanggal_selesai->format('Y-m-d')) }}" required>
            </div>
            <div class="col-sm-6">
                <label class="form-label" for="ubah-pengganti">Pengganti Tugas</label>
                @include('partials.pilih-pegawai', [
                    'name' => 'pengganti_nama', 'id' => 'ubah-pengganti', 'nilai' => 'nama', 'kelas' => 'form-select-sm',
                    'terpilih' => old('pengganti_nama', $pengajuanCuti->pengganti_nama), 'kosong' => 'Belum ditentukan',
                    'utama' => $pengajuanCuti->substituteOptions()->all(), 'labelUtama' => 'Satu bagian', 'kecuali' => [$pengajuanCuti->data_pegawai_id],
                ])
            </div>
            <div class="col-12">
                <label class="form-label" for="ubah-catatan">Catatan / Alasan <span class="hr-field-opsional">(opsional)</span></label>
                <input type="text" name="catatan_admin" id="ubah-catatan" class="form-control form-control-sm" maxlength="1000"
                    placeholder="Tulis catatan koreksi, contoh: tanggal disesuaikan dengan surat" value="{{ old('catatan_admin', $pengajuanCuti->catatan_admin) }}">
            </div>
            <div class="col-12">
                <label class="form-label" for="ubah-keterangan">Keterangan Pemohon</label>
                <textarea name="keterangan" id="ubah-keterangan" rows="2" class="form-control form-control-sm" maxlength="1000">{{ old('keterangan', $pengajuanCuti->keterangan) }}</textarea>
            </div>
            {{-- Hasil kalkulasi otomatis (hari kerja, Sabtu–Minggu & tanggal merah dilewati): read-only, di akhir isian. --}}
            <div class="col-12">
                <label class="form-label" for="ubah-lama">Lama cuti <span class="hr-field-opsional">(otomatis)</span></label>
                <input type="text" id="ubah-lama" class="form-control form-control-sm" readonly tabindex="-1" value="{{ $pengajuanCuti->lama_cuti }} hari kerja">
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

    <script>
        (function () {
            const mulai = document.getElementById('ubah-mulai'), selesai = document.getElementById('ubah-selesai'), hasil = document.getElementById('ubah-lama');
            const HARI_KERJA = @json(config('hrrab.hari_kerja')), LIBUR = @json(config('hrrab.hari_libur'));
            const iso = (d) => d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
            function hitung() {
                if (! mulai.value || ! selesai.value || selesai.value < mulai.value) { hasil.value = ''; return; }
                let n = 0;
                for (const t = new Date(mulai.value + 'T00:00:00'), akhir = new Date(selesai.value + 'T00:00:00'); t <= akhir; t.setDate(t.getDate() + 1)) {
                    if (HARI_KERJA.includes(t.getDay() || 7) && ! LIBUR.includes(iso(t))) n++;
                }
                hasil.value = (n || 1) + ' hari kerja';
            }
            mulai.addEventListener('change', hitung);
            selesai.addEventListener('change', hitung);
        })();
    </script>

    <x-slot:footer>
        <form action="{{ route('admin.pengajuan-cuti.destroy', $pengajuanCuti) }}" method="POST"
            data-confirm="Hapus permanen pengajuan cuti {{ $pengajuanCuti->dataPegawai?->nama_lengkap }}?">
            @csrf
            @method('DELETE')
            <x-button type="submit" variant="danger-outline" size="sm" icon="bi-trash" block>Hapus Pengajuan</x-button>
        </form>
    </x-slot:footer>
</x-card>
