{{--
    Tiket izin keluar (ditunjukkan di layar ke security — TANPA QR, keputusan 21 Okt 2026).
    Anti-tangkapan layar: pita bergaris bergerak + jam berjalan sinkron server.
    Cetak = salinan arsip bertanda "tidak berlaku di gerbang" (tetap konsisten dengan aturan tangkapan layar).
--}}
@extends(Auth::user()->panelLayout())

@section('title', 'Tiket Izin Keluar')

@section('content')
@php
    $p = $izin->dataPegawai;
    $k = $p?->kepegawaian;
    [$mulai, $selesai] = $masaBerlaku;
    $status = $izin->statusTiket();
    $foto = $p?->dokumen?->pas_foto ? \App\Support\Berkas::url($p->dokumen->pas_foto) : null;
    // Teks pita per status; diperbarui skrip tiap detik saat status berubah.
    $teks = [
        'berlaku' => ['IZIN KELUAR SAH', 'Berlaku sampai ' . ($izin->tidakKembali() ? 'akhir hari (tidak kembali)' : $selesai->format('H:i'))],
        'belum' => ['BELUM BERLAKU', 'Bisa dipakai mulai ' . ($mulai->isToday() ? '' : $mulai->translatedFormat('d M') . ' ') . 'pukul ' . $mulai->format('H:i')],
        'lewat' => ['TIKET SUDAH LEWAT', 'Berakhir ' . $selesai->translatedFormat('d M Y') . ' pukul ' . $selesai->format('H:i')],
    ];
@endphp
<main class="dashboard-content">
    <div class="container-fluid">
        <x-page-header title="Tiket Izin Keluar" eyebrow="Izin" class="tiket-tak-cetak"
            :back="$milikSendiri ? route('pegawai.izin-keluar') : route('admin.izin-keluar.show', $izin)" :back-label="$milikSendiri ? 'Izin' : 'Detail Izin'">
            <x-slot:actions>
                <x-button variant="secondary" icon="bi-printer" data-cetak-tiket>Cetak Salinan</x-button>
                <x-button icon="bi-arrows-fullscreen" data-layar-penuh>Layar Penuh</x-button>
            </x-slot:actions>
        </x-page-header>

        <article class="tiket" id="tiket" data-status="{{ $status }}" aria-label="Tiket izin keluar {{ $p?->nama_lengkap }}">
            <p class="tiket-salinan" aria-hidden="true">SALINAN ARSIP — TIDAK BERLAKU SEBAGAI TIKET KELUAR</p>

            {{-- Pita status: garis bergerak + jam berjalan = tanda tiket asli (bukan tangkapan layar). --}}
            <div class="tiket-pita">
                <div class="tiket-pita-isi">
                    <i class="bi bi-door-open" aria-hidden="true"></i>
                    <div>
                        <div class="tiket-status" data-teks-status>{{ $teks[$status][0] }}</div>
                        <div class="tiket-sub" data-teks-sub>{{ $teks[$status][1] }}</div>
                    </div>
                </div>
            </div>

            <div class="tiket-jam" aria-live="off">
                <span class="tiket-denyut" aria-hidden="true"></span>
                <span data-jam>{{ now()->format('H:i:s') }}</span>
                <small data-tanggal>{{ now()->translatedFormat('l, d M Y') }}</small>
            </div>

            <div class="tiket-badan">
                <div class="tiket-orang">
                    @if ($foto)
                        <img src="{{ $foto }}" alt="Foto {{ $p?->nama_lengkap }}" class="tiket-foto">
                    @else
                        <x-avatar :name="$p?->nama_lengkap ?? '?'" size="xl" class="tiket-foto-inisial" />
                    @endif
                    <div class="min-w-0">
                        <div class="tiket-nama">{{ $p?->nama_lengkap ?? '-' }}</div>
                        <div class="tiket-meta">{{ collect([$k?->jabatan, $izin->unit_kerja])->filter()->implode(' · ') }}</div>
                        @if ($p?->nip)<div class="tiket-meta">NIP {{ $p->nip }}</div>@endif
                    </div>
                </div>

                <div class="tiket-sobek" aria-hidden="true"></div>

                <dl class="tiket-rinci">
                    <div><dt>Tanggal</dt><dd>{{ $izin->tanggal->translatedFormat('l, d M Y') }}</dd></div>
                    <div><dt>Jam keluar</dt><dd class="tiket-besar">{{ substr((string) $izin->jam_keluar, 0, 5) }}</dd></div>
                    <div><dt>Kembali</dt><dd class="tiket-besar">{{ $izin->tidakKembali() ? 'Tidak kembali' : substr((string) $izin->jam_kembali, 0, 5) }}</dd></div>
                    <div><dt>Keperluan</dt><dd>{{ $izin->keperluanLabel() }}</dd></div>
                    <div class="lebar"><dt>Alasan</dt><dd>{{ $izin->alasan }}</dd></div>
                </dl>

                <div class="tiket-setuju">
                    <div class="tiket-label">Disetujui oleh</div>
                    @foreach ($penyetuju as $l)
                        <div class="tiket-setuju-baris">
                            <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
                            <div><strong>{{ str_replace(' (Approve Akhir)', '', $l['label']) }}</strong> · {{ $l['oleh'] ?? '-' }}<small>{{ $l['waktu'] }}</small></div>
                        </div>
                    @endforeach
                </div>

                <div class="tiket-kode">
                    <span class="tiket-label">Kode tiket</span>
                    <strong>{{ $izin->kode_tiket }}</strong>
                </div>
            </div>

            <div class="tiket-kaki">
                <i class="bi bi-shield-check me-1" aria-hidden="true"></i><strong>Untuk security:</strong> tiket sah bila jam di atas berjalan dan garis pada pita bergerak.
                Tangkapan layar (screenshot) dan cetakan tidak berlaku. Cocokkan foto &amp; nama dengan orangnya.
            </div>
        </article>
    </div>
