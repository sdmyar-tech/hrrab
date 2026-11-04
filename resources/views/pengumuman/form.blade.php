@extends($layout)

@section('content')
@php
    $baru = ! $pengumuman->exists;
    $jadwal = old('jadwal', $pengumuman->jadwal ?? 'sekali');
    $sasaran = old('sasaran_unit', $pengumuman->sasaran_unit ?? []);
@endphp
<main class="dashboard-content">
    <div class="container-fluid">
        <x-page-header :title="$baru ? 'Buat Pengumuman' : 'Ubah Pengumuman'" eyebrow="Pengumuman" :back="route('pengumuman.index')" back-label="Pengumuman"
            subtitle="Contoh: doa pagi setiap hari, kajian setiap Rabu, acara besar yayasan, atau kabar duka." />

        <form @if ($baru) data-fokus-awal @endif action="{{ $baru ? route('pengumuman.store') : route('pengumuman.update', $pengumuman) }}" method="POST" id="formPengumuman"
            data-confirm="{{ $baru ? 'Terbitkan pengumuman ini?' : 'Simpan perubahan pengumuman?' }}">
            @csrf
            @unless ($baru) @method('PUT') @endunless

            {{-- Galat isian ditampilkan di halaman ini; banner global tidak perlu. --}}
            @section('galat-di-halaman', '1')
            @if ($errors->any())
                <x-alert tone="danger" title="Pengumuman belum tersimpan" class="mb-3">
                    <ul class="mb-0 ps-3">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                </x-alert>
            @endif

            <div class="row g-4 align-items-start">
                <div class="col-lg-8 d-grid gap-3">
                    <x-card title="Isi Pengumuman" icon="bi-megaphone">
                        <x-input name="judul" id="judul" label="Judul" maxlength="150" required :value="$pengumuman->judul" placeholder="Contoh: Kajian Rutin Rabu Sore" />

                        <fieldset class="hr-field">
                            <legend class="form-label required">Kategori</legend>
                            <div class="d-flex flex-wrap gap-2">
                                @foreach (\App\Models\Pengumuman::KATEGORI as $key => $k)
                                    <input type="radio" class="btn-check" name="kategori" id="kat-{{ $key }}" value="{{ $key }}" @checked(old('kategori', $pengumuman->kategori) === $key) required>
                                    <label class="hr-chip" for="kat-{{ $key }}"><i class="bi {{ $k['ikon'] }}" aria-hidden="true"></i>{{ $k['label'] }}</label>
                                @endforeach
                            </div>
                            @error('kategori')<div class="invalid-feedback d-block"><i class="bi bi-exclamation-circle" aria-hidden="true"></i> {{ $message }}</div>@enderror
                        </fieldset>

                        <x-textarea name="isi" id="isi" label="Isi" rows="8" maxlength="3000" required :value="$pengumuman->isi" field-class="mb-0"
                            placeholder="Contoh: Kajian rutin bersama Ustaz Ahmad. Peserta memakai baju putih dan membawa Al-Qur'an."
                            help="Tulis seperti pesan biasa; baris baru tetap tampil apa adanya." />
                    </x-card>

                    @if ($absen)
                        @include('pengumuman._form-absen')
                    @endif
                </div>

                <div class="col-lg-4 d-grid gap-3">
                    <x-card title="Waktu & Tempat" subtitle="Opsional — kosongkan untuk informasi tanpa jadwal." icon="bi-calendar-event">
                        <div class="hr-segmen mb-3" role="group" aria-label="Jenis jadwal">
                            @foreach (['sekali' => 'Sekali', 'harian' => 'Setiap hari', 'mingguan' => 'Mingguan'] as $val => $label)
                                <input type="radio" class="btn-check" name="jadwal" id="jadwal-{{ $val }}" value="{{ $val }}" @checked($jadwal === $val)>
                                <label for="jadwal-{{ $val }}">{{ $label }}</label>
                            @endforeach
                        </div>
                        <div data-jadwal="sekali">
                            <x-input name="tanggal" id="tanggal" type="date" label="Tanggal" :value="$pengumuman->tanggal?->format('Y-m-d')"
                                :min="auth()->user()->can('sistem.override') ? null : now()->format('Y-m-d')" />
                        </div>
                        <div data-jadwal="mingguan">
                            <x-select name="hari" id="hari" label="Setiap hari" :options="\App\Models\Pengumuman::HARI" :selected="(string) ($pengumuman->hari ?? 3)" />
                        </div>
                        <div class="row gx-2">
                            <div class="col-6"><x-input name="jam_mulai" id="jam_mulai" type="time" label="Jam mulai" :value="$pengumuman->jam_mulai ? substr($pengumuman->jam_mulai, 0, 5) : null" /></div>
                            <div class="col-6"><x-input name="jam_selesai" id="jam_selesai" type="time" label="Jam selesai" :value="$pengumuman->jam_selesai ? substr($pengumuman->jam_selesai, 0, 5) : null" /></div>
                        </div>
                        <x-input name="lokasi" id="lokasi" label="Lokasi" maxlength="150" :value="$pengumuman->lokasi" field-class="mb-0"
                            placeholder="Contoh: Masjid Yayasan atau link Google Maps" />
                    </x-card>

                    <x-card title="Ditujukan Kepada" icon="bi-people">
                        <p class="small text-muted mb-2">
                            {!! $wajibUnit ? 'Pilih bagian yang Anda pimpin.' : 'Kosongkan untuk <strong>semua pegawai</strong>, atau pilih bagian tertentu.' !!}
                        </p>
                        <div class="form-check pb-2 mb-2 border-bottom">
                            <input class="form-check-input" type="checkbox" id="pilih-semua-unit" data-pilih-semua="sasaran_unit[]">
                            <label class="form-check-label small fw-semibold" for="pilih-semua-unit">
                                Pilih semua bagian <span class="text-muted fw-normal" data-jumlah-terpilih></span>
                            </label>
                        </div>
                        <div class="notif-pilihan">
                            @foreach ($pilihanUnit as $u)
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="sasaran_unit[]" value="{{ $u }}" id="unit-{{ $loop->index }}" @checked(in_array($u, $sasaran, true))>
                                    <label class="form-check-label small" for="unit-{{ $loop->index }}">{{ $u }}</label>
                                </div>
                            @endforeach
                        </div>
                        <div class="mt-3">
                            <x-toggle name="penting" id="penting" label="Sematkan sebagai penting" :checked="(bool) $pengumuman->penting" help="Tampil paling atas dengan tanda merah." />
                        </div>
                        <x-input name="tampil_sampai" id="tampil_sampai" type="date" label="Tampil sampai" optional field-class="mt-3 mb-0"
                            :value="$pengumuman->tampil_sampai?->format('Y-m-d')" :min="auth()->user()->can('sistem.override') ? null : now()->format('Y-m-d')"
                            help="Kosong: acara sekali tampil sampai hari-H (info tanpa tanggal 14 hari); kegiatan rutin tampil terus." />
                    </x-card>
                </div>
            </div>

            <div class="hr-bilah-aksi mt-3">
                <x-button :href="route('pengumuman.index')" variant="ghost">Batal</x-button>
                <x-button type="submit" icon="bi-send">{{ $baru ? 'Terbitkan' : 'Simpan Perubahan' }}</x-button>
            </div>
        </form>
    </div>
</main>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        function sinkron() {
            const jadwal = document.querySelector('input[name="jadwal"]:checked').value;
            document.querySelectorAll('[data-jadwal]').forEach(function (el) { el.hidden = el.dataset.jadwal !== jadwal; });
        }
        document.querySelectorAll('input[name="jadwal"]').forEach(function (r) { r.addEventListener('change', sinkron); });
        sinkron();
    });
</script>
@include('partials.pilih-semua')
@endsection
