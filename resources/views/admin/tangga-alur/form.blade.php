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
                        subtitle="Pengajuan naik dari Tahap 1 sampai tahap terakhir, tanpa ada yang dilewati. Tahap terakhir = persetujuan akhir.">
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
                                    Nama diambil dari data saat ini. Bila koordinator, GM, atau pemegang jabatan berganti, penyetuju ikut berganti otomatis.
                                    Pengaju tidak pernah menyetujui pengajuannya sendiri.
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
        const IKON_TIPE = { koordinator: 'bi-person-badge', gm: 'bi-diagram-3', kepala_sdm: 'bi-shield-check', jabatan: 'bi-briefcase', akun: 'bi-person' };
        // Penjelasan singkat tiap jenis penyetuju, tampil di bawah pilihan jenis.
        const BANTU_TIPE = {
            koordinator: 'Siapa pun yang sedang menjadi koordinator bagian itu. Bila koordinatornya berganti, penyetuju ikut berganti.',
            gm: 'GM yang membawahi bagian itu.',
            kepala_sdm: 'Semua akun Kepala SDM.',
            jabatan: 'Semua orang yang memegang jabatan ini dan punya hak menyetujui pengajuan.',
            akun: 'Hanya orang yang dipilih. Bila orang ini pindah atau keluar, ubah tahap ini.',
        };
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
                    return j ? j.nama + (s.unit === 'semua' ? '' : ' ' + namaUnit(s.unit)) : 'Jabatan belum dipilih';
                }
                case 'akun': {
                    const a = P.akun.find(function (x) { return String(x.id) === String(s.user_id); });
                    return a ? a.nama : 'Orang belum dipilih';
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
        function namaOtomatis(t) {
            return t.penyetuju.map(labelSumber).join(' / ') || 'Tahap tanpa penyetuju';
        }
        function namaTahap(t) {
            return t.nama.trim() || namaOtomatis(t);
        }
        function semuaNama(t) {
            return Array.from(new Set(t.penyetuju.flatMap(namaPenyetuju)));
        }
        // Pilihan "cukup salah satu / semua" hanya berarti bila penyetuju tahap ini lebih dari satu orang.
        function perluCara(t) {
            return t.penyetuju.length > 1 || semuaNama(t).length > 1;
        }
        function alasanKosong(s) {
            return {
                koordinator: 'Bagian ' + namaUnit(s.unit) + ' belum punya koordinator yang bisa menyetujui.',
                gm: 'Belum ada GM untuk bidang ini.',
                kepala_sdm: 'Belum ada akun Kepala SDM.',
                jabatan: 'Belum ada pemegang jabatan ini yang punya hak menyetujui.',
            }[s.tipe] || '';
        }

        // ---------- Penyusun tahap ----------
        function bidang(id, label, kontrol) {
            return el('div', { class: 'hr-field mb-0' }, [el('label', { class: 'form-label', for: id, text: label }), kontrol]);
        }
        function pilihBagian(s, id, label, opsiSemua) {
            return bidang(id, label, el('select', { class: 'form-select', id: id, 'data-kolom': 'unit' }, [
                opsi('pengaju', 'Bagian pengaju (' + NAMA_UNIT + ')', s.unit),
                opsiSemua ? opsi('semua', opsiSemua, s.unit) : null,
                el('optgroup', { label: 'Bagian tertentu' }, P.unit.map(function (u) { return opsi(u.id, u.nama, s.unit); })),
            ]));
        }
        // Isian lanjutan sesuai jenis penyetuju; labelnya berupa pertanyaan agar jelas apa yang dipilih.
        function detailSumber(s, id) {
            const isian = [];
            if (s.tipe === 'koordinator') isian.push(pilihBagian(s, id + '-unit', 'Koordinator bagian mana?'));
            if (s.tipe === 'gm') isian.push(pilihBagian(s, id + '-unit', 'GM bidang mana?', 'GM mana pun (semua GM)'));
            if (s.tipe === 'jabatan') {
                isian.push(bidang(id + '-jabatan', 'Jabatan apa?', el('select', { class: 'form-select', id: id + '-jabatan', 'data-kolom': 'jabatan_id', required: true },
                    [opsi('', 'Pilih jabatan', s.jabatan_id || '')].concat(P.jabatan.map(function (x) { return opsi(x.id, x.nama, s.jabatan_id); })))));
                isian.push(pilihBagian(s, id + '-unit', 'Di bagian mana?', 'Bagian mana saja'));
            }
            if (s.tipe === 'akun') {
                isian.push(bidang(id + '-akun', 'Siapa orangnya?', el('select', { class: 'form-select', id: id + '-akun', 'data-kolom': 'user_id', required: true },
                    [opsi('', 'Pilih nama', s.user_id || '')].concat(P.akun.map(function (x) { return opsi(x.id, x.nama + (x.ket ? ' — ' + x.ket : ''), s.user_id); })))));
            }
            return isian.length ? el('div', { class: 'tangga-sumber-detail' }, isian) : null;
        }
        // Hasil pilihan: siapa yang benar-benar menyetujui, atau alasan bila belum ada orangnya.
        function isiHasil(kotak, s) {
            const nama = namaPenyetuju(s);
            const belumDipilih = (s.tipe === 'jabatan' && !s.jabatan_id) || (s.tipe === 'akun' && !s.user_id);
            kotak.classList.toggle('is-kosong', !nama.length);
            kotak.replaceChildren(
                ikon(nama.length ? 'bi-person-check' : 'bi-exclamation-triangle'),
                el('span', {}, nama.length ? ['Yang menyetujui: ', el('strong', { text: nama.join(', ') })]
                    : belumDipilih ? [s.tipe === 'jabatan' ? 'Pilih jabatannya dulu.' : 'Pilih nama orangnya dulu.']
                    : [el('strong', { text: 'Belum ada orangnya. ' }), alasanKosong(s)]),
            );
        }

        function barisSumber(s, j, t, idTahap) {
            const id = idTahap + '-p' + j;
            const banyak = t.penyetuju.length > 1;
            const hasil = el('div', { class: 'tangga-sumber-hasil', 'data-hasil': true });
            isiHasil(hasil, s);
            return el('div', { class: 'tangga-sumber', 'data-sumber': j }, [
                // Judul + Hapus hanya bila penyetuju lebih dari satu; penyetuju tunggal cukup diganti jenisnya.
                banyak ? el('div', { class: 'tangga-sumber-kepala' }, [
                    el('span', { class: 'tangga-sumber-judul', text: 'Penyetuju ' + (j + 1) }),
                    el('button', { type: 'button', class: 'btn btn-ghost btn-sm text-danger', 'data-hapus-sumber': j, 'aria-label': 'Hapus penyetuju ' + (j + 1) }, [ikon('bi-trash'), ' Hapus']),
                ]) : null,
                el('div', { class: 'tangga-jenis', role: 'radiogroup', 'aria-label': 'Jenis penyetuju' + (banyak ? ' ' + (j + 1) : '') }, Object.entries(TIPE).flatMap(function ([k, v]) {
                    return [
                        el('input', { type: 'radio', class: 'btn-check', name: id + '-tipe', id: id + '-' + k, value: k, 'data-kolom': 'tipe', checked: s.tipe === k }),
                        el('label', { class: 'hr-chip', for: id + '-' + k }, [ikon(IKON_TIPE[k]), v]),
                    ];
                })),
                el('p', { class: 'tangga-sumber-bantu', text: BANTU_TIPE[s.tipe] }),
                detailSumber(s, id),
                hasil,
            ]);
        }

        function kartuTahap(t, i) {
            uid++;
            const idTahap = 'tahap' + uid;
            const nomor = el('span', { class: 'tangga-tahap-no', 'aria-hidden': 'true', text: String(i + 1) });
            const tombol = function (attr, nama, label, mati) {
                return el('button', Object.assign({ type: 'button', class: 'btn btn-ghost btn-sm', 'aria-label': label, title: label, disabled: mati }, attr), [ikon(nama)]);
            };
            return el('section', { class: 'tangga-tahap', 'data-tahap': i, 'aria-labelledby': idTahap + '-judul' }, [
                el('header', { class: 'tangga-tahap-kepala' }, [
                    nomor,
                    el('h3', { class: 'tangga-tahap-judul', id: idTahap + '-judul' }, [
                        'Tahap ' + (i + 1),
                        i === tahap.length - 1 ? el('span', { class: 'hr-badge hr-badge-success', text: 'Akhir' }) : null,
                    ]),
                    tombol({ 'data-naik': i }, 'bi-arrow-up', 'Naikkan tahap ' + (i + 1), i === 0),
                    tombol({ 'data-turun': i }, 'bi-arrow-down', 'Turunkan tahap ' + (i + 1), i === tahap.length - 1),
                    tombol({ 'data-hapus-tahap': i, class: 'btn btn-ghost btn-sm text-danger' }, 'bi-trash', 'Hapus tahap ' + (i + 1), tahap.length === 1),
                ]),
                el('div', { class: 'tangga-tahap-isi' }, [
                    el('fieldset', { class: 'mb-0' }, [
                        el('legend', { class: 'form-label required', text: 'Siapa yang menyetujui?' }),
                        el('div', { class: 'd-grid gap-2' }, t.penyetuju.map(function (s, j) { return barisSumber(s, j, t, idTahap); })),
                        el('button', { type: 'button', class: 'btn btn-ghost btn-sm tangga-tambah-sumber mt-2', 'data-tambah-sumber': i }, [ikon('bi-plus-lg'), ' Tambah penyetuju lain di tahap ini']),
                    ]),
                    el('fieldset', { class: 'mb-0', 'data-cara': true, hidden: !perluCara(t) }, [
                        el('legend', { class: 'form-label', text: 'Penyetujunya lebih dari satu orang. Kapan tahap ini selesai?' }),
                        el('div', { class: 'hr-segmen', role: 'group', 'aria-label': 'Kapan tahap ' + (i + 1) + ' selesai' }, [
                            el('input', { type: 'radio', class: 'btn-check', name: idTahap + '-semua', id: idTahap + '-satu', value: '0', 'data-kolom-tahap': 'semua', checked: !t.semua }),
                            el('label', { for: idTahap + '-satu', text: 'Cukup salah satu menyetujui' }),
                            el('input', { type: 'radio', class: 'btn-check', name: idTahap + '-semua', id: idTahap + '-semua', value: '1', 'data-kolom-tahap': 'semua', checked: t.semua }),
                            el('label', { for: idTahap + '-semua', text: 'Semua harus menyetujui' }),
                        ]),
                    ]),
                    // Nama tahap jarang diubah: dilipat agar tidak mengganggu, terbuka bila sudah diisi.
                    el('details', { class: 'tangga-tahap-nama', open: !!t.nama.trim() }, [
                        el('summary', {}, ['Ganti nama tahap ', el('span', { class: 'hr-field-opsional', text: '(opsional)' })]),
                        el('div', { class: 'hr-field mb-0 mt-2' }, [
                            el('label', { class: 'form-label', for: idTahap + '-nama', text: 'Nama tahap' }),
                            el('input', { type: 'text', class: 'form-control', id: idTahap + '-nama', 'data-kolom-tahap': 'nama', maxlength: 60, value: t.nama,
                                placeholder: 'Tulis nama tahap, contoh: Verifikasi Keuangan' }),
                            el('div', { class: 'form-text', 'data-nama-otomatis': true, text: 'Jika dikosongkan, nama tahap: ' + namaOtomatis(t) }),
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
                if (t.semua && perluCara(t)) lencana.push(el('span', { class: 'hr-badge hr-badge-info' }, [ikon('bi-people'), ' Semua setuju']));
                daftar.push(itemPratinjau(nama.length ? '' : 'is-kosong', nama.length ? String(i + 1) : ikon('bi-exclamation'), namaTahap(t),
                    nama.length
                        ? el('span', { class: 'hr-tangga-oleh', text: nama.slice(0, 4).join(', ') + (nama.length > 4 ? ' +' + (nama.length - 4) : '') })
                        : el('span', { class: 'hr-tangga-oleh hr-tangga-peringatan', text: 'Belum ada penyetuju' }),
                    lencana));
            });
            daftar.push(itemPratinjau('is-akhir', ikon('bi-flag'), 'Disetujui', null));
            pratinjau.replaceChildren.apply(pratinjau, daftar);
        }

        // Isian berubah: perbarui data, hasil penyetuju baris itu, & pratinjau (tanpa menggambar ulang agar fokus tidak hilang).
        function perbarui(e) {
            const kartu = e.target.closest('[data-tahap]');
            if (!kartu) return;
            const i = +kartu.dataset.tahap;
            const t = tahap[i];
            const kolomTahap = e.target.dataset.kolomTahap;
            if (kolomTahap === 'nama') t.nama = e.target.value;
            if (kolomTahap === 'semua') t.semua = e.target.value === '1';
            const baris = e.target.closest('[data-sumber]');
            const kolom = e.target.dataset.kolom;
            if (baris && kolom) {
                const j = +baris.dataset.sumber;
                if (kolom === 'tipe') {
                    // Radio memicu 'input' lalu 'change': gambar ulang sekali saja.
                    if (t.penyetuju[j].tipe === e.target.value) return;
                    t.penyetuju[j] = { tipe: e.target.value, unit: 'pengaju' };
                    gambarPenyusun();
                    wadah.querySelector('[data-tahap="' + i + '"] [data-sumber="' + j + '"] [data-kolom="tipe"]:checked').focus();
                    return;
                }
                t.penyetuju[j][kolom] = e.target.value;
                isiHasil(baris.querySelector('[data-hasil]'), t.penyetuju[j]);
            }
            kartu.querySelector('[data-cara]').hidden = !perluCara(t);
            kartu.querySelector('[data-nama-otomatis]').textContent = 'Jika dikosongkan, nama tahap: ' + namaOtomatis(t);
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
                fokus = '[data-tahap="' + (tahap.length - 1) + '"] [data-kolom="tipe"]:checked';
            } else if ('tambahSumber' in d) {
                const t = tahap[+d.tambahSumber];
                t.penyetuju.push({ tipe: 'koordinator', unit: 'pengaju' });
                fokus = '[data-tahap="' + d.tambahSumber + '"] [data-sumber="' + (t.penyetuju.length - 1) + '"] [data-kolom="tipe"]:checked';
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
                        masalah = ['Tahap ' + (i + 1) + ': ' + (s.tipe === 'jabatan' ? 'pilih jabatannya.' : 'pilih nama orangnya.'),
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
