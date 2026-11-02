{{-- Akun HRRAB milik pegawai + sakelar "wajib ganti kata sandi" (Kepala SDM/Admin). Bagian di tab Penugasan detail pegawai. --}}
@php $saya = auth()->user(); @endphp
<section class="hr-detail-bagian hr-bagian" id="akun-login" aria-labelledby="judul-akun-login">
    <h2 class="hr-detail-judul" id="judul-akun-login"><i class="bi bi-person-lock" aria-hidden="true"></i> Akun HRRAB</h2>

    @if ($semuaAkun->isEmpty())
        <x-empty-state icon="bi-person-x" title="Belum ada akun" compact>Pegawai ini belum punya akun login.</x-empty-state>
    @else
        <ul class="hr-list">
            @foreach ($semuaAkun as $u)
                @php
                    $alasanKunci = match (true) {
                        $u->id === $saya->id => 'Akun Anda sendiri',
                        $u->isAdmin() && ! $saya->isAdmin() => 'Hanya Admin yang bisa mengatur akun Admin',
                        default => null,
                    };
                @endphp
                <li class="hr-list-item flex-wrap">
                    <div class="hr-list-utama">
                        <span class="hr-list-judul">{{ $u->email }}</span>
                        <span class="d-block small text-muted">
                            {{ $u->roleLabel() }}
                            · {{ $u->password_diganti_pada ? 'kata sandi diganti ' . $u->password_diganti_pada->translatedFormat('d M Y') : 'kata sandi belum pernah diganti sendiri' }}
                        </span>
                    </div>
                    <div class="d-flex align-items-center flex-wrap gap-2 ms-auto">
                        @if ($u->wajib_ganti_password)
                            <x-badge tone="warning" icon="bi-shield-exclamation">Wajib ganti kata sandi</x-badge>
                        @endif
                        @if ($bolehAturAkun)
                            @if ($alasanKunci)
                                <span class="small text-muted"><i class="bi bi-lock" aria-hidden="true"></i> {{ $alasanKunci }}</span>
                            @else
                                <form action="{{ route('admin.akun.wajib-password', $u) }}" method="POST"
                                    data-confirm="{{ $u->wajib_ganti_password
                                        ? "Matikan kewajiban ganti kata sandi untuk {$u->email}?"
                                        : "Wajibkan {$u->email} mengganti kata sandi? Sampai diganti, akun ini tidak bisa membuka halaman apa pun." }}">
                                    @csrf
                                    <input type="hidden" name="wajib" value="{{ $u->wajib_ganti_password ? 0 : 1 }}">
                                    <input type="hidden" name="kembali" value="{{ route('admin.pegawai.show', [$pegawai, 'tab' => 'penugasan']) }}#akun-login">
                                    <x-button type="submit" size="sm" :variant="$u->wajib_ganti_password ? 'ghost' : 'secondary'"
                                        :icon="$u->wajib_ganti_password ? 'bi-unlock' : 'bi-shield-lock'">
                                        {{ $u->wajib_ganti_password ? 'Matikan Wajib Ganti' : 'Wajibkan Ganti Kata Sandi' }}
                                    </x-button>
                                </form>
                            @endif
                        @endif
                    </div>
                </li>
            @endforeach
        </ul>
    @endif
</section>
