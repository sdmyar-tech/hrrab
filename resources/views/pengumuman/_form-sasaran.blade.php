{{--
    Pilihan "Ditujukan Kepada" di form pengumuman: per bagian, per jabatan, per nama (sama seperti peserta absen acara).
    Ketiganya boleh dikombinasikan; kosong semua = semua pegawai. Param: $pilihanUnit, $sasaran['kandidat'|'jabatan'], $pengumuman, $wajibUnit.
--}}
@php
    $pilUnit = old('sasaran_unit', $pengumuman->sasaran_unit ?? []);
    $pilJabatan = old('sasaran_jabatan', $pengumuman->sasaran_jabatan ?? []);
    $pilUser = array_map('intval', old('sasaran_user', $pengumuman->sasaran_user ?? []));
    $kandidat = $sasaran['kandidat'];
    $kandidatJs = $kandidat->map(fn ($k) => ['id' => $k['id'], 'u' => $k['unit'], 'j' => $k['jabatan']])->values();
    $bagian = [
        ['kunci' => 'unit', 'judul' => 'Per bagian', 'ikon' => 'bi-diagram-3', 'pilih' => $pilUnit],
        ['kunci' => 'jabatan', 'judul' => 'Per jabatan', 'ikon' => 'bi-person-badge', 'pilih' => $pilJabatan],
        ['kunci' => 'user', 'judul' => 'Per nama', 'ikon' => 'bi-person-check', 'pilih' => $pilUser],
    ];
    if (! $sasaran['jabatan']) {
        $bagian = array_values(array_filter($bagian, fn ($b) => $b['kunci'] !== 'jabatan'));
    }
@endphp
<div id="panelSasaran">
    <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
        <p class="small text-muted mb-0">
            {!! $wajibUnit ? 'Pilih bagian atau nama pegawai yang dituju.' : 'Kosongkan semua untuk <strong>semua pegawai</strong>. Boleh dikombinasikan.' !!}
        </p>
        <span class="badge text-bg-primary" data-total-sasaran aria-live="polite">Semua pegawai</span>
    </div>
    @error('sasaran_unit')<div class="invalid-feedback d-block mb-2"><i class="bi bi-exclamation-circle" aria-hidden="true"></i> {{ $message }}</div>@enderror

    <div class="d-grid gap-2">
        @foreach ($bagian as $b)
            <details class="sasaran-kotak" @if (count($b['pilih'])) open @endif>
                <summary>
                    <i class="bi {{ $b['ikon'] }}" aria-hidden="true"></i> {{ $b['judul'] }}
                    <span class="text-muted fw-normal" data-jumlah-pilih="{{ $b['kunci'] }}"></span>
                </summary>
                <div class="pt-2">
                    @if ($b['kunci'] === 'unit')
                        <div class="form-check pb-2 mb-2 border-bottom">
                            <input class="form-check-input" type="checkbox" id="pilih-semua-unit" data-pilih-semua="sasaran_unit[]">
                            <label class="form-check-label small fw-semibold" for="pilih-semua-unit">Pilih semua bagian <span class="text-muted fw-normal" data-jumlah-terpilih></span></label>
                        </div>
                        <div class="sasaran-daftar">
                            @foreach ($pilihanUnit as $u)
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="sasaran_unit[]" value="{{ $u }}" id="unit-{{ $loop->index }}" @checked(in_array($u, $pilUnit, true))>
                                    <label class="form-check-label small" for="unit-{{ $loop->index }}">{{ $u }}</label>
                                </div>
                            @endforeach
                        </div>
                    @elseif ($b['kunci'] === 'jabatan')
                        <input type="search" class="form-control form-control-sm mb-1" placeholder="Saring jabatan, contoh: Guru" aria-label="Saring jabatan" data-cari-daftar="jabatan" autocomplete="off">
                        <div class="sasaran-daftar" data-daftar="jabatan">
                            @foreach ($sasaran['jabatan'] as $nama => $n)
                                <label class="form-check small mb-0" data-teks="{{ mb_strtolower($nama) }}">
                                    <input class="form-check-input" type="checkbox" name="sasaran_jabatan[]" value="{{ $nama }}" @checked(in_array($nama, $pilJabatan, true))>
                                    <span class="form-check-label">{{ $nama }} <span class="text-muted">· {{ $n }}</span></span>
                                </label>
                            @endforeach
                        </div>
                    @else
                        <input type="search" class="form-control form-control-sm mb-1" placeholder="Saring nama, contoh: Siti atau SMK" aria-label="Saring nama pegawai" data-cari-daftar="user" autocomplete="off">
                        <div class="sasaran-daftar" data-daftar="user">
                            @foreach ($kandidat as $k)
                                <label class="form-check small mb-0" data-teks="{{ mb_strtolower($k['nama'] . ' ' . implode(' ', $k['unit']) . ' ' . implode(' ', $k['jabatan'])) }}">
                                    <input class="form-check-input" type="checkbox" name="sasaran_user[]" value="{{ $k['id'] }}" @checked(in_array($k['id'], $pilUser, true))>
                                    <span class="form-check-label">{{ $k['nama'] }}<span class="d-block text-muted small">{{ implode(', ', $k['unit']) ?: '-' }}</span></span>
                                </label>
                            @endforeach
                        </div>
                    @endif
                </div>
            </details>
        @endforeach
    </div>
