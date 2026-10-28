@extends(Auth::user()->panelLayout())

@section('content')
@php
    $p = $izin->dataPegawai;
    $k = $p?->kepegawaian;
    [$mulai, $selesai] = $masaBerlaku;
    $status = $izin->statusTiket();
    $foto = $p?->dokumen?->pas_foto ? \App\Support\Berkas::url($p->dokumen->pas_foto) : null;
    $inisial = collect(explode(' ', (string) $p?->nama_lengkap))->filter()->take(2)->map(fn ($s) => mb_substr($s, 0, 1))->implode('');
    // Teks pita per status; diperbarui skrip tiap detik saat status berubah.
    $teks = [
        'berlaku' => ['IZIN KELUAR SAH', 'Berlaku sampai ' . ($izin->tidakKembali() ? 'akhir hari (tidak kembali)' : $selesai->format('H:i'))],
        'belum' => ['BELUM BERLAKU', 'Bisa dipakai mulai ' . ($mulai->isToday() ? '' : $mulai->translatedFormat('d M') . ' ') . 'pukul ' . $mulai->format('H:i')],
        'lewat' => ['TIKET SUDAH LEWAT', 'Berakhir ' . $selesai->translatedFormat('d M Y') . ' pukul ' . $selesai->format('H:i')],
    ];
@endphp
<div class="dashboard-content">
    <div class="container-fluid px-3 px-lg-4 py-4">
        <a href="{{ $milikSendiri ? route('pegawai.izin-keluar') : route('admin.izin-keluar.show', $izin) }}" class="text-decoration-none text-muted small d-inline-block mb-2">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>

        <div class="tiket" id="tiket" data-status="{{ $status }}">
            {{-- Pita status: garis bergerak + jam berjalan = tanda tiket asli (bukan tangkapan layar). --}}
            <div class="tiket-pita">
                <div class="tiket-pita-isi">
                    <i class="bi bi-door-open"></i>
                    <div>
                        <div class="tiket-status" data-teks-status>{{ $teks[$status][0] }}</div>
                        <div class="tiket-sub" data-teks-sub>{{ $teks[$status][1] }}</div>
                    </div>
                </div>
            </div>

            <div class="tiket-jam">
                <span class="tiket-denyut" aria-hidden="true"></span>
                <span data-jam>{{ now()->format('H:i:s') }}</span>
                <small data-tanggal>{{ now()->translatedFormat('l, d M Y') }}</small>
            </div>

            <div class="tiket-badan">
                <div class="tiket-orang">
                    @if ($foto)
                        <img src="{{ $foto }}" alt="Foto {{ $p?->nama_lengkap }}" class="tiket-foto">
                    @else
                        <span class="tiket-foto tiket-inisial">{{ $inisial ?: '?' }}</span>
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
                            <i class="bi bi-check-circle-fill"></i>
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
                <i class="bi bi-shield-check me-1"></i><strong>Untuk security:</strong> tiket sah bila jam di atas berjalan dan garis pada pita bergerak.
                Tangkapan layar (screenshot) tidak berlaku. Cocokkan foto &amp; nama dengan orangnya.
            </div>
        </div>

        <div class="d-flex justify-content-center gap-2 mt-3">
            <button type="button" class="btn btn-outline-secondary btn-sm" data-layar-penuh><i class="bi bi-arrows-fullscreen me-1"></i>Layar penuh</button>
        </div>
    </div>
</div>

