@if ($pengajuan->isPending()
    && $pengajuan->current_level === \App\Support\PengajuanWorkflow::LEVEL_GM
    && auth()->user()->isGm()
    && \App\Support\PengajuanWorkflow::isGmPengganti(auth()->user(), $pengajuan))
    <small class="badge text-bg-warning d-inline-block mt-1">
        Bidang lain &middot; menggantikan {{ \App\Support\PengajuanWorkflow::gmUtamaLabel($pengajuan) }}
    </small>
@endif
