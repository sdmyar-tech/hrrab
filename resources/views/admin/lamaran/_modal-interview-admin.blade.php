{{-- Admin: tambah interview kapan saja — tahap, interviewer, dan tanggal bebas (tanpa urutan alur). --}}
<div class="modal fade" id="modalInterviewAdmin" tabindex="-1" aria-labelledby="judulInterviewAdmin" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form action="{{ route('admin.lamaran.interview.store', $lamaran) }}" method="POST" class="modal-content"
            data-confirm="Tambahkan jadwal interview ini? (Mode Admin)">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title" id="judulInterviewAdmin"><i class="bi bi-shield-lock me-1"></i>Tambah Interview (Admin)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning small py-2">Tanpa urutan tahap & batas tanggal. Interviewer mendapat notifikasi.</div>
                <div class="row g-2">
                    <div class="col-md-5">
                        <label class="form-label small mb-1" for="ia-tahap">Tahap</label>
                        <select name="tahap_interview" id="ia-tahap" class="form-select form-select-sm" required>
                            <option value="1">Interview 1 (Kepala SDM)</option>
                            <option value="2">Interview 2 (Kepala Bagian)</option>
                            <option value="3" selected>Interview Lanjutan</option>
                        </select>
                    </div>
                    <div class="col-md-7">
                        <label class="form-label small mb-1" for="ia-interviewer">Interviewer</label>
                        <select name="interviewer_id" id="ia-interviewer" class="form-select form-select-sm" required>
                            <option value="">-- Pilih --</option>
                            @foreach ($opsiInterviewerAdmin as $opt)
                                <option value="{{ $opt->id }}">{{ $opt->name }} ({{ $opt->roleLabel() }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small mb-1" for="ia-tanggal">Tanggal</label>
                        <input type="date" name="tanggal" id="ia-tanggal" class="form-control form-control-sm" value="{{ now()->format('Y-m-d') }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small mb-1" for="ia-mulai">Jam Mulai</label>
                        <input type="time" name="waktu_mulai" id="ia-mulai" class="form-control form-control-sm" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small mb-1" for="ia-selesai">Jam Selesai</label>
                        <input type="time" name="waktu_selesai" id="ia-selesai" class="form-control form-control-sm">
                    </div>
                    <div class="col-12">
                        <input type="text" name="lokasi" class="form-control form-control-sm" placeholder="Tempat atau link, contoh: Ruang Rapat Lt. 2" aria-label="Lokasi">
                    </div>
                    <div class="col-12">
                        <input type="text" name="catatan" class="form-control form-control-sm" placeholder="Catatan (opsional), contoh: bawa portofolio" aria-label="Catatan">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-warning btn-sm">Tambah Interview</button>
            </div>
        </form>
    </div>
</div>
