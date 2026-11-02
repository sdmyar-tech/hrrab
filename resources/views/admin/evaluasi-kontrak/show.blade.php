@extends('layouts.admin.app')

@php
    use App\Models\EvaluasiKontrak as EK;
    use Illuminate\Support\HtmlString;
    $p = $e->dataPegawai;
    $k = $p?->kepegawaian;
    $sisa = $e->sisaHari();
    $masa = $k?->tanggal_masuk?->diff(now());
    $skala = config('penilaian_kontrak.skala');
    $bagian = config('penilaian_kontrak.bagian');
    $jumlahButir = count(EK::butir());
    $nilai = old('nilai', $e->nilai ?? []);
    [$periodeMulai, $periodeSelesai] = $periode;
    $labelPemutus = $pemutus->isNotEmpty() && $pemutus->every->isGm() ? 'GM' : 'Kepala SDM';
    $selesai = $e->status === EK::STATUS_SELESAI;
    $namaPenilai = $penilai->pluck('name')->implode(', ');
    $namaPemutus = $pemutus->pluck('name')->implode(', ');
    $teksSisa = $sisa < 0 ? 'lewat ' . abs($sisa) . ' hari' : ($sisa === 0 ? 'hari ini' : $sisa . ' hari lagi');
    // Tombol utama bilah bawah: nilai saat menunggu penilaian, keputusan setelah nilai masuk.
    $utamaNilai = $e->status === EK::STATUS_MENUNGGU_ATASAN || ! $bisaPutuskan;
    $adaBilah = $bisaIsi || $bisaPutuskan;
    $ttdLewat = $e->ttd_jadwal && $e->ttd_jadwal->isPast();
@endphp

{{-- Galat isian ditampilkan di halaman ini (ringkasan + di bawah isian); banner global tidak perlu. --}}
@section('galat-di-halaman', '1')

