@php
    $totalBelum = $lonceng['notifikasiBelum'] + $lonceng['pengumumanBelum'];
    $adaPengumuman = $lonceng['pengumuman'] !== null;
@endphp
<style>
    .lonceng-btn { position: relative; }
    .lonceng-badge { position: absolute; top: -4px; right: -4px; min-width: 18px; height: 18px; padding: 0 5px; border-radius: 9px;
        background: #dc3545; color: #fff; font-size: .68rem; font-weight: 700; line-height: 18px; text-align: center; }
    .lonceng-menu { width: min(380px, calc(100vw - 24px)); max-width: calc(100vw - 24px); padding: 0; overflow: hidden; }
    @media (max-width: 575.98px) {
        .lonceng-menu.show { position: fixed !important; inset: 64px 12px auto 12px !important; transform: none !important; width: auto; }
    }
    .lonceng-menu .nav-tabs { border-bottom: 1px solid var(--bs-border-color); }
    .lonceng-menu .nav-link { color: var(--bs-secondary-color); border: 0; border-bottom: 2px solid transparent; border-radius: 0; font-size: .85rem; font-weight: 600; padding: .65rem .5rem; }
    .lonceng-menu .nav-link.active { color: var(--bs-body-color); border-bottom-color: #880f12; background: transparent; }
    .lonceng-list { max-height: 360px; overflow-y: auto; }
    .lonceng-item { display: flex; gap: .65rem; padding: .65rem .9rem; border-bottom: 1px solid var(--bs-border-color-translucent); color: var(--bs-body-color) !important; text-decoration: none; }
    .lonceng-item:hover { background: var(--bs-tertiary-bg); }
    .lonceng-item.baru { background: rgba(136, 15, 18, .05); }
    .lonceng-item .ikon { width: 32px; height: 32px; border-radius: 50%; flex: none; display: inline-flex; align-items: center; justify-content: center; background: var(--bs-tertiary-bg); }
    .lonceng-item .judul { font-size: .82rem; font-weight: 600; line-height: 1.25; }
    .lonceng-item .pesan { font-size: .76rem; color: var(--bs-secondary-color); line-height: 1.3; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    .lonceng-item .waktu { font-size: .7rem; color: var(--bs-secondary-color); }
    .lonceng-dot { width: 8px; height: 8px; border-radius: 50%; background: #dc3545; flex: none; margin-top: .4rem; }
    .lonceng-foot { display: flex; justify-content: space-between; align-items: center; gap: .5rem; padding: .5rem .9rem; font-size: .78rem; }
    .lonceng-foot a, .lonceng-foot button { color: #880f12 !important; background: none; border: 0; padding: 0; font-size: .78rem; }
    html[data-theme="dark"] .lonceng-foot a, html[data-theme="dark"] .lonceng-foot button { color: #f08a8c !important; }
</style>

<div class="dropdown">
    <button class="icon-button lonceng-btn" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" data-bs-display="static" aria-expanded="false"
        aria-label="Notifikasi & pengumuman" title="Notifikasi & pengumuman">
        <i class="bi bi-bell" aria-hidden="true"></i>
        <span class="lonceng-badge" data-lonceng-total @if (! $totalBelum) hidden @endif>{{ $totalBelum > 99 ? '99+' : $totalBelum }}</span>
    </button>

    <div class="dropdown-menu dropdown-menu-end lonceng-menu shadow">
        @if ($adaPengumuman)
        <ul class="nav nav-tabs nav-fill" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#lonceng-notif" type="button" role="tab">
                    <i class="bi bi-bell me-1"></i>Notifikasi
                    <span class="badge rounded-pill text-bg-danger ms-1" data-lonceng-notif @if (! $lonceng['notifikasiBelum']) hidden @endif>{{ $lonceng['notifikasiBelum'] }}</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#lonceng-umum" type="button" role="tab">
                    <i class="bi bi-megaphone me-1"></i>Pengumuman
                    <span class="badge rounded-pill text-bg-danger ms-1" data-lonceng-umum @if (! $lonceng['pengumumanBelum']) hidden @endif>{{ $lonceng['pengumumanBelum'] }}</span>
                </button>
            </li>
        </ul>
        @else
            <div class="px-3 py-2 border-bottom fw-semibold small"><i class="bi bi-bell me-1"></i>Notifikasi</div>
        @endif

        <div class="tab-content">
            <div class="tab-pane fade show active" id="lonceng-notif" role="tabpanel">
                <div class="lonceng-list" data-lonceng-daftar>
                    @forelse ($lonceng['notifikasi'] as $n)
                        <a href="{{ route('notifikasi.buka', $n->id) }}" class="lonceng-item {{ $n->read_at ? '' : 'baru' }}">
                            <span class="ikon"><i class="bi {{ $n->data['ikon'] ?? 'bi-bell' }}"></i></span>
                            <span class="flex-grow-1">
                                <span class="judul d-block">{{ $n->data['judul'] ?? '' }}</span>
                                <span class="pesan">{{ $n->data['pesan'] ?? '' }}</span>
                                <span class="waktu">{{ $n->created_at->diffForHumans() }}@if (! empty($n->data['pengirim'])) &middot; {{ $n->data['pengirim'] }}@endif</span>
                            </span>
                            @unless ($n->read_at)<span class="lonceng-dot" aria-label="belum dibaca"></span>@endunless
                        </a>
                    @empty
                        <p class="text-muted small text-center py-4 mb-0"><i class="bi bi-check2-circle d-block fs-4 mb-1"></i>Tidak ada notifikasi.</p>
                    @endforelse
                </div>
                <div class="lonceng-foot border-top">
                    <a href="{{ route('notifikasi.index') }}">Lihat semua</a>
                    <span class="d-flex gap-3">
                        <button type="button" data-lonceng-desktop hidden><i class="bi bi-display me-1"></i>Notifikasi desktop</button>
                        @if ($lonceng['notifikasiBelum'])
                            <form action="{{ route('notifikasi.baca-semua') }}" method="POST" class="d-inline">@csrf<button type="submit">Tandai dibaca</button></form>
                        @endif
                    </span>
                </div>
            </div>

            @if ($adaPengumuman)
            <div class="tab-pane fade" id="lonceng-umum" role="tabpanel">
                <div class="lonceng-list">
                    @forelse ($lonceng['pengumuman'] as $p)
                        @php $k = $p->kategoriInfo(); @endphp
                        <a href="{{ route('pengumuman.index') }}#pengumuman-{{ $p->id }}" class="lonceng-item {{ $p->sudah_dibaca ? '' : 'baru' }}">
                            <span class="ikon"><i class="bi {{ $k['ikon'] }}"></i></span>
                            <span class="flex-grow-1">
                                <span class="judul d-block">@if ($p->penting)<i class="bi bi-pin-angle-fill text-danger me-1"></i>@endif{{ $p->judul }}</span>
                                <span class="pesan">{{ $p->jadwalLabel() ?? \Illuminate\Support\Str::limit($p->isi, 80) }}</span>
                                <span class="waktu">{{ $k['label'] }} &middot; {{ $p->sasaranLabel() }}</span>
                            </span>
                            @unless ($p->sudah_dibaca)<span class="lonceng-dot" aria-label="belum dibaca"></span>@endunless
                        </a>
                    @empty
                        <p class="text-muted small text-center py-4 mb-0"><i class="bi bi-megaphone d-block fs-4 mb-1"></i>Belum ada pengumuman.</p>
                    @endforelse
                </div>
                <div class="lonceng-foot border-top">
                    <a href="{{ route('pengumuman.index') }}">Lihat semua pengumuman</a>
                    @if ($lonceng['pengumumanBelum'])
                        <form action="{{ route('pengumuman.baca-semua') }}" method="POST" class="d-inline">@csrf<button type="submit">Tandai dibaca</button></form>
                    @endif
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

<script>
    (function () {
        const url = @json(route('notifikasi.ringkas'));
        const elTotal = document.querySelector('[data-lonceng-total]');
        const elNotif = document.querySelector('[data-lonceng-notif]');
        const elUmum = document.querySelector('[data-lonceng-umum]');
        const tombolDesktop = document.querySelector('[data-lonceng-desktop]');
        const KUNCI = 'hrrab-notif-desktop-terakhir';

        function setBadge(el, n) {
            if (!el) return;
            el.textContent = n > 99 ? '99+' : n;
            el.hidden = n < 1;
        }

        function bacaKunci() { try { return localStorage.getItem(KUNCI); } catch (e) { return null; } }
        function simpanKunci(v) { try { localStorage.setItem(KUNCI, v); } catch (e) {} }

        function desktop(data) {
            if (!('Notification' in window) || Notification.permission !== 'granted') return;
            const baru = (data.terbaru || []).find(function (n) { return n.baru; });
            if (!baru || bacaKunci() === baru.id) return;
            simpanKunci(baru.id);
            const n = new Notification(baru.judul, { body: baru.pesan, tag: baru.id });
            n.onclick = function () { window.focus(); window.location.href = baru.url; };
        }

        function segarkan() {
            fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
                .then(function (r) { return r.ok ? r.json() : null; })
                .then(function (data) {
                    if (!data) return;
                    setBadge(elNotif, data.notifikasi_belum);
                    setBadge(elUmum, data.pengumuman_belum);
                    setBadge(elTotal, data.notifikasi_belum + data.pengumuman_belum);
                    desktop(data);
                })
                .catch(function () {});
        }

        if (tombolDesktop && 'Notification' in window && Notification.permission === 'default') {
            tombolDesktop.hidden = false;
            tombolDesktop.addEventListener('click', function () {
                Notification.requestPermission().then(function () { tombolDesktop.hidden = true; segarkan(); });
            });
        }

        setInterval(segarkan, 60000);
        document.addEventListener('visibilitychange', function () { if (!document.hidden) segarkan(); });
    })();
</script>
