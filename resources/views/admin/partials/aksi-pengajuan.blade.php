{{--
    Tombol aksi baris daftar pengajuan (cuti / lembur / izin keluar): ditumpuk sama lebar — Setujui, Tolak, tombol tambahan, Detail.
    Parameter: $item, $detail, $setujui, $tolak (URL), $kelasTolak (kelas tombol Tolak yang dipasangi pop-up alasan),
    $judulSetujui, $teksSetujui (konfirmasi), opsional $pengganti (daftar nama → pilih pengganti tugas dulu, cuti tahap Kepala Bagian),
    opsional $tambahan [[url, label, ikon, kelas]].
--}}
@php $bisaProses = $item->isPending() && \App\Support\PengajuanWorkflow::canAct(auth()->user(), $item); @endphp
{{-- Gaya .aksi-pengajuan ada di public/assets-admin/css/hrrab.css (dipakai juga di daftar formasi). --}}
<div class="aksi-pengajuan">
    @if ($bisaProses)
        @if (isset($pengganti))
            <form action="{{ $setujui }}" method="POST" class="form-approve-kepala">
                @csrf
                <select name="pengganti_nama" class="form-select form-select-sm" required aria-label="Pengganti tugas">
                    <option value="">Pilih pengganti...</option>
                    @foreach ($pengganti as $nama)
                        <option value="{{ $nama }}">{{ $nama }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-success btn-sm" title="Setujui &amp; teruskan ke tahap berikutnya"><i class="bi bi-check-lg"></i> Setujui</button>
            </form>
        @else
            <form action="{{ $setujui }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-success btn-sm"
                    data-confirm="true" data-icon="question" data-title="{{ $judulSetujui }}" data-text="{{ $teksSetujui }}"
                    data-confirm-text="Ya, Setujui" data-confirm-color="#198754" data-cancel-text="Batal">
                    <i class="bi bi-check-lg"></i> Setujui
                </button>
            </form>
        @endif
        <form action="{{ $tolak }}" method="POST">
            @csrf
            <input type="hidden" name="catatan_admin" value="">
            <button type="button" class="btn btn-outline-danger btn-sm {{ $kelasTolak }}"><i class="bi bi-x-lg"></i> Tolak</button>
        </form>
    @endif
    @foreach ($tambahan ?? [] as [$url, $label, $ikon, $kelas])
        <a href="{{ $url }}" class="btn btn-sm {{ $kelas }}"><i class="bi {{ $ikon }}"></i> {{ $label }}</a>
    @endforeach
    <a href="{{ $detail }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-eye"></i> Detail</a>
    {{-- Tahap/keadaan pengajuan sudah tampil di kolom Status; tidak diulang di sini. --}}
</div>