<style>
    .tiket { --t-warna: #15803d; --t-gelap: #14532d; max-width: 440px; margin: 0 auto; border-radius: 18px; overflow: hidden;
        background: #fff; color: #1f2937; box-shadow: 0 10px 30px rgba(16, 24, 40, .14); border: 1px solid #e5e7eb; }
    .tiket[data-status="belum"] { --t-warna: #b45309; --t-gelap: #78350f; }
    .tiket[data-status="lewat"] { --t-warna: #6b7280; --t-gelap: #374151; }

    /* Pita bergaris yang terus bergerak (sulit dipalsukan dengan gambar diam). */
    .tiket-pita { position: relative; color: #fff; padding: 1rem 1.1rem;
        background: repeating-linear-gradient(-45deg, var(--t-warna) 0 18px, var(--t-gelap) 18px 36px);
        background-size: 51px 51px; animation: tiket-geser 1.2s linear infinite; }
    .tiket[data-status="lewat"] .tiket-pita { animation-duration: 4s; }
    @keyframes tiket-geser { to { background-position: 51px 0; } }
    .tiket-pita-isi { display: flex; align-items: center; gap: .75rem; padding: .55rem .75rem; border-radius: 12px; background: rgba(0, 0, 0, .28); }
    .tiket-pita-isi > .bi { font-size: 1.9rem; }
    .tiket-status { font-size: 1.25rem; font-weight: 800; letter-spacing: .04em; line-height: 1.1; }
    .tiket-sub { font-size: .82rem; opacity: .95; }

    .tiket-jam { display: flex; align-items: baseline; justify-content: center; gap: .5rem; flex-wrap: wrap; padding: .6rem 1rem;
        background: #f8fafc; border-bottom: 1px dashed #d1d5db; font-variant-numeric: tabular-nums; }
    .tiket-jam [data-jam] { font-size: 1.75rem; font-weight: 800; letter-spacing: .03em; color: var(--t-gelap); }
    .tiket-jam small { color: #6b7280; }
    .tiket-denyut { width: 10px; height: 10px; border-radius: 50%; background: var(--t-warna); align-self: center; animation: tiket-denyut 1s ease-in-out infinite; }
    @keyframes tiket-denyut { 50% { transform: scale(.5); opacity: .35; } }

    .tiket-badan { padding: 1rem 1.1rem; }
    .tiket-orang { display: flex; align-items: center; gap: .85rem; }
    .tiket-foto { width: 84px; height: 104px; border-radius: 10px; object-fit: cover; flex: none; border: 2px solid var(--t-warna); }
    .tiket-inisial { display: grid; place-items: center; background: #f1f5f9; color: var(--t-gelap); font-size: 1.6rem; font-weight: 800; }
    .tiket-nama { font-size: 1.15rem; font-weight: 800; line-height: 1.2; overflow-wrap: anywhere; }
    .tiket-meta { font-size: .8rem; color: #6b7280; }

    .tiket-sobek { height: 0; border-top: 2px dashed #d1d5db; margin: 1rem -1.1rem; position: relative; }
    .tiket-sobek::before, .tiket-sobek::after { content: ''; position: absolute; top: -11px; width: 20px; height: 20px; border-radius: 50%; background: var(--admin-surface-soft, #f3f4f6); }
    .tiket-sobek::before { left: -10px; }
    .tiket-sobek::after { right: -10px; }

    .tiket-rinci { display: grid; grid-template-columns: 1fr 1fr; gap: .65rem 1rem; margin: 0; }
    .tiket-rinci .lebar { grid-column: 1 / -1; }
    .tiket-rinci dt, .tiket-label { font-size: .68rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; color: #6b7280; }
    .tiket-rinci dd { margin: 0; font-weight: 600; overflow-wrap: anywhere; }
    .tiket-besar { font-size: 1.35rem; font-weight: 800 !important; color: var(--t-gelap); }

    .tiket-setuju { margin-top: 1rem; padding: .7rem .8rem; border-radius: 10px; background: #f0fdf4; border: 1px solid #bbf7d0; }
    .tiket-setuju-baris { display: flex; gap: .5rem; font-size: .82rem; margin-top: .35rem; }
    .tiket-setuju-baris .bi { color: #15803d; }
    .tiket-setuju-baris small { display: block; color: #6b7280; }

    .tiket-kode { display: flex; justify-content: space-between; align-items: center; margin-top: 1rem; padding-top: .75rem; border-top: 1px solid #e5e7eb; }
    .tiket-kode strong { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size: 1.15rem; letter-spacing: .08em; }
    .tiket-kaki { padding: .75rem 1.1rem; font-size: .75rem; color: #4b5563; background: #f9fafb; border-top: 1px solid #e5e7eb; }

    /* Layar penuh: tiket di tengah, latar gelap, siap ditunjukkan ke security. */
    .tiket:fullscreen { max-width: none; border-radius: 0; overflow: auto; display: flex; flex-direction: column; justify-content: center; }
    .tiket:fullscreen > * { width: 100%; max-width: 480px; margin-left: auto; margin-right: auto; }
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
    });
</script>
@endsection
