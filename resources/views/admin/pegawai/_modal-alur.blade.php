{{-- Modal Kepala SDM/Admin: ubah tahap persetujuan cuti/lembur & atasan seorang pegawai. --}}
@use('App\Support\PengajuanWorkflow', 'W')
@php
    // Nilai awal: alur khusus yang tersimpan, atau tahap alur otomatis cuti saat ini.
    $tahapAwal = old('tahap', $alurKhusus?->daftarTahap()
        ?? collect($alurApproval[0]['tahap'] ?? [])->pluck('level')->all());
    $atasanAwal = (int) old('atasan_user_id', $alurKhusus?->atasan_user_id);
    $gmAwal = (int) old('gm_user_id', $alurKhusus?->gm_user_id);
    $unitUtama = $akun?->unitKerja();
    $kepalaOtomatis = W::kepalaUnit($unitUtama, $pegawai->id)->pluck('name')->implode(', ');
    $tahapInfo = [
        W::LEVEL_KEPALA_DIVISI => ['Atasan langsung', 'bi-person-up', 'Menyetujui pertama kali (cuti: juga memilih pengganti tugas).'],
        W::LEVEL_GM => ['GM', 'bi-briefcase', 'Persetujuan General Manager.'],
        W::LEVEL_KEPALA_SDM => ['Kepala SDM', 'bi-shield-check', 'Persetujuan akhir oleh SDM.'],
    ];
    $grupRole = $pilihanAlur['atasan']->groupBy(fn ($u) => $u->roleLabel());
