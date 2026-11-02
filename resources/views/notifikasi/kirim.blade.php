@extends($layout)

@section('title', 'Kirim Notifikasi')

@section('content')
@php
    $target = old('target', 'belum_lengkap');
    $templateLengkapi = [
        'judul' => 'Lengkapi data pegawai Anda',
        'pesan' => 'Data kepegawaian Anda belum lengkap. Mohon segera lengkapi lewat menu Pengkinian Data (atau hubungi Admin SDM) agar administrasi cuti, kontrak, dan BPJS tidak terhambat.',
    ];
    $opsi = [
        'belum_lengkap' => ['Pegawai yang datanya belum lengkap', 'bi-person-vcard', $belumLengkap->count() . ' akun'],
        'unit' => ['Per bagian', 'bi-diagram-3', null],
        'pegawai' => ['Pilih pegawai tertentu', 'bi-person-check', null],
        'semua' => ['Semua pegawai dalam wewenang saya', 'bi-people', $pilihanPegawai->count() . ' akun'],
    ];
    if ($bisaPilihRole) {
        $opsi['role'] = ['Per role', 'bi-person-badge', null];
    }
@endphp
<main class="dashboard-content">
    <div class="container-fluid">
        <x-page-header title="Kirim Notifikasi" eyebrow="Notifikasi" :back="route('notifikasi.index')" back-label="Notifikasi"
            subtitle="Kirim pengingat atau tugas ke pegawai lain. Muncul di lonceng notifikasi penerima (dan notifikasi desktop bila diaktifkan)." />

        <form action="{{ route('notifikasi.kirim.store') }}" method="POST" data-confirm="Kirim notifikasi ini?" id="formKirimNotif">
            @csrf
            {{-- Galat isian ditampilkan di halaman ini; banner global tidak perlu. --}}
            @section('galat-di-halaman', '1')
            @if ($errors->any())
                <x-alert tone="danger" title="Notifikasi belum terkirim" class="mb-3">
                    <ul class="mb-0 ps-3">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                </x-alert>
            @endif

            <div class="row g-4 align-items-start">
                <div class="col-lg-7 d-grid gap-3">
                    {{-- 1. Penerima --}}
                    <x-card title="1. Penerima" subtitle="Pilih kepada siapa notifikasi dikirim." icon="bi-people">
                        <fieldset>
                            <legend class="visually-hidden">Jenis penerima</legend>
                            <div class="hr-opsi-grup hr-opsi-grup-1 mb-4">
                                @foreach ($opsi as $val => [$label, $ikon, $info])
                                    <label class="hr-opsi">
                                        <input class="form-check-input" type="radio" name="target" value="{{ $val }}" @checked($target === $val)>
                                        <i class="bi {{ $ikon }} hr-opsi-ikon" aria-hidden="true"></i>
                                        <span class="flex-grow-1"><strong>{{ $label }}</strong></span>
                                        @if ($info)<x-badge tone="neutral">{{ $info }}</x-badge>@endif
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>

                        <div data-target="belum_lengkap">
                            @if ($belumLengkap->isEmpty())
                                <x-alert tone="success" class="mb-0">Semua akun dalam wewenang Anda sudah lengkap datanya.</x-alert>
                            @else
                                <p class="small text-muted mb-2">Akan dikirim ke {{ $belumLengkap->count() }} akun berikut. Staff diarahkan langsung ke menu Pengkinian Data.</p>
                                <ul class="hr-list notif-penerima">
                                    @foreach ($belumLengkap as $r)
                                        <li class="hr-list-item">
                                            <x-avatar :name="$r['user']->name" size="sm" />
                                            <span class="hr-list-utama">
                                                <span class="hr-list-judul">{{ $r['user']->name }}</span>
                                                <span class="hr-list-sub">{{ $r['user']->roleLabel() }} · {{ $r['user']->dataPegawai?->kepegawaian?->unit_kerja }}</span>
                                                <span class="small text-danger d-block text-wrap"><i class="bi bi-exclamation-circle me-1" aria-hidden="true"></i>Kurang: {{ implode(', ', array_slice($r['kurang'], 0, 4)) }}{{ count($r['kurang']) > 4 ? ' +' . (count($r['kurang']) - 4) : '' }}</span>
                                            </span>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>

                        <div data-target="unit">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="form-label mb-0">Bagian</span>
                                <div class="form-check m-0">
                                    <input class="form-check-input" type="checkbox" id="semua-unit" data-pilih-semua="unit[]">
                                    <label class="form-check-label small fw-semibold" for="semua-unit">Pilih semua <span class="text-muted fw-normal" data-jumlah-terpilih></span></label>
                                </div>
                            </div>
                            <div class="notif-pilihan row row-cols-1 row-cols-sm-2 g-1 mx-0">
                                @foreach ($pilihanUnit as $u)
                                    <div class="col"><div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="unit[]" value="{{ $u }}" id="u-{{ $loop->index }}" @checked(in_array($u, old('unit', []), true))>
                                        <label class="form-check-label small" for="u-{{ $loop->index }}">{{ $u }}</label>
                                    </div></div>
                                @endforeach
                            </div>
                        </div>

                        <div data-target="pegawai">
                            <div class="input-group hr-input-group mb-2">
                                <span class="input-group-text"><i class="bi bi-search" aria-hidden="true"></i></span>
                                <input type="search" class="form-control" id="cari-pegawai" placeholder="Saring nama, contoh: Siti" aria-label="Saring pegawai menurut nama">
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="semua-pegawai" data-pilih-semua="pegawai[]">
                                <label class="form-check-label small fw-semibold" for="semua-pegawai">Pilih semua (yang tampil) <span class="text-muted fw-normal" data-jumlah-terpilih></span></label>
                            </div>
                            <div class="notif-pilihan" id="daftar-pegawai">
                                @foreach ($pilihanPegawai as $p)
                                    <div class="form-check" data-nama="{{ strtolower($p->name) }}">
                                        <input class="form-check-input" type="checkbox" name="pegawai[]" value="{{ $p->id }}" id="p-{{ $p->id }}" @checked(in_array($p->id, old('pegawai', [])))>
                                        <label class="form-check-label small" for="p-{{ $p->id }}">{{ $p->name }} <span class="text-muted">· {{ $p->roleLabel() }}{{ $p->dataPegawai?->kepegawaian?->unit_kerja ? ', ' . $p->dataPegawai->kepegawaian->unit_kerja : '' }}</span></label>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        @if ($bisaPilihRole)
                            <div data-target="role">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="form-label mb-0">Role</span>
                                    <div class="form-check m-0">
                                        <input class="form-check-input" type="checkbox" id="semua-role" data-pilih-semua="role[]">
                                        <label class="form-check-label small fw-semibold" for="semua-role">Pilih semua <span class="text-muted fw-normal" data-jumlah-terpilih></span></label>
                                    </div>
                                </div>
                                <div class="notif-pilihan row row-cols-1 row-cols-sm-2 g-1 mx-0">
                                    @foreach (\App\Models\Role::bawaanAktif() as $r)
                                        <div class="col"><div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="role[]" value="{{ $r }}" id="r-{{ $r }}" @checked(in_array($r, old('role', []), true))>
                                            <label class="form-check-label small" for="r-{{ $r }}">{{ \App\Models\User::roleLabelFor($r) }}</label>
                                        </div></div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <div data-target="semua">
                            <x-alert tone="info" class="mb-0">Dikirim ke {{ $pilihanPegawai->count() }} akun dalam wewenang Anda (tidak termasuk Anda sendiri). Untuk informasi umum, pertimbangkan membuat <a href="{{ route('pengumuman.create') }}" class="alert-link">pengumuman</a>.</x-alert>
                        </div>
                    </x-card>

                    {{-- 2. Pesan --}}
                    <x-card title="2. Pesan" icon="bi-chat-left-text">
                        <x-select name="jenis" id="f-jenis" label="Jenis" :options="['pengingat' => 'Pengingat', 'tugas' => 'Tugas', 'info' => 'Info']" selected="pengingat" />
                        <x-input name="judul" id="judul" label="Judul" maxlength="120" required :value="$templateLengkapi['judul']" placeholder="Contoh: Kumpulkan laporan bulanan" />
                        <x-textarea name="pesan" id="pesan" label="Pesan" rows="6" maxlength="1000" required :value="$templateLengkapi['pesan']" field-class="mb-0"
                            placeholder="Contoh: Mohon kumpulkan laporan bulanan paling lambat Jumat." />
                    </x-card>
                </div>

                {{-- ============ Pratinjau ============ --}}
                <aside class="col-lg-5 hr-samping" aria-label="Pratinjau notifikasi">
                    <x-card title="Pratinjau" subtitle="Begini notifikasi tampil di lonceng penerima." icon="bi-eye">
                        <div class="notif-pratinjau">
                            <div class="notif-item is-baru" aria-live="polite">
                                <span class="hr-stat-ikon hr-tone-info" data-pratinjau-ikon><i class="bi bi-bell" aria-hidden="true"></i></span>
                                <span class="notif-isi">
                                    <span class="notif-kepala">
                                        <span class="notif-judul" data-pratinjau-judul></span>
                                        <span class="hr-badge hr-badge-info" data-pratinjau-jenis>Pengingat</span>
                                    </span>
                                    <span class="notif-pesan" data-pratinjau-pesan></span>
                                    <span class="notif-waktu">baru saja · dari {{ Auth::user()->name }}</span>
                                </span>
                                <span class="notif-titik" aria-hidden="true"></span>
                            </div>
                        </div>
                        <p class="small text-muted mt-3 mb-0" data-pratinjau-penerima></p>
                        <x-slot:footer>
                            <x-button type="submit" size="lg" block icon="bi-send">Kirim Notifikasi</x-button>
                        </x-slot:footer>
                    </x-card>
                </aside>
            </div>
        </form>
    </div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('formKirimNotif');
        function sinkron() {
            const target = form.querySelector('input[name="target"]:checked').value;
            form.querySelectorAll('[data-target]').forEach(function (el) { el.hidden = el.dataset.target !== target; });
            perbaruiPenerima();
        }
        form.querySelectorAll('input[name="target"]').forEach(function (r) { r.addEventListener('change', sinkron); });

        const cari = document.getElementById('cari-pegawai');
        if (cari) {
            cari.addEventListener('input', function () {
                const kata = cari.value.trim().toLowerCase();
                document.querySelectorAll('#daftar-pegawai [data-nama]').forEach(function (el) { el.hidden = kata !== '' && ! el.dataset.nama.includes(kata); });
            });
        }

        // Pratinjau langsung: judul, pesan, jenis (warna & ikon), dan ringkasan penerima.
        const JENIS = { pengingat: ['Pengingat', 'info', 'bi-bell'], tugas: ['Tugas', 'warning', 'bi-list-check'], info: ['Info', 'neutral', 'bi-info-circle'] };
        const el = (s) => document.querySelector(s);
        function perbaruiPratinjau() {
            const [label, tone, ikon] = JENIS[el('#f-jenis').value] || JENIS.info;
            el('[data-pratinjau-judul]').textContent = el('#judul').value.trim() || 'Judul notifikasi';
            el('[data-pratinjau-pesan]').textContent = el('#pesan').value.trim() || 'Isi pesan akan tampil di sini.';
            el('[data-pratinjau-jenis]').textContent = label;
            el('[data-pratinjau-jenis]').className = 'hr-badge hr-badge-' + tone;
            el('[data-pratinjau-ikon]').className = 'hr-stat-ikon hr-tone-' + tone;
            el('[data-pratinjau-ikon]').innerHTML = '<i class="bi ' + ikon + '" aria-hidden="true"></i>';
        }
        function perbaruiPenerima() {
            const target = form.querySelector('input[name="target"]:checked').value;
            const n = (nama) => form.querySelectorAll('input[name="' + nama + '[]"]:checked').length;
            const teks = {
                belum_lengkap: @json($belumLengkap->count() . ' akun yang datanya belum lengkap'),
                semua: @json($pilihanPegawai->count() . ' akun dalam wewenang Anda'),
                unit: n('unit') + ' bagian dipilih',
                pegawai: n('pegawai') + ' pegawai dipilih',
                role: n('role') + ' role dipilih',
            }[target];
            el('[data-pratinjau-penerima]').innerHTML = '<i class="bi bi-people me-1" aria-hidden="true"></i>Penerima: <strong></strong>';
            el('[data-pratinjau-penerima] strong').textContent = teks;
        }
        ['#f-jenis', '#judul', '#pesan'].forEach((s) => el(s).addEventListener('input', perbaruiPratinjau));
        el('#f-jenis').addEventListener('change', perbaruiPratinjau);
        form.addEventListener('change', (e) => { if (e.target.matches('input[type=checkbox]')) perbaruiPenerima(); });
        perbaruiPratinjau();
        sinkron();
    });
</script>
@include('partials.pilih-semua')
@endsection
