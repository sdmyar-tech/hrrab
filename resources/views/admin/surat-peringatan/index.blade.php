@extends('layouts.admin.app')

@section('title', 'Surat Peringatan')

@section('content')
@php
    $adaCari = request()->filled('q');
    $resetBulan = route('admin.surat-peringatan.index', ['bulan' => $bulan->format('Y-m')]);
@endphp
<main class="dashboard-content">
    <div class="container-fluid">
        <x-page-header title="Surat Peringatan" eyebrow="Kepegawaian"
            subtitle="SP bertahap dalam satu bulan: SP1 → SP2 → SP3 (maksimal 3). Bulan berikutnya mulai lagi dari SP1.">
            <x-slot:actions>
                @include('partials.tombol-ekspor', ['eksporRoute' => 'admin.surat-peringatan.export', 'eksporParam' => ['bulan' => $bulan->format('Y-m')]])
                @if ($pegawai->isNotEmpty())
                    <x-button variant="danger" icon="bi-exclamation-octagon" data-bs-toggle="modal" data-bs-target="#modalBeriSp">Beri SP</x-button>
                @endif
            </x-slot:actions>
        </x-page-header>

        <x-card :padding="false" aria-label="Daftar surat peringatan">
            <x-filter-bar :action="route('admin.surat-peringatan.index')"
                search="Cari pegawai, contoh: Siti" search-label="Cari surat peringatan menurut nama pegawai"
                :count="$daftar->count()" :unit="'SP pada ' . $bulan->translatedFormat('F Y')" :filtered="$adaCari" :reset="$resetBulan">
                <div class="input-group input-group-sm sp-bulan">
                    <span class="input-group-text"><i class="bi bi-calendar3" aria-hidden="true"></i><span class="ms-1">Bulan</span></span>
                    <input type="month" name="bulan" class="form-control" value="{{ $bulan->format('Y-m') }}" onchange="this.form.submit()" aria-label="Bulan">
                </div>
                <x-slot:summary>
                    <span class="d-inline-flex flex-wrap gap-1" aria-label="Jumlah per tingkat">
                        @foreach ([1, 2, 3] as $t)
                            <x-status-badge :badge="(new \App\Models\SuratPeringatan(['tingkat' => $t]))->badgeClass()">SP{{ $t }}: {{ $daftar->where('tingkat', $t)->count() }}</x-status-badge>
                        @endforeach
                    </span>
                </x-slot:summary>
            </x-filter-bar>

            <x-table caption="Daftar surat peringatan" sticky striped scroll>
                <x-slot:head>
                    <tr>
                        <th>Pegawai</th>
                        <th>Tanggal</th>
                        <th>SP</th>
                        <th>Alasan</th>
                        <th>Diberikan Oleh</th>
                        <th class="hr-kolom-aksi">Aksi</th>
                    </tr>
                </x-slot:head>
                @forelse ($daftar as $sp)
                    <tr>
                        <td>
                            <span class="hr-sel-judul">{{ $sp->dataPegawai?->nama_lengkap ?? '-' }}</span>
                            <span class="hr-sel-sub">{{ collect([$sp->dataPegawai?->kepegawaian?->jabatan, $sp->unit_kerja])->filter()->implode(' · ') }}</span>
                        </td>
                        <td class="text-nowrap">{{ $sp->tanggal->translatedFormat('d M Y') }}</td>
                        <td><x-status-badge :badge="$sp->badgeClass()">{{ $sp->label() }}</x-status-badge></td>
                        <td>
                            {{ $sp->alasan }}
                            @if ($sp->keterangan)<span class="hr-sel-sub sp-keterangan">{{ $sp->keterangan }}</span>@endif
                        </td>
                        <td>{{ $sp->pemberi?->name ?? '-' }}</td>
                        <td class="text-end hr-kolom-aksi">
                            <div class="hr-aksi-baris">
                                @if (\App\Support\SuratPeringatanAkses::bisaHapus(auth()->user(), $sp))
                                    <form action="{{ route('admin.surat-peringatan.destroy', $sp) }}" method="POST">
                                        @csrf @method('DELETE')
                                        <x-icon-button type="submit" icon="bi-trash" tone="danger" label="Hapus {{ $sp->label() }} {{ $sp->dataPegawai?->nama_lengkap }}"
                                            data-confirm="true" data-icon="warning" data-title="Hapus {{ $sp->label() }}?"
                                            data-text="{{ $sp->label() }} untuk {{ $sp->dataPegawai?->nama_lengkap }} akan dihapus. Tindakan ini tidak bisa dibatalkan."
                                            data-confirm-text="Ya, Hapus" data-cancel-text="Batal" />
                                    </form>
                                @else
                                    <span class="hr-ikon-btn invisible" aria-hidden="true"></span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <x-table-empty :colspan="6" :icon="$adaCari ? 'bi-search' : 'bi-check-circle'"
                        :title="$adaCari ? 'Tidak ada surat peringatan yang cocok' : 'Tidak ada surat peringatan pada ' . $bulan->translatedFormat('F Y')">
                        {{ $adaCari ? 'Coba nama lain atau reset pencarian.' : 'Pilih bulan lain untuk melihat riwayat SP.' }}
                        @if ($adaCari)
                            <x-slot:action><x-button :href="$resetBulan" variant="secondary" icon="bi-x-circle">Reset filter</x-button></x-slot:action>
                        @endif
                    </x-table-empty>
                @endforelse
            </x-table>
        </x-card>
    </div>
