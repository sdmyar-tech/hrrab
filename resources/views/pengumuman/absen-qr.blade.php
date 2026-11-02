{{-- Layar QR untuk panitia (tampilkan di proyektor / tablet di lokasi). QR berganti tiap {{ $langkah }} detik agar tidak bisa dibagikan. --}}
@extends('layouts.guest.app')

@section('title', 'QR Absen')
@section('body-class', 'layar-qr-body')

@section('content')
<main class="layar-qr">
    <div class="layar-qr-alat">
        <x-button variant="ghost" size="sm" icon="bi-arrows-fullscreen" data-layar-penuh>Layar penuh</x-button>
        <x-button variant="ghost" size="sm" icon="bi-x-lg" :href="route('pengumuman.absen', $pengumuman)">Tutup</x-button>
    </div>

    <header class="layar-qr-kepala">
        <p class="layar-qr-eyebrow"><i class="bi bi-qr-code-scan" aria-hidden="true"></i> Absen Acara</p>
        <h1 class="layar-qr-judul">{{ $pengumuman->judul }}</h1>
        <p class="layar-qr-waktu">{{ $pengumuman->absenJendelaLabel() }}@if ($pengumuman->lokasi) · {{ \App\Support\Lokasi::label($pengumuman->lokasi) }}@endif</p>
    </header>

    <div class="layar-qr-kotak" data-kotak-qr>
        <div class="layar-qr-gambar" id="qrAbsen" role="img" aria-label="Kode QR absen — berganti otomatis"></div>
        <div class="layar-qr-bilah" role="progressbar" aria-label="Sisa waktu QR" aria-valuemin="0" aria-valuemax="{{ $langkah }}">
            <span data-bilah style="width: 100%"></span>
        </div>
        <p class="layar-qr-sisa">QR berganti dalam <strong data-sisa>{{ $langkah }}</strong> detik</p>
    </div>
    <p class="layar-qr-fase" data-pesan-fase role="status" hidden></p>

    <ol class="layar-qr-langkah" aria-label="Cara absen">
        <li><span>1</span>Buka kamera HP atau HRRAB → Pengumuman</li>
        <li><span>2</span>Scan QR di layar ini</li>
        <li><span>3</span>Kehadiran langsung tercatat</li>
    </ol>

    <dl class="layar-qr-angka" aria-live="polite">
        <div><dt>Sudah absen</dt><dd data-angka="sudah">0</dd></div>
        <div><dt>Belum</dt><dd data-angka="belum">0</dd></div>
        <div><dt>Izin/cuti</dt><dd data-angka="berhalangan">0</dd></div>
        <div><dt>Diundang</dt><dd data-angka="total">0</dd></div>
    </dl>
</main>
@endsection

