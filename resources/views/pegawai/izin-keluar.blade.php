@extends(Auth::user()->panelLayout())

@section('title', 'Izin')

@section('content')
@php
    $menunggu = $riwayat->filter(fn ($r) => $r->isPending())->count();
    $tiketAktif = $riwayat->filter(fn ($r) => $r->punyaTiket() && $r->statusTiket() !== 'lewat');
    $bulanIni = $riwayat->filter(fn ($r) => $r->tanggal->isSameMonth(now()))->count();
@endphp
<main class="dashboard-content">
    <div class="container-fluid">
        <x-page-header title="Izin" eyebrow="Pengajuan Saya"
            subtitle="Izin keluar (dengan tiket untuk security), izin telat masuk, atau izin tidak masuk sehari atau lebih.">
            <x-slot:actions>
                @include('partials.tombol-ekspor', ['eksporRoute' => 'pegawai.izin-keluar.export', 'eksporTeks' => 'Ekspor Riwayat'])
            </x-slot:actions>
        </x-page-header>

        {{-- ============ Ringkasan ============ --}}
        <section class="row g-3 mb-4" aria-label="Ringkasan izin">
            <div class="col-6 col-xl-4">
                <x-stat-card label="Tiket Siap Dipakai" :value="$tiketAktif->count()" icon="bi-ticket-perforated" :tone="$tiketAktif->isNotEmpty() ? 'success' : 'neutral'"
                    :meta="$tiketAktif->isNotEmpty() ? 'Tampilkan di gerbang' : 'Belum ada tiket berlaku'"
                    :href="$tiketAktif->isNotEmpty() ? route('pegawai.izin-keluar.tiket', $tiketAktif->first()) : null" />
            </div>
            <div class="col-6 col-xl-4">
                <x-stat-card label="Menunggu Persetujuan" :value="$menunggu" icon="bi-hourglass-split" :tone="$menunggu ? 'warning' : 'neutral'" meta="Belum diputuskan" />
            </div>
            <div class="col-12 col-xl-4">
                <x-stat-card label="Izin Bulan Ini" :value="$bulanIni" unit="kali" icon="bi-door-open" tone="info" :meta="now()->translatedFormat('F Y')" />
            </div>
        </section>

        <div class="row g-4 align-items-start">
            {{-- ============ Form ============ --}}
            <div class="col-lg-7">
                @php $jenisLama = old('jenis', \App\Models\PengajuanIzinKeluar::JENIS_JAM); @endphp
                <x-card title="Formulir Izin" subtitle="Isian bertanda * wajib diisi." icon="bi-door-open">
                    <form action="{{ route('pegawai.izin-keluar.store') }}" method="POST" data-confirm="Ajukan izin ini?">
                        @csrf

                        <fieldset class="hr-field">
                            <legend class="form-label required">Jenis Izin</legend>
                            <div class="hr-opsi-grup">
                                <label class="hr-opsi" for="jenisJam">
                                    <input class="form-check-input" type="radio" name="jenis" id="jenisJam" value="jam" @checked(! in_array($jenisLama, ['harian', 'telat'], true))>
                                    <span><strong>Izin Keluar</strong><small>Keluar saat jam kerja; dapat tiket untuk security.</small></span>
                                </label>
                                <label class="hr-opsi" for="jenisTelat">
                                    <input class="form-check-input" type="radio" name="jenis" id="jenisTelat" value="telat" @checked($jenisLama === 'telat')>
                                    <span><strong>Izin Telat Masuk</strong><small>Datang terlambat; boleh dilaporkan setelah tiba.</small></span>
                                </label>
                                <label class="hr-opsi" for="jenisHarian">
                                    <input class="form-check-input" type="radio" name="jenis" id="jenisHarian" value="harian" @checked($jenisLama === 'harian')>
                                    <span><strong>Izin Tidak Masuk</strong><small>Sehari atau lebih (sakit, urusan keluarga, dll.); tercatat di absensi.</small></span>
                                </label>
                            </div>
                        </fieldset>

                        <x-select name="keperluan" id="keperluan" label="Keperluan" :options="$keperluanOptions" placeholder="Pilih keperluan" required />

                        <div class="row gx-3">
                            <div class="col-sm-5">
                                <x-input name="tanggal" id="tanggal" type="date" :value="now()->toDateString()" required
                                    label="Tanggal" :min="now()->toDateString()" :max="now()->addDays($maksHari)->toDateString()"
                                    data-min-normal="{{ now()->toDateString() }}" data-min-sakit="{{ now()->subDays($maksMundurSakit)->toDateString() }}"
                                    data-min-telat="{{ now()->subHours($maksJamMundur)->toDateString() }}"
                                    :help="'Paling lambat ' . $maksHari . ' hari ke depan.'" />
                            </div>
                            {{-- Izin tidak masuk: jumlah hari (bantuan isi, tidak dikirim) ↔ tanggal selesai, hari kerja saja. --}}
                            <div class="col-sm-2" data-bagian-izin="harian">
                                <div class="hr-field">
                                    <label for="jumlah_hari" class="form-label">Jumlah Hari</label>
                                    <input type="number" id="jumlah_hari" class="form-control" min="1" max="14" step="1" placeholder="Contoh: 2" inputmode="numeric">
                                </div>
                            </div>
                            <div class="col-sm-5" data-bagian-izin="harian">
                                <x-input name="tanggal_selesai" id="tanggal_selesai" type="date" label="Tanggal Selesai" required />
                            </div>
                        </div>

                        <div data-bagian-izin="harian">
                            <div class="hr-hitungan mb-3" aria-live="polite">
                                <i class="bi bi-calculator" aria-hidden="true"></i>
                                <label for="lama_izin_display" class="mb-0">Lama izin</label>
                                <input type="text" id="lama_izin_display" readonly placeholder="Terisi otomatis">
                            </div>
                            <p class="form-text mt-n2 mb-3" id="hintLewati">Sabtu–Minggu dan tanggal merah tidak dihitung.</p>
                        </div>

                        <div data-bagian-izin="telat">
                            <x-input name="jam_tiba" id="jam_tiba" type="time" label="Jam Tiba / Perkiraan Tiba" required
                                :help="'Sudah sampai? Isi jam tiba. Boleh dilaporkan paling lambat ' . $maksJamMundur . ' jam setelah tiba.'" />
                        </div>

                        <div data-bagian-izin="jam">
                            <div class="row gx-3">
                                <div class="col-6"><x-input name="jam_keluar" id="jam_keluar" type="time" label="Jam Keluar" required
                                    :help="'Paling awal jam saat ini.'" /></div>
                                <div class="col-6"><x-input name="jam_kembali" id="jam_kembali" type="time" label="Perkiraan Kembali" /></div>
                            </div>
                            <div class="mb-3">
                                <x-checkbox name="tidak_kembali" id="tidak_kembali" value="1" label="Tidak kembali lagi hari ini" />
                            </div>
                        </div>

                        <x-textarea name="alasan" id="alasan" label="Alasan / Tujuan" rows="3" maxlength="500" required
                            placeholder="Contoh: ke bank untuk urusan gaji" />

                        <x-button type="submit" size="lg" block icon="bi-send">Ajukan Izin</x-button>
                    </form>
                </x-card>
            </div>

            {{-- ============ Samping: alur & cara pakai ============ --}}
            <div class="col-lg-5 d-grid gap-3">
                @if ($rute)
                    <x-card title="Alur Persetujuan" icon="bi-signpost-split" subtitle="Pengajuan Anda melewati tahap berikut secara berurutan.">
                        <x-tangga-alur :tahap="\App\Support\PengajuanWorkflow::tahapTampil($rute)" awal="Anda mengajukan" akhir="Izin disetujui" arah="vertikal" label="Alur persetujuan izin" />
                    </x-card>
                @endif
                <x-card title="Ketentuan" icon="bi-info-circle">
                    <ul class="hr-daftar-info">
                        <li><i class="bi bi-phone" aria-hidden="true"></i><strong>Izin keluar:</strong> diajukan sebelum keluar (jam keluar paling awal jam saat ini). Setelah disetujui, buka tiket dari riwayat di bawah lalu tunjukkan layarnya ke security — tangkapan layar tidak berlaku.</li>
                        <li><i class="bi bi-clock-history" aria-hidden="true"></i><strong>Izin telat masuk:</strong> tidak sempat izin dulu (mis. ban pecah di jalan)? Boleh diajukan paling lambat <strong>{{ $maksJamMundur }} jam</strong> setelah tiba. Setelah disetujui, telat sampai jam tiba tercatat "Telat Izin".</li>
                        <li><i class="bi bi-calendar-x" aria-hidden="true"></i><strong>Izin tidak masuk:</strong> dihitung hari kerja (Sabtu–Minggu &amp; tanggal merah dilewati). Setelah disetujui tercatat Izin/Sakit/Dinas Luar di absensi. Izin sakit boleh diajukan paling lambat {{ $maksMundurSakit }} hari setelah tanggal sakit.</li>
                        <li><i class="bi bi-thermometer-half" aria-hidden="true"></i>Keperluan <strong>Sakit</strong> hanya untuk izin telat masuk dan izin tidak masuk.</li>
                    </ul>
                </x-card>
            </div>
        </div>

        {{-- ============ Riwayat ============ --}}
        <section class="hr-section">
            <x-card title="Riwayat Izin" icon="bi-clock-history" :padding="false">
                @if ($urutOpsi)
                    <x-slot:actions>@include('partials.urut')</x-slot:actions>
                @endif
                <x-table caption="Riwayat izin">
                    <x-slot:head>
                        <tr><th>Tanggal</th><th>Waktu</th><th>Keperluan</th><th>Status</th><th class="text-end">Tiket</th></tr>
                    </x-slot:head>
                    @forelse ($riwayat as $item)
                        <tr>
                            <td>
                                <span class="fw-semibold text-nowrap">{{ $item->tanggalLabel('d M Y') }}</span>
                                <span class="small text-muted d-block">{{ $item->jenisLabel() }}</span>
                            </td>
                            <td class="text-nowrap">{{ $item->jamLabel() }}</td>
                            <td>
                                {{ $item->keperluanLabel() }}
                                <span class="small text-muted d-block">{{ \Illuminate\Support\Str::limit($item->alasan, 50) }}</span>
                            </td>
                            <td>
                                <x-status-badge :badge="$item->badgeClass()">{{ $item->statusLabel() }}</x-status-badge>
                                @if ($item->isPending())
                                    <span class="small text-muted d-block mt-1">Tahap: {{ $item->currentLevelLabel() }}</span>
                                @elseif ($item->status === 'rejected' && $item->catatan_admin)
                                    <span class="small text-muted d-block mt-1">{{ $item->catatan_admin }}</span>
                                @endif
                            </td>
                            <td class="text-end">
                                @if ($item->punyaTiket())
                                    @php $st = $item->statusTiket(); @endphp
                                    <x-button :href="route('pegawai.izin-keluar.tiket', $item)" size="sm" :variant="$st === 'lewat' ? 'secondary' : 'success'" icon="bi-ticket-perforated">
                                        {{ $st === 'lewat' ? 'Lihat' : 'Tampilkan Tiket' }}
                                    </x-button>
                                @else
                                    <span class="text-muted small">–</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <x-empty-state icon="bi-door-open" title="Belum ada izin" compact>Ajukan lewat formulir di atas; status dan tiket (izin keluar) muncul di sini.</x-empty-state>
                            </td>
                        </tr>
                    @endforelse
                </x-table>
            </x-card>
        </section>
    </div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const keperluan = document.getElementById('keperluan');
        const tanggal = document.getElementById('tanggal');
        const tanggalSelesai = document.getElementById('tanggal_selesai');
        const jumlahHari = document.getElementById('jumlah_hari');
        const lamaDisplay = document.getElementById('lama_izin_display');
        const hintLewati = document.getElementById('hintLewati');
        const TEKS_LEWATI = hintLewati.textContent;
        const tidakKembali = document.getElementById('tidak_kembali');
        const jamKembali = document.getElementById('jam_kembali');
        const labelKembali = document.querySelector('label[for="jam_kembali"]');

        // Hari kerja & libur sama dengan App\Support\Kalender di server (tanggal merah dari menu Kalender).
        const HARI_KERJA = @json(config('hrrab.hari_kerja'));
        const LIBUR = @json(config('hrrab.hari_libur'));
        const keIso = (d) => d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
        const dariIso = (s) => new Date(s + 'T00:00:00');
        const isHariKerja = (d) => HARI_KERJA.includes(d.getDay() || 7) && ! LIBUR.includes(keIso(d));
        const fmtPendek = new Intl.DateTimeFormat('id-ID', { weekday: 'short', day: 'numeric', month: 'short' });

        /** Hari kerja ke-n dari tanggal mulai (mulai ikut dihitung bila hari kerja). 2 hari mulai Jumat = Senin. */
        function hariKerjaKe(mulai, n) {
            const hasil = new Date(mulai.getTime());
            while (! isHariKerja(hasil)) hasil.setDate(hasil.getDate() + 1);
            for (let i = 1; i < n; i++) {
                do { hasil.setDate(hasil.getDate() + 1); } while (! isHariKerja(hasil));
            }
            return hasil;
        }

        function rincian(mulai, selesai) {
            let kerja = 0;
            const lewati = [];
            for (const t = new Date(mulai.getTime()); t <= selesai; t.setDate(t.getDate() + 1)) {
                if (isHariKerja(t)) kerja++;
                else lewati.push(fmtPendek.format(t) + (LIBUR.includes(keIso(t)) ? ' (tanggal merah)' : ''));
            }
            return { kerja, lewati };
        }

        function updateLama() {
            hintLewati.textContent = TEKS_LEWATI;
            hintLewati.classList.remove('text-danger');
            if (! tanggal.value || ! tanggalSelesai.value || tanggalSelesai.value < tanggal.value) {
                lamaDisplay.value = '';
                return;
            }
            const { kerja, lewati } = rincian(dariIso(tanggal.value), dariIso(tanggalSelesai.value));
            lamaDisplay.value = kerja > 0 ? kerja + ' hari kerja' : '';
            if (kerja === 0) {
                hintLewati.textContent = 'Rentang ini tidak berisi hari kerja. Pilih tanggal lain.';
                hintLewati.classList.add('text-danger');
            } else if (lewati.length) {
                hintLewati.textContent = 'Tidak dihitung: ' + lewati.join(', ') + '.';
            }
        }

        function isiSelesaiDariJumlah() {
            const n = parseInt(jumlahHari.value, 10);
            if (tanggal.value && n >= 1) tanggalSelesai.value = keIso(hariKerjaKe(dariIso(tanggal.value), n));
        }

        // "Tidak kembali" → jam kembali tidak perlu diisi.
        const jenisAktif = () => document.querySelector('input[name="jenis"]:checked')?.value || 'jam';

        function aturKembali() {
            const keluar = jenisAktif() === 'jam';
            jamKembali.disabled = ! keluar || tidakKembali.checked;
            jamKembali.required = keluar && ! tidakKembali.checked;
            labelKembali.classList.toggle('required', ! tidakKembali.checked);
            if (tidakKembali.checked) jamKembali.value = '';
        }

        // Bagian yang tidak dipakai disembunyikan & dinonaktifkan (tidak ikut terkirim/divalidasi).
        function aturJenis() {
            const jenis = jenisAktif();
            const harian = jenis === 'harian';
            document.querySelectorAll('[data-bagian-izin]').forEach(function (el) {
                const aktif = el.dataset.bagianIzin === jenis;
                el.classList.toggle('d-none', ! aktif);
                el.querySelectorAll('input').forEach((i) => { i.disabled = ! aktif; });
            });
            // Keperluan Sakit hanya untuk telat masuk & tidak masuk.
            const opsiSakit = keperluan.querySelector('option[value="sakit"]');
            if (opsiSakit) {
                opsiSakit.hidden = opsiSakit.disabled = jenis === 'jam';
                if (jenis === 'jam' && keperluan.value === 'sakit') keperluan.value = '';
            }
            // Izin keluar mulai hari ini; izin telat boleh dilaporkan belakangan (12 jam); izin sakit (tidak masuk) boleh mundur beberapa hari.
            const sakit = harian && keperluan.value === 'sakit';
            tanggal.min = jenis === 'telat' ? tanggal.dataset.minTelat : (sakit ? tanggal.dataset.minSakit : tanggal.dataset.minNormal);
            if (tanggal.value < tanggal.min) tanggal.value = tanggal.min;
            tanggalSelesai.min = tanggal.value;
            aturKembali();
            if (harian) { isiSelesaiDariJumlah(); updateLama(); }
        }

        document.querySelectorAll('input[name="jenis"]').forEach((r) => r.addEventListener('change', aturJenis));
        keperluan.addEventListener('change', aturJenis);
        tidakKembali.addEventListener('change', aturKembali);
        tanggal.addEventListener('change', function () {
            tanggalSelesai.min = tanggal.value;
            if (jumlahHari.value) isiSelesaiDariJumlah();
            else if (! tanggalSelesai.value || tanggalSelesai.value < tanggal.value) tanggalSelesai.value = tanggal.value;
            updateLama();
        });
        jumlahHari.addEventListener('input', function () { isiSelesaiDariJumlah(); updateLama(); });
        tanggalSelesai.addEventListener('change', function () {
            if (tanggalSelesai.value < tanggal.value) tanggalSelesai.value = tanggal.value;
            const { kerja } = rincian(dariIso(tanggal.value), dariIso(tanggalSelesai.value));
            jumlahHari.value = kerja > 0 ? kerja : '';
            updateLama();
        });

        // Awal: pertahankan tanggal lama (gagal validasi), selain itu 1 hari kerja.
        if (tanggalSelesai.value) jumlahHari.value = rincian(dariIso(tanggal.value), dariIso(tanggalSelesai.value)).kerja || '';
        else jumlahHari.value = 1;
        aturJenis();
    });
</script>
@endsection
