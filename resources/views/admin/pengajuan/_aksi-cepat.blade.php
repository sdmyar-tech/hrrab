{{--
    Bilah "Perlu keputusan Anda" di atas detail pengajuan, khusus layar < 1200px (kartu Tindakan baru tampil di bawah).
    Parameter sama dengan admin.pengajuan._kartu-aksi; tombol membuka dialog yang sama (admin.pengajuan._persetujuan).
--}}
@if ($item->isPending() && \App\Support\PengajuanWorkflow::canAct(auth()->user(), $item))
    <div class="hr-aksi-cepat d-xl-none" role="region" aria-label="Tindakan cepat">
        <div class="min-w-0">
            <strong class="d-block">Perlu keputusan Anda</strong>
            <span class="hr-sel-sub">Tahap {{ $item->currentLevelLabel() }}</span>
        </div>
        <div class="hr-aksi-cepat-tombol">
            <x-button size="sm" icon="bi-check-lg" data-persetujuan="setujui" :data-url="$setujui" :data-judul="$judul"
                :data-konteks="json_encode($konteks, JSON_UNESCAPED_UNICODE)"
                :data-pengganti="isset($pengganti) ? json_encode(array_values(collect($pengganti)->all()), JSON_UNESCAPED_UNICODE) : null">Setujui</x-button>
            <x-button size="sm" variant="danger" icon="bi-x-lg" data-persetujuan="tolak" :data-url="$tolak" :data-judul="$judul"
                :data-konteks="json_encode($konteks, JSON_UNESCAPED_UNICODE)">Tolak</x-button>
        </div>
    </div>
@endif