@section('styles')
<style>
    /* Layar gelap bertema merek: kontras tinggi agar QR mudah dipindai dari jauh. */
    .layar-qr-body { margin: 0; background: var(--hr-gray-900); color: #fff; }
    .layar-qr {
        position: relative; min-height: 100vh; display: flex; flex-direction: column; align-items: center; justify-content: center;
        gap: var(--hr-space-5); padding: var(--hr-space-7) var(--hr-space-4) var(--hr-space-6); text-align: center;
        background:
            radial-gradient(60rem 36rem at 50% -10%, rgba(136, 15, 18, .85), transparent 70%),
            radial-gradient(circle at 1px 1px, rgba(255, 255, 255, .06) 1px, transparent 0) var(--hr-gray-900);
        background-size: auto, 24px 24px;
    }
    .layar-qr-alat { position: absolute; top: var(--hr-space-4); right: var(--hr-space-4); display: flex; gap: var(--hr-space-2); }
    .layar-qr-alat .btn { --bs-btn-color: rgba(255, 255, 255, .85); --bs-btn-hover-color: #fff; --bs-btn-hover-bg: rgba(255, 255, 255, .12); --bs-btn-active-bg: rgba(255, 255, 255, .18); --bs-btn-active-color: #fff; }
    .layar-qr-eyebrow { margin: 0 0 var(--hr-space-2); font-size: var(--hr-text-sm); font-weight: var(--hr-weight-semibold); letter-spacing: .1em; text-transform: uppercase; color: var(--hr-primary-200); }
    .layar-qr-judul { margin: 0; font-size: clamp(var(--hr-text-3xl), 1.5rem + 2vw, 3rem); font-weight: 800; letter-spacing: -.02em; line-height: 1.15; }
    .layar-qr-waktu { margin: var(--hr-space-2) 0 0; font-size: var(--hr-text-lg); color: rgba(255, 255, 255, .75); }

    /* Kartu QR dengan denyut halus (tanda layar hidup). */
    .layar-qr-kotak { position: relative; width: min(84vw, 440px); padding: var(--hr-space-5); border-radius: var(--hr-radius-lg); background: #fff; color: var(--hr-gray-800); box-shadow: var(--hr-shadow-xl); }
    .layar-qr-kotak::before { content: ""; position: absolute; inset: -8px; border-radius: calc(var(--hr-radius-lg) + 8px); border: 2px solid rgba(255, 255, 255, .35); animation: qr-denyut 2.4s var(--hr-easing) infinite; pointer-events: none; }
    @keyframes qr-denyut { 0% { opacity: .9; transform: scale(1); } 70%, 100% { opacity: 0; transform: scale(1.06); } }
    .layar-qr-gambar img, .layar-qr-gambar canvas { width: 100% !important; height: auto !important; display: block; }
    .layar-qr-kotak.redup .layar-qr-gambar { opacity: .08; }
    .layar-qr-bilah { height: 6px; margin-top: var(--hr-space-4); border-radius: var(--hr-radius-pill); background: var(--hr-gray-200); overflow: hidden; }
    .layar-qr-bilah span { display: block; height: 100%; border-radius: inherit; background: var(--hr-primary); transition: width 1s linear; }
    .layar-qr-sisa { margin: var(--hr-space-2) 0 0; font-size: var(--hr-text-sm); color: var(--hr-gray-500); }
    .layar-qr-sisa strong { color: var(--hr-gray-900); font-variant-numeric: tabular-nums; }
    .layar-qr-fase { margin: 0; padding: var(--hr-space-3) var(--hr-space-4); border-radius: var(--hr-radius-md); background: rgba(255, 255, 255, .1); border: 1px solid rgba(255, 255, 255, .2); }

    .layar-qr-langkah { display: flex; flex-wrap: wrap; justify-content: center; gap: var(--hr-space-2) var(--hr-space-5); margin: 0; padding: 0; list-style: none; font-size: var(--hr-text-base); color: rgba(255, 255, 255, .8); }
    .layar-qr-langkah li { display: inline-flex; align-items: center; gap: var(--hr-space-2); }
    .layar-qr-langkah span { width: 24px; height: 24px; display: inline-grid; place-items: center; border-radius: 50%; background: rgba(255, 255, 255, .15); font-size: var(--hr-text-xs); font-weight: var(--hr-weight-bold); }

    .layar-qr-angka { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: var(--hr-space-3); width: min(100%, 560px); margin: 0; }
    .layar-qr-angka > div { padding: var(--hr-space-3); border-radius: var(--hr-radius-md); background: rgba(255, 255, 255, .08); border: 1px solid rgba(255, 255, 255, .12); }
    .layar-qr-angka dt { font-size: var(--hr-text-xs); font-weight: var(--hr-weight-medium); color: rgba(255, 255, 255, .7); }
    .layar-qr-angka dd { margin: 0; font-size: var(--hr-text-3xl); font-weight: 800; font-variant-numeric: tabular-nums; }
    @media (max-width: 575.98px) {
        .layar-qr { padding-top: calc(var(--hr-space-7) + var(--hr-space-4)); }
        .layar-qr-angka { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    @media (prefers-reduced-motion: reduce) { .layar-qr-kotak::before { animation: none; } }
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
                ['sudah', 'belum', 'berhalangan', 'total'].forEach((k) => { document.querySelector('[data-angka="' + k + '"]').textContent = d[k]; });
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
