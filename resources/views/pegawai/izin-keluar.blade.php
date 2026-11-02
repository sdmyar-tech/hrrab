@extends(Auth::user()->panelLayout())

@section('title', 'Izin Keluar')

@section('content')
@php
    $menunggu = $riwayat->filter(fn ($r) => $r->isPending())->count();
    $tiketAktif = $riwayat->filter(fn ($r) => $r->isApproved() && $r->statusTiket() !== 'lewat');
    $bulanIni = $riwayat->filter(fn ($r) => $r->tanggal->isSameMonth(now()))->count();
@endphp
<main class="dashboard-content">
    <div class="container-fluid">
        <x-page-header title="Izin Keluar Jam Kerja" eyebrow="Pengajuan Saya"
            subtitle="Setelah disetujui, tunjukkan tiket keluar di layar HP kepada security di gerbang.">
            <x-slot:actions>
                @include('partials.tombol-ekspor', ['eksporRoute' => 'pegawai.izin-keluar.export', 'eksporTeks' => 'Ekspor Riwayat'])
            </x-slot:actions>
        </x-page-header>

        {{-- ============ Ringkasan ============ --}}
        <section class="row g-3 mb-4" aria-label="Ringkasan izin keluar">
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
                <x-card title="Formulir Izin Keluar" subtitle="Isian bertanda * wajib diisi." icon="bi-door-open">
                    <form action="{{ route('pegawai.izin-keluar.store') }}" method="POST" data-confirm="Ajukan izin keluar ini?">
                        @csrf

                        <x-input name="tanggal" id="tanggal" type="date" label="Tanggal" :value="now()->toDateString()" required
                            :min="now()->toDateString()" :max="now()->addDays($maksHari)->toDateString()"
                            :help="'Paling lambat ' . $maksHari . ' hari ke depan.'" />

                        <div class="row gx-3">
                            <div class="col-6"><x-input name="jam_keluar" id="jam_keluar" type="time" label="Jam Keluar" required /></div>
                            <div class="col-6"><x-input name="jam_kembali" id="jam_kembali" type="time" label="Perkiraan Kembali" /></div>
                        </div>
                        <div class="mb-3">
                            <x-checkbox name="tidak_kembali" id="tidak_kembali" value="1" label="Tidak kembali lagi hari ini" />
                        </div>

                        <x-select name="keperluan" id="keperluan" label="Keperluan" :options="$keperluanOptions" placeholder="Pilih keperluan" required />

                        <x-textarea name="alasan" id="alasan" label="Alasan / Tujuan" rows="3" maxlength="500" required
                            placeholder="Contoh: ke bank untuk urusan gaji" />

                        <x-button type="submit" size="lg" block icon="bi-send">Ajukan Izin Keluar</x-button>
                    </form>
                </x-card>
            </div>

            {{-- ============ Samping: alur & cara pakai ============ --}}
            <div class="col-lg-5 d-grid gap-3">
                @if ($rute)
                    <x-card title="Alur Persetujuan" icon="bi-signpost-split">
                        <x-timeline label="Alur persetujuan izin keluar">
                            @foreach ($rute as $r)
                                <x-timeline.item state="pending" icon="bi-person" :title="str_replace(' (Approve Akhir)', '', $r['label'])" />
                            @endforeach
                            <x-timeline.item state="pending" icon="bi-ticket-perforated" title="Tiket keluar terbit" />
                        </x-timeline>
                    </x-card>
                @endif
                <x-card title="Cara Memakai Tiket" icon="bi-info-circle">
                    <ul class="hr-daftar-info">
                        <li><i class="bi bi-phone" aria-hidden="true"></i>Buka tiket dari riwayat di bawah lalu tunjukkan layarnya ke security.</li>
                        <li><i class="bi bi-shield-check" aria-hidden="true"></i>Tiket sah bila jam berjalan dan pita bergerak — tangkapan layar tidak berlaku.</li>
                    </ul>
                </x-card>
            </div>
        </div>

        {{-- ============ Riwayat ============ --}}
        <section class="hr-section">
            <x-card title="Riwayat Izin Keluar" icon="bi-clock-history" :padding="false">
                @if ($urutOpsi)
                    <x-slot:actions>@include('partials.urut')</x-slot:actions>
                @endif
                <x-table caption="Riwayat izin keluar">
                    <x-slot:head>
                        <tr><th>Tanggal</th><th>Jam</th><th>Keperluan</th><th>Status</th><th class="text-end">Tiket</th></tr>
                    </x-slot:head>
                    @forelse ($riwayat as $item)
                        <tr>
                            <td>
                                <span class="fw-semibold text-nowrap">{{ $item->tanggal->translatedFormat('d M Y') }}</span>
                                <span class="small text-muted d-block">{{ $item->tanggal->translatedFormat('l') }}</span>
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
                                @if ($item->isApproved())
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
                                <x-empty-state icon="bi-door-open" title="Belum ada izin keluar" compact>Ajukan lewat formulir di atas; tiket muncul di sini setelah disetujui.</x-empty-state>
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
        // "Tidak kembali" → jam kembali tidak perlu diisi.
        const tidakKembali = document.getElementById('tidak_kembali');
        const jamKembali = document.getElementById('jam_kembali');
        const label = document.querySelector('label[for="jam_kembali"]');
        const atur = () => {
            jamKembali.disabled = tidakKembali.checked;
            jamKembali.required = !tidakKembali.checked;
            label.classList.toggle('required', !tidakKembali.checked);
            if (tidakKembali.checked) jamKembali.value = '';
        };
        tidakKembali.addEventListener('change', atur);
        atur();
    });
</script>
@endsection
