@forelse ($daftar as $k)
    <span class="rl-chip {{ in_array($k, $kunci, true) ? 'kunci' : '' }}" title="{{ \App\Models\Role::HAK_AKSES[$k][1] }}">@if (in_array($k, $kunci, true))<i class="bi bi-lock-fill me-1"></i>@endif{{ \App\Models\Role::HAK_AKSES[$k][0] }}</span>
@empty
    <span class="text-muted small">Tanpa hak akses tambahan</span>
@endforelse