</main>

@if ($pegawai->isNotEmpty())
    <x-modal id="modalBeriSp" title="Beri Surat Peringatan" icon="bi-exclamation-octagon" tone="danger"
        subtitle="Tingkat SP dihitung otomatis per bulan. Pegawai menerima notifikasi.">
        <form action="{{ route('admin.surat-peringatan.store') }}" method="POST" id="formSp" data-confirm="Berikan surat peringatan ini? Pegawai akan menerima notifikasi.">
            @csrf
            <div class="row g-3">
                <div class="col-12">
                    {{-- Label untuk pilihan pegawai (wajib); kotak saring di atasnya punya label pembaca layar sendiri. --}}
                    <label class="form-label required" for="sp-pegawai">Pegawai</label>
                    <input type="search" id="cari-pegawai" class="form-control form-control-sm mb-2" placeholder="Saring daftar, contoh: Siti" aria-label="Saring daftar pegawai menurut nama" data-cari-sp>
                    <select name="data_pegawai_id" id="sp-pegawai" class="form-select" required>
                        <option value="">Pilih pegawai</option>
                        @foreach ($pegawai as $p)
                            <option value="{{ $p->id }}" data-tingkat="{{ (int) ($tingkatBulanIni[$p->id] ?? 0) }}" @selected((int) old('data_pegawai_id') === $p->id)>
                                {{ $p->nama_lengkap }} — {{ $p->kepegawaian?->unit_kerja }}
                            </option>
                        @endforeach
                    </select>
                    <div class="sp-pratinjau mt-2" data-pratinjau hidden></div>
                </div>
                <div class="col-md-6">
                    <label class="form-label required" for="sp-tanggal">Tanggal</label>
                    <input type="date" name="tanggal" id="sp-tanggal" class="form-control" value="{{ old('tanggal', now()->toDateString()) }}" max="{{ now()->toDateString() }}" required>
                </div>
                <div class="col-12">
                    <label class="form-label required" for="sp-alasan">Alasan / pelanggaran</label>
                    <input type="text" name="alasan" id="sp-alasan" class="form-control" maxlength="255" value="{{ old('alasan') }}" placeholder="Tulis pelanggarannya, contoh: terlambat 5 kali dalam sebulan" required>
                </div>
                <div class="col-12">
                    <label class="form-label" for="sp-keterangan">Keterangan <span class="hr-field-opsional">(opsional)</span></label>
                    <textarea name="keterangan" id="sp-keterangan" rows="3" class="form-control" maxlength="2000" placeholder="Tulis rincian, contoh: terlambat tanggal 3, 7, dan 12; diminta hadir tepat waktu mulai bulan depan.">{{ old('keterangan') }}</textarea>
                </div>
            </div>
        </form>
        <x-slot:footer>
            <x-button variant="secondary" data-bs-dismiss="modal">Batal</x-button>
            <x-button type="submit" variant="danger" form="formSp" icon="bi-exclamation-octagon" data-tombol-sp>Berikan SP</x-button>
        </x-slot:footer>
    </x-modal>
@endif

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('formSp');
        if (!form) return;
        const pilih = form.querySelector('#sp-pegawai');
        const tanggal = form.querySelector('#sp-tanggal');
        const kotak = form.querySelector('[data-pratinjau]');
        const tombol = document.querySelector('[data-tombol-sp]');
        const bulanIni = @json(now()->format('Y-m'));

        // Pratinjau tingkat: SP bulan ini sudah sampai mana → SP berikutnya; SP3 = penuh (tombol dikunci).
        const pratinjau = () => {
            const opsi = pilih.selectedOptions[0];
            kotak.hidden = !opsi || !opsi.value;
            tombol.disabled = false;
            if (kotak.hidden) return;
            if (tanggal.value.slice(0, 7) !== bulanIni) {
                kotak.className = 'sp-pratinjau mt-2';
                kotak.textContent = 'Tingkat SP dihitung menurut bulan tanggal yang dipilih.';
                return;
            }
            const ada = +opsi.dataset.tingkat;
            kotak.className = 'sp-pratinjau mt-2' + (ada >= 3 ? ' penuh' : '');
            kotak.textContent = ada >= 3 ? 'Sudah SP3 bulan ini — tidak bisa memberi SP lagi sampai bulan depan.'
                : 'Akan menjadi SP' + (ada + 1) + (ada ? ' (bulan ini sudah SP' + ada + ').' : ' (belum ada SP bulan ini).');
            tombol.disabled = ada >= 3;
        };
        pilih.addEventListener('change', pratinjau);
        tanggal.addEventListener('change', pratinjau);
        pratinjau();

        form.querySelector('[data-cari-sp]').addEventListener('input', (e) => {
            const kata = e.target.value.trim().toLowerCase();
            [...pilih.options].forEach((o) => { if (o.value) o.hidden = kata && !o.textContent.toLowerCase().includes(kata); });
        });

        // Galat validasi dari server → buka lagi dialog dengan isian terakhir.
        @if ($errors->any())
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalBeriSp')).show();
        @endif
    });
</script>
@endsection
