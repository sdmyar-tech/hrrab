{{--
    Bagian "Absen Acara" di form pengumuman (SDM/Admin/GM).
    Alur peserta: scan QR di lokasi → izinkan lokasi → hadir bila dalam radius.
--}}
@php
    $p = $pengumuman;
    $kriteria = $p->absen_kriteria ?? [];
    $aktif = (bool) old('absen_aktif', $p->absen_aktif);
    $semua = (bool) old('absen_semua', $kriteria['semua'] ?? false);
    $pilUnit = old('absen_unit', $kriteria['unit'] ?? []);
    $pilJabatan = old('absen_jabatan', $kriteria['jabatan'] ?? []);
    $pilUser = array_map('intval', old('absen_user', $kriteria['user'] ?? []));
    $radius = (int) old('absen_radius', $p->absen_radius ?? \App\Support\AbsenAcara::RADIUS_BAWAAN);
    $lat = old('absen_lat', $p->absen_lat);
    $lng = old('absen_lng', $p->absen_lng);
    $pusat = config('hrrab.absen_acara.pusat_peta', [0.5071, 101.4478]);
    $kandidatJs = $absen['kandidat']->map(fn ($k) => ['id' => $k['id'], 'u' => $k['unit'], 'j' => $k['jabatan']])->values();
