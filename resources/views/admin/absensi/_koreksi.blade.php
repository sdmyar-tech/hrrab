{{--
    Modal koreksi absensi (satu untuk seluruh halaman). Dibuka oleh tombol ber-atribut data-koreksi='{...}'
    (lihat _tombol-koreksi). Alur: 1) pilih apa yang terjadi → 2) isi seperlunya → 3) alasan (klik cepat / ketik).
    Form di badan modal; tombol Simpan di kaki memakai form="formKoreksi" (pola x-modal Batch 4).
--}}
@php $ikAbs = \App\Support\Absensi\TampilanAbsensi::IKON; @endphp
<x-modal id="modalKoreksi" title="Koreksi Absensi" :icon="$ikAbs['koreksi']" size="lg" scrollable>
    <form method="POST" id="formKoreksi" novalidate>
        @csrf
        <input type="hidden" name="tanggal">
        <input type="hidden" name="aksi" value="simpan">
        <input type="hidden" name="status_manual">

        <dl class="hr-konteks mb-4">
            <dt>Tanggal</dt><dd data-k="judul"></dd>
            <dt><i class="bi {{ $ikAbs['mesin'] }} me-1" aria-hidden="true"></i>Jam di mesin</dt><dd><span class="abs-jam" data-k="scan"></span></dd>
            <dt>Surat lembur</dt><dd data-k="surat"></dd>
        </dl>

        <fieldset class="mb-4">
            <legend class="abs-langkah-form">1. Apa yang perlu diperbaiki?</legend>
            <div class="hr-opsi-grup abs-opsi-3">
                <label class="hr-opsi" for="km-jam">
                    <input class="form-check-input" type="radio" name="mode" value="jam" id="km-jam">
                    <span><strong><i class="bi bi-clock" aria-hidden="true"></i>Jam masuk/pulang</strong><small>Lupa finger atau jam keliru</small></span>
                </label>
                <label class="hr-opsi" for="km-tidak">
                    <input class="form-check-input" type="radio" name="mode" value="tidak_masuk" id="km-tidak">
                    <span><strong><i class="bi bi-door-closed" aria-hidden="true"></i>Tidak masuk</strong><small>Izin, sakit, dinas luar</small></span>
                </label>
                <label class="hr-opsi" for="km-lembur">
                    <input class="form-check-input" type="radio" name="mode" value="lembur" id="km-lembur">
                    <span><strong><i class="bi {{ $ikAbs['lembur'] }}" aria-hidden="true"></i>Lembur</strong><small>Lupa finger saat lembur</small></span>
                </label>
            </div>
        </fieldset>

        <p class="abs-langkah-form">2. Isi datanya</p>
        <div class="mb-4" data-mode="jam">
            <div class="row g-3">
                <div class="col-6"><x-input type="time" name="jam_masuk" id="k-masuk" label="Jam masuk" field-class="mb-0" /></div>
                <div class="col-6"><x-input type="time" name="jam_keluar" id="k-keluar" label="Jam pulang" field-class="mb-0" /></div>
            </div>
            <p class="form-text mb-0 mt-2">Isi sesuai jam sebenarnya. Status (On Time / Telat) dihitung otomatis.</p>
        </div>

        <div class="mb-4" data-mode="tidak_masuk">
            <div class="abs-chip-baris" role="group" aria-label="Keterangan tidak masuk">
                @foreach (\App\Models\Absensi::STATUS_MANUAL as $kunci => $label)
                    @continue($kunci === \App\Models\Absensi::MANUAL_HADIR)
                    <button type="button" class="hr-chip" data-status="{{ $kunci }}" aria-pressed="false">{{ $label }}</button>
                @endforeach
            </div>
            <p class="form-text mb-0 mt-2">Cuti yang sudah disetujui otomatis tercatat — tidak perlu diisi di sini.</p>
        </div>

        <div class="mb-4" data-mode="lembur">
            <label class="form-label" for="k-lembur">Lama lembur (jam:menit)</label>
            <div class="abs-chip-baris">
                <input type="text" name="lembur_manual" id="k-lembur" class="form-control abs-isian-pendek" placeholder="Contoh: 02:00" pattern="\d{1,2}:\d{2}" inputmode="numeric">
                @foreach (['01:00', '02:00', '03:00'] as $j)
                    <button type="button" class="hr-chip" data-lembur="{{ $j }}">{{ (int) $j }} jam</button>
                @endforeach
            </div>
            <p class="form-text mb-0 mt-2" data-k="info-lembur">Hanya berlaku bila pegawai punya surat lembur yang disetujui.</p>
        </div>

        <label class="form-label abs-langkah-form required d-block" for="k-alasan">3. Alasan</label>
        <div class="abs-chip-baris mb-2" role="group" aria-label="Alasan cepat">
            @foreach (\App\Support\Absensi\TampilanAbsensi::ALASAN_CEPAT as $a)
                <button type="button" class="hr-chip" data-alasan="{{ $a }}" aria-pressed="false">{{ $a }}</button>
            @endforeach
        </div>
        <x-textarea name="alasan" id="k-alasan" rows="2" maxlength="500" placeholder="Tulis alasan lain, contoh: lupa scan pulang" required />
    </form>

    <x-slot:footer>
        <x-button variant="ghost" icon="bi-arrow-counterclockwise" class="me-auto" data-k="reset">Batalkan koreksi</x-button>
        <x-button variant="secondary" data-bs-dismiss="modal">Batal</x-button>
        <x-button type="submit" form="formKoreksi" icon="bi-check-lg">Simpan</x-button>
    </x-slot:footer>
