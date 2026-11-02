{{--
    Penunjuk langkah horizontal untuk form bertahap.
    <x-stepper id="langkahPengkinian" :steps="[['key' => 'pribadi', 'label' => 'Identitas', 'icon' => 'bi-person'], ...]" :current="0" />
    Tampilan saja: halaman yang mengatur perpindahan langkah (mis. tombol Lanjut/Kembali) cukup memanggil
    window.hrStepper('langkahPengkinian', indeks) untuk memperbarui penanda (selesai = centang, aktif = marun).
--}}
@props([
    'id',
    'steps' => [],
    'current' => 0,
])
<ol {{ $attributes->merge(['class' => 'hr-stepper']) }} id="{{ $id }}" aria-label="Langkah pengisian">
    @foreach ($steps as $i => $step)
        <li class="hr-stepper-item {{ $i < $current ? 'is-done' : ($i === $current ? 'is-active' : '') }}" data-step="{{ $step['key'] }}"
            @if ($i === $current) aria-current="step" @endif>
            <span class="hr-stepper-penanda" aria-hidden="true">
                <span class="hr-stepper-no">{{ $i + 1 }}</span>
                <i class="bi bi-check-lg"></i>
            </span>
            <span class="hr-stepper-label">
                @if (! empty($step['icon']))<i class="bi {{ $step['icon'] }} d-none d-md-inline" aria-hidden="true"></i>@endif
                {{ $step['label'] }}
            </span>
        </li>
    @endforeach
</ol>
@once
    <script>
        window.hrStepper = function (id, indeks) {
            document.querySelectorAll('#' + id + ' .hr-stepper-item').forEach(function (li, i) {
                li.classList.toggle('is-done', i < indeks);
                li.classList.toggle('is-active', i === indeks);
                i === indeks ? li.setAttribute('aria-current', 'step') : li.removeAttribute('aria-current');
            });
        };
    </script>
@endonce
