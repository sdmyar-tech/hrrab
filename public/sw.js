/* HRRAB service worker: aset statis tersimpan, halaman yang pernah dibuka tetap bisa dibaca saat tanpa internet. */
const VERSI = 'hrrab-v1';
const CACHE_ASET = VERSI + '-aset';
const CACHE_HALAMAN = VERSI + '-halaman';
const OFFLINE = '/offline.html';

self.addEventListener('install', (e) => {
    e.waitUntil(caches.open(CACHE_ASET).then((c) => c.addAll([OFFLINE, '/favicon-192x192.png'])).then(() => self.skipWaiting()));
});

self.addEventListener('activate', (e) => {
    e.waitUntil(
        caches.keys()
            .then((nama) => Promise.all(nama.filter((n) => !n.startsWith(VERSI)).map((n) => caches.delete(n))))
            .then(() => self.clients.claim())
    );
});

// Keluar akun: hapus halaman tersimpan agar data tidak tertinggal di perangkat bersama.
self.addEventListener('message', (e) => {
    if (e.data === 'bersihkan') e.waitUntil(caches.delete(CACHE_HALAMAN));
});

const ASET = /\.(?:css|js|woff2?|ttf|png|jpe?g|gif|svg|webp|ico)$/i;
// Jangan simpan: unduhan/berkas pribadi, API, dan halaman yang berubah tiap detik (QR absen).
const LEWATI = /^\/(?:berkas|storage|api|logout)(?:\/|$)|\/absen\/qr(?:\/|$)/;

self.addEventListener('fetch', (e) => {
    const req = e.request;
    if (req.method !== 'GET') return;
    const url = new URL(req.url);
    if (url.origin !== location.origin || LEWATI.test(url.pathname)) return;

    // Aset statis: tampilkan dari simpanan, segarkan di latar belakang.
    if (ASET.test(url.pathname)) {
        e.respondWith(
            caches.open(CACHE_ASET).then((c) =>
                c.match(req).then((ada) => {
                    const ambil = fetch(req).then((res) => { if (res.ok) c.put(req, res.clone()); return res; }).catch(() => ada);
                    return ada || ambil;
                })
            )
        );
        return;
    }

    // Halaman: utamakan jaringan; gagal → salinan terakhir; belum pernah dibuka → halaman offline.
    if (req.mode === 'navigate') {
        e.respondWith(
            fetch(req)
                .then((res) => {
                    if (res.ok && res.type === 'basic' && !res.redirected) {
                        const salinan = res.clone();
                        caches.open(CACHE_HALAMAN).then((c) => c.put(req, salinan));
                    }
                    return res;
                })
                .catch(() => caches.match(req, { cacheName: CACHE_HALAMAN }).then((ada) => ada || caches.match(OFFLINE, { cacheName: CACHE_ASET })))
        );
    }
});
