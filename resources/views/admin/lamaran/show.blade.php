@extends('layouts.admin.app')

@section('content')
<main class="dashboard-content">
    <div class="container-fluid px-3 px-lg-4 py-4">
        <div class="page-heading">
            <div class="page-heading-copy">
                <span class="page-icon"><i class="bi bi-person-lines-fill"></i></span>
                <div>
                    <p class="eyebrow mb-1">Rekrutmen</p>
                    <h1 class="h3 mb-1">{{ $lamaran->namaKandidat() }}</h1>
                    <p class="text-muted mb-0">
                        {{ $lamaran->isJalurInternal() ? 'Posisi tujuan' : 'Melamar' }}: {{ $lamaran->judulPosisi() }} ({{ $lamaran->unitKerjaTujuan() }})
                        @if ($lamaran->isJalurInternal())
                            <span class="badge text-bg-light border ms-1"><i class="bi bi-person-check me-1"></i>Jalur Langsung</span>
                        @endif
                    </p>
                </div>
            </div>
            <div class="heading-actions">
                <span class="badge fs-6 {{ $lamaran->badgeClass() }}">{{ $lamaran->statusLabel() }}</span>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-lg-5">
                @if ($lamaran->isJalurInternal())
                    <div class="panel p-4 mb-3">
                        <h2 class="h6 fw-bold mb-3">Jalur Langsung</h2>
                        <dl class="row mb-0 small">
                            <dt class="col-5 text-muted">Kandidat</dt>
                            <dd class="col-7">{{ $lamaran->isKandidatPegawai() ? 'Pegawai yayasan (mutasi/promosi)' : 'Calon pegawai baru' }}</dd>
                            <dt class="col-5 text-muted">Diusulkan oleh</dt>
                            <dd class="col-7">{{ $lamaran->direkomendasikan_oleh ?: '-' }}</dd>
                            <dt class="col-5 text-muted">Posisi Tujuan</dt>
                            <dd class="col-7">
                                {{ $lamaran->jabatanTujuan() }} &middot; {{ $lamaran->unitKerjaTujuan() }}
                                <span class="text-muted d-block">{{ $lamaran->lowongan ? 'Lowongan: ' . $lamaran->lowongan->judul : 'Tanpa lowongan' }}</span>
                            </dd>
                            <dt class="col-5 text-muted">Ditambahkan</dt>
                            <dd class="col-7">{{ $lamaran->diverifikasiOleh?->name ?? '-' }}, {{ optional($lamaran->created_at)->format('d M Y') }}</dd>
                        </dl>
                    </div>
                @endif

                @if ($canReview)
                    <div class="panel p-4 mb-3">
                        <h2 class="h6 fw-bold mb-1">Link Jadwal Kandidat</h2>
                        <p class="text-muted small mb-2">Bagikan ke kandidat lewat WhatsApp/email. Bisa dibuka tanpa login & selalu menampilkan jadwal terbaru.</p>
                        <div class="input-group input-group-sm">
                            <input type="text" class="form-control" id="link-jadwal" value="{{ $lamaran->linkJadwal() }}" readonly>
                            <button type="button" class="btn btn-outline-primary" id="btn-salin-link">
                                <i class="bi bi-clipboard me-1"></i> Salin
                            </button>
                            <a href="{{ $lamaran->linkJadwal() }}" target="_blank" rel="noopener" class="btn btn-outline-secondary" title="Buka">
                                <i class="bi bi-box-arrow-up-right"></i>
                            </a>
                        </div>
                    </div>
                @endif

                @if ($lamaran->isKandidatPegawai())
                    @php $pg = $lamaran->dataPegawai; @endphp
                    <div class="panel p-4">
                        <h2 class="h6 fw-bold mb-3">Data Pegawai</h2>
                        <dl class="row mb-0 small">
                            <dt class="col-5 text-muted">Nama Lengkap</dt><dd class="col-7 fw-semibold">{{ $pg?->nama_lengkap ?? '-' }}</dd>
                            <dt class="col-5 text-muted">NIP</dt><dd class="col-7">{{ $pg?->nip }}</dd>
                            <dt class="col-5 text-muted">Email</dt><dd class="col-7">{{ $pg?->email }}</dd>
                            <dt class="col-5 text-muted">No. HP</dt><dd class="col-7">{{ $pg?->no_hp }}</dd>
                            <dt class="col-5 text-muted">Jabatan Sekarang</dt><dd class="col-7">{{ $pg?->kepegawaian?->jabatan ?? '-' }}</dd>
                            <dt class="col-5 text-muted">Unit Sekarang</dt><dd class="col-7">{{ $pg?->kepegawaian?->unit_kerja ?? '-' }}</dd>
                            <dt class="col-5 text-muted">Status Kerja</dt><dd class="col-7">{{ $pg?->kepegawaian?->status_kerja ?? '-' }}</dd>
                        </dl>
                        @if ($pg)
                            <a href="{{ route('admin.pegawai.show', $pg->id) }}" class="btn btn-outline-secondary btn-sm mt-3">
                                <i class="bi bi-person-vcard me-1"></i> Lihat Data Pegawai
                            </a>
                        @endif
                    </div>
                @else
                <div class="panel p-4">
                    <h2 class="h6 fw-bold mb-3">Data Pelamar</h2>
                    @php $p = $lamaran->dataPelamar; @endphp
                    <div class="d-flex align-items-center gap-3 mb-3">
                        @if ($p?->foto_path)
                            <img src="{{ asset('storage/' . $p->foto_path) }}" alt="Pas Foto {{ $p->nama_lengkap }}"
                                class="rounded border flex-shrink-0" style="width:96px;height:96px;object-fit:cover;">
                        @endif
                        <div>
                            <div class="fw-bold fs-5 lh-sm">{{ $p?->nama_lengkap ?? '-' }}</div>
                            <div class="text-muted small">{{ $lamaran->judulPosisi() }}</div>
                        </div>
                    </div>
                    <dl class="row mb-0 small">
                        <dt class="col-5 text-muted">Nama Lengkap</dt><dd class="col-7 fw-semibold">{{ $p?->nama_lengkap ?? '-' }}</dd>
                        <dt class="col-5 text-muted">Email</dt><dd class="col-7">{{ $p?->email }}</dd>
                        <dt class="col-5 text-muted">NIK</dt><dd class="col-7">{{ $p?->nik ?? '-' }}</dd>
                        <dt class="col-5 text-muted">No. HP</dt><dd class="col-7">{{ $p?->no_hp }}</dd>
                        <dt class="col-5 text-muted">TTL</dt><dd class="col-7">{{ $p?->tempat_lahir }}, {{ optional($p?->tanggal_lahir)->format('d M Y') }}</dd>
                        <dt class="col-5 text-muted">Jenis Kelamin</dt><dd class="col-7">{{ $p?->jenis_kelamin }}</dd>
                        <dt class="col-5 text-muted">Pendidikan</dt><dd class="col-7">{{ $p?->pendidikan_terakhir }}</dd>
                        <dt class="col-5 text-muted">Alamat KTP</dt><dd class="col-7">{{ $p?->alamat_ktp }}</dd>
                        @if ($p?->sosial_media)
                            <dt class="col-5 text-muted">Sosial Media</dt>
                            <dd class="col-7">
                                @foreach ($p->sosial_media as $sm)
                                    <a href="{{ $sm['url'] }}" target="_blank" rel="noopener noreferrer" class="d-block text-truncate">
                                        <i class="bi bi-box-arrow-up-right me-1"></i>{{ $sm['platform'] }}
                                    </a>
                                @endforeach
                            </dd>
                        @endif
                        @if ($p?->cv_path)
                            <dt class="col-5 text-muted">CV</dt>
                            <dd class="col-7"><a href="{{ asset('storage/' . $p->cv_path) }}" target="_blank" rel="noopener">Unduh CV</a></dd>
                        @endif
                        @if ($p?->ijazah_path)
                            <dt class="col-5 text-muted">Ijazah</dt>
                            <dd class="col-7"><a href="{{ asset('storage/' . $p->ijazah_path) }}" target="_blank" rel="noopener">Lihat Ijazah</a></dd>
                        @endif
                        @if ($p?->sertifikat_path)
                            <dt class="col-5 text-muted">Sertifikat</dt>
                            <dd class="col-7"><a href="{{ asset('storage/' . $p->sertifikat_path) }}" target="_blank" rel="noopener">Lihat Sertifikat</a></dd>
                        @endif
                    </dl>

                    <h2 class="h6 fw-bold mt-4 mb-2">Riwayat Pendidikan</h2>
                    @forelse ($p?->riwayatPendidikan ?? [] as $pend)
                        <div class="border rounded p-2 mb-2 small">
                            <strong>{{ $pend->jenjang }}</strong> &mdash; {{ $pend->nama_institusi }}
                            @if ($pend->jurusan) <span class="text-muted">({{ $pend->jurusan }})</span> @endif
                            <br>
                            <span class="text-muted">{{ $pend->periodeLabel() }}@if ($pend->nilai) &middot; Nilai/IPK {{ $pend->nilai }}@endif</span>
                        </div>
                    @empty
                        <p class="text-muted small">Belum ada riwayat pendidikan.</p>
                    @endforelse

                    <h2 class="h6 fw-bold mt-4 mb-2">Riwayat Pekerjaan</h2>
                    @forelse ($p?->riwayatPekerjaan ?? [] as $riwayat)
                        <div class="border rounded p-2 mb-2 small">
                            <strong>{{ $riwayat->posisi }}</strong> &mdash; {{ $riwayat->nama_perusahaan }}<br>
                            <span class="text-muted">
                                {{ optional($riwayat->tanggal_mulai)->format('M Y') }} -
                                {{ $riwayat->tanggal_selesai ? $riwayat->tanggal_selesai->format('M Y') : 'Sekarang' }}
                            </span>
                            @if ($riwayat->deskripsi)
                                <p class="mb-0 mt-1">{{ $riwayat->deskripsi }}</p>
                            @endif
                        </div>
                    @empty
                        <p class="text-muted small">Belum ada riwayat pekerjaan.</p>
                    @endforelse
                </div>
                @endif
            </div>

            <div class="col-lg-7">
                @if (! $canReview)
                    <div class="alert alert-secondary small">
                        Anda hanya bisa melihat data lamaran ini, kecuali mengisi hasil interview yang ditugaskan kepada Anda.
                        Aksi verifikasi/jadwal/kontrak/keputusan akhir hanya bisa dilakukan oleh Kepala SDM/GM.
                    </div>
                @endif

                @if ($lamaran->isMenungguVerifikasi())
                    <div class="panel p-4 mb-3">
                        <h2 class="h6 fw-bold mb-3">Verifikasi Data Pelamar</h2>
                        @if ($canReview)
                            <div class="d-flex gap-2">
                                <form action="{{ route('admin.lamaran.verifikasi.terima', $lamaran) }}" method="POST"
                                    data-confirm="Terima pelamar ini dan lanjutkan ke tahap interview?">
                                    @csrf
                                    <button type="submit" class="btn btn-success">
                                        <i class="bi bi-check-lg me-1"></i> Terima Verifikasi
                                    </button>
                                </form>
                                <form action="{{ route('admin.lamaran.verifikasi.tolak', $lamaran) }}" method="POST" class="form-reject-lamaran">
                                    @csrf
                                    <input type="hidden" name="catatan_admin" value="">
                                    <button type="button" class="btn btn-outline-danger btn-reject-lamaran">
                                        <i class="bi bi-x-lg me-1"></i> Tolak
                                    </button>
                                </form>
                            </div>
                        @else
                            <p class="text-muted small mb-0">Menunggu verifikasi Admin/SDM.</p>
                        @endif
                    </div>
                @endif

                @if ($lamaran->isProses() || $lamaran->interviewJadwals->isNotEmpty())
                    <div class="panel p-4 mb-3">
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
                            <h2 class="h6 fw-bold mb-0">Interview</h2>
                            <span class="badge {{ $jumlahInterviewLulus >= \App\Support\InterviewFlow::MIN_LULUS ? 'text-bg-success' : 'text-bg-light border' }}">
                                {{ $jumlahInterviewLulus }} lulus &middot; min. {{ \App\Support\InterviewFlow::MIN_LULUS }}
                            </span>
                        </div>
                        <p class="text-muted small mb-3">
                            Interview 1 oleh Kepala SDM &rarr; Interview 2 oleh Kepala Bagian {{ $lamaran->unitKerjaTujuan() }} (otomatis setelah Interview 1 lulus)
                            &rarr; interview lanjutan bila perlu (bebas) &rarr; <strong>Konfirmasi Interview Selesai</strong> &rarr; onboarding.
                        </p>

                        @forelse ($lamaran->interviewJadwals as $jadwal)
                            <div class="border rounded p-2 mb-2 small {{ $jadwal->isTerjadwal() ? 'border-primary' : '' }}">
                                <strong>{{ $jadwal->tahapLabel() }}</strong>
                                &mdash; {{ $jadwal->tanggal->format('d M Y') }}, {{ substr($jadwal->waktu_mulai, 0, 5) }}
                                @if ($jadwal->lokasi) &middot; @include('partials.lokasi', ['lokasi' => $jadwal->lokasi, 'tanpaIkon' => true]) @endif
                                <span class="badge text-bg-light border">{{ $jadwal->statusLabel() }}</span>
                                <span class="badge {{ $jadwal->hasilBadgeClass() }}">
                                    @if ($jadwal->hasil_status)<i class="bi bi-lock-fill me-1" title="Hasil sudah final, tidak bisa diubah"></i>@endif{{ $jadwal->hasilLabel() }}
                                </span>
                                <br>
                                <span class="text-muted">Interviewer: {{ $jadwal->interviewer?->name ?? '-' }}
                                    @if ($jadwal->interviewer) ({{ $jadwal->interviewer->roleLabel() }}) @endif
                                </span>
                                @if ($jadwal->catatan)
                                    <br><span class="text-muted">Catatan jadwal: {{ $jadwal->catatan }}</span>
                                @endif
                                @if ($jadwal->catatan_hasil)
                                    <br><span class="text-muted">Catatan hasil: {{ $jadwal->catatan_hasil }}</span>
                                @endif
                                @if (auth()->user()->isAdmin())
                                    <div class="mt-2">@include('admin.editor-data._tombol', ['entitas' => 'interview', 'id' => $jadwal->id, 'teks' => 'Ubah apa saja (Admin)'])</div>
                                @endif

                                @if ($jadwal->isTerjadwal() && $lamaran->isProses())
                                    <div class="mt-2 d-flex flex-wrap gap-2 align-items-start">
                                        @if (\App\Support\InterviewFlow::bisaIsiHasil(auth()->user(), $jadwal))
                                            <form action="{{ route('admin.lamaran.interview.hasil', [$lamaran, $jadwal]) }}" method="POST" class="d-flex flex-wrap gap-1 align-items-center"
                                                data-confirm="Simpan hasil {{ $jadwal->tahapLabel() }}? Hasil final tidak bisa diubah, dan Tidak Lulus langsung menolak lamaran.">
                                                @csrf
                                                <select name="hasil_status" class="form-select form-select-sm" style="width:auto;" required>
                                                    <option value="">Isi Hasil...</option>
                                                    <option value="lulus">Lulus</option>
                                                    <option value="dipertimbangkan">Dipertimbangkan</option>
                                                    <option value="tidak_lulus">Tidak Lulus</option>
                                                </select>
                                                <input type="text" name="catatan_hasil" class="form-control form-control-sm" placeholder="Catatan (opsional)" style="width:180px;">
                                                <button type="submit" class="btn btn-outline-success btn-sm">Simpan Hasil</button>
                                            </form>
                                        @endif

                                        @if (\App\Support\InterviewFlow::bisaUbahJadwal(auth()->user(), $jadwal))
                                            <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="collapse"
                                                data-bs-target="#ubah-jadwal-{{ $jadwal->id }}" aria-expanded="false">
                                                <i class="bi bi-pencil me-1"></i> Ubah Jadwal
                                            </button>
                                        @endif

                                        @if ($canReview)
                                            <form action="{{ route('admin.lamaran.interview.cancel', [$lamaran, $jadwal]) }}" method="POST" class="form-cancel-interview">
                                                @csrf
                                                <input type="hidden" name="catatan" value="">
                                                <button type="button" class="btn btn-outline-danger btn-sm btn-cancel-interview">Batalkan</button>
                                            </form>
                                        @endif
                                    </div>

                                    @if (\App\Support\InterviewFlow::bisaUbahJadwal(auth()->user(), $jadwal))
                                        <div class="collapse mt-2" id="ubah-jadwal-{{ $jadwal->id }}">
                                            <form action="{{ route('admin.lamaran.interview.update', [$lamaran, $jadwal]) }}" method="POST"
                                                class="row g-2 bg-light rounded p-2" data-confirm="Simpan perubahan jadwal {{ $jadwal->tahapLabel() }}?">
                                                @csrf
                                                @method('PUT')
                                                <div class="col-md-4">
                                                    <label class="form-label small mb-1">Tanggal</label>
                                                    <input type="date" name="tanggal" class="form-control form-control-sm" value="{{ $jadwal->tanggal->format('Y-m-d') }}" required>
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label small mb-1">Jam Mulai</label>
                                                    <input type="time" name="waktu_mulai" class="form-control form-control-sm" value="{{ substr($jadwal->waktu_mulai, 0, 5) }}" required>
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label small mb-1">Jam Selesai</label>
                                                    <input type="time" name="waktu_selesai" class="form-control form-control-sm" value="{{ $jadwal->waktu_selesai ? substr($jadwal->waktu_selesai, 0, 5) : '' }}">
                                                </div>
                                                <div class="col-md-6">
                                                    <input type="text" name="lokasi" class="form-control form-control-sm" placeholder="Nama tempat, link Google Maps, atau link meeting" value="{{ $jadwal->lokasi }}">
                                                </div>
                                                <div class="col-md-6">
                                                    <input type="text" name="catatan" class="form-control form-control-sm" placeholder="Catatan jadwal (opsional)" value="{{ $jadwal->catatan }}">
                                                </div>
                                                <div class="col-12 d-flex justify-content-between align-items-center">
                                                    <span class="text-muted" style="font-size:.75rem;">Interviewer tidak bisa diganti setelah dijadwalkan.</span>
                                                    <button type="submit" class="btn btn-primary btn-sm">Simpan Jadwal</button>
                                                </div>
                                            </form>
                                        </div>
                                    @endif
                                    <div class="text-muted mt-1" style="font-size:.75rem;">
                                        @if ($jadwal->tahap_interview === \App\Models\InterviewJadwal::TAHAP_KEPALA_SDM)
                                            Lulus: Interview 2 dengan Kepala Bagian otomatis dibuat.
                                        @else
                                            Lulus: bisa tambah interview lanjutan atau konfirmasi interview selesai.
                                        @endif
                                        <span class="text-danger">Tidak Lulus: lamaran langsung ditolak.</span>
                                    </div>
                                @endif
                            </div>
                        @empty
                            <p class="text-muted small">Belum ada jadwal interview.</p>
                        @endforelse

                        @if ($canReview && $tahapBerikutnya && $interviewerBerikutnya)
                            <form action="{{ route('admin.lamaran.interview.store', $lamaran) }}" method="POST" class="row g-2 mt-2"
                                data-confirm="Jadwalkan interview ini?">
                                @csrf
                                <div class="col-md-4">
                                    <label class="form-label small mb-1">Interviewer</label>
                                    <div class="form-control form-control-sm bg-light">
                                        {{ $interviewerBerikutnya->name }}
                                        <span class="text-muted">({{ $tahapBerikutnya === \App\Models\InterviewJadwal::TAHAP_KEPALA_BAGIAN ? 'Interview 2' : 'Interview 1' }})</span>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small mb-1">Tanggal</label>
                                    <input type="date" name="tanggal" class="form-control form-control-sm" value="{{ \App\Support\Kalender::hariKerjaTerdekat(now())->format('Y-m-d') }}" required>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small mb-1">Jam Mulai</label>
                                    <input type="time" name="waktu_mulai" class="form-control form-control-sm" required>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small mb-1">Jam Selesai</label>
                                    <input type="time" name="waktu_selesai" class="form-control form-control-sm">
                                </div>
                                <div class="col-md-8">
                                    <input type="text" name="lokasi" class="form-control form-control-sm" placeholder="Nama tempat, link Google Maps, atau link meeting">
                                </div>
                                <div class="col-md-4 d-grid">
                                    <button type="submit" class="btn btn-primary btn-sm">
                                        Jadwalkan {{ $tahapBerikutnya === \App\Models\InterviewJadwal::TAHAP_KEPALA_BAGIAN ? 'Interview 2' : 'Interview 1' }}
                                    </button>
                                </div>
                            </form>
                        @elseif ($canReview && $tahapBerikutnya && ! $interviewerBerikutnya)
                            <div class="alert alert-warning small mt-2 mb-0">
                                Belum ada akun <strong>Kepala SDM</strong> untuk menjadi interviewer Interview 1. Tambahkan lewat Kelola User.
                            </div>
                        @endif

                        @if ($canReview && ($bisaTambahLanjutan || $bisaKonfirmasiInterview))
                            <div class="d-flex flex-wrap gap-2 mt-3 pt-3 border-top">
                                @if ($bisaTambahLanjutan)
                                    <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalInterviewLanjutan">
                                        <i class="bi bi-plus-lg me-1"></i> Tambah Interview Lanjutan
                                    </button>
                                @endif
                                @if ($bisaKonfirmasiInterview)
                                    <form action="{{ route('admin.lamaran.interview.selesai', $lamaran) }}" method="POST" class="ms-auto"
                                        data-confirm="Konfirmasi interview selesai ({{ $jumlahInterviewLulus }} interview lulus) dan lanjut ke onboarding? Interview lanjutan tidak bisa ditambah lagi.">
                                        @csrf
                                        <button type="submit" class="btn btn-success btn-sm">
                                            <i class="bi bi-check2-all me-1"></i> Konfirmasi Interview Selesai
                                        </button>
                                    </form>
                                @elseif ($lamaran->tahap === \App\Models\Lamaran::TAHAP_INTERVIEW)
                                    <span class="text-muted small align-self-center ms-auto">Konfirmasi selesai tersedia setelah minimal {{ \App\Support\InterviewFlow::MIN_LULUS }} interview lulus.</span>
                                @endif
                            </div>
                        @endif

                        @if ($canReview && $bisaTambahLanjutan)
                            <div class="modal fade" id="modalInterviewLanjutan" tabindex="-1" aria-labelledby="judulInterviewLanjutan" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <form action="{{ route('admin.lamaran.interview.store', $lamaran) }}" method="POST" class="modal-content"
                                        data-confirm="Jadwalkan interview lanjutan ini?">
                                        @csrf
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="judulInterviewLanjutan">Interview Lanjutan</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="row g-2">
                                                <div class="col-12">
                                                    <label class="form-label small mb-1">Interviewer</label>
                                                    <select name="interviewer_id" class="form-select form-select-sm" required>
                                                        <option value="">-- Pilih interviewer --</option>
                                                        @foreach ($opsiInterviewerLanjutan as $opt)
                                                            <option value="{{ $opt->id }}">{{ $opt->name }} ({{ $opt->roleLabel() }})</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label small mb-1">Tanggal</label>
                                                    <input type="date" name="tanggal" class="form-control form-control-sm" value="{{ \App\Support\Kalender::hariKerjaTerdekat(now())->format('Y-m-d') }}" @unless (auth()->user()->isAdmin()) min="{{ now()->format('Y-m-d') }}" @endunless required>
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label small mb-1">Jam Mulai</label>
                                                    <input type="time" name="waktu_mulai" class="form-control form-control-sm" required>
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label small mb-1">Jam Selesai</label>
                                                    <input type="time" name="waktu_selesai" class="form-control form-control-sm">
                                                </div>
                                                <div class="col-12">
                                                    <input type="text" name="lokasi" class="form-control form-control-sm" placeholder="Nama tempat, link Google Maps, atau link meeting">
                                                </div>
                                                <div class="col-12">
                                                    <input type="text" name="catatan" class="form-control form-control-sm" placeholder="Tujuan interview ini (opsional), mis. tes praktik">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                                            <button type="submit" class="btn btn-primary btn-sm">Jadwalkan</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        @endif
                    </div>
                @endif

                @if ($lamaran->isProses())
                    <div class="panel p-4 mb-3">
                        @php $bisaMulaiOnboarding = $canReview && $lamaran->tahap === \App\Models\Lamaran::TAHAP_SCREENING && $lamaran->screeningOnboardings->where('status', \App\Models\ScreeningOnboarding::STATUS_BERJALAN)->isEmpty(); @endphp
                        <div class="d-flex justify-content-between align-items-center gap-2 mb-1">
                            <h2 class="h6 fw-bold mb-0">Screening / Onboarding</h2>
                            @if ($bisaMulaiOnboarding)
                                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalMulaiOnboarding">
                                    <i class="bi bi-play-fill me-1"></i> Mulai Onboarding
                                </button>
                            @endif
                        </div>
                        <p class="text-muted small mb-3">
                            Hasil dikonfirmasi <strong>Kepala SDM</strong>@if ($kepalaTerkait) &amp; <strong>{{ $kepalaTerkait->name }}</strong> (Kepala Bagian {{ $lamaran->unitKerjaTujuan() }})@endif.
                            Setelah lulus, jadwal kontrak otomatis dibuat sehari setelah hari terakhir onboarding; tanggal masuk = hari pertama onboarding.
                        </p>

                        @if ($lamaran->tahap === \App\Models\Lamaran::TAHAP_INTERVIEW)
                            <p class="text-muted small mb-0"><i class="bi bi-hourglass-split me-1"></i>Tersedia setelah interview dikonfirmasi selesai.</p>
                        @endif

                        @foreach ($lamaran->screeningOnboardings as $screening)
                            @php
                                $screening->setRelation('lamaran', $lamaran);
                                $sisiKonfirmasi = [
                                    \App\Support\OnboardingFlow::SISI_SDM => ['label' => 'Kepala SDM', 'hasil' => $screening->hasil_sdm, 'oleh' => $screening->konfirmasiSdmOleh, 'pada' => $screening->konfirmasi_sdm_pada, 'catatan' => $screening->catatan_sdm],
                                ];
                                if ($screening->kepala_id) {
                                    $sisiKonfirmasi[\App\Support\OnboardingFlow::SISI_KEPALA] = ['label' => 'Kepala Bagian (' . ($screening->kepala?->name ?? '-') . ')', 'hasil' => $screening->hasil_kepala, 'oleh' => $screening->konfirmasiKepalaOleh, 'pada' => $screening->konfirmasi_kepala_pada, 'catatan' => $screening->catatan_kepala];
                                }
                            @endphp
                            <div class="border rounded p-2 mb-2 small {{ $screening->status === \App\Models\ScreeningOnboarding::STATUS_BERJALAN ? 'border-primary' : '' }}">
                                <div class="d-flex flex-wrap justify-content-between gap-1">
                                    <strong>{{ $screening->tanggal_mulai->translatedFormat('d M Y') }} &ndash; {{ $screening->tanggal_selesai->translatedFormat('d M Y') }} ({{ $screening->durasi_hari }} hari)</strong>
                                    <span>
                                        <span class="badge text-bg-light border">{{ $screening->statusLabel() }}</span>
                                        <span class="badge {{ $screening->hasilBadgeClass() }}">{{ $screening->hasilLabel() }}</span>
                                    </span>
                                </div>
                                <span class="text-muted">Pengawas: {{ $screening->pengawas?->name ?? '-' }}@if ($screening->lokasi) &middot; @include('partials.lokasi', ['lokasi' => $screening->lokasi, 'tanpaIkon' => true])@endif</span>
                                @if ($screening->catatan)
                                    <br><span class="text-muted">Arahan: {{ $screening->catatan }}</span>
                                @endif

                                <div class="row g-2 mt-1">
                                    @foreach ($sisiKonfirmasi as $sisi => $k)
                                        <div class="col-md-6">
                                            <div class="bg-light rounded p-2 h-100">
                                                <div class="d-flex justify-content-between">
                                                    <span class="fw-semibold">{{ $k['label'] }}</span>
                                                    <span class="badge {{ $k['hasil'] === 'lulus' ? 'text-bg-success' : ($k['hasil'] === 'tidak_lulus' ? 'text-bg-danger' : 'text-bg-light border') }}">
                                                        {{ \App\Models\ScreeningOnboarding::labelHasil($k['hasil']) }}
                                                    </span>
                                                </div>
                                                @if ($k['oleh'])
                                                    <span class="text-muted d-block">{{ $k['oleh']->name }}, {{ $k['pada']?->translatedFormat('d M Y H:i') }}</span>
                                                @endif
                                                @if ($k['catatan'])
                                                    <span class="text-muted d-block">{{ $k['catatan'] }}</span>
                                                @endif
                                                @if (\App\Support\OnboardingFlow::bisaKonfirmasi(auth()->user(), $screening, $sisi))
                                                    <button type="button" class="btn btn-success btn-sm mt-2" data-bs-toggle="modal" data-bs-target="#modalKonfirmasi-{{ $screening->id }}-{{ $sisi }}">
                                                        <i class="bi bi-clipboard-check me-1"></i> Proses Konfirmasi
                                                    </button>
                                                @endif
                                            </div>
                                        </div>

                                        @if (\App\Support\OnboardingFlow::bisaKonfirmasi(auth()->user(), $screening, $sisi))
                                            <div class="modal fade" id="modalKonfirmasi-{{ $screening->id }}-{{ $sisi }}" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog modal-dialog-centered">
                                                    <form action="{{ route('admin.lamaran.screening.hasil', [$lamaran, $screening]) }}" method="POST" class="modal-content"
                                                        data-confirm="Simpan konfirmasi onboarding? Hasil tidak bisa diubah.">
                                                        @csrf
                                                        <input type="hidden" name="sisi" value="{{ $sisi }}">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Konfirmasi Onboarding &mdash; {{ $k['label'] }}</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <p class="small text-muted">{{ $lamaran->namaKandidat() }} &middot; {{ $screening->tanggal_mulai->translatedFormat('d M') }} &ndash; {{ $screening->tanggal_selesai->translatedFormat('d M Y') }}</p>
                                                            @php $lulusTerkunci = ! \App\Support\OnboardingFlow::bolehKonfirmasiLulus($screening) && ! auth()->user()->isAdmin(); @endphp
                                                            @if ($lulusTerkunci)
                                                                <div class="alert alert-warning small py-2">
                                                                    <i class="bi bi-hourglass-split me-1"></i>Onboarding masih berjalan. <strong>Lulus</strong> bisa dikonfirmasi mulai
                                                                    {{ $screening->tanggal_selesai->translatedFormat('l, d M Y') }}. <strong>Tidak Lulus</strong> bisa kapan saja.
                                                                </div>
                                                            @endif
                                                            <label class="form-label small mb-1">Hasil</label>
                                                            <div class="d-flex gap-3 mb-3">
                                                                <div class="form-check">
                                                                    <input class="form-check-input" type="radio" name="hasil_status" value="lulus" id="hasil-{{ $screening->id }}-{{ $sisi }}-l" required @disabled($lulusTerkunci)>
                                                                    <label class="form-check-label" for="hasil-{{ $screening->id }}-{{ $sisi }}-l">Lulus</label>
                                                                </div>
                                                                <div class="form-check">
                                                                    <input class="form-check-input" type="radio" name="hasil_status" value="tidak_lulus" id="hasil-{{ $screening->id }}-{{ $sisi }}-t">
                                                                    <label class="form-check-label" for="hasil-{{ $screening->id }}-{{ $sisi }}-t">Tidak Lulus</label>
                                                                </div>
                                                            </div>
                                                            <label class="form-label small mb-1">Catatan penilaian <span class="text-muted">(opsional)</span></label>
                                                            <textarea name="catatan_hasil" rows="3" class="form-control form-control-sm" maxlength="1000"></textarea>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                                                            <button type="submit" class="btn btn-success btn-sm">Simpan Konfirmasi</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        @endforeach

                        @if ($bisaMulaiOnboarding)
                            <div class="modal fade" id="modalMulaiOnboarding" tabindex="-1" aria-labelledby="judulMulaiOnboarding" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <form action="{{ route('admin.lamaran.screening.store', $lamaran) }}" method="POST" class="modal-content"
                                        data-confirm="Mulai masa onboarding untuk kandidat ini?">
                                        @csrf
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="judulMulaiOnboarding">Mulai Screening / Onboarding</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="row g-2">
                                                <div class="col-12">
                                                    <label class="form-label small mb-1">Pengawas Harian</label>
                                                    <select name="pengawas_id" class="form-select form-select-sm" required>
                                                        <option value="">-- Pilih --</option>
                                                        @foreach ($pengawasOptions as $opt)
                                                            <option value="{{ $opt->id }}">{{ $opt->name }} ({{ $opt->roleLabel() }})</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="col-md-7">
                                                    <label class="form-label small mb-1">Hari Pertama</label>
                                                    <input type="date" name="tanggal_mulai" id="onboarding-mulai" class="form-control form-control-sm"
                                                        value="{{ \App\Support\Kalender::hariKerjaTerdekat(now())->format('Y-m-d') }}" @unless (auth()->user()->isAdmin()) min="{{ now()->format('Y-m-d') }}" @endunless required>
                                                </div>
                                                <div class="col-md-5">
                                                    <label class="form-label small mb-1">Durasi (hari)</label>
                                                    <input type="number" name="durasi_hari" id="onboarding-durasi" class="form-control form-control-sm"
                                                        value="{{ \App\Models\ScreeningOnboarding::DURASI_HARI }}" min="{{ \App\Support\OnboardingFlow::DURASI_MIN }}" max="{{ \App\Support\OnboardingFlow::DURASI_MAX }}" required>
                                                </div>
                                                <div class="col-12">
                                                    <div class="alert alert-info small py-2 mb-0" id="onboarding-ringkasan"></div>
                                                </div>
                                                <div class="col-12">
                                                    <input type="text" name="lokasi" class="form-control form-control-sm" placeholder="Lokasi onboarding / link Google Maps (opsional)" maxlength="255">
                                                </div>
                                                <div class="col-12">
                                                    <textarea name="catatan" rows="2" class="form-control form-control-sm" placeholder="Arahan selama onboarding (opsional)" maxlength="1000"></textarea>
                                                </div>
                                                <div class="col-12 text-muted" style="font-size:.75rem;">
                                                    Konfirmasi hasil: Kepala SDM{{ $kepalaTerkait ? ' & ' . $kepalaTerkait->name : '' }}.
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                                            <button type="submit" class="btn btn-primary btn-sm">Mulai Onboarding</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="panel p-4 mb-3">
                        <h2 class="h6 fw-bold mb-1">Jadwal Tanda Tangan Kontrak</h2>
                        @if ($tanggalKontrakMinimal)
                            <p class="text-muted small mb-3">Paling cepat {{ $tanggalKontrakMinimal->translatedFormat('d M Y') }} (boleh di hari terakhir onboarding).</p>
                        @endif

                        @forelse ($lamaran->kontrakJadwals as $jadwal)
                            <div class="border rounded p-2 mb-2 small">
                                <div class="d-flex justify-content-between align-items-center gap-2">
                                    <span>
                                        <strong>{{ $jadwal->tanggal->translatedFormat('d M Y') }}</strong>
                                        @if ($jadwal->waktu), {{ substr($jadwal->waktu, 0, 5) }} @endif
                                        @if ($jadwal->lokasi) &middot; @include('partials.lokasi', ['lokasi' => $jadwal->lokasi, 'tanpaIkon' => true]) @endif
                                        <span class="badge text-bg-light border">{{ $jadwal->statusLabel() }}</span>
                                    </span>
                                    @if ($canReview && $jadwal->status === \App\Models\KontrakJadwal::STATUS_TERJADWAL && $tanggalKontrakMinimal)
                                        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="collapse" data-bs-target="#ubah-kontrak-{{ $jadwal->id }}">
                                            <i class="bi bi-pencil me-1"></i> Ubah
                                        </button>
                                    @endif
                                </div>
                                @if ($jadwal->catatan)
                                    <span class="text-muted">{{ $jadwal->catatan }}</span>
                                @endif

                                @if ($canReview && $jadwal->status === \App\Models\KontrakJadwal::STATUS_TERJADWAL && $tanggalKontrakMinimal)
                                    <div class="collapse mt-2" id="ubah-kontrak-{{ $jadwal->id }}">
                                        <form action="{{ route('admin.lamaran.kontrak.update', [$lamaran, $jadwal]) }}" method="POST" class="row g-2 bg-light rounded p-2"
                                            data-confirm="Simpan perubahan jadwal kontrak?">
                                            @csrf
                                            @method('PUT')
                                            <div class="col-md-5">
                                                <label class="form-label small mb-1">Tanggal</label>
                                                <input type="date" name="tanggal" class="form-control form-control-sm" value="{{ $jadwal->tanggal->format('Y-m-d') }}"
                                                    @unless (auth()->user()->isAdmin()) min="{{ $tanggalKontrakMinimal->format('Y-m-d') }}" @endunless required>
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label small mb-1">Jam</label>
                                                <input type="time" name="waktu" class="form-control form-control-sm" value="{{ $jadwal->waktu ? substr($jadwal->waktu, 0, 5) : '' }}">
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label small mb-1">Lokasi</label>
                                                <input type="text" name="lokasi" class="form-control form-control-sm" value="{{ $jadwal->lokasi }}">
                                            </div>
                                            <div class="col-12 d-flex justify-content-end">
                                                <button type="submit" class="btn btn-primary btn-sm">Simpan Jadwal</button>
                                            </div>
                                        </form>
                                    </div>
                                @endif
                            </div>
                        @empty
                            <p class="text-muted small mb-0">
                                @if ($onboardingLulus)
                                    Belum ada jadwal kontrak.
                                @else
                                    Jadwal kontrak dibuat otomatis setelah onboarding dinyatakan <strong>Lulus</strong>.
                                @endif
                            </p>
                        @endforelse

                        @if ($canReview && $onboardingLulus && $lamaran->tahap === \App\Models\Lamaran::TAHAP_KONTRAK
                            && $lamaran->kontrakJadwals->where('status', \App\Models\KontrakJadwal::STATUS_TERJADWAL)->isEmpty())
                            <form action="{{ route('admin.lamaran.kontrak.store', $lamaran) }}" method="POST" class="row g-2 mt-2" data-confirm="Jadwalkan tanda tangan kontrak ini?">
                                @csrf
                                <div class="col-md-4">
                                    <label class="form-label small mb-1">Tanggal</label>
                                    <input type="date" name="tanggal" class="form-control form-control-sm" value="{{ $tanggalKontrakMinimal->format('Y-m-d') }}"
                                        @unless (auth()->user()->isAdmin()) min="{{ $tanggalKontrakMinimal->format('Y-m-d') }}" @endunless required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small mb-1">Jam</label>
                                    <input type="time" name="waktu" class="form-control form-control-sm">
                                </div>
                                <div class="col-md-5 d-grid">
                                    <label class="form-label small mb-1">&nbsp;</label>
                                    <button type="submit" class="btn btn-primary btn-sm">Jadwalkan Kontrak</button>
                                </div>
                                <div class="col-12">
                                    <input type="text" name="lokasi" class="form-control form-control-sm" placeholder="Nama tempat atau link Google Maps">
                                </div>
                            </form>
                        @endif
                    </div>

                    @if ($canReview)
                        <div class="panel p-4">
                            <h2 class="h6 fw-bold mb-3">Keputusan Akhir</h2>

                            <form action="{{ route('admin.lamaran.rekrut', $lamaran) }}" method="POST" class="row g-2 mb-3" id="form-rekrut"
                                data-confirm="{{ $lamaran->isKandidatPegawai() ? 'Tetapkan pegawai ini ke posisi baru?' : 'Rekrut pelamar ini menjadi pegawai?' }}">
                                @csrf
                                @php $adminBebas = auth()->user()->isAdmin() && ! $onboardingLulus; @endphp
                                @if ($adminBebas)
                                    <div class="col-12">
                                        <div class="alert alert-warning small py-2 mb-1">
                                            <i class="bi bi-shield-lock me-1"></i><strong>Mode Admin:</strong> rekrut langsung tanpa menunggu interview/onboarding. Isi tanggal masuk manual.
                                        </div>
                                    </div>
                                @elseif (! $onboardingLulus)
                                    <div class="col-12">
                                        <div class="alert alert-warning small py-2 mb-1">
                                            <i class="bi bi-lock me-1"></i>Rekrut tersedia setelah onboarding dinyatakan <strong>Lulus</strong> (tanggal masuk diambil dari hari pertama onboarding).
                                        </div>
                                    </div>
                                @endif
                                @php
                                    // Sama dengan aturan server: rekrut setelah tanda tangan kontrak & kuota lowongan belum penuh (Admin bebas).
                                    $kontrakBerikut = $lamaran->kontrakJadwals->where('status', \App\Models\KontrakJadwal::STATUS_TERJADWAL)->sortBy('tanggal')->first();
                                    $tungguKontrak = ! auth()->user()->isAdmin() && $kontrakBerikut && $kontrakBerikut->tanggal->gt(now()->startOfDay());
                                    $kuotaPenuh = ! auth()->user()->isAdmin() && $lamaran->lowongan?->isKuotaPenuh();
                                @endphp
                                @if ($onboardingLulus && $tungguKontrak)
                                    <div class="col-12">
                                        <div class="alert alert-info small py-2 mb-1">
                                            <i class="bi bi-calendar-check me-1"></i>Rekrut bisa dilakukan mulai hari tanda tangan kontrak: <strong>{{ $kontrakBerikut->tanggal->translatedFormat('l, d F Y') }}</strong>.
                                        </div>
                                    </div>
                                @endif
                                @if ($kuotaPenuh)
                                    <div class="col-12">
                                        <div class="alert alert-warning small py-2 mb-1">
                                            <i class="bi bi-people-fill me-1"></i>Kuota lowongan sudah terpenuhi ({{ $lamaran->lowongan->jumlah_terisi }}/{{ $lamaran->lowongan->jumlah_dibutuhkan }}). Tambah jumlah kebutuhan di lowongan, atau tolak kandidat ini.
                                        </div>
                                    </div>
                                @endif
                                @if ($lamaran->isKandidatPegawai())
                                    <div class="col-12">
                                        <div class="alert alert-info small py-2 mb-1">
                                            Posisi akan diubah dari <strong>{{ $lamaran->dataPegawai?->kepegawaian?->jabatan ?? '-' }} ({{ $lamaran->dataPegawai?->kepegawaian?->unit_kerja ?? '-' }})</strong>
                                            menjadi <strong>{{ $lamaran->jabatanTujuan() }} ({{ $lamaran->unitKerjaTujuan() }})</strong>.
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small mb-1">Tanggal Efektif</label>
                                        @if ($adminBebas)<input type="date" name="tanggal_masuk" id="rekrut-tanggal-masuk" class="form-control form-control-sm" value="{{ now()->format('Y-m-d') }}" required>@else<input type="date" id="rekrut-tanggal-masuk" class="form-control form-control-sm bg-light" value="{{ $onboardingLulus?->tanggal_mulai?->format('Y-m-d') }}" readonly>@endif
                                        <div class="form-text" style="font-size:.72rem;">{{ $adminBebas ? 'Diisi manual (mode Admin)' : 'Otomatis: hari pertama onboarding' }}</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small mb-1">Status Kerja</label>
                                        <select name="status_kerja" class="form-select form-select-sm" required>
                                            @foreach (['Tetap', 'Kontrak', 'Probation', 'Magang', 'Harian Lepas'] as $sk)
                                                <option value="{{ $sk }}" {{ $lamaran->dataPegawai?->kepegawaian?->status_kerja === $sk ? 'selected' : '' }}>{{ $sk }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                @else
                                <div class="col-md-4">
                                    <label class="form-label small mb-1">NIP Baru</label>
                                    <input type="text" name="nip" class="form-control form-control-sm" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small mb-1">Tanggal Masuk</label>
                                    @if ($adminBebas)<input type="date" name="tanggal_masuk" id="rekrut-tanggal-masuk" class="form-control form-control-sm" value="{{ now()->format('Y-m-d') }}" required>@else<input type="date" id="rekrut-tanggal-masuk" class="form-control form-control-sm bg-light" value="{{ $onboardingLulus?->tanggal_mulai?->format('Y-m-d') }}" readonly>@endif
                                    <div class="form-text" style="font-size:.72rem;">{{ $adminBebas ? 'Diisi manual (mode Admin)' : 'Otomatis: hari pertama onboarding' }}</div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small mb-1">Status Kerja</label>
                                    <select name="status_kerja" class="form-select form-select-sm" required>
                                        <option value="Probation">Probation</option>
                                        <option value="Kontrak">Kontrak</option>
                                        <option value="Tetap">Tetap</option>
                                        <option value="Magang">Magang</option>
                                        <option value="Harian Lepas">Harian Lepas</option>
                                    </select>
                                </div>
                                @endif
                                <div class="col-md-6">
                                    <label class="form-label small mb-1">Masa Kontrak <span class="text-muted">(opsional)</span></label>
                                    <select name="masa_kontrak_bulan" id="rekrut-masa-kontrak" class="form-select form-select-sm">
                                        <option value="">-- Tidak ditentukan --</option>
                                        @foreach (range(3, 36, 3) as $bulan)
                                            <option value="{{ $bulan }}">{{ $bulan }} Bulan</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small mb-1">Tanggal Habis Kontrak</label>
                                    <input type="text" id="rekrut-tanggal-habis" class="form-control form-control-sm" disabled placeholder="Otomatis terkalkulasi">
                                </div>
                                <div class="col-12 d-flex gap-2 pt-3 mt-1 border-top">
                                    <button type="submit" class="btn btn-success flex-fill" @disabled((! $onboardingLulus && ! $adminBebas) || $tungguKontrak || $kuotaPenuh)>
                                        <i class="bi bi-check-circle me-1"></i> {{ $lamaran->isKandidatPegawai() ? 'Tetapkan ke Posisi Baru' : 'Rekrut Jadi Pegawai' }}
                                    </button>
                                    <button type="button" form="form-tolak-lamaran" class="btn btn-outline-danger flex-fill btn-reject-lamaran">
                                        <i class="bi bi-x-circle me-1"></i> Tolak Pelamar
                                    </button>
                                </div>
                            </form>

                            <form action="{{ route('admin.lamaran.tolak', $lamaran) }}" method="POST" id="form-tolak-lamaran" class="form-reject-lamaran d-none">
                                @csrf
                                <input type="hidden" name="catatan_admin" value="">
                            </form>
                        </div>
                    @endif
                @endif

                @if ($lamaran->isSelesai())
                    <div class="panel p-4">
                        <h2 class="h6 fw-bold mb-3">Keputusan Akhir</h2>
                        <p class="mb-1"><strong>Keputusan:</strong> {{ $lamaran->statusLabel() }}</p>
                        @if ($lamaran->catatan_admin)
                            <p class="mb-1"><strong>Catatan:</strong> {{ $lamaran->catatan_admin }}</p>
                        @endif
                        <p class="text-muted small mb-0">Diputuskan: {{ optional($lamaran->diputuskan_pada)->format('d M Y H:i') }}</p>
                    </div>
                @endif

                @if (auth()->user()->isAdmin())
                    <div class="panel p-4 mt-3 border-warning-subtle">
                        <h2 class="h6 fw-bold mb-1"><i class="bi bi-shield-lock me-1"></i>Ubah Langsung (Admin)</h2>
                        <p class="small text-muted mb-3">Pindahkan tahap/status lamaran tanpa mengikuti alur (mis. lompat ke onboarding, buka kembali lamaran yang ditolak).</p>
                        <form action="{{ route('admin.lamaran.ubah-admin', $lamaran) }}" method="POST" class="row g-2"
                            data-confirm="Ubah tahap/status lamaran ini secara langsung?">
                            @csrf
                            @method('PUT')
                            <div class="col-md-6">
                                <label class="form-label small mb-1">Tahap</label>
                                <select name="tahap" class="form-select form-select-sm">
                                    @foreach (['verifikasi' => 'Verifikasi', 'interview' => 'Interview', 'screening' => 'Screening / Onboarding', 'kontrak' => 'Kontrak', 'selesai' => 'Selesai'] as $nilai => $label)
                                        <option value="{{ $nilai }}" @selected($lamaran->tahap === $nilai)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small mb-1">Status</label>
                                <select name="status" class="form-select form-select-sm">
                                    @foreach (['menunggu_verifikasi' => 'Menunggu Verifikasi', 'proses' => 'Proses', 'diterima' => 'Diterima', 'ditolak' => 'Ditolak', 'dibatalkan' => 'Dibatalkan'] as $nilai => $label)
                                        <option value="{{ $nilai }}" @selected($lamaran->status === $nilai)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <input type="text" name="catatan_admin" class="form-control form-control-sm" maxlength="1000" placeholder="Catatan (opsional)" value="{{ $lamaran->catatan_admin }}">
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-warning btn-sm"><i class="bi bi-save me-1"></i>Simpan Langsung</button>
                            </div>
                        </form>

                        {{-- Semua data rekrutmen kandidat ini: bisa diubah/dihapus apa pun statusnya (selesai, dibatalkan, lewat tanggal). --}}
                        <hr>
                        <h3 class="h6 fw-bold mb-2"><i class="bi bi-database-gear me-1"></i>Semua Data Kandidat Ini</h3>
                        <ul class="list-unstyled small mb-0 data-admin-lamaran">
                            <li class="d-flex flex-wrap gap-2 align-items-center justify-content-between py-1 border-bottom">
                                <span><strong>Lamaran</strong> #{{ $lamaran->id }} · {{ $lamaran->statusLabel() }}</span>
                                @include('admin.editor-data._tombol', ['entitas' => 'lamaran', 'id' => $lamaran->id, 'teks' => 'Ubah', 'kembaliKe' => route('admin.lamaran.index')])
                            </li>
                            @foreach ($lamaran->interviewJadwals as $j)
                                <li class="d-flex flex-wrap gap-2 align-items-center justify-content-between py-1 border-bottom">
                                    <span>{{ $j->tahapLabel() }} · {{ $j->tanggal->format('d M Y') }} · {{ $j->statusLabel() }}@if ($j->hasil_status) · {{ $j->hasilLabel() }}@endif</span>
                                    @include('admin.editor-data._tombol', ['entitas' => 'interview', 'id' => $j->id, 'teks' => 'Ubah'])
                                </li>
                            @endforeach
                            @foreach ($lamaran->screeningOnboardings as $s)
                                <li class="d-flex flex-wrap gap-2 align-items-center justify-content-between py-1 border-bottom">
                                    <span>Onboarding · {{ $s->tanggal_mulai->format('d M') }}–{{ $s->tanggal_selesai->format('d M Y') }} · {{ $s->status }}@if ($s->hasil_status) · {{ $s->hasil_status }}@endif</span>
                                    @include('admin.editor-data._tombol', ['entitas' => 'onboarding', 'id' => $s->id, 'teks' => 'Ubah'])
                                </li>
                            @endforeach
                            @foreach ($lamaran->kontrakJadwals as $kj)
                                <li class="d-flex flex-wrap gap-2 align-items-center justify-content-between py-1 border-bottom">
                                    <span>Kontrak · {{ $kj->tanggal->format('d M Y') }}{{ $kj->waktu ? ', ' . substr($kj->waktu, 0, 5) : '' }} · {{ $kj->status }}</span>
                                    @include('admin.editor-data._tombol', ['entitas' => 'kontrak', 'id' => $kj->id, 'teks' => 'Ubah'])
                                </li>
                            @endforeach
                            @if ($lamaran->dataPelamar)
                                <li class="d-flex flex-wrap gap-2 align-items-center justify-content-between py-1">
                                    <span>Biodata pelamar · {{ $lamaran->dataPelamar->nama_lengkap }}</span>
                                    @include('admin.editor-data._tombol', ['entitas' => 'pelamar', 'id' => $lamaran->dataPelamar->id, 'teks' => 'Ubah', 'hapus' => false])
                                </li>
                            @endif
                        </ul>
                    </div>
                @endif
            </div>
        </div>
    </div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.btn-reject-lamaran').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const form = btn.form || btn.closest('form');

                Swal.fire({
                    icon: 'question',
                    title: 'Tolak Pelamar?',
                    input: 'textarea',
                    inputLabel: 'Alasan penolakan',
                    inputPlaceholder: 'Tuliskan alasan penolakan...',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Tolak',
                    cancelButtonText: 'Batal',
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    reverseButtons: true,
                    preConfirm: function (value) {
                        if (!value || !value.trim()) {
                            Swal.showValidationMessage('Alasan penolakan wajib diisi.');
                        }
                        return value;
                    },
                }).then(function (result) {
                    if (result.isConfirmed) {
                        form.querySelector('[name="catatan_admin"]').value = result.value;
                        form.submit();
                    }
                });
            });
        });

        document.querySelectorAll('.btn-cancel-interview').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const form = btn.closest('form');

                Swal.fire({
                    icon: 'question',
                    title: 'Batalkan Jadwal Interview?',
                    input: 'textarea',
                    inputLabel: 'Alasan resmi pembatalan',
                    inputPlaceholder: 'Tuliskan alasan pembatalan...',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Batalkan',
                    cancelButtonText: 'Tutup',
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    reverseButtons: true,
                    preConfirm: function (value) {
                        if (!value || !value.trim()) {
                            Swal.showValidationMessage('Alasan pembatalan wajib diisi.');
                        }
                        return value;
                    },
                }).then(function (result) {
                    if (result.isConfirmed) {
                        form.querySelector('[name="catatan"]').value = result.value;
                        form.submit();
                    }
                });
            });
        });

        const btnSalin = document.getElementById('btn-salin-link');
        if (btnSalin) {
            btnSalin.addEventListener('click', function () {
                const input = document.getElementById('link-jadwal');
                input.select();
                const selesai = function () {
                    btnSalin.innerHTML = '<i class="bi bi-check2 me-1"></i> Tersalin';
                    setTimeout(function () { btnSalin.innerHTML = '<i class="bi bi-clipboard me-1"></i> Salin'; }, 2000);
                };
                if (navigator.clipboard) {
                    navigator.clipboard.writeText(input.value).then(selesai, function () { document.execCommand('copy'); selesai(); });
                } else {
                    document.execCommand('copy');
                    selesai();
                }
            });
        }

        // Sama dengan App\Support\Kalender di server: hari kerja, libur, dan masa kontrak tanpa loncat bulan.
        const Kalender = {
            hariKerja: @json(config('hrrab.hari_kerja')),
            libur: @json(config('hrrab.hari_libur')),
            tanggal: function (s) { const p = s.split('-').map(Number); return new Date(p[0], p[1] - 1, p[2]); },
            iso: function (d) { return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0'); },
            isHariKerja: function (d) { return this.hariKerja.includes(d.getDay() || 7) && ! this.libur.includes(this.iso(d)); },
            terdekat: function (d) { const h = new Date(d); while (! this.isHariKerja(h)) h.setDate(h.getDate() + 1); return h; },
            hariKerjaSetelah: function (d) { const h = new Date(d); h.setDate(h.getDate() + 1); return this.terdekat(h); },
            hariKerjaKe: function (mulai, n) { let h = this.terdekat(mulai); for (let i = 1; i < n; i++) h = this.hariKerjaSetelah(h); return h; },
            habisKontrak: function (masuk, bulan) {
                const t = new Date(masuk.getFullYear(), masuk.getMonth() + bulan, 1);
                t.setDate(Math.min(masuk.getDate(), new Date(t.getFullYear(), t.getMonth() + 1, 0).getDate()) - 1);
                return t;
            },
        };

        const inputTanggalMasuk = document.getElementById('rekrut-tanggal-masuk');
        const selectMasaKontrak = document.getElementById('rekrut-masa-kontrak');
        const outputTanggalHabis = document.getElementById('rekrut-tanggal-habis');

        function hitungTanggalHabisKontrak() {
            if (!inputTanggalMasuk || !selectMasaKontrak || !outputTanggalHabis) return;

            const bulan = parseInt(selectMasaKontrak.value, 10);
            if (!inputTanggalMasuk.value || !bulan) {
                outputTanggalHabis.value = '';
                return;
            }

            outputTanggalHabis.value = Kalender.habisKontrak(Kalender.tanggal(inputTanggalMasuk.value), bulan)
                .toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' });
        }

        if (inputTanggalMasuk && selectMasaKontrak) {
            inputTanggalMasuk.addEventListener('change', hitungTanggalHabisKontrak);
            selectMasaKontrak.addEventListener('change', hitungTanggalHabisKontrak);
        }

        const inputMulai = document.getElementById('onboarding-mulai');
        const inputDurasi = document.getElementById('onboarding-durasi');
        const ringkasan = document.getElementById('onboarding-ringkasan');

        function hitungOnboarding() {
            if (!inputMulai || !inputDurasi || !ringkasan || !inputMulai.value) return;

            const durasi = Math.max(1, parseInt(inputDurasi.value, 10) || 1);
            const format = { weekday: 'long', day: '2-digit', month: 'long', year: 'numeric' };
            const mulai = Kalender.tanggal(inputMulai.value);
            const selesai = Kalender.hariKerjaKe(mulai, durasi);
            const hariIni = new Date(); hariIni.setHours(0, 0, 0, 0);
            let kontrak = Kalender.hariKerjaSetelah(selesai);
            if (kontrak < hariIni) kontrak = Kalender.terdekat(hariIni);

            ringkasan.innerHTML = (Kalender.isHariKerja(mulai) ? '' : '<span class="text-danger">Hari pertama bukan hari kerja — pilih ' + @json(\App\Support\Kalender::namaHariKerja()) + '.</span><br>')
                + durasi + ' hari kerja (' + @json(\App\Support\Kalender::namaHariKerja()) + ', libur tidak dihitung)<br>'
                + 'Hari terakhir: <strong>' + selesai.toLocaleDateString('id-ID', format) + '</strong><br>'
                + 'Kontrak paling cepat: <strong>' + kontrak.toLocaleDateString('id-ID', format) + '</strong>';
        }

        if (inputMulai && inputDurasi) {
            inputMulai.addEventListener('change', hitungOnboarding);
            inputDurasi.addEventListener('input', hitungOnboarding);
            hitungOnboarding();
        }
    });
</script>
@endsection