</x-modal>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const modal = document.getElementById('modalKoreksi');
        const form = document.getElementById('formKoreksi');
        if (!form) return;
        const k = (n) => modal.querySelector('[data-k="' + n + '"]');
        const judul = document.getElementById('modalKoreksi-judul');
        const alasan = form.alasan;
        // Chip pilihan: tanda aktif + aria-pressed (dibaca pembaca layar sebagai tombol yang ditekan).
        const tandai = (daftar, pilih) => daftar.forEach((b) => { b.classList.toggle('is-active', b === pilih); b.setAttribute('aria-pressed', b === pilih ? 'true' : 'false'); });

        function pilihMode(mode) {
            form.querySelectorAll('[name="mode"]').forEach((r) => { r.checked = r.value === mode; });
            form.querySelectorAll('[data-mode]').forEach((el) => { el.hidden = el.dataset.mode !== mode; });
        }
        function pilihStatus(status) {
            form.status_manual.value = status || '';
            const chip = form.querySelectorAll('[data-status]');
            tandai(chip, Array.from(chip).find((b) => b.dataset.status === status) || null);
        }

        form.querySelectorAll('[name="mode"]').forEach((r) => r.addEventListener('change', () => pilihMode(r.value)));
        form.querySelectorAll('[data-status]').forEach((b) => b.addEventListener('click', () => pilihStatus(b.dataset.status)));
        form.querySelectorAll('[data-lembur]').forEach((b) => b.addEventListener('click', () => { form.lembur_manual.value = b.dataset.lembur; }));
        form.querySelectorAll('[data-alasan]').forEach((b) => b.addEventListener('click', function () {
            alasan.value = b.dataset.alasan;
            alasan.dispatchEvent(new Event('input', { bubbles: true })); // perbarui penghitung karakter
            tandai(form.querySelectorAll('[data-alasan]'), b);
        }));

        document.querySelectorAll('[data-koreksi]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const d = JSON.parse(btn.dataset.koreksi);
                form.action = d.url;
                form.tanggal.value = d.tanggal;
                form.aksi.value = 'simpan';
                judul.textContent = d.nama;
                k('judul').textContent = d.label;
                k('scan').innerHTML = (d.scan_masuk || '<span class="abs-jam is-kosong">–</span>') + ' → ' + (d.scan_keluar || '<span class="abs-jam is-kosong">–</span>');
                k('surat').textContent = d.surat || 'Tidak ada';
                k('info-lembur').textContent = d.surat ? d.surat + '. Isi lama lembur sebenarnya.' : 'Pegawai ini tidak punya surat lembur disetujui pada tanggal ini — lembur manual tetap dicatat.';
                form.jam_masuk.value = d.masuk || '';
                form.jam_keluar.value = d.keluar || '';
                form.lembur_manual.value = d.lembur || '';
                alasan.value = d.alasan || '';
                alasan.dispatchEvent(new Event('input', { bubbles: true }));
                tandai(form.querySelectorAll('[data-alasan]'), null);
                pilihStatus(d.status && d.status !== 'hadir' ? d.status : '');
                // Mode awal menebak kebutuhan: status manual → "Tidak masuk"; lembur belum terverifikasi → "Lembur"; selain itu "Jam".
                pilihMode(d.status && d.status !== 'hadir' ? 'tidak_masuk' : (d.perlu_lembur ? 'lembur' : 'jam'));
                k('reset').hidden = !d.dikoreksi;
                bootstrap.Modal.getOrCreateInstance(modal).show();
            });
        });

        // Validasi di sini (form novalidate) agar pesannya jelas & seragam, bukan tooltip bawaan browser.
        const tahan = function (e, judulPesan, teks, fokus) {
            e.preventDefault();
            e.stopImmediatePropagation();
            Swal.fire({ icon: 'info', title: judulPesan, text: teks || '', confirmButtonColor: '#880f12' }).then(() => fokus && fokus.focus());
        };
        form.addEventListener('submit', function (e) {
            if (form.aksi.value === 'reset') return;
            const mode = form.querySelector('[name="mode"]:checked')?.value;
            if (mode === 'jam') {
                pilihStatus('');
                if (!form.jam_masuk.value && !form.jam_keluar.value) return tahan(e, 'Isi jam masuk atau pulang', '', form.jam_masuk);
                if (form.jam_masuk.value && form.jam_keluar.value && form.jam_keluar.value <= form.jam_masuk.value) return tahan(e, 'Jam pulang harus setelah jam masuk', '', form.jam_keluar);
            } else if (mode === 'tidak_masuk' && !form.status_manual.value) {
                return tahan(e, 'Pilih keterangan', 'Izin, Sakit, Dinas Luar, atau Alpa.');
            } else if (mode === 'lembur' && !/^\d{1,2}:\d{2}$/.test(form.lembur_manual.value)) {
                return tahan(e, 'Isi lama lembur', 'Format jam:menit, mis. 02:00.', form.lembur_manual);
            }
            if (!alasan.value.trim()) return tahan(e, 'Isi alasan koreksi', 'Klik salah satu alasan cepat, atau tulis sendiri.', alasan);
        }, true);

        // "Batalkan koreksi" = kembali ke data mesin (tanpa wajib alasan).
        k('reset').addEventListener('click', function () {
            Swal.fire({
                icon: 'question', title: 'Batalkan koreksi?', text: 'Absensi hari ini kembali memakai jam dari mesin.',
                showCancelButton: true, confirmButtonColor: '#880f12', confirmButtonText: 'Ya, batalkan koreksi', cancelButtonText: 'Tidak', reverseButtons: true,
            }).then(function (r) {
                if (!r.isConfirmed) return;
                form.aksi.value = 'reset';
                alasan.required = false;
                form.submit();
            });
        });
        modal.addEventListener('hidden.bs.modal', () => { alasan.required = true; });
        // Fokus selalu di dalam modal saat terbuka (Esc & Tab langsung berfungsi walau pop-up lain baru ditutup).
        modal.addEventListener('shown.bs.modal', () => (form.querySelector('[name="mode"]:checked') || form.alasan).focus({ preventScroll: true }));
    });
</script>