</div>

<style>
    .sasaran-kotak { border: 1px solid var(--admin-border); border-radius: var(--hr-radius-md); background: var(--admin-surface-soft); padding: var(--hr-space-2) var(--hr-space-3); }
    .sasaran-kotak > summary { cursor: pointer; font-size: var(--hr-text-sm); font-weight: var(--hr-weight-semibold); }
    .sasaran-daftar { max-height: 190px; overflow-y: auto; padding-right: var(--hr-space-1); }
    .sasaran-daftar .form-check { display: flex; gap: var(--hr-space-2); padding: var(--hr-space-1); border-radius: var(--hr-radius-sm); margin: 0; }
    .sasaran-daftar .form-check:hover { background: var(--admin-surface-hover); }
    .sasaran-daftar .form-check-input { margin-left: 0; flex-shrink: 0; }
</style>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const panel = document.getElementById('panelSasaran');
        const kandidat = @json($kandidatJs);
        const el = (s) => panel.querySelector(s);
        const terpilih = (k) => Array.from(panel.querySelectorAll('[name="sasaran_' + k + '[]"]:checked')).map((c) => c.value);

        function hitung() {
            const u = terpilih('unit'), j = terpilih('jabatan'), id = terpilih('user').map(Number);
            ['unit', 'jabatan', 'user'].forEach(function (k) {
                const span = el('[data-jumlah-pilih="' + k + '"]');
                if (span) span.textContent = terpilih(k).length ? '(' + terpilih(k).length + ' dipilih)' : '';
            });
            const lencana = el('[data-total-sasaran]');
            if (!u.length && !j.length && !id.length) {
                lencana.textContent = 'Semua pegawai';
                return;
            }
            // Perkiraan penerima (akun pegawai aktif yang cocok bagian/jabatan/nama); jumlah bagian tanpa pegawai tercatat tetap 0.
            const n = kandidat.filter((k) => k.u.some((x) => u.includes(x)) || k.j.some((x) => j.includes(x)) || id.includes(k.id)).length;
            lencana.textContent = '± ' + n + ' pegawai';
        }
        panel.addEventListener('change', hitung);
        panel.querySelectorAll('[data-cari-daftar]').forEach((cari) => cari.addEventListener('input', function () {
            const kata = cari.value.trim().toLowerCase();
            panel.querySelectorAll('[data-daftar="' + cari.dataset.cariDaftar + '"] [data-teks]').forEach((b) => { b.hidden = kata && !b.dataset.teks.includes(kata); });
        }));
        hitung();
    });
</script>