</main>

<style>
    /* Warna pita per status dari token: hijau = sah, kuning = belum berlaku, abu = lewat. */
    .tiket { --t-warna: var(--hr-success); --t-gelap: var(--hr-success-dark); max-width: 440px; margin: 0 auto; border-radius: var(--hr-radius-lg); overflow: hidden;
        background: var(--admin-surface); color: var(--admin-text); box-shadow: var(--hr-shadow-lg); border: 1px solid var(--admin-border); }
    .tiket[data-status="belum"] { --t-warna: var(--hr-warning); --t-gelap: var(--hr-warning-dark); }
    .tiket[data-status="lewat"] { --t-warna: var(--hr-neutral); --t-gelap: var(--hr-gray-700); }
    .tiket-salinan { display: none; }

    /* Pita bergaris yang terus bergerak (sulit dipalsukan dengan gambar diam). */
    .tiket-pita { position: relative; color: #fff; padding: var(--hr-space-4);
        background: repeating-linear-gradient(-45deg, var(--t-warna) 0 18px, var(--t-gelap) 18px 36px);
        background-size: 51px 51px; animation: tiket-geser 1.2s linear infinite; }
    .tiket[data-status="lewat"] .tiket-pita { animation-duration: 4s; }
    @keyframes tiket-geser { to { background-position: 51px 0; } }
    .tiket-pita-isi { display: flex; align-items: center; gap: var(--hr-space-3); padding: var(--hr-space-2) var(--hr-space-3); border-radius: var(--hr-radius); background: rgba(0, 0, 0, .28); }
    .tiket-pita-isi > .bi { font-size: 2rem; }
    .tiket-status { font-size: var(--hr-text-xl); font-weight: 800; letter-spacing: .04em; line-height: 1.1; }
    .tiket-sub { font-size: var(--hr-text-sm); opacity: .95; }

    .tiket-jam { display: flex; align-items: baseline; justify-content: center; gap: var(--hr-space-2); flex-wrap: wrap; padding: var(--hr-space-3) var(--hr-space-4);
        background: var(--admin-surface-soft); border-bottom: 1px dashed var(--admin-border-strong); font-variant-numeric: tabular-nums; }
    .tiket-jam [data-jam] { font-size: var(--hr-text-3xl); font-weight: 800; letter-spacing: .03em; color: var(--t-gelap); }
    html[data-theme="dark"] .tiket-jam [data-jam] { color: var(--t-warna); }
    .tiket-jam small { color: var(--admin-muted); }
    .tiket-denyut { width: 10px; height: 10px; border-radius: 50%; background: var(--t-warna); align-self: center; animation: tiket-denyut 1s ease-in-out infinite; }
    @keyframes tiket-denyut { 50% { transform: scale(.5); opacity: .35; } }

    .tiket-badan { padding: var(--hr-space-4) var(--hr-space-5); }
    .tiket-orang { display: flex; align-items: center; gap: var(--hr-space-4); }
    .tiket-foto { width: 84px; height: 104px; border-radius: var(--hr-radius-md); object-fit: cover; flex: none; border: 2px solid var(--t-warna); }
    .tiket-foto-inisial { box-shadow: 0 0 0 2px var(--t-warna); }
    .tiket-nama { font-size: var(--hr-text-xl); font-weight: 800; line-height: 1.2; overflow-wrap: anywhere; color: var(--admin-heading); }
    .tiket-meta { font-size: var(--hr-text-sm); color: var(--admin-muted); }

    .tiket-sobek { height: 0; border-top: 2px dashed var(--admin-border-strong); margin: var(--hr-space-4) calc(-1 * var(--hr-space-5)); position: relative; }
    .tiket-sobek::before, .tiket-sobek::after { content: ''; position: absolute; top: -11px; width: 20px; height: 20px; border-radius: 50%; background: var(--admin-bg); }
    .tiket-sobek::before { left: -10px; }
    .tiket-sobek::after { right: -10px; }

    .tiket-rinci { display: grid; grid-template-columns: 1fr 1fr; gap: var(--hr-space-3) var(--hr-space-4); margin: 0; }
    .tiket-rinci .lebar { grid-column: 1 / -1; }
    .tiket-rinci dt, .tiket-label { font-size: var(--hr-text-xs); font-weight: var(--hr-weight-bold); letter-spacing: .06em; text-transform: uppercase; color: var(--admin-muted); }
    .tiket-rinci dd { margin: 0; font-weight: var(--hr-weight-semibold); overflow-wrap: anywhere; color: var(--admin-heading); }
    .tiket-besar { font-size: var(--hr-text-2xl); font-weight: 800 !important; color: var(--t-gelap) !important; }
    html[data-theme="dark"] .tiket-besar { color: var(--t-warna) !important; }

    .tiket-setuju { margin-top: var(--hr-space-4); padding: var(--hr-space-3); border-radius: var(--hr-radius-md); background: var(--hr-success-soft); }
    .tiket-setuju-baris { display: flex; gap: var(--hr-space-2); font-size: var(--hr-text-sm); margin-top: var(--hr-space-1); }
    .tiket-setuju-baris .bi { color: var(--hr-success); }
    .tiket-setuju-baris small { display: block; color: var(--admin-muted); }

    .tiket-kode { display: flex; justify-content: space-between; align-items: center; margin-top: var(--hr-space-4); padding-top: var(--hr-space-3); border-top: 1px solid var(--admin-border); }
    .tiket-kode strong { font-family: var(--hr-font-mono); font-size: var(--hr-text-xl); letter-spacing: .08em; color: var(--admin-heading); }
    .tiket-kaki { padding: var(--hr-space-3) var(--hr-space-5); font-size: var(--hr-text-xs); color: var(--hr-gray-600); background: var(--admin-surface-soft); border-top: 1px solid var(--admin-border); }

    /* Layar penuh: tiket di tengah, siap ditunjukkan ke security. */
    .tiket:fullscreen { max-width: none; border-radius: 0; overflow: auto; display: flex; flex-direction: column; justify-content: center; }
    .tiket:fullscreen > * { width: 100%; max-width: 480px; margin-left: auto; margin-right: auto; }

    /* Cetak: hanya tiket, tanpa animasi, dengan tanda salinan (tidak berlaku di gerbang). */
    @media print {
        .app-sidebar, .app-topbar, .app-footer, .skip-link, .tiket-tak-cetak, .banner-umpan, .swal2-container { display: none !important; }
        .app-main { margin: 0 !important; }
        .dashboard-content > .container-fluid { padding: 0 !important; }
        body { background: #fff !important; }
        .tiket { box-shadow: none; border: 1px solid #999; max-width: 480px; }
        .tiket-pita, .tiket-denyut { animation: none !important; }
        .tiket-salinan { display: block; margin: 0; padding: var(--hr-space-2); background: #111; color: #fff; text-align: center; font-size: var(--hr-text-xs); font-weight: var(--hr-weight-bold); letter-spacing: .06em; }
        .tiket-pita, .tiket-pita-isi { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    }
</style>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const tiket = document.getElementById('tiket');
        // Jam mengikuti server (selisih jam HP diabaikan), zona waktu aplikasi.
        const selisih = {{ now()->getTimestampMs() }} - Date.now();
        const mulai = {{ $mulai->getTimestampMs() }}, selesai = {{ $selesai->getTimestampMs() }};
        const teks = @json($teks);
        const zona = @json(config('app.timezone'));
        const fJam = new Intl.DateTimeFormat('id-ID', { timeZone: zona, hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false });
        const fTgl = new Intl.DateTimeFormat('id-ID', { timeZone: zona, weekday: 'long', day: '2-digit', month: 'short', year: 'numeric' });
        const elJam = tiket.querySelector('[data-jam]'), elTgl = tiket.querySelector('[data-tanggal]');

        function detik() {
            const kini = Date.now() + selisih;
            elJam.textContent = fJam.format(kini).replace(/\./g, ':');
            elTgl.textContent = fTgl.format(kini);
            const status = kini < mulai ? 'belum' : (kini > selesai ? 'lewat' : 'berlaku');
            if (tiket.dataset.status !== status) {
                tiket.dataset.status = status;
                tiket.querySelector('[data-teks-status]').textContent = teks[status][0];
                tiket.querySelector('[data-teks-sub]').textContent = teks[status][1];
            }
        }
        detik();
        setInterval(detik, 1000);

        const tombol = document.querySelector('[data-layar-penuh]');
        if (!tiket.requestFullscreen) tombol.hidden = true;
        tombol.addEventListener('click', () => tiket.requestFullscreen().catch(() => {}));
        document.querySelector('[data-cetak-tiket]').addEventListener('click', () => window.print());
    });
</script>
@endsection
