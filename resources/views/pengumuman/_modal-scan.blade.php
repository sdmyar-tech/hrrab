{{-- Modal pemindai QR absen acara (kamera HP). Kalau kamera tidak bisa dipakai, pegawai bisa scan dengan aplikasi kamera bawaan. --}}
<div class="modal fade" id="modalScanAcara" tabindex="-1" aria-labelledby="modalScanAcaraJudul" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title h5" id="modalScanAcaraJudul"><i class="bi bi-qr-code-scan me-1"></i>Scan QR Absen</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <p class="small text-muted mb-2">Arahkan kamera ke QR yang ditampilkan panitia di lokasi acara.</p>
                <div id="pembacaQr" class="pembaca-qr rounded-3 overflow-hidden bg-dark"></div>
                <div class="alert alert-warning small mt-2 mb-0" data-pesan-scan hidden></div>
                <p class="small text-muted mt-3 mb-0">
                    <i class="bi bi-info-circle me-1"></i>Kamera tidak terbuka? Scan QR memakai <strong>aplikasi kamera HP</strong> —
                    tautannya langsung membuka halaman absen.
                </p>
            </div>
        </div>
    </div>
</div>
<style>
    .pembaca-qr { width: 100%; min-height: 260px; }
    .pembaca-qr video { width: 100% !important; object-fit: cover; }
</style>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html5-qrcode/2.3.8/html5-qrcode.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const modalEl = document.getElementById('modalScanAcara');
        const pesan = modalEl.querySelector('[data-pesan-scan]');
        const PESAN_IZIN = 'Izin kamera ditolak atau kamera sedang dipakai aplikasi lain. Izinkan kamera, atau gunakan aplikasi kamera HP.';
        let pembaca = null;
        // Nyalakan & matikan kamera dijalankan BERURUTAN: membuka lagi saat kamera lama belum mati tidak boleh bertabrakan.
        let antrean = Promise.resolve();
        const antre = (kerja) => { antrean = antrean.then(kerja).catch(() => {}); return antrean; };
        const terbuka = () => modalEl.classList.contains('show');
        const tampilPesan = (teks) => { pesan.textContent = teks; pesan.hidden = !teks; };

        function nyalakan() {
            antre(async function () {
                if (!terbuka() || pembaca) return; // modal sudah ditutup sebelum gilirannya
                const p = new Html5Qrcode('pembacaQr');
                pembaca = p;
                try {
                    await p.start({ facingMode: 'environment' }, {
                        fps: 10,
                        // Kotak bidik 75% sisi terpendek; minimal 50 px (syarat pustaka) walau tampilan belum selesai diukur.
                        qrbox: (w, h) => { const s = Math.max(50, Math.floor(Math.min(w, h) * 0.75)); return { width: s, height: s }; },
                    }, bacaHasil, () => {});
                } catch (e) {
                    pembaca = null;
                    if (terbuka()) tampilPesan(PESAN_IZIN);
                    return;
                }
                if (!terbuka()) matikan(); // ditutup saat kamera masih menyala
            });
        }

        function matikan() {
            return antre(async function () {
                const p = pembaca;
                pembaca = null;
                if (!p) return;
                // isScanning bisa masih false bila start baru selesai setelah modal ditutup — pakai status pustaka (2 = memindai, 3 = jeda).
                const berjalan = p.isScanning || (typeof p.getState === 'function' && [2, 3].includes(p.getState()));
                try { if (berjalan) await p.stop(); } catch (e) { /* sudah berhenti */ }
                // Jaring pengaman: matikan semua track kamera yang masih menempel di video.
                modalEl.querySelectorAll('#pembacaQr video').forEach((v) => { if (v.srcObject) v.srcObject.getTracks().forEach((t) => t.stop()); });
                try { p.clear(); } catch (e) { /* tampilan sudah bersih */ }
            });
        }

        function bacaHasil(teks) {
            let url = null;
            try { url = new URL(teks); } catch (e) { /* bukan URL */ }
            // Hanya QR absen dari aplikasi ini yang diikuti.
            if (url && url.origin === location.origin && /^\/acara\/\d+\/absen$/.test(url.pathname)) {
                tampilPesan('');
                matikan().then(() => { location.href = url.href; });
            } else {
                tampilPesan('QR ini bukan QR absen acara HRRAB.');
            }
        }

        document.querySelectorAll('[data-scan-acara]').forEach((b) => b.addEventListener('click', () => bootstrap.Modal.getOrCreateInstance(modalEl).show()));

        modalEl.addEventListener('shown.bs.modal', function () {
            tampilPesan('');
            if (!window.isSecureContext || !navigator.mediaDevices || typeof Html5Qrcode === 'undefined') {
                return tampilPesan('Kamera tidak bisa dibuka di browser ini. Gunakan aplikasi kamera HP untuk scan QR.');
            }
            nyalakan();
        });
        modalEl.addEventListener('hidden.bs.modal', matikan);
        // Video kamera yang dihapus saat masih memulai memicu AbortError dari pustaka — wajar, bukan kesalahan.
        window.addEventListener('unhandledrejection', (e) => { if (e.reason && e.reason.name === 'AbortError' && !terbuka()) e.preventDefault(); });
    });
</script>
