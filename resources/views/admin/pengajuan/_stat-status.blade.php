{{--
    Kartu angka per status di atas daftar pengajuan (Batch 5): Total · Menunggu · Disetujui · Ditolak.
    Klik kartu = saring status (saringan lain tetap). Parameter: $hitung (App\Support\HitungStatus::dari), $route (nama rute daftar), $satuan.
--}}
@php
    $param = request()->except(['status', 'page']);
    $kartu = [
        ['', 'Total ' . $satuan, $hitung['semua'], 'bi-collection', 'primary'],
        ['pending', 'Menunggu', $hitung['pending'], 'bi-hourglass-split', 'warning'],
        ['approved', 'Disetujui', $hitung['approved'], 'bi-check2-circle', 'success'],
        ['rejected', 'Ditolak', $hitung['rejected'], 'bi-x-circle', 'danger'],
    ];
@endphp
<section class="row g-3 mb-4" aria-label="Ringkasan status {{ $satuan }}">
    @foreach ($kartu as [$status, $label, $angka, $ikon, $tone])
        <div class="col-6 col-xl-3">
            <x-stat-card :label="$label" :value="$angka" :icon="$ikon" :tone="$tone"
                :href="route($route, $status === '' ? $param : $param + ['status' => $status])"
                @class(['hr-stat-aktif' => (string) request('status') === $status]) />
        </div>
    @endforeach
</section>
