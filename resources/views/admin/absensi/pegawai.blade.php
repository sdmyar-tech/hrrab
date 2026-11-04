@extends($layout)

@section('content')
@php
    $ik = \App\Support\Absensi\TampilanAbsensi::IKON;
    $durasi = fn ($m) => \App\Support\Absensi\PenilaianAbsensi::durasi($m);
    $r = $rekap;
    $persen = $r['hari_kerja'] ? (int) round($r['hadir_total'] / $r['hari_kerja'] * 100) : null;
    $izinCuti = $r['cuti'] + $r['izin'] + $r['sakit'] + $r['dinas_luar'];
    $rincianIzin = collect(['cuti' => $r['cuti'], 'izin' => $r['izin'], 'sakit' => $r['sakit'], 'dinas luar' => $r['dinas_luar']])->filter()->map(fn ($n, $k) => $n . ' ' . $k);
    // Kategori tiap hari untuk saringan cepat di tabel rincian (tanpa muat ulang halaman).
    $kategoriHari = fn ($h) => implode(' ', array_filter([
        'semua',
        in_array($h['status'], ['telat', 'telat_izin'], true) ? 'telat' : null,
        $h['status'] === 'alpa' ? 'alpa' : null,
        in_array($h['status'], ['cuti', 'izin', 'sakit', 'dinas_luar'], true) ? 'cuti' : null,
        ($h['tanpa_scan_pulang'] || $h['lembur_status'] === 'belum' || ($h['pulang_cepat_menit'] && ! $h['pulang_cepat_izin'])) ? 'cek' : null,
        $h['lembur_status'] ? 'lembur' : null,
        $h['hari_kerja'] || $h['masuk'] ? 'kerja' : null,
    ]));
    $jumlahKat = fn ($k) => collect($hari)->filter(fn ($h) => str_contains(' ' . $kategoriHari($h) . ' ', ' ' . $k . ' '))->count();
    $kolom = $bisaKelola ? 5 : 4;
