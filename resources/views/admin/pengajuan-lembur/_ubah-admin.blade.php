<x-card title="Ubah Langsung (Admin)" icon="bi-shield-lock" subtitle="Mengubah data & status tanpa mengikuti tahapan persetujuan. Gunakan untuk koreksi.">
    <form action="{{ route('admin.pengajuan-lembur.ubah-admin', $pengajuanLembur) }}" method="POST"
        data-confirm="Simpan perubahan langsung pada pengajuan lembur ini?">
        @csrf
        @method('PUT')
        <div class="row g-3">
            @include('admin.partials.status-admin', ['pengajuan' => $pengajuanLembur])
            <div class="col-sm-6">
                <label class="form-label" for="ubah-tanggal">Tanggal</label>
                <input type="date" name="tanggal" id="ubah-tanggal" class="form-control form-control-sm" value="{{ old('tanggal', $pengajuanLembur->tanggal->format('Y-m-d')) }}" required>
            </div>
            <div class="col-sm-6">
                <label class="form-label" for="ubah-jam-mulai">Jam Mulai</label>
                <input type="text" data-jam24 inputmode="numeric" maxlength="5" autocomplete="off" placeholder="Contoh: 16:00" name="jam_mulai" id="ubah-jam-mulai" class="form-control form-control-sm" value="{{ old('jam_mulai', substr((string) $pengajuanLembur->jam_mulai, 0, 5)) }}" required>
            </div>
            <div class="col-sm-6">
                <label class="form-label" for="ubah-jam-selesai">Jam Selesai</label>
                <input type="text" data-jam24 inputmode="numeric" maxlength="5" autocomplete="off" placeholder="Contoh: 20:30" name="jam_selesai" id="ubah-jam-selesai" class="form-control form-control-sm" value="{{ old('jam_selesai', substr((string) $pengajuanLembur->jam_selesai, 0, 5)) }}" required>
            </div>
            <div class="col-12">
                <label class="form-label" for="ubah-uraian">Uraian Tugas</label>
                <textarea name="uraian_tugas" id="ubah-uraian" rows="2" class="form-control form-control-sm" maxlength="2000" required>{{ old('uraian_tugas', $pengajuanLembur->uraian_tugas) }}</textarea>
            </div>
            <div class="col-12">
                <label class="form-label" for="ubah-catatan">Catatan / Alasan <span class="hr-field-opsional">(opsional)</span></label>
                <input type="text" name="catatan_admin" id="ubah-catatan" class="form-control form-control-sm" maxlength="1000"
                    placeholder="Tulis catatan koreksi, contoh: jam disesuaikan dengan surat tugas" value="{{ old('catatan_admin', $pengajuanLembur->catatan_admin) }}">
            </div>
            {{-- Hasil kalkulasi otomatis dari jam mulai–selesai: read-only, di akhir isian. --}}
            <div class="col-12">
                <label class="form-label" for="ubah-jumlah-jam">Jumlah jam <span class="hr-field-opsional">(otomatis)</span></label>
                <input type="text" id="ubah-jumlah-jam" class="form-control form-control-sm" readonly tabindex="-1" value="{{ rtrim(rtrim(number_format((float) $pengajuanLembur->jumlah_jam, 2, ',', ''), '0'), ',') }} jam">
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
            const mulai = document.getElementById('ubah-jam-mulai'), selesai = document.getElementById('ubah-jam-selesai'), hasil = document.getElementById('ubah-jumlah-jam');
            const menit = (s) => /^\d{2}:\d{2}$/.test(s) ? parseInt(s.slice(0, 2), 10) * 60 + parseInt(s.slice(3), 10) : null;
            function hitung() {
                const a = menit(mulai.value), b = menit(selesai.value);
                if (a === null || b === null || b <= a) { hasil.value = ''; return; }
                hasil.value = (Math.round((b - a) / 60 * 100) / 100).toString().replace('.', ',') + ' jam';
            }
            ['input', 'change'].forEach((e) => { mulai.addEventListener(e, hitung); selesai.addEventListener(e, hitung); });
        })();
    </script>

    <x-slot:footer>
        <form action="{{ route('admin.pengajuan-lembur.destroy', $pengajuanLembur) }}" method="POST"
            data-confirm="Hapus permanen pengajuan lembur {{ $pengajuanLembur->dataPegawai?->nama_lengkap }}?">
            @csrf
            @method('DELETE')
            <x-button type="submit" variant="danger-outline" size="sm" icon="bi-trash" block>Hapus Pengajuan</x-button>
        </form>
    </x-slot:footer>
</x-card>
