{{-- Layar QR untuk panitia (tampilkan di proyektor / tablet di lokasi). QR berganti tiap {{ $langkah }} detik agar tidak bisa dibagikan. --}}
@extends('layouts.guest.app')

@section('title', 'QR Absen')

@section('content')
<div class="layar-qr min-vh-100 d-flex flex-column align-items-center justify-content-center p-3 text-center">
    <div class="d-flex gap-2 position-absolute top-0 end-0 p-3">
        <button type="button" class="btn btn-light btn-sm" data-layar-penuh><i class="bi bi-arrows-fullscreen me-1"></i>Layar penuh</button>
        <a href="{{ route('pengumuman.absen', $pengumuman) }}" class="btn btn-light btn-sm"><i class="bi bi-x-lg me-1"></i>Tutup</a>
    </div>

    <p class="text-uppercase small fw-semibold mb-1 opacity-75"><i class="bi bi-qr-code-scan me-1"></i>Absen Acara</p>
    <h1 class="h2 fw-bold mb-1">{{ $pengumuman->judul }}</h1>
    <p class="opacity-75 mb-4">{{ $pengumuman->absenJendelaLabel() }}</p>

    <div class="kotak-qr bg-white rounded-4 p-3 shadow" data-kotak-qr>
        <div id="qrAbsen" aria-label="Kode QR absen"></div>
        <div class="progress mt-3" style="height:6px" role="progressbar" aria-label="Sisa waktu QR">
            <div class="progress-bar" data-bilah style="width:100%;background:#880f12"></div>
        </div>
        <p class="small text-muted mb-0 mt-2">QR berganti dalam <strong data-sisa>{{ $langkah }}</strong> detik</p>
    </div>
    <div class="pesan-fase alert alert-light mt-3 mb-0" data-pesan-fase hidden></div>

    <div class="d-flex gap-4 mt-4 fs-5">
        <div><strong class="fs-2 d-block" data-angka="sudah">0</strong><span class="small opacity-75">sudah absen</span></div>
        <div><strong class="fs-2 d-block" data-angka="belum">0</strong><span class="small opacity-75">belum</span></div>
        <div><strong class="fs-2 d-block" data-angka="total">0</strong><span class="small opacity-75">diundang</span></div>
    </div>
    <p class="small opacity-75 mt-4 mb-0">Buka HRRAB → Pengumuman → <strong>Absen Sekarang</strong>, atau scan dengan kamera HP.</p>
</div>
@endsection

@section('styles')
<style>
    body { background: #2b0405; }
    .layar-qr { color: #fff; background: radial-gradient(circle at top, #880f12, #2b0405 70%); }
    .kotak-qr { width: min(88vw, 420px); }
    #qrAbsen img, #qrAbsen canvas { width: 100% !important; height: auto !important; }
    .kotak-qr.redup #qrAbsen { opacity: .08; }
</style>
@endsection

@section('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const LANGKAH = {{ $langkah }};
        const urlToken = @json(route('pengumuman.absen.qr-token', $pengumuman));
        const kotak = document.querySelector('[data-kotak-qr]');
        const pesanFase = document.querySelector('[data-pesan-fase]');
        const qr = new QRCode(document.getElementById('qrAbsen'), { width: 512, height: 512, correctLevel: QRCode.CorrectLevel.M });
        let sisa = 0, urlTerakhir = '';

        function tampilFase(fase) {
            const teks = fase === 'belum_buka' ? 'Absen belum dibuka. QR akan tampil otomatis saat waktunya tiba.'
                : fase === 'tutup' ? 'Waktu absen sudah ditutup.' : '';
            pesanFase.textContent = teks;
            pesanFase.hidden = !teks;
            kotak.classList.toggle('redup', !!teks);
        }

        async function muat() {
            try {
                const r = await fetch(urlToken, { headers: { Accept: 'application/json' }, cache: 'no-store' });
                if (!r.ok) throw new Error(r.status);
                const d = await r.json();
                if (d.url !== urlTerakhir) { qr.clear(); qr.makeCode(d.url); urlTerakhir = d.url; }
                sisa = d.sisa;
                ['sudah', 'belum', 'total'].forEach((k) => { document.querySelector('[data-angka="' + k + '"]').textContent = d[k]; });
                tampilFase(d.fase);
            } catch (e) {
                pesanFase.textContent = 'Koneksi terputus — mencoba lagi…';
                pesanFase.hidden = false;
                sisa = 3;
            }
        }

        // Hitung mundur; ambil token baru saat habis (ditambah 1 detik agar token server sudah berganti).
        let memuat = false;
        const muatSekali = (jeda) => { memuat = true; setTimeout(() => muat().finally(() => { memuat = false; }), jeda); };
        setInterval(function () {
            if (memuat) return;
            sisa = Math.max(0, sisa - 1);
            document.querySelector('[data-sisa]').textContent = sisa;
            document.querySelector('[data-bilah]').style.width = (sisa / LANGKAH * 100) + '%';
            if (sisa === 0) muatSekali(1000);
        }, 1000);
        // Angka kehadiran tetap diperbarui walau QR belum berganti.
        setInterval(function () { if (!memuat && sisa > 5) muatSekali(0); }, 10000);
        muatSekali(0);

        document.querySelector('[data-layar-penuh]').addEventListener('click', function () {
            document.fullscreenElement ? document.exitFullscreen() : document.documentElement.requestFullscreen().catch(() => {});
        });
    });
</script>
@endsection
