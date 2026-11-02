@extends(Auth::user()->panelLayout())

@section('content')
@use('App\Models\TanggaAlur')
@php
    $labelSasaran = TanggaAlur::SASARAN_LABEL[$sasaran];
    $awal = ($sasaran === TanggaAlur::SASARAN_STAF ? 'Pegawai ' : 'Koordinator ') . $unit->nama;
@endphp
<main class="dashboard-content">
    <div class="container-fluid px-3 px-lg-4 py-4">
        <x-page-header :title="'Tangga Alur · ' . $unit->nama" eyebrow="Tangga Alur Pengajuan"
            :subtitle="'Pengaju: ' . $labelSasaran . ' bagian ' . $unit->nama . '. Berlaku untuk cuti, lembur, dan izin keluar.'"
            :back="route('admin.tangga-alur.index')" back-label="Tangga Alur Pengajuan">
            @if ($tangga)
                <x-badge tone="success" dot>Diatur</x-badge>
            @else
                <x-badge tone="neutral">Belum diatur — isian awal = alur otomatis</x-badge>
            @endif
        </x-page-header>

        <form method="POST" action="{{ route('admin.tangga-alur.update', [$unit, $sasaran]) }}" id="formTangga" novalidate>
            @csrf @method('PUT')
            <input type="hidden" name="tahap_json" id="tahapJson">

            <div class="row g-4">
                <div class="col-xl-7 d-grid gap-4 align-content-start">
                    <x-card title="Tahap Persetujuan" icon="bi-list-ol"
                        subtitle="Susun dari atas ke bawah. Setiap tahap wajib dilalui; tahap terakhir = persetujuan akhir.">
                        <div data-daftar-tahap></div>
                        <div class="mt-3">
                            <x-button variant="outline" icon="bi-plus-lg" data-tambah-tahap>Tambah Tahap</x-button>
                        </div>
                    </x-card>

                    <x-card title="Pengaturan Lain" icon="bi-sliders">
                        <x-input name="catatan" label="Catatan" optional maxlength="255" :value="$tangga?->catatan"
                            placeholder="Tulis keterangan singkat, contoh: berlaku selama koordinator GA merangkap DRIVER" />
                        <details class="mt-3">
                            <summary class="fw-semibold small">Terapkan juga ke bagian lain ({{ $unitLain->count() }} bagian)</summary>
                            <p class="small text-muted mt-2 mb-2">
                                Tangga {{ strtolower($labelSasaran) }} bagian yang dicentang ikut diganti dengan tangga ini.
                                "Bagian pengaju" otomatis menyesuaikan bagian masing-masing.
                            </p>
                            <button type="button" class="btn btn-link btn-sm px-0 mb-2" data-centang-semua>Centang semua</button>
                            <div class="row g-1">
                                @foreach ($unitLain as $id => $nama)
                                    <div class="col-sm-6 col-lg-4">
                                        <div class="form-check mb-0">
                                            <input class="form-check-input" type="checkbox" name="terapkan_ke[]" value="{{ $id }}" id="terapkan-{{ $id }}"
                                                @checked(in_array((string) $id, (array) old('terapkan_ke', []), true))>
                                            <label class="form-check-label small" for="terapkan-{{ $id }}">{{ $nama }}</label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </details>
                    </x-card>
                </div>

                <div class="col-xl-5">
                    <div class="tangga-pratinjau">
                        <x-card title="Pratinjau Tangga" icon="bi-eye" subtitle="Seperti yang dilihat pegawai saat mengajukan.">
                            <ol class="hr-tangga hr-tangga-vertikal" data-pratinjau aria-live="polite" aria-label="Pratinjau tangga alur"></ol>
                            <x-slot:footer>
                                <p class="small text-muted mb-0">
                                    Nama penyetuju dihitung dari data saat ini (koordinator, GM bidang, pemegang jabatan yang punya hak menyetujui).
                                    Pengaju sendiri tidak pernah menyetujui pengajuannya.
                                </p>
                            </x-slot:footer>
                        </x-card>
                    </div>
                </div>
            </div>

            <div class="hr-bilah-aksi mt-4">
                <x-button variant="secondary" :href="route('admin.tangga-alur.index')">Batal</x-button>
                <x-button type="submit" icon="bi-check-lg">Simpan Tangga</x-button>
            </div>
        </form>
    </div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const P = @json($pilihan);
        const UNIT_INI = {{ $unit->id }};
        const NAMA_UNIT = @json($unit->nama);
        const AWAL = @json($awal);
        const TIPE = @json(TanggaAlur::TIPE_LABEL);
        const MAKS = {{ TanggaAlur::MAKS_TAHAP }};
        let tahap = @json($tahapAwal).map(function (t) {
            return { nama: t.nama || '', semua: !!t.semua, penyetuju: (t.penyetuju || []).map(function (s) { return Object.assign({ unit: 'pengaju' }, s); }) };
        });

        const form = document.getElementById('formTangga');
        const wadah = form.querySelector('[data-daftar-tahap]');
        const pratinjau = form.querySelector('[data-pratinjau]');
        let uid = 0;

        // ---------- Pembantu ----------
        function el(tag, attr, anak) {
            const n = document.createElement(tag);
            Object.entries(attr || {}).forEach(function ([k, v]) {
                if (v === null || v === undefined || v === false) return;
                if (k === 'text') n.textContent = v; else if (k === 'class') n.className = v; else n.setAttribute(k, v === true ? '' : v);
            });
            (anak || []).forEach(function (c) { if (c) n.appendChild(typeof c === 'string' ? document.createTextNode(c) : c); });
            return n;
        }
        function ikon(nama) { return el('i', { class: 'bi ' + nama, 'aria-hidden': 'true' }); }
        function opsi(nilai, teks, terpilih) { return el('option', { value: nilai, text: teks, selected: String(nilai) === String(terpilih) }); }
        function namaUnit(u) {
            if (u === 'pengaju') return NAMA_UNIT;
            if (u === 'semua') return 'semua bagian';
            const x = P.unit.find(function (b) { return String(b.id) === String(u); });
            return x ? x.nama : 'bagian terhapus';
        }
        function idUnit(u) { return u === 'pengaju' ? UNIT_INI : u; }

        // Label sumber: sama dengan TanggaAlurWorkflow::labelSumber.
        function labelSumber(s) {
            switch (s.tipe) {
                case 'koordinator': return 'Koordinator ' + namaUnit(s.unit);
                case 'gm': return s.unit === 'semua' ? 'GM mana pun' : 'GM bidang ' + namaUnit(s.unit);
                case 'kepala_sdm': return 'Kepala SDM';
                case 'jabatan': {
                    const j = P.jabatan.find(function (x) { return String(x.id) === String(s.jabatan_id); });
                    return (j ? j.nama : 'Pilih jabatan') + (s.unit === 'semua' ? '' : ' ' + namaUnit(s.unit));
                }
                case 'akun': {
                    const a = P.akun.find(function (x) { return String(x.id) === String(s.user_id); });
                    return a ? a.nama : 'Pilih akun';
                }
            }
            return '-';
        }
        // Nama penyetuju sumber dari data pilihan (pratinjau).
        function namaPenyetuju(s) {
            switch (s.tipe) {
                case 'koordinator': return s.unit === 'semua' ? [] : (P.koordinator[idUnit(s.unit)] || []);
                case 'gm': return s.unit === 'semua' ? P.gm.semua : (P.gm[idUnit(s.unit)] || []);
                case 'kepala_sdm': return P.kepalaSdm;
                case 'jabatan': return ((P.pemegang[s.jabatan_id] || {})[s.unit === 'semua' ? 'semua' : idUnit(s.unit)]) || [];
                case 'akun': {
                    const a = P.akun.find(function (x) { return String(x.id) === String(s.user_id); });
                    return a ? [a.nama] : [];
                }
            }
            return [];
        }
        function namaTahap(t) {
            return t.nama.trim() || t.penyetuju.map(labelSumber).join(' / ') || 'Tahap tanpa penyetuju';
        }
        function semuaNama(t) {
            return Array.from(new Set(t.penyetuju.flatMap(namaPenyetuju)));
        }

        // ---------- Penyusun tahap ----------
        function detailSumber(s) {
            const kotak = el('div', { class: 'd-flex flex-wrap gap-2 min-w-0' });
            const pilihUnit = function (bolehSemua, labelSemua) {
                const sel = el('select', { class: 'form-select form-select-sm', 'data-kolom': 'unit', 'aria-label': 'Bagian' }, [
                    opsi('pengaju', 'Bagian pengaju (' + NAMA_UNIT + ')', s.unit),
                    bolehSemua ? opsi('semua', labelSemua, s.unit) : null,
                ].concat(P.unit.map(function (u) { return opsi(u.id, u.nama, s.unit); })));
                sel.style.flex = '1 1 160px';
                return sel;
            };
            if (s.tipe === 'koordinator') kotak.append(pilihUnit(false));
            if (s.tipe === 'gm') kotak.append(pilihUnit(true, 'GM mana pun'));
            if (s.tipe === 'jabatan') {
                const j = el('select', { class: 'form-select form-select-sm', 'data-kolom': 'jabatan_id', 'aria-label': 'Jabatan', required: true },
                    [opsi('', 'Pilih jabatan', s.jabatan_id || '')].concat(P.jabatan.map(function (x) { return opsi(x.id, x.nama, s.jabatan_id); })));
                j.style.flex = '1 1 180px';
                kotak.append(j, pilihUnit(true, 'Bagian mana pun'));
            }
            if (s.tipe === 'akun') {
                const a = el('select', { class: 'form-select form-select-sm', 'data-kolom': 'user_id', 'aria-label': 'Akun penyetuju', required: true },
                    [opsi('', 'Pilih akun', s.user_id || '')].concat(P.akun.map(function (x) { return opsi(x.id, x.nama + (x.ket ? ' · ' + x.ket : ''), s.user_id); })));
                a.style.flex = '1 1 220px';
                kotak.append(a);
            }
            if (s.tipe === 'kepala_sdm') kotak.append(el('span', { class: 'small text-muted align-self-center', text: 'Semua akun Kepala SDM' }));
            return kotak;
        }

        function barisSumber(s, i, j) {
            const nama = namaPenyetuju(s);
            return el('div', { class: 'tangga-sumber', 'data-sumber': j }, [
                el('select', { class: 'form-select form-select-sm', 'data-kolom': 'tipe', 'aria-label': 'Jenis penyetuju' },
                    Object.entries(TIPE).map(function ([k, v]) { return opsi(k, v, s.tipe); })),
                detailSumber(s),
                el('button', { type: 'button', class: 'btn btn-ghost btn-sm', 'data-hapus-sumber': j, 'aria-label': 'Hapus penyetuju ' + labelSumber(s), title: 'Hapus penyetuju' }, [ikon('bi-x-lg')]),
                el('div', { class: 'tangga-sumber-nama' + (nama.length ? '' : ' is-kosong'), 'data-nama-sumber': true,
                    text: nama.length ? 'Penyetuju: ' + nama.join(', ') : 'Belum ada akun yang bisa menyetujui dari sumber ini' }),
            ]);
        }

        function kartuTahap(t, i) {
            uid++;
            const nomor = el('span', { class: 'tangga-tahap-no', 'aria-hidden': 'true', text: String(i + 1) });
            const tombol = function (attr, nama, label, mati) {
                return el('button', Object.assign({ type: 'button', class: 'btn btn-ghost btn-sm', 'aria-label': label, title: label, disabled: mati }, attr), [ikon(nama)]);
            };
            return el('section', { class: 'tangga-tahap', 'data-tahap': i, 'aria-labelledby': 'judul-tahap-' + uid }, [
                el('header', { class: 'tangga-tahap-kepala' }, [
                    nomor,
                    el('h3', { class: 'tangga-tahap-judul', id: 'judul-tahap-' + uid, text: 'Tahap ' + (i + 1) + (i === tahap.length - 1 ? ' · persetujuan akhir' : '') }),
                    tombol({ 'data-naik': i }, 'bi-arrow-up', 'Naikkan tahap ' + (i + 1), i === 0),
                    tombol({ 'data-turun': i }, 'bi-arrow-down', 'Turunkan tahap ' + (i + 1), i === tahap.length - 1),
                    tombol({ 'data-hapus-tahap': i, class: 'btn btn-ghost btn-sm text-danger' }, 'bi-trash', 'Hapus tahap ' + (i + 1), tahap.length === 1),
                ]),
                el('div', { class: 'tangga-tahap-isi' }, [
                    el('div', { class: 'hr-field mb-0' }, [
                        el('label', { class: 'form-label', for: 'nama-tahap-' + uid }, ['Nama tahap ', el('span', { class: 'hr-field-opsional', text: '(opsional)' })]),
                        el('input', { type: 'text', class: 'form-control', id: 'nama-tahap-' + uid, 'data-kolom-tahap': 'nama', maxlength: 60, value: t.nama,
                            placeholder: 'Kosongkan untuk nama otomatis, contoh: Verifikasi Keuangan' }),
                    ]),
                    el('fieldset', { class: 'mb-0' }, [
                        el('legend', { class: 'form-label required', text: 'Penyetuju' }),
                        el('div', { class: 'd-grid gap-2' }, t.penyetuju.map(function (s, j) { return barisSumber(s, i, j); })),
                        el('button', { type: 'button', class: 'btn btn-link btn-sm px-0 mt-1', 'data-tambah-sumber': i }, [ikon('bi-plus-lg'), ' Tambah penyetuju']),
                    ]),
                    el('fieldset', { class: 'mb-0' }, [
                        el('legend', { class: 'form-label', text: 'Cara menyetujui' }),
                        el('div', { class: 'hr-segmen', role: 'group', 'aria-label': 'Cara menyetujui tahap ' + (i + 1) }, [
                            el('input', { type: 'radio', class: 'btn-check', name: 'semua-' + uid, id: 'satu-' + uid, value: '0', 'data-kolom-tahap': 'semua', checked: !t.semua }),
                            el('label', { for: 'satu-' + uid, text: 'Salah satu cukup' }),
                            el('input', { type: 'radio', class: 'btn-check', name: 'semua-' + uid, id: 'semua-' + uid, value: '1', 'data-kolom-tahap': 'semua', checked: t.semua }),
                            el('label', { for: 'semua-' + uid, text: 'Semua harus setuju' }),
                        ]),
                    ]),
                ]),
            ]);
        }

        function gambarPenyusun() {
            wadah.replaceChildren.apply(wadah, tahap.flatMap(function (t, i) {
                const kartu = kartuTahap(t, i);
                return i < tahap.length - 1 ? [kartu, el('div', { class: 'tangga-panah', 'aria-hidden': 'true' }, [ikon('bi-arrow-down')])] : [kartu];
            }));
            form.querySelector('[data-tambah-tahap]').disabled = tahap.length >= MAKS;
            gambarPratinjau();
        }

        // ---------- Pratinjau (markup sama dengan komponen x-tangga-alur) ----------
        function itemPratinjau(kelas, penanda, label, oleh, lencana) {
            return el('li', { class: 'hr-tangga-item ' + kelas }, [
                el('span', { class: 'hr-tangga-penanda', 'aria-hidden': 'true' }, [penanda]),
                el('div', { class: 'hr-tangga-isi' }, [
                    el('span', { class: 'hr-tangga-kepala' }, [el('span', { class: 'hr-tangga-label', text: label })].concat(lencana || [])),
                    oleh,
                ]),
            ]);
        }
        function gambarPratinjau() {
            const daftar = [itemPratinjau('is-awal', ikon('bi-person'), AWAL, el('span', { class: 'hr-tangga-oleh', text: 'Mengajukan' }))];
            tahap.forEach(function (t, i) {
                const nama = semuaNama(t);
                const lencana = [];
                if (i === tahap.length - 1) lencana.push(el('span', { class: 'hr-badge hr-badge-success', text: 'Akhir' }));
                if (t.semua) lencana.push(el('span', { class: 'hr-badge hr-badge-info' }, [ikon('bi-people'), ' Semua setuju']));
                daftar.push(itemPratinjau(nama.length ? '' : 'is-kosong', nama.length ? String(i + 1) : ikon('bi-exclamation'), namaTahap(t),
                    nama.length
                        ? el('span', { class: 'hr-tangga-oleh', text: nama.slice(0, 4).join(', ') + (nama.length > 4 ? ' +' + (nama.length - 4) : '') })
                        : el('span', { class: 'hr-tangga-oleh hr-tangga-peringatan', text: 'Belum ada penyetuju' }),
                    lencana));
            });
            daftar.push(itemPratinjau('is-akhir', ikon('bi-flag'), 'Disetujui', null));
            pratinjau.replaceChildren.apply(pratinjau, daftar);
        }

        // Isian berubah: perbarui data, nama penyetuju baris itu, & pratinjau (tanpa menggambar ulang agar fokus tidak hilang).
        function perbarui(e) {
            const kartu = e.target.closest('[data-tahap]');
            if (!kartu) return;
            const t = tahap[+kartu.dataset.tahap];
            const kolomTahap = e.target.dataset.kolomTahap;
            if (kolomTahap === 'nama') t.nama = e.target.value;
            if (kolomTahap === 'semua') t.semua = e.target.value === '1';
            const baris = e.target.closest('[data-sumber]');
            const kolom = e.target.dataset.kolom;
            if (baris && kolom) {
                const s = t.penyetuju[+baris.dataset.sumber];
                if (kolom === 'tipe') {
                    t.penyetuju[+baris.dataset.sumber] = { tipe: e.target.value, unit: 'pengaju' };
                    gambarPenyusun();
                    wadah.querySelector('[data-tahap="' + kartu.dataset.tahap + '"] [data-sumber="' + baris.dataset.sumber + '"] [data-kolom="tipe"]').focus();
                    return;
                }
                s[kolom] = e.target.value;
                const nama = namaPenyetuju(s);
                const teks = baris.querySelector('[data-nama-sumber]');
                teks.textContent = nama.length ? 'Penyetuju: ' + nama.join(', ') : 'Belum ada akun yang bisa menyetujui dari sumber ini';
                teks.classList.toggle('is-kosong', !nama.length);
            }
            gambarPratinjau();
        }
        wadah.addEventListener('input', perbarui);
        wadah.addEventListener('change', perbarui);

        // Tambah / hapus / pindah tahap & penyetuju.
        form.addEventListener('click', function (e) {
            const b = e.target.closest('button');
            if (!b) return;
            const d = b.dataset;
            let fokus = null;
            if ('tambahTahap' in d) {
                if (tahap.length >= MAKS) return;
                tahap.push({ nama: '', semua: false, penyetuju: [{ tipe: 'kepala_sdm', unit: 'pengaju' }] });
                fokus = '[data-tahap="' + (tahap.length - 1) + '"] [data-kolom="tipe"]';
            } else if ('tambahSumber' in d) {
                const t = tahap[+d.tambahSumber];
                t.penyetuju.push({ tipe: 'koordinator', unit: 'pengaju' });
                fokus = '[data-tahap="' + d.tambahSumber + '"] [data-sumber="' + (t.penyetuju.length - 1) + '"] [data-kolom="tipe"]';
            } else if ('hapusSumber' in d) {
                const i = +b.closest('[data-tahap]').dataset.tahap;
                tahap[i].penyetuju.splice(+d.hapusSumber, 1);
                fokus = '[data-tambah-sumber="' + i + '"]';
            } else if ('hapusTahap' in d) {
                if (tahap.length === 1) return;
                tahap.splice(+d.hapusTahap, 1);
                fokus = '[data-tambah-tahap]';
            } else if ('naik' in d || 'turun' in d) {
                const i = +(d.naik ?? d.turun);
                const j = 'naik' in d ? i - 1 : i + 1;
                [tahap[i], tahap[j]] = [tahap[j], tahap[i]];
                fokus = '[data-tahap="' + j + '"] [' + ('naik' in d ? 'data-naik' : 'data-turun') + ']';
            } else if ('centangSemua' in d) {
                const kotak = form.querySelectorAll('input[name="terapkan_ke[]"]');
                const semua = Array.from(kotak).every(function (c) { return c.checked; });
                kotak.forEach(function (c) { c.checked = !semua; });
                b.textContent = semua ? 'Centang semua' : 'Hapus semua centang';
                return;
            } else {
                return;
            }
            gambarPenyusun();
            const sasaran = fokus && form.querySelector(fokus);
            if (sasaran && !sasaran.disabled) sasaran.focus();
        });

        // Kirim: cek kelengkapan lalu simpan sebagai JSON.
        form.addEventListener('submit', function (e) {
            let masalah = null;
            tahap.some(function (t, i) {
                if (!t.penyetuju.length) masalah = ['Tahap ' + (i + 1) + ' belum punya penyetuju.', '[data-tambah-sumber="' + i + '"]'];
                t.penyetuju.some(function (s, j) {
                    if ((s.tipe === 'jabatan' && !s.jabatan_id) || (s.tipe === 'akun' && !s.user_id)) {
                        masalah = ['Tahap ' + (i + 1) + ': lengkapi ' + (s.tipe === 'jabatan' ? 'jabatan' : 'akun') + ' penyetuju.',
                            '[data-tahap="' + i + '"] [data-sumber="' + j + '"] [data-kolom="' + (s.tipe === 'jabatan' ? 'jabatan_id' : 'user_id') + '"]'];
                    }
                    return masalah;
                });
                return masalah;
            });
            if (masalah) {
                e.preventDefault();
                e.stopImmediatePropagation();
                Swal.fire({ icon: 'warning', title: 'Tangga belum lengkap', text: masalah[0], confirmButtonText: 'Perbaiki', returnFocus: false })
                    .then(function () { const x = form.querySelector(masalah[1]); if (x) x.focus(); });
                return;
            }
            document.getElementById('tahapJson').value = JSON.stringify(tahap);
        }, true);

        gambarPenyusun();
    });
</script>
@endsection
