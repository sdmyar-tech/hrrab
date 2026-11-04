{{-- Admin: tambah interview kapan saja — tahap, interviewer, dan tanggal bebas (tanpa urutan alur). Dipasang di luar tab (stack modal-lamaran). --}}
<x-modal id="modalInterviewAdmin" title="Tambah Interview (Admin)" icon="bi-shield-lock" tone="warning"
    :subtitle="$lamaran->namaKandidat() . ' · ' . $lamaran->judulPosisi()">
    <form action="{{ route('admin.lamaran.interview.store', $lamaran) }}" method="POST" id="form-interview-admin"
        data-confirm="Tambahkan jadwal interview untuk {{ $lamaran->namaKandidat() }}? (Mode Admin)">
        @csrf
        <x-alert tone="warning" class="mb-3">Tanpa urutan tahap &amp; batas tanggal. Interviewer mendapat notifikasi.</x-alert>
        <div class="row g-3">
            <div class="col-md-5">
                <label class="form-label" for="ia-tahap">Tahap</label>
                <select name="tahap_interview" id="ia-tahap" class="form-select" required>
                    <option value="1">Interview 1 (Kepala SDM)</option>
                    <option value="2">Interview 2 (Kepala Bagian)</option>
                    <option value="3" selected>Interview Lanjutan</option>
                </select>
            </div>
            <div class="col-md-7">
                <label class="form-label" for="ia-interviewer">Interviewer</label>
                <select name="interviewer_id" id="ia-interviewer" class="form-select" required>
                    <option value="">Pilih interviewer</option>
                    @foreach ($opsiInterviewerAdmin as $opt)
                        <option value="{{ $opt->id }}">{{ $opt->name }} ({{ $opt->roleLabel() }})</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="ia-tanggal">Tanggal</label>
                <input type="date" name="tanggal" id="ia-tanggal" class="form-control" value="{{ now()->format('Y-m-d') }}" required>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label" for="ia-mulai">Jam Mulai</label>
                <input type="time" name="waktu_mulai" id="ia-mulai" class="form-control" required>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label" for="ia-selesai">Jam Selesai</label>
                <input type="time" name="waktu_selesai" id="ia-selesai" class="form-control">
            </div>
            <div class="col-12">
                <label class="form-label" for="ia-lokasi">Tempat / Link <span class="hr-field-opsional">(opsional)</span></label>
                <input type="text" name="lokasi" id="ia-lokasi" class="form-control" placeholder="Tempat atau link, contoh: Ruang Rapat Lt. 2">
            </div>
            <div class="col-12">
                <label class="form-label" for="ia-catatan">Catatan <span class="hr-field-opsional">(opsional)</span></label>
                <input type="text" name="catatan" id="ia-catatan" class="form-control" placeholder="Pesan untuk kandidat, contoh: bawa portofolio">
            </div>
        </div>
    </form>
    <x-slot:footer>
        <x-button variant="secondary" data-bs-dismiss="modal">Batal</x-button>
        <x-button type="submit" form="form-interview-admin" icon="bi-plus-lg">Tambah Interview</x-button>
    </x-slot:footer>
</x-modal>
