{{--
    Navigasi tab (Bootstrap tab, tanpa muat ulang). Isi tiap tab ditulis dengan <x-tabs.pane>.
    <x-tabs id="profil" :items="[['key' => 'akun', 'label' => 'Profil', 'icon' => 'bi-person'], ...]" active="akun" vertical />
    <div class="tab-content">
        <x-tabs.pane tabs="profil" key="akun" active>...</x-tabs.pane>
    </div>
    vertical: tab di kiri (desktop), mendatar & bisa digeser di HP. Tab terakhir yang dibuka diingat lewat #hash URL.
--}}
@props([
    'id',
    'items' => [],
    'active' => null,
    'vertical' => false,
    'label' => 'Bagian halaman',
])
@php $aktif = $active ?? ($items[0]['key'] ?? null); @endphp
<div {{ $attributes->merge(['class' => 'hr-tabs' . ($vertical ? ' hr-tabs-vertikal' : '')]) }} role="tablist" aria-label="{{ $label }}"
    @if ($vertical) aria-orientation="vertical" @endif data-hr-tabs="{{ $id }}">
    @foreach ($items as $tab)
        <button type="button" class="hr-tab {{ $tab['key'] === $aktif ? 'active' : '' }}" role="tab"
            id="{{ $id }}-tab-{{ $tab['key'] }}" data-bs-toggle="tab" data-bs-target="#{{ $id }}-{{ $tab['key'] }}"
            aria-controls="{{ $id }}-{{ $tab['key'] }}" aria-selected="{{ $tab['key'] === $aktif ? 'true' : 'false' }}"
            @if ($tab['key'] !== $aktif) tabindex="-1" @endif>
            @if (! empty($tab['icon']))<i class="bi {{ $tab['icon'] }}" aria-hidden="true"></i>@endif
            <span>{{ $tab['label'] }}</span>
            @if (! empty($tab['badge']))<span class="hr-tab-badge">{{ $tab['badge'] }}</span>@endif
        </button>
    @endforeach
</div>
@once
    <script>
        // Tab dari #hash URL (mis. /profile#profil-keamanan) dibuka saat halaman dimuat; ganti tab memperbarui hash tanpa menggulir.
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof bootstrap === 'undefined') return;
            const hash = decodeURIComponent(location.hash.slice(1));
            document.querySelectorAll('[data-hr-tabs]').forEach(function (nav) {
                const awal = hash && nav.querySelector('[data-bs-target="#' + CSS.escape(hash) + '"]');
                if (awal) bootstrap.Tab.getOrCreateInstance(awal).show();
                nav.addEventListener('shown.bs.tab', function (e) {
                    history.replaceState(null, '', e.target.dataset.bsTarget);
                });
            });
        });
    </script>
@endonce
