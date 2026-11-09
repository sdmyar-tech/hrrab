@extends(Auth::user()->panelLayout())

@section('title', 'Pengajuan Lembur')

@section('content')
@php
    $disetujui = $pengajuanLemburs->where('status', \App\Models\PengajuanLembur::STATUS_APPROVED);
    $jamBulanIni = $disetujui->filter(fn ($l) => $l->tanggal?->isSameMonth(now()))->sum('jumlah_jam');
    $jamTahunIni = $disetujui->filter(fn ($l) => $l->tanggal?->year === now()->year)->sum('jumlah_jam');
    $menunggu = $pengajuanLemburs->filter(fn ($l) => $l->isPending())->count();
    $jam = fn ($n) => rtrim(rtrim(number_format((float) $n, 2, ',', '.'), '0'), ',');
@endphp
<main class="dashboard-content">
    <div class="container-fluid">
        <x-page-header title="Pengajuan Lembur" eyebrow="Pengajuan Saya" subtitle="Catat lembur sesuai surat tugas, lalu pantau persetujuannya.">
            <x-slot:actions>
                @include('partials.tombol-ekspor', ['eksporRoute' => 'pegawai.pengajuan-lembur.export'])
            </x-slot:actions>
        </x-page-header>

        {{-- ============ Ringkasan ============ --}}
        <section class="row g-3 mb-4" aria-label="Ringkasan lembur">
            <div class="col-6 col-xl-4">
                <x-stat-card label="Lembur Bulan Ini" :value="$jam($jamBulanIni)" unit="jam" icon="bi-clock-history" tone="info" meta="Yang sudah disetujui" />
            </div>
            <div class="col-6 col-xl-4">
                <x-stat-card label="Menunggu Persetujuan" :value="$menunggu" icon="bi-hourglass-split" :tone="$menunggu ? 'warning' : 'neutral'" meta="Belum diputuskan" />
            </div>
            <div class="col-12 col-xl-4">
                <x-stat-card label="Total Tahun Ini" :value="$jam($jamTahunIni)" unit="jam" icon="bi-bar-chart" tone="primary" meta="Disetujui sepanjang {{ now()->year }}" />
            </div>
        </section>

        <div class="row g-4 align-items-start">
            {{-- ============ Form ============ --}}
            <div class="col-lg-7">
                <x-card title="Formulir Lembur" subtitle="Isian bertanda * wajib diisi." icon="bi-stopwatch">
                    <form action="{{ route('pegawai.pengajuan-lembur.store') }}" method="POST" id="formLembur" data-confirm="Ajukan lembur ini?">
                        @csrf

                        @if (count($unitOptions) > 1)
                            <x-select name="unit_kerja" id="unit_kerja" label="Bagian Tempat Lembur" required placeholder="Pilih bagian"
                                :options="collect($unitOptions)->mapWithKeys(fn ($u, $i) => [$u => $u . ($i === 0 ? ' (bagian utama)' : '')])->all()"
                                help="Anda bertugas di beberapa bagian. Pengajuan diproses kepala bagian yang dipilih." />
                        @endif

                        <div class="row gx-3">
                            <div class="col-sm-7">
                                <x-input name="tanggal" id="tanggal" type="date" label="Tanggal" :value="now()->format('Y-m-d')" required />
                            </div>
                            <div class="col-sm-5">
                                <div class="hr-field">
                                    <label for="hari" class="form-label">Hari</label>
                                    <input type="text" id="hari" class="form-control" readonly placeholder="Terisi otomatis">
                                </div>
                            </div>
                        </div>

                        <x-textarea name="uraian_tugas" id="uraian_tugas" label="Uraian Tugas" rows="3" required
                            placeholder="Contoh: rekap dokumen akreditasi untuk audit besok pagi" />

                        <div class="row gx-3">
                            {{-- Teks + data-jam24, bukan type="time": native ikut locale perangkat (AM/PM). --}}
                            <div class="col-6"><x-input name="jam_mulai" id="jam_mulai" label="Jam Mulai" required data-jam24 inputmode="numeric" maxlength="5" autocomplete="off" placeholder="Contoh: 16:00" /></div>
                            <div class="col-6"><x-input name="jam_selesai" id="jam_selesai" label="Jam Selesai" required data-jam24 inputmode="numeric" maxlength="5" autocomplete="off" placeholder="Contoh: 20:30" /></div>
                        </div>

                        <div class="hr-hitungan mb-3" aria-live="polite">
                            <i class="bi bi-calculator" aria-hidden="true"></i>
                            <label for="jumlah_jam" class="mb-0">Jumlah jam</label>
                            <input type="text" id="jumlah_jam" readonly placeholder="Terisi otomatis">
                        </div>

                        <x-button type="submit" size="lg" block icon="bi-send">Ajukan Lembur</x-button>
                    </form>
                </x-card>
            </div>

            <div class="col-lg-5 d-grid gap-3 align-content-start">
                {{-- Alur bergantung bagian tempat lembur: satu tangga per bagian, yang tampil mengikuti pilihan bagian. --}}
                @foreach ($alurPerUnit as $unitAlur => $alur)
                    <x-card title="Alur Persetujuan" icon="bi-signpost-split" :subtitle="count($alurPerUnit) > 1 ? 'Lembur di bagian ' . $unitAlur : 'Pengajuan Anda melewati tahap berikut secara berurutan.'"
                        data-alur-unit="{{ $unitAlur }}" :hidden="count($alurPerUnit) > 1 && ! $loop->first">
                        <x-tangga-alur :tahap="$alur" awal="Anda mengajukan" akhir="Lembur disetujui" arah="vertikal" :label="'Alur persetujuan lembur bagian ' . $unitAlur" />
                    </x-card>
                @endforeach
                <x-card title="Ketentuan" icon="bi-info-circle">
                    <ul class="hr-daftar-info">
                        <li><i class="bi bi-file-earmark-text" aria-hidden="true"></i>Lembur dihitung dari <strong>surat tugas</strong> atasan, bukan dari jam pulang mesin absen.</li>
                        <li><i class="bi bi-clock" aria-hidden="true"></i>Isi jam mulai & selesai yang sebenarnya; jumlah jam dihitung otomatis.</li>
                        <li><i class="bi bi-person-check" aria-hidden="true"></i>Pengajuan diproses berjenjang sesuai alur persetujuan bagian Anda; statusnya tampil di riwayat.</li>
                    </ul>
                </x-card>
            </div>
        </div>

        {{-- ============ Riwayat ============ --}}
        <section class="hr-section">
            <x-card title="Riwayat Pengajuan Lembur" icon="bi-clock-history" :padding="false">
                @if ($urutOpsi)
                    <x-slot:actions>@include('partials.urut')</x-slot:actions>
                @endif
                <x-table caption="Riwayat pengajuan lembur">
                    <x-slot:head>
                        <tr><th>Tanggal</th><th>Uraian</th><th class="text-end">Waktu</th><th>Status</th><th>Catatan</th></tr>
                    </x-slot:head>
                    @forelse ($pengajuanLemburs as $item)
                        <tr>
                            <td>
                                <span class="fw-semibold text-nowrap">{{ optional($item->tanggal)->translatedFormat('d M Y') }}</span>
                                <span class="small text-muted d-block">{{ $item->hari }}@if (count($unitOptions) > 1 && $item->unit_kerja) · {{ $item->unit_kerja }}@endif</span>
                            </td>
                            <td class="text-muted">{{ \Illuminate\Support\Str::limit($item->uraian_tugas, 60) }}</td>
                            <td class="hr-angka">
                                {{ substr((string) $item->jam_mulai, 0, 5) }}–{{ substr((string) $item->jam_selesai, 0, 5) }}
                                <span class="small text-muted d-block">{{ $jam($item->jumlah_jam) }} jam</span>
                            </td>
                            <td><x-status-badge :badge="$item->badgeClass()">{{ $item->statusLabel() }}</x-status-badge></td>
                            <td class="text-muted">{{ $item->catatan_admin ?? '–' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <x-empty-state icon="bi-clock-history" title="Belum ada pengajuan lembur" compact>Ajukan lewat formulir di atas; statusnya akan tampil di sini.</x-empty-state>
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
        const hariNames = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', "Jum'at", 'Sabtu'];
        const tanggalInput = document.getElementById('tanggal');
        const hariInput = document.getElementById('hari');
        const jamMulaiInput = document.getElementById('jam_mulai');
        const jamSelesaiInput = document.getElementById('jam_selesai');
        const jumlahJamInput = document.getElementById('jumlah_jam');

        function updateHari() {
            if (!tanggalInput.value) {
                hariInput.value = '';
                return;
            }
            const date = new Date(tanggalInput.value + 'T00:00:00');
            hariInput.value = hariNames[date.getDay()];
        }

        function updateJumlahJam() {
            const lengkap = /^\d{2}:\d{2}$/;
            if (!lengkap.test(jamMulaiInput.value) || !lengkap.test(jamSelesaiInput.value)) {
                jumlahJamInput.value = '';
                return;
            }
            const [h1, m1] = jamMulaiInput.value.split(':').map(Number);
            const [h2, m2] = jamSelesaiInput.value.split(':').map(Number);
            const minutes = (h2 * 60 + m2) - (h1 * 60 + m1);
            jumlahJamInput.value = minutes > 0 ? (minutes / 60).toFixed(2) + ' jam' : '';
        }

        // Bertugas di beberapa bagian: kartu alur mengikuti bagian tempat lembur yang dipilih.
        const unitInput = document.getElementById('unit_kerja');
        function updateAlur() {
            const kartu = document.querySelectorAll('[data-alur-unit]');
            const dipilih = unitInput && unitInput.value ? unitInput.value : (kartu[0] && kartu[0].dataset.alurUnit);
            kartu.forEach(function (k) { k.hidden = k.dataset.alurUnit !== dipilih; });
        }

        tanggalInput.addEventListener('change', updateHari);
        ['input', 'change'].forEach(function (ev) {
            jamMulaiInput.addEventListener(ev, updateJumlahJam);
            jamSelesaiInput.addEventListener(ev, updateJumlahJam);
        });
        if (unitInput) unitInput.addEventListener('change', updateAlur);

        updateHari();
        updateJumlahJam();
        updateAlur();
    });
</script>
@endsection
