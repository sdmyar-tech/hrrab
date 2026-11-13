@extends(Auth::user()->panelLayout())

@section('title', 'Permintaan Ganti Sandi')

@section('content')
<main class="dashboard-content">
    <div class="container-fluid px-3 px-lg-4 py-4">
        <x-page-header title="Permintaan Ganti Sandi" eyebrow="Sistem"
            subtitle="Pegawai mengajukan penggantian kata sandi dari halaman Profil. Setelah Anda setujui, form ganti kata sandi muncul di akun pegawai itu (sekali pakai)." />

        <div class="row g-3 mb-4">
            <div class="col-sm-4"><x-stat-card label="Menunggu persetujuan" :value="$menunggu" icon="bi-hourglass-split" :tone="$menunggu ? 'warning' : 'neutral'" meta="Perlu diputuskan" /></div>
        </div>

        <x-card title="Daftar Permintaan" icon="bi-key" :padding="false">
            <x-table caption="Permintaan ganti kata sandi">
                <x-slot:head>
                    <tr><th>Pemohon</th><th>Alasan</th><th>Diajukan</th><th>Status</th><th class="text-end">Aksi</th></tr>
                </x-slot:head>
                @forelse ($permintaan as $p)
                    <tr>
                        <td>
                            <span class="fw-semibold">{{ $p->user?->name ?? '—' }}</span>
                            <span class="small text-muted d-block">{{ $p->user?->email }}</span>
                        </td>
                        <td class="text-muted">{{ $p->alasan ?: '–' }}</td>
                        <td class="text-nowrap">{{ $p->created_at->translatedFormat('d M Y H:i') }}</td>
                        <td>
                            <x-badge :tone="$p->nada()">{{ $p->label() }}</x-badge>
                            @if ($p->diproses_pada)
                                <span class="small text-muted d-block">{{ $p->pemroses?->name }} · {{ $p->diproses_pada->translatedFormat('d M Y') }}</span>
                            @endif
                            @if ($p->catatan)<span class="small text-muted d-block">{{ $p->catatan }}</span>@endif
                        </td>
                        <td class="text-end">
                            @if ($p->status === \App\Models\PermintaanGantiPassword::STATUS_PENDING)
                                <div class="d-inline-flex flex-wrap gap-2 justify-content-end">
                                    <form action="{{ route('admin.permintaan-password.setujui', $p) }}" method="POST" class="m-0"
                                        data-confirm="Setujui permintaan ganti kata sandi {{ $p->user?->name }}?">
                                        @csrf
                                        <x-button type="submit" size="sm" icon="bi-check-lg">Setujui</x-button>
                                    </form>
                                    <form action="{{ route('admin.permintaan-password.tolak', $p) }}" method="POST" class="m-0 d-inline-flex gap-1" data-form-tolak>
                                        @csrf
                                        <input type="text" name="catatan" class="form-control form-control-sm" maxlength="500" required
                                            placeholder="Alasan menolak, contoh: belum lewat 30 hari" aria-label="Alasan menolak">
                                        <x-button type="submit" size="sm" variant="danger-outline" icon="bi-x-lg">Tolak</x-button>
                                    </form>
                                </div>
                            @else
                                <span class="text-muted">–</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">
                            <x-empty-state icon="bi-key" title="Belum ada permintaan" compact>Permintaan dari pegawai akan tampil di sini.</x-empty-state>
                        </td>
                    </tr>
                @endforelse
            </x-table>
            @if ($permintaan->hasPages())
                <x-slot:footer>{{ $permintaan->links() }}</x-slot:footer>
            @endif
        </x-card>
    </div>
</main>
@endsection