@endphp
<section class="panel p-4 mt-3" id="panelAbsen">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
        <div>
            <h2 class="h6 fw-bold mb-1"><i class="bi bi-qr-code-scan me-1 text-primary"></i>Absen Acara</h2>
            <p class="small text-muted mb-0">Peserta scan QR di lokasi acara, lalu lokasi HP-nya dicek. Di luar radius <strong>ditolak</strong>.</p>
        </div>
        <div class="form-check form-switch m-0">
            <input class="form-check-input" type="checkbox" role="switch" name="absen_aktif" value="1" id="absen_aktif" @checked($aktif)>
            <label class="form-check-label fw-semibold" for="absen_aktif">Tetapkan absen untuk acara ini</label>
        </div>
    </div>

    <div data-absen-isi class="mt-3" @if (! $aktif) hidden @endif>
        @error('absen_peserta')<div class="alert alert-danger small py-2">{{ $message }}</div>@enderror

        {{-- 1. Waktu --}}
        <p class="fw-semibold small mb-2"><span class="langkah-no">1</span>Kapan absen bisa dilakukan?</p>
        <div class="row g-2 mb-2">
            <div class="col-sm-6">
                <label class="form-label small mb-1" for="absen_buka">Dibuka</label>
                <input type="datetime-local" name="absen_buka" id="absen_buka" class="form-control @error('absen_buka') is-invalid @enderror"
                    value="{{ old('absen_buka', $p->absen_buka?->format('Y-m-d\TH:i')) }}">
            </div>
            <div class="col-sm-6">
                <label class="form-label small mb-1" for="absen_tutup">Ditutup</label>
                <input type="datetime-local" name="absen_tutup" id="absen_tutup" class="form-control @error('absen_tutup') is-invalid @enderror"
                    value="{{ old('absen_tutup', $p->absen_tutup?->format('Y-m-d\TH:i')) }}">
            </div>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
            <button type="button" class="btn btn-outline-secondary btn-sm" data-isi-jam-acara>
                <i class="bi bi-magic me-1"></i>Isi dari jam acara
            </button>
            <span class="form-text m-0">Buka 30 menit sebelum acara, tutup saat selesai. Di luar waktu ini absen tidak bisa (status "Kedaluwarsa").</span>
        </div>

        {{-- 2. Lokasi --}}
        <p class="fw-semibold small mb-2"><span class="langkah-no">2</span>Di mana titik acaranya?</p>
        <div class="d-flex flex-wrap gap-2 mb-2">
            <button type="button" class="btn btn-outline-primary btn-sm" data-lokasi-saya><i class="bi bi-crosshair me-1"></i>Pakai lokasi saya sekarang</button>
            <input type="text" class="form-control form-control-sm" style="max-width:260px" data-koordinat
                placeholder="atau tempel koordinat / link Google Maps" aria-label="Koordinat atau link Google Maps">
        </div>
        <div id="petaAbsen" class="peta-absen rounded-3 border mb-2" role="application" aria-label="Peta lokasi acara. Klik untuk memindahkan titik."></div>
        <input type="hidden" name="absen_lat" value="{{ $lat }}">
        <input type="hidden" name="absen_lng" value="{{ $lng }}">
        <div class="d-flex flex-wrap align-items-center gap-2 small mb-3">
            <span class="text-muted" data-info-titik>{{ $lat ? 'Titik: ' . round($lat, 6) . ', ' . round($lng, 6) : 'Klik peta untuk menaruh titik acara.' }}</span>
            <span class="ms-sm-auto text-muted">Radius</span>
            @foreach ([50, 100, 200, 500] as $r)
                <button type="button" class="btn btn-sm {{ $radius === $r ? 'btn-primary' : 'btn-outline-secondary' }}" data-radius="{{ $r }}">{{ $r }} m</button>
            @endforeach
            <input type="number" name="absen_radius" class="form-control form-control-sm" style="width:90px" min="{{ \App\Support\AbsenAcara::RADIUS_MIN }}"
                max="{{ \App\Support\AbsenAcara::RADIUS_MAKS }}" value="{{ $radius }}" aria-label="Radius (meter)">
        </div>

        {{-- 3. Peserta --}}
        <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
            <p class="fw-semibold small mb-0"><span class="langkah-no">3</span>Siapa yang wajib absen?</p>
            <span class="badge text-bg-primary ms-auto fs-6" data-total-peserta>0 pegawai</span>
        </div>
        <div class="form-check form-switch mb-2">
            <input class="form-check-input" type="checkbox" role="switch" name="absen_semua" value="1" id="absen_semua" @checked($semua)>
            <label class="form-check-label" for="absen_semua">Pilih semua pegawai <span class="text-muted small">({{ $absen['kandidat']->count() }} akun pegawai aktif)</span></label>
        </div>
        <p class="small text-muted mb-2" data-saat-tidak-semua>Boleh dikombinasikan — mis. semua <em>Kepala</em> + seluruh pegawai unit <em>SDM</em> + beberapa nama.</p>

        <div class="row g-2" data-saat-tidak-semua>
            @foreach ([
                ['kunci' => 'unit', 'judul' => 'Per unit', 'cari' => 'Cari unit…', 'ikon' => 'bi-diagram-3', 'opsi' => $absen['unit'], 'pilih' => $pilUnit],
                ['kunci' => 'jabatan', 'judul' => 'Per jabatan', 'cari' => 'Cari jabatan…', 'ikon' => 'bi-person-badge', 'opsi' => $absen['jabatan'], 'pilih' => $pilJabatan],
            ] as $kel)
                <div class="col-md-6 col-xl-4">
                    <div class="border rounded-3 p-2 h-100">
                        <div class="small fw-semibold mb-1"><i class="bi {{ $kel['ikon'] }} me-1"></i>{{ $kel['judul'] }} <span class="text-muted fw-normal" data-jumlah-pilih="{{ $kel['kunci'] }}"></span></div>
                        <input type="search" class="form-control form-control-sm mb-1" placeholder="{{ $kel['cari'] }}" data-cari-daftar="{{ $kel['kunci'] }}">
                        <div class="daftar-pilih" data-daftar="{{ $kel['kunci'] }}">
                            @forelse ($kel['opsi'] as $nama => $n)
                                <label class="form-check small mb-0" data-teks="{{ mb_strtolower($nama) }}">
                                    <input class="form-check-input" type="checkbox" name="absen_{{ $kel['kunci'] }}[]" value="{{ $nama }}" @checked(in_array($nama, $kel['pilih'], true))>
                                    <span class="form-check-label">{{ $nama }} <span class="text-muted">· {{ $n }}</span></span>
                                </label>
                            @empty
                                <p class="small text-muted mb-0">Tidak ada data.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            @endforeach
            <div class="col-md-12 col-xl-4">
                <div class="border rounded-3 p-2 h-100">
                    <div class="small fw-semibold mb-1"><i class="bi bi-person-check me-1"></i>Pilih nama <span class="text-muted fw-normal" data-jumlah-pilih="user"></span></div>
                    <input type="search" class="form-control form-control-sm mb-1" placeholder="Cari nama / unit…" data-cari-daftar="user">
                    <div class="daftar-pilih" data-daftar="user">
                        @foreach ($absen['kandidat'] as $k)
                            <label class="form-check small mb-0" data-teks="{{ mb_strtolower($k['nama'] . ' ' . implode(' ', $k['unit']) . ' ' . implode(' ', $k['jabatan'])) }}">
                                <input class="form-check-input" type="checkbox" name="absen_user[]" value="{{ $k['id'] }}" @checked(in_array($k['id'], $pilUser, true))>
                                <span class="form-check-label">{{ $k['nama'] }}<span class="d-block text-muted" style="font-size:.75rem">{{ implode(', ', $k['unit']) ?: '-' }}</span></span>
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css">
<style>
    .peta-absen { height: 280px; z-index: 0; }
    .daftar-pilih { max-height: 190px; overflow-y: auto; padding-right: .25rem; }
    .daftar-pilih .form-check { display: flex; gap: .4rem; padding: .2rem .25rem; border-radius: .375rem; }
    .daftar-pilih .form-check:hover { background: var(--bs-tertiary-bg); }
    .daftar-pilih .form-check-input { margin-left: 0; flex-shrink: 0; }
    .langkah-no { display: inline-grid; place-items: center; width: 1.35rem; height: 1.35rem; border-radius: 50%; background: var(--bs-primary); color: #fff; font-size: .75rem; margin-right: .4rem; }
</style>
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
@include('pengumuman._ambil-lokasi')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const panel = document.getElementById('panelAbsen');
        const form = panel.closest('form');
        const sakelar = document.getElementById('absen_aktif');
        const isi = panel.querySelector('[data-absen-isi]');
        const kandidat = @json($kandidatJs);
        const el = (s) => panel.querySelector(s);
        const lat = el('[name=absen_lat]'), lng = el('[name=absen_lng]'), radius = el('[name=absen_radius]');

        // ---------- Peta (Leaflet + OpenStreetMap) ----------
        let peta = null, penanda = null, lingkaran = null;
        function tampilTitik(pindahPeta) {
            if (!peta || !lat.value) return;
            const titik = [parseFloat(lat.value), parseFloat(lng.value)];
            if (!penanda) {
                penanda = L.marker(titik, { draggable: true }).addTo(peta);
                penanda.on('dragend', () => { const p = penanda.getLatLng(); aturTitik(p.lat, p.lng, false); });
                lingkaran = L.circle(titik, { radius: +radius.value || 100, color: '#880f12', fillOpacity: .12 }).addTo(peta);
            }
            penanda.setLatLng(titik);
            lingkaran.setLatLng(titik).setRadius(+radius.value || 100);
            if (pindahPeta) peta.fitBounds(lingkaran.getBounds(), { maxZoom: 18 });
            el('[data-info-titik]').textContent = 'Titik: ' + titik[0].toFixed(6) + ', ' + titik[1].toFixed(6);
        }
        function aturTitik(a, b, pindahPeta) {
            lat.value = (+a).toFixed(7); lng.value = (+b).toFixed(7);
            tampilTitik(pindahPeta);
        }
        function siapkanPeta() {
            if (peta || typeof L === 'undefined') { peta && peta.invalidateSize(); return; }
            peta = L.map('petaAbsen').setView(lat.value ? [+lat.value, +lng.value] : @json($pusat), lat.value ? 17 : 13);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; OpenStreetMap' }).addTo(peta);
            peta.on('click', (e) => aturTitik(e.latlng.lat, e.latlng.lng, false));
            tampilTitik(true);
        }

        el('[data-lokasi-saya]').addEventListener('click', function () {
            const tombol = this, isiAwal = tombol.innerHTML;
            tombol.disabled = true;
            ambilLokasi({
                akurasiCukup: 25, maksDetik: 30,
                onProgres: (p) => { tombol.textContent = 'Mencari lokasi… ' + (p.akurasi !== null ? '±' + p.akurasi + ' m' : p.detik + ' dtk'); },
            }).then((pos) => {
                aturTitik(pos.coords.latitude, pos.coords.longitude, true);
                if (pos.coords.accuracy > 50) Swal.fire({ icon: 'info', title: 'Lokasi kurang akurat (±' + Math.round(pos.coords.accuracy) + ' m)', text: 'Geser penanda di peta ke titik acara yang tepat.' });
            }).catch((err) => {
                const p = panduanLokasi(err);
                Swal.fire({ icon: 'info', title: p.judul, text: p.teks + ' Atau klik titik acara langsung di peta.' });
            }).finally(() => { tombol.disabled = false; tombol.innerHTML = isiAwal; });
        });
        // Tempel "-0.47, 101.38" atau link Google Maps (…@-0.47,101.38,17z / ?q=-0.47,101.38).
        el('[data-koordinat]').addEventListener('change', function () {
            const m = this.value.match(/(-?\d{1,2}\.\d+)\s*,\s*(-?\d{1,3}\.\d+)/);
            if (!m) return Swal.fire({ icon: 'info', title: 'Koordinat tidak dikenali', text: 'Contoh: -0.4766, 101.3870 atau link Google Maps yang memuat koordinat.' });
            aturTitik(m[1], m[2], true);
            this.value = '';
        });
        panel.querySelectorAll('[data-radius]').forEach((b) => b.addEventListener('click', function () {
            radius.value = b.dataset.radius;
            radius.dispatchEvent(new Event('input'));
        }));
        radius.addEventListener('input', function () {
            panel.querySelectorAll('[data-radius]').forEach((b) => {
                b.classList.toggle('btn-primary', b.dataset.radius === radius.value);
                b.classList.toggle('btn-outline-secondary', b.dataset.radius !== radius.value);
            });
            tampilTitik(false);
        });

        // ---------- Waktu ----------
        el('[data-isi-jam-acara]').addEventListener('click', function () {
            const tgl = form.querySelector('[name=tanggal]').value;
            const mulai = form.querySelector('[name=jam_mulai]').value;
            const selesai = form.querySelector('[name=jam_selesai]').value;
            if (!tgl || !mulai) return Swal.fire({ icon: 'info', title: 'Isi tanggal & jam mulai acara dulu', text: 'Ada di bagian "Waktu & Tempat" (jadwal Sekali).' });
            const buka = new Date(tgl + 'T' + mulai); buka.setMinutes(buka.getMinutes() - 30);
            const tutup = selesai ? new Date(tgl + 'T' + selesai) : new Date(new Date(tgl + 'T' + mulai).getTime() + 2 * 3600e3);
            const fmt = (d) => d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0') + 'T' + String(d.getHours()).padStart(2, '0') + ':' + String(d.getMinutes()).padStart(2, '0');
            el('[name=absen_buka]').value = fmt(buka);
            el('[name=absen_tutup]').value = fmt(tutup);
        });

        // ---------- Peserta ----------
        const semua = document.getElementById('absen_semua');
        const terpilih = (k) => Array.from(panel.querySelectorAll('[name="absen_' + k + '[]"]:checked')).map((c) => c.value);
        function hitung() {
            const u = terpilih('unit'), j = terpilih('jabatan'), id = terpilih('user').map(Number);
            const n = semua.checked ? kandidat.length
                : kandidat.filter((k) => k.u.some((x) => u.includes(x)) || k.j.some((x) => j.includes(x)) || id.includes(k.id)).length;
            el('[data-total-peserta]').textContent = n + ' pegawai';
            el('[data-total-peserta]').classList.toggle('text-bg-danger', n === 0);
            el('[data-total-peserta]').classList.toggle('text-bg-primary', n > 0);
            ['unit', 'jabatan', 'user'].forEach((k) => { const c = terpilih(k).length; el('[data-jumlah-pilih="' + k + '"]').textContent = c ? '(' + c + ' dipilih)' : ''; });
            panel.querySelectorAll('[data-saat-tidak-semua]').forEach((x) => { x.hidden = semua.checked; });
            return n;
        }
        panel.addEventListener('change', (e) => { if (e.target.matches('[name^="absen_"]')) hitung(); });
        panel.querySelectorAll('[data-cari-daftar]').forEach((cari) => cari.addEventListener('input', function () {
            const kata = cari.value.trim().toLowerCase();
            panel.querySelectorAll('[data-daftar="' + cari.dataset.cariDaftar + '"] [data-teks]').forEach((b) => { b.hidden = kata && !b.dataset.teks.includes(kata); });
        }));

        // ---------- Sakelar ----------
        function sinkronSakelar() {
            isi.hidden = !sakelar.checked;
            if (sakelar.checked) setTimeout(siapkanPeta, 50);
        }
        sakelar.addEventListener('change', sinkronSakelar);
        sinkronSakelar();
        hitung();

        // Validasi sebelum konfirmasi kirim (fase capture: berjalan sebelum dialog "Terbitkan?").
        form.addEventListener('submit', function (e) {
            if (!sakelar.checked) return;
            const tahan = (judul, teks, fokus) => {
                e.preventDefault(); e.stopImmediatePropagation();
                Swal.fire({ icon: 'info', title: judul, text: teks || '', confirmButtonColor: '#880f12' }).then(() => fokus && fokus.scrollIntoView({ behavior: 'smooth', block: 'center' }));
            };
            const buka = el('[name=absen_buka]').value, tutup = el('[name=absen_tutup]').value;
            if (!buka || !tutup) return tahan('Isi waktu absen', 'Tentukan kapan absen dibuka dan ditutup.', el('[name=absen_buka]'));
            if (tutup <= buka) return tahan('Waktu absen tidak valid', 'Jam ditutup harus setelah jam dibuka.', el('[name=absen_tutup]'));
            if (!lat.value) return tahan('Tentukan lokasi acara', 'Klik titik acara di peta atau pakai lokasi Anda sekarang.', el('#petaAbsen'));
            if (hitung() === 0) return tahan('Pilih peserta absen', 'Pilih semua, per unit, per jabatan, atau nama pegawai.', el('[data-total-peserta]'));
        }, true);
    });
</script>
