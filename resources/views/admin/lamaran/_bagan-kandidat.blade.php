{{-- Gambaran posisi kandidat langsung di bagan unit tujuan. Dari KandidatInternalController::bagan() (dimuat lewat fetch). --}}
<p class="small text-muted mb-2">
    Kandidat akan mengisi posisi <strong>{{ $jabatan }}</strong> di bagian <strong>{{ $unit }}</strong> (kotak merah).
    @if ($diganti)
        Lowongan ini menggantikan: {{ collect($diganti)->pluck('nama')->implode(', ') }}.
    @endif
</p>
@include('admin.pengajuan-formasi._bagan-statis', ['bagan' => $bagan, 'unit' => $unit, 'legendaBaru' => 'Posisi yang akan diisi kandidat'])
