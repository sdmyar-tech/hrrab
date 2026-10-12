{{--
    window.ambilLokasi(): baca lokasi dengan toleran untuk HP yang GPS-nya lambat dapat sinyal (mis. di dalam ruangan).
    - Dua sumber sekaligus: lokasi jaringan/cache (cepat, Wi-Fi/seluler) + GPS presisi (lebih lama).
    - Terus memantau sampai akurasi "cukup" atau batas waktu habis, lalu memakai hasil TERBAIK yang didapat.
    - Hanya gagal bila izin ditolak atau sama sekali tidak ada bacaan sampai batas waktu.
--}}
@once
<script>
    // kendali.sekarang(): hentikan menunggu & pakai bacaan terbaik saat ini (tombol "Kirim sekarang").
    window.ambilLokasi = function ({ akurasiCukup = 30, maksDetik = 45, onProgres = null, kendali = null } = {}) {
        return new Promise(function (resolve, reject) {
            if (!navigator.geolocation) return reject({ code: 0 });

            const geo = navigator.geolocation;
            const mulai = Date.now();
            let terbaik = null, selesai = false, errTerakhir = null, detak = null;
            const ids = [];

            const akhiri = function (err) {
                if (selesai) return;
                selesai = true;
                ids.forEach((id) => geo.clearWatch(id));
                clearInterval(detak);
                terbaik ? resolve(terbaik) : reject(err || errTerakhir || { code: 3 });
            };
            const kabar = () => onProgres && onProgres({
                detik: Math.round((Date.now() - mulai) / 1000), maksDetik,
                akurasi: terbaik ? Math.round(terbaik.coords.accuracy) : null,
            });
            const terima = function (pos) {
                if (!terbaik || pos.coords.accuracy < terbaik.coords.accuracy) terbaik = pos;
                kabar();
                if (pos.coords.accuracy <= akurasiCukup) akhiri();
            };
            const gagal = function (err) {
                errTerakhir = err;
                if (err.code === 1) akhiri(err); // izin ditolak: tidak ada gunanya menunggu
            };

            // Cepat: lokasi jaringan / bacaan terakhir (≤ 1 menit) — biasanya muncul dalam 1–3 detik.
            ids.push(geo.watchPosition(terima, gagal, { enableHighAccuracy: false, maximumAge: 60000, timeout: maksDetik * 1000 }));
            // Presisi: GPS satelit, terus diperbarui sampai akurasinya cukup.
            ids.push(geo.watchPosition(terima, gagal, { enableHighAccuracy: true, maximumAge: 0, timeout: maksDetik * 1000 }));

            if (kendali) kendali.sekarang = () => akhiri();

            detak = setInterval(function () {
                kabar();
                if (Date.now() - mulai >= maksDetik * 1000) akhiri();
            }, 1000);
        });
    };

    /** Panduan bila lokasi tidak bisa dibaca sama sekali (khususnya HP Android seperti Infinix, Oppo, Vivo, Xiaomi). */
    window.panduanLokasi = function (err) {
        if (err && err.code === 1) {
            return { judul: 'Izin lokasi ditolak', teks: 'Izinkan akses lokasi: ketuk ikon gembok/ⓘ di samping alamat situs → Izin → Lokasi → Izinkan, lalu tekan Coba Lagi.' };
        }
        return {
            judul: 'Lokasi belum terbaca',
            teks: '1) Pengaturan HP → Lokasi → aktifkan "Akurasi Lokasi Google" / "Tingkatkan akurasi" (Wi-Fi & Bluetooth scanning). '
                + '2) Pengaturan → Aplikasi → Chrome → Izin → Lokasi → "Izinkan" dan nyalakan "Gunakan lokasi akurat". '
                + '3) Matikan mode hemat daya. '
                + '4) Buka Google Maps sebentar sampai titik biru muncul, kembali ke sini, lalu tekan Coba Lagi.',
        };
    };
</script>
@endonce