@endphp
<main class="dashboard-content">
    <div class="container-fluid">
        <x-page-header :title="$pegawai->nama_lengkap" eyebrow="Riwayat Absensi" :back="route('admin.absensi.harian')" back-label="Absensi Harian"
            :subtitle="collect([$pegawai->kepegawaian?->jabatan, $pegawai->kepegawaian?->unit_kerja])->filter()->implode(' · ') ?: null">
            <x-slot:media><x-avatar :name="$pegawai->nama_lengkap" size="lg" /></x-slot:media>
            <x-badge :tone="$pegawai->finger_id ? 'neutral' : 'warning'" :icon="$ik['mesin']">ID mesin {{ $pegawai->finger_id ?? 'belum ada' }}</x-badge>
            <x-badge :tone="$pegawai->jadwalKerja ? 'neutral' : 'warning'" icon="bi-clock">{{ $pegawai->jadwalKerja ? 'Jam kerja ' . $pegawai->jadwalKerja->ringkas() : 'Jam kerja belum diatur' }}</x-badge>
            <x-slot:actions>
                @include('partials.tombol-ekspor', ['eksporRoute' => 'admin.absensi.pegawai.export',
                    'eksporParam' => ['pegawai' => $pegawai, 'dari' => $dari->toDateString(), 'sampai' => $sampai->toDateString()]])
                @if ($bisaKelola)
                    <x-button variant="secondary" :href="route('admin.absensi.pengaturan', ['q' => $pegawai->nip ?: $pegawai->nama_lengkap])" :icon="$ik['pengaturan']">Ubah Jam Kerja</x-button>
                @endif
            </x-slot:actions>
        </x-page-header>

        @include('admin.absensi._nav')

        @unless ($pegawai->ikut_absensi)
            <x-alert tone="warning" class="mb-4">Pegawai ini <strong>tidak ikut absensi</strong>, sehingga tidak muncul di Absensi Harian & Rekap.</x-alert>
        @endunless
        @foreach ($pesanRentang as $p)
            <x-alert tone="warning" class="mb-4">{{ $p }}</x-alert>
        @endforeach

        @include('admin.absensi._periode', ['rute' => 'admin.absensi.pegawai', 'ruteParam' => ['pegawai' => $pegawai],
            'dari' => $dari, 'sampai' => $sampai, 'preset' => $preset])

        <section class="row row-cols-2 row-cols-md-3 row-cols-xl-5 g-3 mb-4" aria-label="Ringkasan absensi pribadi">
            <div class="col">
                <x-stat-card label="Hadir" :value="$r['hadir_total']" :unit="'/ ' . $r['hari_kerja'] . ' hari'" icon="bi-person-check" tone="success"
                    :meta="($persen !== null ? $persen . '% kehadiran · ' : '') . $r['on_time'] . ' tepat waktu'" />
            </div>
            <div class="col">
                <x-stat-card label="Telat" :value="$r['telat']" unit="kali" icon="bi-alarm" :tone="$r['telat'] ? 'warning' : 'neutral'"
                    :meta="$r['telat'] ? 'Total ' . $durasi($r['telat_menit']) . ($r['telat_izin'] ? ' · +' . $r['telat_izin'] . ' berizin' : '') : ($r['telat_izin'] ? $r['telat_izin'] . ' telat berizin' : 'Tidak pernah telat')" />
            </div>
            <div class="col">
                <x-stat-card label="Izin / Cuti" :value="$izinCuti" unit="hari" :icon="$ik['izin']" tone="info"
                    :meta="$rincianIzin->implode(' · ') ?: 'Tidak ada'" />
            </div>
            <div class="col">
                <x-stat-card label="Alpa" :value="$r['alpa']" unit="hari" icon="bi-x-octagon" :tone="$r['alpa'] ? 'danger' : 'neutral'" meta="Hari kerja tanpa scan" />
            </div>
            <div class="col">
                <x-stat-card label="Lembur" :value="$durasi($r['lembur_menit'])" :icon="$ik['lembur']" tone="primary"
                    :meta="$r['lembur_surat_menit'] ? 'Dari surat ' . $durasi($r['lembur_surat_menit']) : 'Tidak ada surat lembur'" />
            </div>
        </section>

        <x-card title="Kalender Kehadiran" icon="bi-calendar3" class="mb-4"
            :subtitle="$bisaKelola ? 'Warna menunjukkan status tiap hari. Klik tanggal untuk mengoreksi.' : 'Warna menunjukkan status tiap hari.'">
            @include('admin.absensi._kalender', ['hari' => $hari, 'dari' => $dari, 'sampai' => $sampai, 'pegawai' => $pegawai, 'bisaKoreksi' => $bisaKelola])
        </x-card>

        <x-card :padding="false" title="Rincian per Hari" icon="bi-list-ul" subtitle="Jam masuk, jam pulang, dan keterangan setiap tanggal.">
            <x-filter-bar>
                <x-slot:chips>
                    @foreach (['kerja' => 'Hari kerja', 'telat' => 'Telat', 'alpa' => 'Alpa', 'cuti' => 'Cuti / izin', 'cek' => 'Perlu dicek', 'lembur' => 'Lembur', 'semua' => 'Semua tanggal'] as $k => $label)
                        <x-chip :active="$k === 'kerja'" :count="$jumlahKat($k)" data-saring-hari="{{ $k }}">{{ $label }}</x-chip>
                    @endforeach
                </x-slot:chips>
                <x-slot:summary>
                    <p class="hr-filter-bar-jumlah" aria-live="polite"><strong data-jumlah-hari>{{ $jumlahKat('kerja') }}</strong> tanggal ditampilkan</p>
                    <x-button variant="ghost" size="sm" icon="bi-arrow-down-up" class="ms-auto" data-balik-urut title="Balik urutan tanggal"><span>Terbaru dulu</span></x-button>
                </x-slot:summary>
            </x-filter-bar>
            <x-table caption="Rincian absensi per hari" sticky striped scroll>
                <x-slot:head>
                    <tr>
                        <th>Tanggal</th>
                        <th>Jam Masuk</th>
                        <th>Jam Pulang</th>
                        <th>Status</th>
                        @if ($bisaKelola)<th class="hr-kolom-aksi">Aksi</th>@endif
                    </tr>
                </x-slot:head>
                @foreach ($hari as $h)
                    <tr class="{{ in_array($h['status'], ['libur', 'belum'], true) ? 'abs-baris-libur' : '' }}" data-kategori=" {{ $kategoriHari($h) }} ">
                        <td class="text-nowrap">
                            <span class="hr-sel-judul">{{ $h['tanggal']->translatedFormat('d M Y') }}</span>
                            <span class="hr-sel-sub">{{ $h['tanggal']->translatedFormat('l') }}</span>
                        </td>
                        <td>
                            @if ($h['masuk'])<span class="abs-jam">{{ $h['masuk'] }}</span>@else<span class="abs-jam is-kosong">–</span>@endif
                            <span class="hr-sel-sub">{{ $h['jadwal'] ? 'Jadwal ' . $h['jadwal']['masuk'] : 'Bukan hari kerja' }}</span>
                        </td>
                        <td>
                            @if ($h['keluar'])<span class="abs-jam">{{ $h['keluar'] }}</span>@else<span class="abs-jam is-kosong">–</span>@endif
                            @if ($h['jadwal'])<span class="hr-sel-sub">Jadwal {{ $h['jadwal']['pulang'] }}</span>@endif
                        </td>
                        <td>
                            @include('admin.absensi._status', ['h' => $h, 'catatanPenuh' => true])
                            @if ($h['dikoreksi'])
                                <span class="hr-sel-sub mt-1"><i class="bi bi-pencil-square me-1" aria-hidden="true"></i>{{ $h['absensi']->alasan_koreksi }} — {{ $h['absensi']->dikoreksiOleh?->name ?? 'SDM' }}</span>
                            @endif
                        </td>
                        @if ($bisaKelola)
                            <td class="text-end hr-kolom-aksi"><div class="hr-aksi-baris">@include('admin.absensi._tombol-koreksi', ['pegawai' => $pegawai, 'h' => $h])</div></td>
                        @endif
                    </tr>
                @endforeach
                <x-table-empty :colspan="$kolom" icon="bi-funnel" title="Tidak ada tanggal dengan keterangan ini" data-kosong-saring hidden>
                    Pilih saringan lain di atas, misalnya "Semua tanggal".
                </x-table-empty>
            </x-table>
        </x-card>
    </div>
