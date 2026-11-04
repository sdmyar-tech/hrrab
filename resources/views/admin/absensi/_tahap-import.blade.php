{{-- Penanda tahapan import (x-stepper). Param: $tahap (1 = Unggah, 2 = Cek, 3 = Selesai) --}}
<x-stepper id="tahapImport" class="mb-4" :current="$tahap - 1" :steps="[
    ['key' => 'unggah', 'label' => 'Unggah file', 'icon' => 'bi-cloud-arrow-up'],
    ['key' => 'cek', 'label' => 'Cek data', 'icon' => 'bi-search'],
    ['key' => 'selesai', 'label' => 'Selesai', 'icon' => 'bi-check2-circle'],
]" />