@section('content')
<main class="dashboard-content">
    <div class="container-fluid">
        <x-page-header :title="$p?->nama_lengkap ?? '-'" eyebrow="Evaluasi Kontrak"
            :subtitle="collect([$k?->jabatan, $e->unit_kerja])->filter()->implode(' · ') ?: null"
            :back="route('admin.evaluasi-kontrak.index')" back-label="Evaluasi Kontrak">
            <x-status-badge :badge="$e->badgeClass()" size="lg" dot>{{ $e->statusLabel() }}</x-status-badge>
            @if ($bisaIsi || $bisaPutuskan || $bisaAturTtd)
                <x-slot:actions>
                    @if ($bisaIsi)
                        <x-button variant="secondary" href="#penilaian" icon="bi-list-check">{{ $e->diisi_pada ? 'Ubah Penilaian' : 'Isi Penilaian' }}</x-button>
                    @endif
                    @if ($satuLangkah || $bisaPutuskan)
                        <x-button href="#keputusan" icon="bi-patch-check">{{ $e->keputusan ? 'Ubah Keputusan' : 'Isi Keputusan' }}</x-button>
                    @elseif ($bisaAturTtd)
                        <x-button href="#keputusan" icon="bi-calendar-check">Atur Jadwal TTD</x-button>
                    @endif
                </x-slot:actions>
            @endif
        </x-page-header>

        @if ($errors->any())
            <x-alert tone="danger" title="Periksa kembali isian yang ditandai merah" class="mb-3">
                Data belum tersimpan. Perbaiki isian berikut, lalu simpan lagi:
                <ul class="mb-0 ps-3">@foreach ($errors->all() as $pesan)<li>{{ $pesan }}</li>@endforeach</ul>
            </x-alert>
        @endif

        {{-- Form keputusan & jadwal TTD berdiri sendiri; isiannya memakai atribut form="…" (tidak bersarang di formIsi). --}}
        @if ($bisaPutuskan && ! $satuLangkah)
            <form action="{{ route('admin.evaluasi-kontrak.putuskan', $e) }}" method="POST" id="formPutuskan" data-confirm="Simpan keputusan? Data kontrak pegawai akan ikut diperbarui.">@csrf</form>
        @endif
        @if ($bisaAturTtd)
            <form action="{{ route('admin.evaluasi-kontrak.jadwal-ttd', $e) }}" method="POST" id="formTtd" data-confirm="Simpan jadwal TTD? Pegawai akan menerima notifikasi.">@csrf</form>
        @endif

        @if ($bisaIsi)
            <form action="{{ route('admin.evaluasi-kontrak.isi', $e) }}" method="POST" id="formIsi"
                data-confirm="{{ $satuLangkah ? 'Simpan penilaian & keputusan? Data kontrak pegawai diperbarui dan pegawai menerima jadwal TTD.' : 'Kirim penilaian ini ke ' . $labelPemutus . '?' }}">
                @csrf
        @endif

        <div class="row g-4">
            {{-- ===== Kolom kiri: data pegawai, lalu penilaian & keputusan ===== --}}
            <div class="col-xl-8 d-grid gap-4 align-content-start">
                <x-card>
                    <section class="hr-detail-bagian" aria-labelledby="judul-identitas">
                        <h2 class="hr-detail-judul" id="judul-identitas"><i class="bi bi-person-vcard" aria-hidden="true"></i>Identitas Pegawai</h2>
                        <x-info-list :items="array_filter([
                            'Tempat, tanggal lahir' => collect([$p?->tempat_lahir, $p?->tanggal_lahir?->translatedFormat('d F Y')])->filter()->implode(', '),
                            'Jenis kelamin' => $p?->jenis_kelamin,
                            'TMT sebagai pegawai' => $k?->tanggal_masuk?->translatedFormat('d F Y'),
                            'Masa kerja' => $masa ? ($masa->y ? $masa->y . ' tahun ' : '') . $masa->m . ' bulan' : null,
                            'Divisi / bagian' => collect([$e->unit_kerja, $k?->jabatan])->filter()->implode(' · '),
                            'Status kerja' => $e->status_kerja,
                            'Periode penilaian' => $bisaIsi ? false : ($periodeMulai?->translatedFormat('d M Y') ?? '-') . ' – ' . $periodeSelesai?->translatedFormat('d M Y'),
                        ], fn ($v) => $v !== false)" />
                    </section>

                    <section class="hr-detail-bagian" aria-labelledby="judul-pendukung">
                        <h2 class="hr-detail-judul" id="judul-pendukung"><i class="bi bi-bar-chart" aria-hidden="true"></i>Data Pendukung</h2>
                        <p class="hr-sel-sub mb-3">Absensi {{ $rentangAbsensi[0]->translatedFormat('d M Y') }} – {{ $rentangAbsensi[1]->translatedFormat('d M Y') }}, cuti tahun {{ now()->year }}.</p>
                        <div class="ek-angka">
                            @if ($absensi && ($absensi['hari_kerja'] ?? 0) > 0)
                                <div class="ek-kartu"><span>Hadir</span><strong>{{ $absensi['hadir_total'] ?? 0 }}/{{ $absensi['hari_kerja'] }} hari</strong></div>
                                <div class="ek-kartu {{ ($absensi['telat'] ?? 0) > 5 ? 'waspada' : '' }}"><span>Telat</span><strong>{{ $absensi['telat'] ?? 0 }} kali</strong><small>{{ $absensi['telat_menit'] ?? 0 }} menit</small></div>
                                <div class="ek-kartu {{ ($absensi['alpa'] ?? 0) > 0 ? 'bahaya' : '' }}"><span>Alpa</span><strong>{{ $absensi['alpa'] ?? 0 }} hari</strong></div>
                                <div class="ek-kartu"><span>Izin / sakit</span><strong>{{ ($absensi['izin'] ?? 0) + ($absensi['sakit'] ?? 0) }} hari</strong></div>
                            @endif
                            <div class="ek-kartu"><span>Cuti {{ now()->year }}</span><strong>{{ $cutiTahunIni }} hari</strong></div>
                            <div class="ek-kartu {{ $spKontrak->isNotEmpty() ? ($spKontrak->max('tingkat') >= 3 ? 'bahaya' : 'waspada') : '' }}"><span>SP selama kontrak</span><strong>{{ $spKontrak->count() }} kali</strong>
                                <small>{{ $spKontrak->isEmpty() ? 'tidak ada' : collect([1, 2, 3])->map(fn ($t) => 'SP' . $t . ': ' . $spKontrak->where('tingkat', $t)->count())->implode(' · ') }}</small></div>
                            <div class="ek-kartu"><span>Evaluasi sebelumnya</span><strong>{{ $riwayat->count() }} kali</strong>
                                @if ($r = $riwayat->first())<small>{{ $r->keputusan ? $r->labelKeputusan() : $r->statusLabel() }}{{ $r->rata_nilai ? ' · ' . $r->rata_nilai . '/4' : '' }}</small>@endif
                            </div>
                        </div>
                        @unless ($absensi && ($absensi['hari_kerja'] ?? 0) > 0)
                            <p class="hr-sel-sub mt-2 mb-0">Belum ada data absensi pada rentang ini (pegawai mungkin tidak ikut absensi mesin).</p>
                        @endunless
                    </section>

                    <section class="hr-detail-bagian" aria-labelledby="judul-sp">
                        <h2 class="hr-detail-judul" id="judul-sp"><i class="bi bi-exclamation-octagon" aria-hidden="true"></i>Surat Peringatan selama Kontrak</h2>
                        <p class="hr-sel-sub mb-2">Periode {{ $periodeMulai?->translatedFormat('d M Y') ?? 'awal' }} – {{ $periodeSelesai?->translatedFormat('d M Y') }}.</p>
                        @forelse ($spKontrak as $sp)
                            <div class="d-flex justify-content-between align-items-start gap-2 py-2 border-top">
                                <div class="min-w-0">
                                    {{-- Warna SP1–SP3 makin tegas (kelas sp-badge di ui.css). --}}
                                    <span class="badge {{ $sp->badgeClass() }} me-1">{{ $sp->label() }}</span>{{ $sp->tanggal->translatedFormat('d M Y') }} · {{ $sp->alasan }}
                                    @if ($sp->keterangan)<span class="hr-sel-sub">{{ $sp->keterangan }}</span>@endif
                                </div>
                                <span class="hr-sel-sub text-nowrap">{{ $sp->pemberi?->name }}</span>
                            </div>
                        @empty
                            <p class="mb-0"><x-badge tone="success" icon="bi-check-circle">Bersih</x-badge> Tidak ada surat peringatan selama periode ini.</p>
                        @endforelse
                    </section>
                </x-card>

                <x-card>
                    {{-- ===== Penilaian 50 butir ===== --}}
                    <x-form-section id="penilaian" class="hr-bagian" title="Penilaian" icon="bi-list-check"
                        :subtitle="$bisaIsi ? 'Beri nilai 1–4 untuk setiap butir; semua butir wajib dinilai.' : ($e->diisi_pada ? 'Dinilai ' . ($e->pengisi?->name ?? '-') . ' · ' . $e->diisi_pada->translatedFormat('d M Y H:i') : 'Belum dinilai.')">
                        @if ($bisaIsi)
                            <div class="col-md-6">
                                <x-input type="date" name="periode_mulai" id="periode-mulai" label="Awal Periode Penilaian" optional
                                    :value="$periodeMulai?->toDateString()" field-class="mb-0" />
                            </div>
                            <div class="col-md-6">
                                <x-input type="date" name="periode_selesai" id="periode-selesai" label="Akhir Periode Penilaian" optional
                                    :value="$periodeSelesai?->toDateString()" help="Bawaan: sejak kontrak sebelumnya berakhir (atau TMT) sampai habis kontrak." field-class="mb-0" />
                            </div>
                        @endif

                        @if ($bisaIsi || $e->diisi_pada)
                            <div class="col-12">
                                <div class="pk-legenda mb-2" aria-label="Arti nilai">
                                    @foreach ($skala as $n => $ket)<span><b>{{ $n }}</b> {{ $ket }}</span>@endforeach
                                </div>
                                @error('nilai')<x-alert tone="danger" class="mb-2">{{ $message }}</x-alert>@enderror

                                @foreach ($bagian as $b)
                                    <div class="pk-bagian" data-bagian="{{ $b['kode'] }}">
                                        <div class="pk-bagian-kepala">
                                            <h3>{{ $b['kode'] }}. {{ $b['judul'] }}</h3>
                                            @if ($bisaIsi)
                                                <div class="pk-isi-semua" role="group" aria-label="Isi semua butir {{ $b['judul'] }}">
                                                    <span>Isi semua:</span>
                                                    @foreach (array_keys($skala) as $n)
                                                        <button type="button" class="btn btn-link btn-sm p-0" data-isi-semua="{{ $b['kode'] }}" data-nilai="{{ $n }}"
                                                            aria-label="Isi semua butir {{ $b['judul'] }} dengan nilai {{ $n }}">{{ $n }}</button>
                                                    @endforeach
                                                </div>
                                            @else
                                                <span class="pk-rata-bagian">rata-rata {{ $e->rataBagian()[$b['kode']] ?? '-' }}</span>
                                            @endif
                                        </div>
                                        @foreach ($b['kelompok'] as $kel)
                                            @if ($kel['judul'])<div class="pk-kelompok">{{ $kel['judul'] }}</div>@endif
                                            @foreach ($kel['butir'] as $no => $teks)
                                                <div class="pk-butir">
                                                    <span class="pk-no">{{ $no }}</span>
                                                    <span class="pk-teks">{{ $teks }}</span>
                                                    @if ($bisaIsi)
                                                        <div class="btn-group btn-group-sm pk-skala" role="group" aria-label="Nilai butir {{ $no }}">
                                                            @foreach (array_reverse(array_keys($skala)) as $n)
                                                                <input type="radio" class="btn-check" name="nilai[{{ $no }}]" id="n{{ $no }}-{{ $n }}" value="{{ $n }}" @checked((int) ($nilai[$no] ?? 0) === $n)>{{-- wajib dicek JS (tandai kuning) & server --}}
                                                                <label class="btn btn-outline-secondary" for="n{{ $no }}-{{ $n }}" title="{{ $skala[$n] }}">{{ $n }}</label>
                                                            @endforeach
                                                        </div>
                                                    @else
                                                        @php $v = $nilai[$no] ?? null; @endphp
                                                        <span class="pk-hasil n{{ $v }}" title="{{ $v ? $skala[$v] : 'Belum dinilai' }}">{{ $v ?? '–' }}</span>
                                                    @endif
                                                </div>
                                            @endforeach
                                        @endforeach
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="col-12">
                                <x-empty-state icon="bi-hourglass-split" title="Belum dinilai" compact>
                                    {{ $selesai ? 'Evaluasi ini diputuskan tanpa penilaian.' : 'Menunggu penilaian oleh ' . ($namaPenilai ?: 'penilai') . '.' }}
                                </x-empty-state>
                            </div>
                        @endif

                        <div class="col-12">
                            @if ($bisaIsi)
                                <x-textarea name="catatan" id="catatan" label="Catatan Penilai" optional rows="3" maxlength="1000" :value="$e->catatan"
                                    placeholder="Ringkasan kinerja, contoh: target tercapai; perlu meningkatkan ketepatan waktu" field-class="mb-0" />
                                <p class="hr-sel-sub mt-2 mb-0">
                                    @unless ($satuLangkah)<i class="bi bi-info-circle me-1" aria-hidden="true"></i>Penilai hanya memberi nilai; keputusan kontrak diambil {{ $labelPemutus }}.@endunless
                                    @if ($e->diisi_pada) Terakhir diisi {{ $e->pengisi?->name }} · {{ $e->diisi_pada->translatedFormat('d M Y H:i') }}.@endif
                                </p>
                            @elseif ($e->diisi_pada && $e->catatan)
                                <x-info-list :items="['Catatan penilai' => new HtmlString(nl2br(e($e->catatan)))]" />
                            @endif
                        </div>
                    </x-form-section>

                    {{-- ===== Keputusan (Kepala SDM; GM untuk Kepala SDM) & jadwal TTD ===== --}}
                    <x-form-section id="keputusan" class="hr-bagian" :title="'Keputusan ' . $labelPemutus" icon="bi-patch-check"
                        :subtitle="$bisaIsi && $bisaPutuskan && ! $satuLangkah ? 'Disimpan terpisah dari nilai lewat tombol Simpan Keputusan.' : null">
                        @if ($e->keputusan)
                            <div class="col-12">
                                <x-info-list :items="array_filter([
                                    'Keputusan' => $e->labelKeputusan(),
                                    'Diputuskan' => collect([$e->pemutus?->name, $e->diputuskan_pada?->translatedFormat('d M Y H:i')])->filter()->implode(' · '),
                                    'Catatan' => $e->catatan_sdm ?: false,
                                    'Jadwal TTD kontrak' => $e->keputusan !== EK::TIDAK ? ($e->labelTtd() ?? 'Belum dijadwalkan') : false,
                                ], fn ($v) => $v !== false)" />
                            </div>
                            @if ($bisaAturTtd)
                                <div class="col-12">
                                    <details class="ek-ttd" @if (! $e->ttd_jadwal) open @endif>
                                        <summary class="fw-semibold"><i class="bi bi-calendar-check me-1" aria-hidden="true"></i>{{ $e->ttd_jadwal ? 'Ubah jadwal TTD' : 'Atur jadwal TTD' }}</summary>
                                        <div class="row g-3 mt-1">
                                            <div class="col-md-6">
                                                <x-input type="datetime-local" name="ttd_jadwal" id="ttd-ubah-jadwal" label="Tanggal & Jam" form="formTtd" required
                                                    :value="$e->ttd_jadwal?->format('Y-m-d\TH:i')" field-class="mb-0" />
                                            </div>
                                            <div class="col-md-6">
                                                <x-input name="ttd_lokasi" id="ttd-ubah-lokasi" label="Lokasi" icon="bi-geo-alt" form="formTtd" required maxlength="150"
                                                    :value="$e->ttd_lokasi ?? EK::TTD_LOKASI_BAWAAN" placeholder="Tempat tanda tangan, contoh: Ruangan SDM" field-class="mb-0" />
                                            </div>
                                            <div class="col-12">
                                                <x-button type="submit" variant="outline" form="formTtd" icon="bi-calendar-check">Simpan Jadwal TTD</x-button>
                                            </div>
                                        </div>
                                    </details>
                                </div>
                            @endif
                            @if ($bisaPutuskan || $satuLangkah)
                                <div class="col-12"><p class="hr-form-section-sub mb-0">{{ $satuLangkah ? 'Ubah keputusan:' : 'Ubah keputusan (Admin):' }}</p></div>
                            @endif
                        @endif

                        @if ($satuLangkah)
                            {{-- Penilai sekaligus pemutus: keputusan diisi bersama nilai (di dalam formIsi). --}}
                            <div class="col-12">
                                <x-alert tone="info" class="mb-0">Anda penilai sekaligus pemutus: nilai &amp; keputusan disimpan sekaligus.</x-alert>
                            </div>
                            <div class="col-12">@include('admin.evaluasi-kontrak._isian-keputusan', ['form' => null])</div>
                        @elseif ($bisaPutuskan)
                            @if ($e->status === EK::STATUS_MENUNGGU_ATASAN)
                                <div class="col-12">
                                    <x-alert tone="warning" class="mb-0">Penilaian belum masuk (Admin tetap bisa memutuskan).</x-alert>
                                </div>
                            @endif
                            <div class="col-12">@include('admin.evaluasi-kontrak._isian-keputusan', ['form' => 'formPutuskan'])</div>
                        @elseif (! $e->keputusan)
                            <div class="col-12">
                                <x-empty-state icon="bi-hourglass-split" title="Belum diputuskan" compact>
                                    {{ $e->status === EK::STATUS_MENUNGGU_ATASAN ? 'Diputuskan ' . $labelPemutus . ' setelah penilaian masuk.' : 'Menunggu keputusan ' . ($namaPemutus ?: $labelPemutus) . '.' }}
                                </x-empty-state>
                            </div>
                        @endif
                    </x-form-section>
                </x-card>

                @if ($adaBilah)
                    <div class="hr-bilah-aksi">
                        <x-button variant="secondary" :href="route('admin.evaluasi-kontrak.index')">Batal</x-button>
                        <div class="d-flex flex-wrap justify-content-end gap-2 ms-auto">
                            @if ($satuLangkah)
                                <x-button type="submit" icon="bi-check-lg">Simpan Nilai &amp; Tetapkan Kontrak</x-button>
                            @else
                                {{-- Tombol utama (primer) selalu paling kanan. --}}
                                @if ($bisaPutuskan && $utamaNilai)
                                    <x-button type="submit" form="formPutuskan" variant="secondary" icon="bi-check-lg">Simpan Keputusan</x-button>
                                @endif
                                @if ($bisaIsi)
                                    <x-button type="submit" :variant="$utamaNilai ? 'primary' : 'secondary'" icon="bi-send">{{ $e->diisi_pada ? 'Simpan Perubahan Nilai' : 'Kirim Nilai ke ' . $labelPemutus }}</x-button>
                                @endif
                                @if ($bisaPutuskan && ! $utamaNilai)
                                    <x-button type="submit" form="formPutuskan" icon="bi-check-lg">Simpan Keputusan</x-button>
                                @endif
                            @endif
                        </div>
                    </div>
                @endif
            </div>

            {{-- ===== Kolom kanan (lengket): ringkasan, hasil nilai langsung, alur ===== --}}
            <div class="col-xl-4">
                <div class="hr-detail-samping ek-samping d-grid gap-4">
                    <x-card title="Ringkasan" icon="bi-clipboard-data">
                        <section class="hr-detail-bagian">
                            <dl class="hr-info-list">
                                <div class="hr-info-item"><dt>Habis kontrak</dt><dd>{{ $e->tanggal_habis_kontrak->translatedFormat('d M Y') }}</dd></div>
                                <div class="hr-info-item"><dt>Sisa waktu</dt>
                                    <dd class="{{ $selesai ? 'is-kosong' : ($sisa <= 7 ? 'text-danger fw-semibold' : '') }}">{{ $selesai ? 'Sudah diputuskan' : ucfirst($teksSisa) }}</dd></div>
                                <div class="hr-info-item"><dt>Status</dt><dd><x-status-badge :badge="$e->badgeClass()" dot>{{ $e->statusLabel() }}</x-status-badge></dd></div>
                                <div class="hr-info-item is-lebar"><dt>Penilai</dt><dd class="{{ $namaPenilai ? '' : 'is-kosong' }}">{{ $namaPenilai ?: 'Belum ada penilai tercatat' }}</dd></div>
                                <div class="hr-info-item is-lebar"><dt>Pemutus ({{ $labelPemutus }})</dt><dd class="{{ $namaPemutus ? '' : 'is-kosong' }}">{{ $namaPemutus ?: '-' }}</dd></div>
                            </dl>
                        </section>

                        <section class="hr-detail-bagian" aria-labelledby="judul-hasil">
                            <h2 class="hr-detail-judul" id="judul-hasil"><i class="bi bi-calculator" aria-hidden="true"></i>Hasil Penilaian</h2>
                            <div class="ek-angka">
                                <div class="ek-kartu"><span>Terisi</span><strong><span data-terisi>{{ count(array_filter($nilai)) }}</span>/{{ $jumlahButir }}</strong></div>
                                <div class="ek-kartu"><span>Total nilai</span><strong data-total>{{ $e->total_nilai ?? array_sum($nilai) }}</strong><small data-predikat>{{ $e->total_nilai !== null ? EK::predikat($e->total_nilai) : 'maks. ' . $jumlahButir * 4 }}</small></div>
                                <div class="ek-kartu"><span>Rata-rata</span><strong data-rata>{{ $e->rata_nilai ?? '-' }}</strong><small>skala 1–4</small></div>
                            </div>
                            <div class="mt-3">
                                <p class="form-label mb-1">Keterangan skor <span class="hr-field-opsional">(total = jumlah seluruh butir)</span></p>
                                @foreach (EK::rentangPredikat() as [$min, $maks, $label])
                                    <div class="pk-skor-baris {{ $e->total_nilai !== null && $e->total_nilai >= $min && $e->total_nilai <= $maks ? 'aktif' : '' }}" data-min="{{ $min }}" data-maks="{{ $maks }}">
                                        <span>{{ $min }} – {{ $maks }}</span><b>{{ $label }}</b>
                                    </div>
                                @endforeach
                            </div>
                        </section>
                    </x-card>

                    <x-card title="Alur Evaluasi" icon="bi-signpost-2">
                        <x-timeline label="Alur evaluasi kontrak">
                            <x-timeline.item state="done" title="Evaluasi dibuat" :meta="$e->created_at?->translatedFormat('d M Y')">
                                Kontrak habis {{ $e->tanggal_habis_kontrak->translatedFormat('d M Y') }}.
                            </x-timeline.item>

                            @if ($e->diisi_pada)
                                <x-timeline.item state="done" title="Penilaian" :meta="$e->diisi_pada->translatedFormat('d M Y')">
                                    Oleh {{ $e->pengisi?->name ?? '-' }}{{ $e->total_nilai !== null ? ' · total ' . $e->total_nilai . ' (' . EK::predikat($e->total_nilai) . ')' : '' }}.
                                </x-timeline.item>
                            @elseif ($selesai)
                                <x-timeline.item state="pending" title="Penilaian">Tidak diisi; langsung diputuskan.</x-timeline.item>
                            @else
                                <x-timeline.item state="active" title="Penilaian">Menunggu {{ $namaPenilai ?: 'penilai' }}.</x-timeline.item>
                            @endif

                            @if ($e->keputusan)
                                <x-timeline.item state="done" :title="'Keputusan ' . $labelPemutus" :meta="$e->diputuskan_pada?->translatedFormat('d M Y')">
                                    {{ $e->labelKeputusan() }}{{ $e->pemutus ? ' · ' . $e->pemutus->name : '' }}.
                                </x-timeline.item>
                            @elseif ($e->status === EK::STATUS_MENUNGGU_SDM)
                                <x-timeline.item state="active" :title="'Keputusan ' . $labelPemutus">Menunggu {{ $namaPemutus ?: $labelPemutus }}.</x-timeline.item>
                            @else
                                <x-timeline.item state="pending" :title="'Keputusan ' . $labelPemutus">Setelah penilaian masuk.</x-timeline.item>
                            @endif

                            @if ($e->keputusan === EK::TIDAK)
                                <x-timeline.item state="stopped" title="Kontrak tidak diperpanjang">Tidak ada penandatanganan kontrak baru.</x-timeline.item>
                            @elseif ($e->keputusan && $e->ttd_jadwal)
                                <x-timeline.item :state="$ttdLewat ? 'done' : 'active'" title="Tanda tangan kontrak" :meta="$e->ttd_jadwal->translatedFormat('d M Y')">
                                    {{ $ttdLewat ? 'Dijadwalkan' : 'Terjadwal' }} {{ $e->labelTtd() }}.
                                </x-timeline.item>
                            @elseif ($e->keputusan)
                                <x-timeline.item state="active" title="Tanda tangan kontrak">Jadwal belum diatur.</x-timeline.item>
                            @else
                                <x-timeline.item state="pending" title="Tanda tangan kontrak">Dijadwalkan bersama keputusan.</x-timeline.item>
                            @endif
                        </x-timeline>
                    </x-card>
                </div>
            </div>
        </div>

        @if ($bisaIsi)
            </form>
        @endif
    </div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const isi = document.getElementById('formIsi');
        if (isi) {
            // Predikat dari total nilai (rentang dari config); baru berlaku setelah semua butir dinilai.
            const RENTANG = @json(EK::rentangPredikat());
            const PREDIKAT = (total) => (RENTANG.find(([min]) => total >= min) || [])[2] || '-';
            const butir = [...isi.querySelectorAll('.pk-butir')];
            // Hasil langsung: terisi, total, rata-rata, predikat & baris keterangan skor yang berlaku.
            const hitung = () => {
                const nilai = butir.map((b) => +(b.querySelector('input:checked') || {}).value || 0).filter(Boolean);
                const rata = nilai.length ? Math.round((nilai.reduce((a, n) => a + n, 0) / nilai.length) * 100) / 100 : null;
                document.querySelector('[data-terisi]').textContent = nilai.length;
                document.querySelector('[data-total]').textContent = nilai.reduce((a, n) => a + n, 0);
                document.querySelector('[data-rata]').textContent = rata ?? '-';
                const total = nilai.reduce((a, n) => a + n, 0);
                const lengkap = nilai.length === butir.length;
                document.querySelector('[data-predikat]').textContent = lengkap ? PREDIKAT(total) : 'lengkapi semua butir';
                document.querySelectorAll('.pk-skor-baris').forEach((b) => b.classList.toggle('aktif', lengkap && total >= +b.dataset.min && total <= +b.dataset.maks));
            };
            isi.addEventListener('change', (ev) => { if (ev.target.name?.startsWith('nilai[')) { ev.target.closest('.pk-butir').classList.remove('belum'); hitung(); } });
            isi.querySelectorAll('[data-isi-semua]').forEach((btn) => btn.addEventListener('click', () => {
                isi.querySelectorAll(`.pk-bagian[data-bagian="${btn.dataset.isiSemua}"] input[value="${btn.dataset.nilai}"]`).forEach((r) => { r.checked = true; r.closest('.pk-butir').classList.remove('belum'); });
                hitung();
            }));
            // Kirim: tandai butir yang belum dinilai dan arahkan ke yang pertama.
            isi.addEventListener('submit', (ev) => {
                const belum = butir.filter((b) => !b.querySelector('input:checked'));
                if (belum.length) {
                    ev.preventDefault(); ev.stopImmediatePropagation();
                    belum.forEach((b) => b.classList.add('belum'));
                    belum[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
                    Swal.fire({ icon: 'info', title: 'Penilaian belum lengkap', text: belum.length + ' butir belum dinilai (ditandai kuning).',
                        confirmButtonText: 'Lengkapi', confirmButtonColor: getComputedStyle(document.documentElement).getPropertyValue('--hr-primary').trim() });
                }
            }, true);
            hitung();
        }
        // Isian keputusan: lama hanya untuk "Perpanjang"; naik gaji & jadwal TTD tidak untuk "Tidak diperpanjang".
        document.querySelectorAll('[data-isian-keputusan]').forEach((kotak) => {
            const pilih = kotak.querySelector('[data-keputusan]');
            const naik = kotak.querySelector('[data-naik]');
            const atur = () => {
                const v = pilih.value;
                kotak.querySelectorAll('[data-jika]').forEach((el) => { el.hidden = el.dataset.jika !== v; });
                kotak.querySelectorAll('[data-jika-bukan]').forEach((el) => { el.hidden = !v || el.dataset.jikaBukan === v; });
                kotak.querySelectorAll('[data-wajib-bukan-tidak]').forEach((el) => { el.required = !!v && v !== 'tidak'; });
                kotak.querySelector('[data-naik-isi]').hidden = !naik.checked;
                kotak.querySelector('[name=keputusan_naik_gaji_nominal]').required = naik.checked && v !== 'tidak';
                kotak.querySelector('[data-catatan-keputusan]').required = v === 'tidak';
            };
            pilih.addEventListener('change', atur);
            naik.addEventListener('change', atur);
            atur();
        });
        // Nominal rupiah: tampil dengan titik ribuan saat diketik (server menyimpan angkanya saja).
        document.querySelectorAll('[data-rupiah]').forEach((el) => el.addEventListener('input', () => {
            const angka = el.value.replace(/\D/g, '');
            el.value = angka ? Number(angka).toLocaleString('id-ID') : '';
        }));
    });
</script>
@endsection
