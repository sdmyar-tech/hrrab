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
        let pembaca = null;

        const tampilPesan = (teks) => { pesan.textContent = teks; pesan.hidden = !teks; };
        const berhenti = () => { if (pembaca && pembaca.isScanning) pembaca.stop().catch(() => {}); };

        document.querySelectorAll('[data-scan-acara]').forEach((b) => b.addEventListener('click', () => bootstrap.Modal.getOrCreateInstance(modalEl).show()));

        modalEl.addEventListener('shown.bs.modal', function () {
            tampilPesan('');
            if (!window.isSecureContext || !navigator.mediaDevices || typeof Html5Qrcode === 'undefined') {
                return tampilPesan('Kamera tidak bisa dibuka di browser ini. Gunakan aplikasi kamera HP untuk scan QR.');
            }
            pembaca = pembaca || new Html5Qrcode('pembacaQr');
            pembaca.start({ facingMode: 'environment' }, { fps: 10, qrbox: (w, h) => { const s = Math.floor(Math.min(w, h) * 0.75); return { width: s, height: s }; } },
                function (teks) {
                    let url = null;
                    try { url = new URL(teks); } catch (e) { /* bukan URL */ }
                    // Hanya QR absen dari aplikasi ini yang diikuti.
                    if (url && url.origin === location.origin && /^\/acara\/\d+\/absen$/.test(url.pathname)) {
                        berhenti();
                        tampilPesan('');
                        location.href = url.href;
                    } else {
                        tampilPesan('QR ini bukan QR absen acara HRRAB.');
                    }
                },
                () => {}
            ).catch(() => tampilPesan('Izin kamera ditolak atau kamera sedang dipakai aplikasi lain. Izinkan kamera, atau gunakan aplikasi kamera HP.'));
        });
        modalEl.addEventListener('hidden.bs.modal', berhenti);
    });
</script>
