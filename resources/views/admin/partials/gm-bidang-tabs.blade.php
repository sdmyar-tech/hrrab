@if ($bidangTabs)
    @php $activeTab = request('bidang', 'saya') === 'lain' ? 'lain' : 'saya'; @endphp
    <ul class="nav nav-tabs px-3 pt-3">
        @foreach (['saya' => 'Bidang Saya', 'lain' => 'Bidang Lain (Pengganti)'] as $tab => $label)
            <li class="nav-item">
                <a class="nav-link {{ $activeTab === $tab ? 'active' : '' }}"
                    href="{{ route($route, array_merge(request()->except(['bidang', 'page']), ['bidang' => $tab])) }}">
                    {{ $label }}
                    @if ($bidangTabs[$tab] > 0)
                        <span class="badge {{ $tab === 'lain' ? 'text-bg-warning' : 'text-bg-primary' }} ms-1">{{ $bidangTabs[$tab] }}</span>
                    @endif
                </a>
            </li>
        @endforeach
    </ul>
    @if ($activeTab === 'lain')
        <div class="alert alert-warning small mx-3 mt-3 mb-0">
            Pengajuan dari bidang GM lain. Approve di sini bila GM utamanya berhalangan — akan tercatat sebagai <strong>GM pengganti</strong>.
        </div>
    @endif
@endif
