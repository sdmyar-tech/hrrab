{{-- Akun login milik pegawai + sakelar "wajib ganti password" (Kepala SDM/Admin). --}}
@php $saya = auth()->user(); @endphp
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2" id="akun-login">
    <h2 class="pd-section-title mb-0"><i class="bi bi-person-lock"></i> Akun Login</h2>
</div>

@if ($semuaAkun->isEmpty())
    <div class="pd-empty mb-4"><i class="bi bi-person-x"></i>Pegawai ini belum punya akun login.</div>
@else
    <div class="d-grid gap-2 mb-4">
        @foreach ($semuaAkun as $u)
            @php
                $alasanKunci = match (true) {
                    $u->id === $saya->id => 'Akun Anda sendiri',
                    $u->isAdmin() && ! $saya->isAdmin() => 'Hanya Admin yang bisa mengatur akun Admin',
                    default => null,
                };
            @endphp
            <div class="akun-baris {{ $u->wajib_ganti_password ? 'is-wajib' : '' }}">
                <div class="min-w-0">
                    <div class="fw-semibold text-truncate">{{ $u->email }}</div>
                    <div class="small text-muted">
                        {{ $u->roleLabel() }}
                        · {{ $u->password_diganti_pada ? 'password diganti ' . $u->password_diganti_pada->translatedFormat('d M Y') : 'password belum pernah diganti sendiri' }}
                    </div>
                </div>
                <div class="d-flex align-items-center flex-wrap gap-2 ms-auto">
                    @if ($u->wajib_ganti_password)
                        <span class="badge text-bg-warning"><i class="bi bi-shield-exclamation me-1"></i>Wajib ganti password</span>
                    @endif
                    @if ($bolehAturAkun)
                        @if ($alasanKunci)
                            <span class="small text-muted" title="{{ $alasanKunci }}"><i class="bi bi-lock"></i> {{ $alasanKunci }}</span>
                        @else
                            <form action="{{ route('admin.akun.wajib-password', $u) }}" method="POST"
                                data-confirm="{{ $u->wajib_ganti_password
                                    ? "Matikan kewajiban ganti password untuk {$u->email}?"
                                    : "Wajibkan {$u->email} mengganti password? Sampai diganti, akun ini tidak bisa membuka halaman apa pun." }}">
                                @csrf
                                <input type="hidden" name="wajib" value="{{ $u->wajib_ganti_password ? 0 : 1 }}">
                                <input type="hidden" name="kembali" value="{{ route('admin.pegawai.show', [$pegawai, 'tab' => 'penugasan']) }}#akun-login">
                                <button type="submit" class="btn btn-sm {{ $u->wajib_ganti_password ? 'btn-light' : 'btn-outline-warning' }}">
                                    <i class="bi {{ $u->wajib_ganti_password ? 'bi-unlock' : 'bi-shield-lock' }}"></i>
                                    {{ $u->wajib_ganti_password ? 'Matikan Wajib Ganti' : 'Wajibkan Ganti Password' }}
                                </button>
                            </form>
                        @endif
                    @endif
                </div>
            </div>
        @endforeach
    </div>
@endif

@once
    <style>
        .akun-baris { display: flex; flex-wrap: wrap; align-items: center; gap: .5rem 1rem; padding: .7rem .9rem; border: 1px solid var(--admin-border); border-radius: 10px; background: var(--admin-surface-soft); }
        .akun-baris.is-wajib { border-color: #f2c76b; background: #fffaf0; }
        html[data-theme="dark"] .akun-baris.is-wajib { background: rgba(245, 158, 11, .08); border-color: rgba(245, 158, 11, .35); }
    </style>
@endonce