</main>

@if ($bisaKelola)
    @include('admin.absensi._koreksi')
@endif
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const tbody = document.querySelector('tr[data-kategori]')?.parentElement;
        if (!tbody) return;
        const kosong = tbody.querySelector('[data-kosong-saring]');
        const jumlah = document.querySelector('[data-jumlah-hari]');
        const chip = document.querySelectorAll('[data-saring-hari]');
        const saring = function (k) {
            let tampil = 0;
            tbody.querySelectorAll('tr[data-kategori]').forEach(function (tr) {
                const cocok = tr.dataset.kategori.includes(' ' + k + ' ');
                tr.hidden = !cocok;
                tampil += cocok ? 1 : 0;
            });
            kosong.hidden = tampil > 0;
            if (jumlah) jumlah.textContent = tampil;
            chip.forEach(function (b) {
                const aktif = b.dataset.saringHari === k;
                b.classList.toggle('is-active', aktif);
                b.setAttribute('aria-pressed', aktif ? 'true' : 'false');
            });
        };
        chip.forEach((b) => b.addEventListener('click', () => saring(b.dataset.saringHari)));
        saring('kerja'); // bawaan: sembunyikan libur & tanggal tanpa data agar daftar ringkas
        document.querySelector('[data-balik-urut]').addEventListener('click', function () {
            Array.from(tbody.querySelectorAll('tr[data-kategori]')).reverse().forEach((tr) => tbody.insertBefore(tr, kosong));
            const label = this.querySelector('span');
            label.textContent = label.textContent === 'Terbaru dulu' ? 'Terlama dulu' : 'Terbaru dulu';
        });
    });
</script>
@endsection