@endphp
<div class="modal fade" id="modalAlurPegawai" tabindex="-1" aria-labelledby="judulModalAlur" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
        <form class="modal-content" method="POST" action="{{ route('admin.pegawai.alur.update', $pegawai) }}" id="formAlurPegawai">
            @csrf @method('PUT')
            <div class="modal-header">
                <h2 class="modal-title" id="judulModalAlur"><i class="bi bi-diagram-3 me-1"></i>Ubah Alur & Atasan · {{ $pegawai->nama_lengkap }}</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted mb-3">
                    Centang tahap yang harus dilalui pengajuan <strong>cuti & lembur</strong> pegawai ini. Urutan selalu
                    Atasan → GM → Kepala SDM; tahap terakhir yang dicentang menjadi persetujuan akhir.
                </p>

                <div class="d-grid gap-2" data-daftar-tahap>
                    @foreach (\App\Models\AlurPegawai::URUTAN as $level)
                        @php [$judul, $ikon, $ket] = $tahapInfo[$level]; @endphp
                        <div class="tahap-alur" data-tahap="{{ $level }}">
                            <label class="d-flex align-items-start gap-2 mb-0">
                                <input class="form-check-input mt-1" type="checkbox" name="tahap[]" value="{{ $level }}" @checked(in_array($level, $tahapAwal, true))>
                                <span class="tahap-ikon"><i class="bi {{ $ikon }}"></i></span>
                                <span class="min-w-0">
                                    <span class="fw-semibold d-block">{{ $judul }}</span>
                                    <span class="small text-muted">{{ $ket }}</span>
                                </span>
                            </label>

                            @if ($level === W::LEVEL_KEPALA_DIVISI)
                                <div class="tahap-pilih">
                                    <label class="form-label small mb-1" for="alur-atasan">Siapa atasannya?</label>
                                    <select name="atasan_user_id" id="alur-atasan" class="form-select form-select-sm">
                                        <option value="">Otomatis: kepala unit {{ $unitUtama ?? '-' }}{{ $kepalaOtomatis ? " ({$kepalaOtomatis})" : ' — belum ada, naik ke GM' }}</option>
                                        @foreach ($grupRole as $role => $daftar)
                                            <optgroup label="{{ $role }}">
                                                @foreach ($daftar as $u)
                                                    <option value="{{ $u->id }}" @selected($atasanAwal === $u->id)>{{ $u->name }}{{ $u->unitKerja() ? ' · ' . $u->unitKerja() : '' }}</option>
                                                @endforeach
                                            </optgroup>
                                        @endforeach
                                    </select>
                                </div>
                            @elseif ($level === W::LEVEL_GM)
                                <div class="tahap-pilih">
                                    <label class="form-label small mb-1" for="alur-gm">GM yang menyetujui</label>
                                    <select name="gm_user_id" id="alur-gm" class="form-select form-select-sm">
                                        <option value="">GM mana pun (GM bidang unit diutamakan)</option>
                                        @foreach ($pilihanAlur['gm'] as $u)
                                            <option value="{{ $u->id }}" @selected($gmAwal === $u->id)>{{ $u->name }}{{ $u->divisi ? ' · ' . implode(', ', $u->divisi) : '' }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>

                <div class="alur-pratinjau mt-3" data-pratinjau-alur aria-live="polite"></div>

                <label class="form-label small mt-3 mb-1" for="alur-catatan">Catatan <span class="text-muted fw-normal">(opsional)</span></label>
                <input type="text" name="catatan" id="alur-catatan" class="form-control form-control-sm" maxlength="255"
                    value="{{ old('catatan', $alurKhusus?->catatan) }}" placeholder="Contoh: sementara selama kepala unit cuti panjang">
                <p class="form-text mb-0">Pengajuan yang sedang menunggu ikut alur baru mulai tahap berikutnya. Kembalikan ke otomatis kapan saja.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check2"></i> Simpan Alur</button>
            </div>
        </form>
    </div>
</div>

<style>
    .tahap-alur { border: 1px solid var(--admin-border); border-radius: 10px; padding: .75rem .9rem; background: var(--admin-surface); transition: border-color .15s, background .15s; }
    .tahap-alur.aktif { border-color: #e3b9ba; background: var(--hr-primary-soft); }
    .tahap-alur label { cursor: pointer; }
    .tahap-ikon { width: 30px; height: 30px; flex: none; display: grid; place-items: center; border-radius: 8px; background: var(--admin-surface-soft); color: var(--hr-primary); }
    .tahap-alur.aktif .tahap-ikon { background: var(--hr-primary); color: #fff; }
    .tahap-pilih { margin: .6rem 0 0 calc(1em + 30px + 1rem); }
    .tahap-alur:not(.aktif) .tahap-pilih { display: none; }
    .alur-pratinjau { display: flex; flex-wrap: wrap; align-items: center; gap: .35rem; padding: .6rem .8rem; border-radius: 10px; background: var(--admin-surface-soft); font-size: .8125rem; }
    .alur-pratinjau .langkah { padding: .2rem .55rem; border-radius: 999px; background: var(--admin-surface); border: 1px solid var(--admin-border); font-weight: 600; }
    .alur-pratinjau .langkah.akhir { background: #e6f4ea; border-color: #c4e6cd; color: #146c2e; }
    @media (max-width: 575.98px) { .tahap-pilih { margin-left: 0; } }
</style>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('formAlurPegawai');
        const pratinjau = form.querySelector('[data-pratinjau-alur]');
        const label = { kepala_divisi: 'Atasan', gm: 'GM', kepala_sdm: 'Kepala SDM' };

        function nama(sel) {
            const o = sel.options[sel.selectedIndex];
            return sel.value ? o.textContent.split(' · ')[0] : '';
        }

        function segarkan() {
            const dipilih = [];
            form.querySelectorAll('[data-tahap]').forEach((kotak) => {
                const cek = kotak.querySelector('input[type=checkbox]');
                kotak.classList.toggle('aktif', cek.checked);
                if (!cek.checked) return;
                const sel = kotak.querySelector('select');
                dipilih.push(label[cek.value] + (sel && nama(sel) ? ' (' + nama(sel) + ')' : ''));
            });
            pratinjau.innerHTML = '';
            if (!dipilih.length) {
                pratinjau.innerHTML = '<span class="text-danger fw-semibold"><i class="bi bi-exclamation-triangle me-1"></i>Pilih minimal satu tahap.</span>';
                return;
            }
            pratinjau.insertAdjacentHTML('beforeend', '<span class="text-muted me-1">Alur:</span>');
            dipilih.forEach((t, i) => {
                const s = document.createElement('span');
                s.className = 'langkah' + (i === dipilih.length - 1 ? ' akhir' : '');
                s.textContent = t + (i === dipilih.length - 1 ? ' · akhir' : '');
                pratinjau.appendChild(s);
                if (i < dipilih.length - 1) pratinjau.insertAdjacentHTML('beforeend', '<i class="bi bi-arrow-right text-muted"></i>');
            });
        }

        form.addEventListener('change', segarkan);
        form.addEventListener('submit', function (e) {
            if (!form.querySelector('input[name="tahap[]"]:checked')) {
                e.preventDefault(); e.stopImmediatePropagation();
                Swal.fire({ icon: 'info', title: 'Pilih minimal satu tahap', confirmButtonColor: '#8b1215' });
            }
        }, true);
        segarkan();

        // Ada galat validasi dari server → buka lagi modalnya.
        @if ($errors->has('tahap') || $errors->has('tahap.*') || $errors->has('atasan_user_id') || $errors->has('gm_user_id'))
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalAlurPegawai')).show();
        @endif
    });
</script>
