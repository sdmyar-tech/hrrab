{{--
    Pilihan bidang untuk GM di daftar cuti / lembur / izin keluar (Batch 5): "Bidang Saya" · "Bidang Lain (Pengganti)".
    Parameter: $route (nama rute daftar), $bagian = 'chip' (chip di x-filter-bar, slot chips) | 'peringatan' (banner saat bidang lain dibuka).
--}}
@if ($bidangTabs)
    @php $activeTab = request('bidang', 'saya') === 'lain' ? 'lain' : 'saya'; @endphp
    @if (($bagian ?? 'chip') === 'chip')
        @foreach (['saya' => ['Bidang Saya', 'bi-diagram-3'], 'lain' => ['Bidang Lain (Pengganti)', 'bi-person-gear']] as $tab => [$label, $ikon])
            <x-chip :href="route($route, array_merge(request()->except(['bidang', 'page']), ['bidang' => $tab]))" :active="$activeTab === $tab"
                :icon="$ikon" :count="$bidangTabs[$tab] > 0 ? $bidangTabs[$tab] : null">{{ $label }}</x-chip>
        @endforeach
    @elseif ($activeTab === 'lain')
        <x-alert tone="warning" class="mb-3">
            Pengajuan dari bidang GM lain. Setujui di sini bila GM utamanya berhalangan — akan tercatat sebagai <strong>GM pengganti</strong>.
        </x-alert>
    @endif
@endif
