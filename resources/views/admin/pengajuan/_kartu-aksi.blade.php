{{--
    Kartu tindakan lengket di kolom kanan halaman detail pengajuan (Batch 5, identik untuk cuti / lembur / izin keluar).
    Parameter: $item, $setujui, $tolak (URL), $judul ("pengajuan cuti"), $konteks ([[label, nilai], …]),
    opsional $pengganti (koleksi nama, cuti tahap pertama), $alur (RingkasanPengajuan::…()['alur'], untuk nama penyetuju yang ditunggu).
    Setujui/Tolak membuka dialog konfirmasi dari admin.pengajuan._persetujuan (dipasang di halaman).
--}}
@php
    $bisaProses = $item->isPending() && \App\Support\PengajuanWorkflow::canAct(auth()->user(), $item);
    $berjalan = collect($alur ?? [])->firstWhere('status', 'berjalan');
    $idCatatan = 'catatan-tolak-' . $item->getKey();
@endphp
<x-card :title="$item->isPending() ? 'Tindakan' : 'Keputusan'" :icon="$item->isPending() ? 'bi-lightning-charge' : 'bi-flag'" class="hr-kartu-aksi">
    @if ($item->isPending())
        <dl class="hr-konteks hr-konteks-ringkas">
            <dt>Tahap saat ini</dt>
            <dd>{{ $item->currentLevelLabel() }}</dd>
            @if ($berjalan && $berjalan['oleh'])
                <dt>Menunggu</dt>
                <dd>{{ $berjalan['oleh'] }}</dd>
            @endif
        </dl>
        @include('admin.partials.gm-pengganti-badge', ['pengajuan' => $item])

        @if ($bisaProses)
            <div class="hr-field mb-0 mt-3">
                <label class="form-label" for="{{ $idCatatan }}">Alasan penolakan <span class="hr-field-opsional">(diisi bila menolak)</span></label>
                <textarea id="{{ $idCatatan }}" class="form-control" rows="3" maxlength="1000"
                    placeholder="Tulis alasan bila menolak, contoh: kuota cuti bulan ini sudah penuh."></textarea>
                <div class="form-text">Alasan dikirim ke pegawai. Setujui tidak memerlukan catatan.</div>
            </div>
            <div class="hr-kartu-aksi-tombol">
                <x-button icon="bi-check-lg" data-persetujuan="setujui" :data-url="$setujui" :data-judul="$judul"
                    :data-konteks="json_encode($konteks, JSON_UNESCAPED_UNICODE)"
                    :data-pengganti="isset($pengganti) ? json_encode(array_values(collect($pengganti)->all()), JSON_UNESCAPED_UNICODE) : null">Setujui</x-button>
                <x-button variant="danger" icon="bi-x-lg" data-persetujuan="tolak" :data-url="$tolak" :data-judul="$judul"
                    :data-konteks="json_encode($konteks, JSON_UNESCAPED_UNICODE)" data-catatan-sumber="#{{ $idCatatan }}">Tolak</x-button>
            </div>
        @else
            <x-alert tone="info" class="mt-3 mb-0">Anda tidak punya tindakan pada tahap ini. Pengajuan sedang menunggu {{ $item->currentLevelLabel() }}.</x-alert>
        @endif
    @else
        <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
            <x-status-badge :badge="$item->badgeClass()" size="lg" dot>{{ $item->statusLabel() }}</x-status-badge>
        </div>
        <dl class="hr-konteks hr-konteks-ringkas">
            @if ($item->approvedBy)
                <dt>{{ $item->status === $item::STATUS_REJECTED ? 'Ditolak oleh' : 'Persetujuan akhir' }}</dt>
                <dd>{{ $item->approvedBy->name }}</dd>
            @endif
            @if ($item->approved_at)
                <dt>Waktu</dt>
                <dd>{{ $item->approved_at->translatedFormat('d M Y, H:i') }}</dd>
            @endif
            @if ($item->catatan_admin)
                <dt>{{ $item->status === $item::STATUS_REJECTED ? 'Alasan' : 'Catatan' }}</dt>
                <dd>{{ $item->catatan_admin }}</dd>
            @endif
        </dl>
    @endif
</x-card>
