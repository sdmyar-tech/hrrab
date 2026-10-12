@extends($layout)

@section('content')
@php
    $info = [
        'sudah' => ['ikon' => 'bi-check-circle-fill text-success', 'judul' => 'Anda sudah absen',
            'teks' => 'Tercatat pukul ' . $peserta?->waktu_absen?->format('H:i') . ($peserta?->jarak !== null ? " ({$peserta->jarak} m dari lokasi)." : '.')],
        'belum_buka' => ['ikon' => 'bi-clock text-primary', 'judul' => 'Absen belum dibuka',
            'teks' => 'Absen dibuka ' . $pengumuman->absen_buka?->translatedFormat('l, d M Y \p\u\k\u\l H:i') . '.'],
        'tutup' => ['ikon' => 'bi-hourglass-bottom text-secondary', 'judul' => 'Waktu absen sudah ditutup',
            'teks' => 'Absen ditutup ' . $pengumuman->absen_tutup?->translatedFormat('d M Y, H:i') . '. Hubungi SDM bila ada kendala.'],
        'bukan_peserta' => ['ikon' => 'bi-person-x text-secondary', 'judul' => 'Anda bukan peserta absen acara ini',
            'teks' => 'Absen hanya untuk pegawai yang dipilih panitia. Hubungi SDM bila seharusnya Anda ikut.'],
        'qr_kedaluwarsa' => ['ikon' => 'bi-qr-code text-warning', 'judul' => 'Scan QR di lokasi acara',
            'teks' => 'QR berganti setiap 30 detik. Scan QR yang sedang tampil di layar panitia.'],
    ][$keadaan] ?? null;
@endphp
<div class="dashboard-content">
    <div class="container-fluid px-3 px-lg-4 py-4">
        <div class="absen-scan mx-auto">
            <section class="panel p-4 text-center">
                <p class="eyebrow mb-1"><i class="bi bi-qr-code-scan me-1"></i>Absen Acara</p>
                <h1 class="h4 mb-1">{{ $pengumuman->judul }}</h1>
                <p class="small text-muted mb-0">
                    {{ $pengumuman->absenJendelaLabel() }}
                    @if ($pengumuman->lokasi) · @include('partials.lokasi', ['lokasi' => $pengumuman->lokasi]) @endif
                </p>
            </section>

            @if ($keadaan === 'lokasi')
                <section class="panel p-4 mt-3">
                    <ol class="langkah-absen list-unstyled mb-4">
                        <li class="selesai"><i class="bi bi-check-circle-fill"></i><span>QR terverifikasi</span></li>
                        <li class="aktif"><i class="bi bi-geo-alt-fill"></i><span>Kirim lokasi Anda <small class="d-block text-muted">harus dalam radius {{ $pengumuman->absen_radius }} m dari titik acara</small></span></li>
                    </ol>
                    <form method="POST" action="{{ route('acara.absen.simpan', $pengumuman) }}" id="formAbsenAcara">
                        @csrf
                        <input type="hidden" name="lat"><input type="hidden" name="lng"><input type="hidden" name="akurasi">
                        <button type="button" class="btn btn-primary btn-lg w-100" data-kirim-lokasi>
                            <i class="bi bi-geo-alt me-1"></i><span>Absen Sekarang</span>
                        </button>
                    </form>
                    <p class="small text-muted text-center mt-2 mb-0" data-status-lokasi>Browser akan meminta izin lokasi — pilih <strong>Izinkan</strong>.</p>
                </section>
            @elseif ($info)
                <section class="panel p-4 mt-3 text-center">
                    <i class="bi {{ $info['ikon'] }} d-block mb-2" style="font-size:2.75rem"></i>
                    <h2 class="h5">{{ $info['judul'] }}</h2>
                    <p class="text-muted mb-3">{{ $info['teks'] }}</p>
                    @if ($keadaan === 'qr_kedaluwarsa')
                        <button type="button" class="btn btn-primary" data-scan-acara><i class="bi bi-camera me-1"></i>Buka Pemindai QR</button>
                    @endif
                    <a href="{{ route('pengumuman.index') }}#pengumuman-{{ $pengumuman->id }}" class="btn btn-outline-secondary {{ $keadaan === 'qr_kedaluwarsa' ? 'ms-1' : '' }}">Ke Pengumuman</a>
                </section>
            @endif
        </div>
    </div>
</div>

<style>
    .absen-scan { max-width: 480px; }
    .langkah-absen li { display: flex; gap: .75rem; align-items: flex-start; padding: .5rem 0; }
    .langkah-absen li > i { font-size: 1.25rem; line-height: 1.2; }
    .langkah-absen li.selesai > i { color: var(--bs-success); }
    .langkah-absen li.aktif > i { color: #880f12; }
</style>

@if ($keadaan === 'qr_kedaluwarsa')
    @include('pengumuman._modal-scan')
@endif

@if ($keadaan === 'lokasi')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('formAbsenAcara');
        const tombol = form.querySelector('[data-kirim-lokasi]');
        const status = document.querySelector('[data-status-lokasi]');
        const label = tombol.querySelector('span');

        function gagal(judul, teks) {
            tombol.disabled = false; label.textContent = 'Coba Lagi';
            status.textContent = teks;
            Swal.fire({ icon: 'info', title: judul, text: teks, confirmButtonColor: '#880f12' });
        }

        tombol.addEventListener('click', function () {
            if (!navigator.geolocation) return gagal('Lokasi tidak didukung', 'Browser ini tidak bisa membaca lokasi. Coba Chrome atau Safari terbaru.');
            if (!window.isSecureContext) return gagal('Koneksi tidak aman', 'Lokasi hanya bisa dibaca lewat alamat https.');
            tombol.disabled = true; label.textContent = 'Membaca lokasi…';
            status.textContent = 'Tunggu sebentar, sedang membaca GPS…';
            navigator.geolocation.getCurrentPosition(function (pos) {
                form.lat.value = pos.coords.latitude;
                form.lng.value = pos.coords.longitude;
                form.akurasi.value = Math.round(pos.coords.accuracy || 0);
                label.textContent = 'Mengirim…';
                form.submit();
            }, function (err) {
                if (err.code === 1) return gagal('Izin lokasi ditolak', 'Izinkan akses lokasi: ketuk ikon gembok/ⓘ di samping alamat situs → Lokasi → Izinkan, lalu tekan Coba Lagi.');
                if (err.code === 3) return gagal('GPS terlalu lama', 'Pindah ke area terbuka atau dekat jendela, pastikan GPS menyala, lalu coba lagi.');
                gagal('Lokasi tidak tersedia', 'Nyalakan GPS / Lokasi di HP Anda, lalu coba lagi.');
            }, { enableHighAccuracy: true, timeout: 20000, maximumAge: 0 });
        });
    });
</script>
@endif
@endsection
